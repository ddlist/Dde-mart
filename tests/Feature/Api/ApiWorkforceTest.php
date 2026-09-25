<?php

namespace Tests\Feature\Api;

use App\Models\DocumentType;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Owner;
use App\Models\PayoutRequest;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Store;
use App\Models\Verification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
 * DDE-Mart API — workforce tests (original): work auth, driver + vendor surfaces.
 */
class ApiWorkforceTest extends TestCase
{
    use RefreshDatabase;

    protected function workLogin(string $role, string $phone): string
    {
        $req = $this->postJson('/api/v1/work/auth/otp/request', [
            'phone' => $phone, 'role' => $role,
        ])->assertOk();

        $verify = $this->postJson('/api/v1/work/auth/otp/verify', [
            'phone' => $phone, 'role' => $role, 'code' => $req->json('data.debug_code'),
        ])->assertOk();

        return $verify->json('data.token');
    }

    public function test_driver_auth_and_surfaces(): void
    {
        $driver = Driver::create(['name' => 'Rider', 'phone' => '03001', 'status' => 'active']);
        $token = $this->workLogin('driver', '03001');
        $auth = ['Authorization' => 'Bearer '.$token];

        $this->getJson('/api/v1/driver/profile', $auth)->assertOk()->assertJsonPath('data.name', 'Rider');

        $this->postJson('/api/v1/driver/availability', ['is_online' => true], $auth)->assertOk();
        $this->assertTrue($driver->fresh()->is_online);

        // Unknown number → 404; suspended driver → 403.
        $this->postJson('/api/v1/work/auth/otp/request', ['phone' => '0999', 'role' => 'driver'])->assertOk();
        $this->postJson('/api/v1/work/auth/otp/verify', ['phone' => '0999', 'role' => 'driver', 'code' => '000000'])
            ->assertStatus(401); // wrong code

        $driver->update(['status' => 'suspended']);
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $req = $this->postJson('/api/v1/work/auth/otp/request', ['phone' => '03001', 'role' => 'driver'])->assertOk();
        $this->postJson('/api/v1/work/auth/otp/verify', [
            'phone' => '03001', 'role' => 'driver', 'code' => $req->json('data.debug_code'),
        ])->assertStatus(403);
    }

