<?php

namespace Tests\Feature\Api;

use App\Models\Provider;
use App\Models\ProviderCategory;
use App\Models\ProviderService;
use App\Models\ProviderWorker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/*
 * DDE-Mart API — provider catalog tests (original): own services/workers
 * CRUD + toggles, strictly owner-scoped.
 */
class ApiProviderCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function providerToken(string $phone = '03003'): string
    {
        Provider::create(['name' => 'Fix', 'phone' => $phone, 'status' => 'active']);
        $req = $this->postJson('/api/v1/work/auth/otp/request', ['phone' => $phone, 'role' => 'provider']);

        return $this->postJson('/api/v1/work/auth/otp/verify', [
            'phone' => $phone, 'role' => 'provider', 'code' => $req->json('data.debug_code'),
        ])->json('data.token');
    }

    public function test_service_crud_and_toggle_scoped(): void
    {
        $auth = ['Authorization' => 'Bearer '.$this->providerToken()];
        $category = ProviderCategory::create(['title' => 'Home']);

        $create = $this->postJson('/api/v1/provider/services', [
            'category_id' => $category->id, 'title' => 'Plumbing',
            'price' => 300, 'discount_price' => 250,
        ], $auth)->assertCreated();
        $serviceId = $create->json('data.id');

        // Discount at/above price is rejected.
        $this->postJson('/api/v1/provider/services', [
            'title' => 'Bad', 'price' => 100, 'discount_price' => 100,
        ], $auth)->assertStatus(422);

        $list = $this->getJson('/api/v1/provider/services', $auth)->assertOk();
        $list->assertJsonCount(1, 'data');
        $list->assertJsonPath('data.0.category.title', 'Home');

        $this->putJson("/api/v1/provider/services/{$serviceId}", [
            'price' => 350,
        ], $auth)->assertOk();
        $this->assertEquals(350.0, (float) ProviderService::find($serviceId)->price);

        $this->postJson("/api/v1/provider/services/{$serviceId}/toggle", [], $auth)
            ->assertOk()->assertJsonPath('data.is_active', false);
        $this->assertFalse((bool) ProviderService::find($serviceId)->is_active);

        Auth::forgetGuards();
        $this->getJson('/api/v1/provider/services')->assertStatus(401);
    }

    public function test_worker_crud_and_foreign_isolation(): void
    {
        $auth = ['Authorization' => 'Bearer '.$this->providerToken('03003')];
        $otherToken = $this->providerToken('03004');
        $otherAuth = ['Authorization' => 'Bearer '.$otherToken];

        $create = $this->postJson('/api/v1/provider/workers', [
            'name' => 'Ali', 'phone' => '0300777',
        ], $auth)->assertCreated();
        $workerId = $create->json('data.id');

        $this->getJson('/api/v1/provider/workers', $auth)->assertOk()->assertJsonCount(1, 'data');

        Auth::forgetGuards(); // sanctum memoizes the user per test — reset on switch

        $this->getJson('/api/v1/provider/workers', $otherAuth)->assertOk()->assertJsonCount(0, 'data');

        // Foreign provider sees 404 on update/toggle, nothing changes.
        $this->putJson("/api/v1/provider/workers/{$workerId}", ['name' => 'Hijack'], $otherAuth)
            ->assertStatus(404);
        $this->postJson("/api/v1/provider/workers/{$workerId}/toggle", [], $otherAuth)
            ->assertStatus(404);
        $this->assertEquals('Ali', ProviderWorker::find($workerId)->name);

        Auth::forgetGuards(); // reset again when switching back

        $this->putJson("/api/v1/provider/workers/{$workerId}", ['name' => 'Ali Raza'], $auth)->assertOk();
        $this->assertEquals('Ali Raza', ProviderWorker::find($workerId)->name);
    }

    public function test_payouts_request_and_list(): void
    {
        $auth = ['Authorization' => 'Bearer '.$this->providerToken()];

        $req = $this->postJson('/api/v1/provider/payouts', [
            'amount' => 2000, 'method' => 'bank',
        ], $auth)->assertCreated()->assertJsonPath('data.status', 'pending');

        $list = $this->getJson('/api/v1/provider/payouts', $auth)->assertOk();
        $list->assertJsonCount(1, 'data');
        $this->assertEquals($req->json('data.id'), $list->json('data.0.id'));

        Auth::forgetGuards();
        $this->getJson('/api/v1/provider/payouts')->assertStatus(401);
    }
}