    public function test_driver_jobs_documents_payouts(): void
    {
        $driver = Driver::create(['name' => 'Rider', 'phone' => '03001', 'status' => 'active']);
        $auth = ['Authorization' => 'Bearer '.$this->workLogin('driver', '03001')];

        $order = \App\Models\ParcelOrder::create([
            'sender_name' => 'A', 'receiver_name' => 'B', 'total' => 50, 'driver_id' => $driver->id,
        ]);

        $jobs = $this->getJson('/api/v1/driver/jobs', $auth)->assertOk();
        $this->assertEquals($order->id, $jobs->json('data.mine.0.id'));
        $this->assertEquals('parcel', $jobs->json('data.mine.0.type'));

        $type = DocumentType::create(['title' => 'License', 'owner_type' => 'driver', 'front_required' => true]);
        Storage::fake('public');

        $this->postJson('/api/v1/driver/documents', [
            'document_type_id' => $type->id,
        ], $auth)->assertStatus(422); // front required

        $this->postJson('/api/v1/driver/documents', [
            'document_type_id' => $type->id,
            'front' => UploadedFile::fake()->image('lic.jpg'),
        ], $auth)->assertCreated();

        $this->assertEquals(1, Verification::count());

        $this->postJson('/api/v1/driver/payouts', [
            'amount' => 200, 'method' => 'bank',
        ], $auth)->assertCreated();

        $payout = PayoutRequest::firstOrFail();
        $this->assertEquals('driver', $payout->requester_type);
        $this->assertEquals($driver->id, $payout->requester_id);

        $this->getJson('/api/v1/driver/payouts', $auth)->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_vendor_surfaces_and_toggles(): void
    {
        $owner = Owner::create(['name' => 'Ali', 'phone' => '03002', 'status' => 'active']);
        $store = Store::create(['name' => 'Shop', 'slug' => 'shop', 'status' => 'active', 'owner_id' => $owner->id]);
        $product = Product::create(['name' => 'P', 'slug' => 'p', 'price' => 10, 'vendor_id' => $store->id]);
        $other = Product::create(['name' => 'Q', 'slug' => 'q', 'price' => 5]);

        $auth = ['Authorization' => 'Bearer '.$this->workLogin('vendor', '03002')];

        $this->getJson('/api/v1/vendor/stores', $auth)->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/vendor/products', $auth)->assertOk()->assertJsonCount(1, 'data');

        // Toggling another store's product → 404.
        $this->postJson("/api/v1/vendor/products/{$other->id}/toggle", [], $auth)->assertStatus(404);
        $this->postJson("/api/v1/vendor/products/{$product->id}/toggle", [], $auth)->assertOk();
        $this->assertFalse($product->fresh()->is_active);

        $this->postJson("/api/v1/vendor/stores/{$store->id}/toggle", ['is_open' => false], $auth)
            ->assertOk()->assertJsonPath('data.is_open', false);

        // Checkout links single-store carts; vendor sees the order.
        $cust = $this->postJson('/api/v1/auth/register', ['name' => 'C', 'phone' => '03111'])
            ->json('data.token');
        $custAuth = ['Authorization' => 'Bearer '.$cust];
        \Illuminate\Support\Facades\Auth::forgetGuards();

        // Inactive items are correctly rejected…
        $this->postJson('/api/v1/checkout', [
            'items' => [['product_id' => $product->id]], 'payment_method' => 'cod',
        ], $custAuth)->assertStatus(422);

        // …toggle back on, then checkout succeeds.
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->postJson("/api/v1/vendor/products/{$product->id}/toggle", [], $auth)->assertOk();
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->postJson('/api/v1/checkout', [
            'items' => [['product_id' => $product->id]], 'payment_method' => 'cod',
        ], $custAuth)->assertCreated();

        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->getJson('/api/v1/vendor/orders', $auth)->assertOk()->assertJsonCount(1, 'data');

        $this->postJson('/api/v1/vendor/payouts', [
            'store_id' => $store->id, 'amount' => 500, 'method' => 'bank',
        ], $auth)->assertCreated();
        $this->assertEquals($store->id, PayoutRequest::firstOrFail()->requester_id);
    }

    public function test_uploads_validated(): void
    {
        $customer = $this->postJson('/api/v1/auth/register', ['name' => 'C', 'phone' => '03111'])
            ->json('data.token');
        $auth = ['Authorization' => 'Bearer '.$customer];
        Storage::fake('public');

        $this->postJson('/api/v1/uploads', [
            'file' => UploadedFile::fake()->image('a.png'), 'folder' => 'avatars',
        ], $auth)->assertCreated()->assertJsonPath('data.path', fn ($p) => str_starts_with($p, 'avatars/'));

        // Omitted folder defaults to avatars; unknown folder rejected.
        $this->postJson('/api/v1/uploads', [
            'file' => UploadedFile::fake()->image('a.png'),
        ], $auth)->assertCreated();
        $this->postJson('/api/v1/uploads', [
            'file' => UploadedFile::fake()->image('a.png'), 'folder' => 'nope',
        ], $auth)->assertStatus(422);

        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->postJson('/api/v1/uploads', [
            'file' => UploadedFile::fake()->image('a.png'),
        ])->assertStatus(401);
    }

    public function test_provider_and_owner_roles_login(): void
    {
        Provider::create(['name' => 'Fix', 'phone' => '03003', 'status' => 'active']);
        $token = $this->workLogin('provider', '03003');
        $this->assertNotEmpty($token);

        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->getJson('/api/v1/driver/profile', ['Authorization' => 'Bearer '.$token])
            ->assertStatus(403); // provider token lacks driver ability
    }
}
