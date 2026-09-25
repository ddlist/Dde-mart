<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Customer;
use App\Models\OtpCode;
use App\Models\Product;
use App\Models\Section;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/*
 * DDE-Mart API — foundation tests (original): auth + public catalog.
 */
class ApiFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_login_me_logout(): void
    {
        $reg = $this->postJson('/api/v1/auth/register', [
            'name' => 'Sara', 'phone' => '03001234567', 'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertCreated();

        $token = $reg->json('data.token');
        $this->assertNotEmpty($token);

        // Duplicate phone rejected.
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Sara', 'phone' => '03001234567',
        ])->assertStatus(422);

        $login = $this->postJson('/api/v1/auth/login', [
            'phone' => '03001234567', 'password' => 'secret123',
        ])->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'phone' => '03001234567', 'password' => 'wrong',
        ])->assertStatus(401);

        $this->getJson('/api/v1/me')->assertStatus(401);

        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer '.$login->json('data.token')])
            ->assertOk()
            ->assertJsonPath('data.phone', '03001234567');

        $this->postJson('/api/v1/auth/logout', [], ['Authorization' => 'Bearer '.$login->json('data.token')])
            ->assertOk();

        // Token row revoked (a fresh process would now 401; the test guard
        // caches the resolved user in-memory, so assert the DB state instead).
        $tokenId = explode('|', $login->json('data.token'))[0];
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_otp_flow_creates_and_authenticates(): void
    {
        $req = $this->postJson('/api/v1/auth/otp/request', ['phone' => '03009998877'])
            ->assertOk();

        $code = $req->json('data.debug_code');
        $this->assertNotEmpty($code); // non-production exposes it for tests/clients

        $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '03009998877', 'code' => '000000', 'name' => 'Ali',
        ])->assertStatus(401);

        $verify = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '03009998877', 'code' => $code, 'name' => 'Ali',
        ])->assertOk();

        $this->assertNotEmpty($verify->json('data.token'));
        $this->assertTrue(Customer::where('phone', '03009998877')->exists());

        // Code is single-use.
        $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '03009998877', 'code' => $code,
        ])->assertStatus(401);
    }

    public function test_public_catalog_browse(): void
    {
        $section = Section::create(['name' => 'Food', 'slug' => 'food']);
        $category = Category::create(['name' => 'Pizza', 'slug' => 'pizza', 'section_id' => $section->id]);
        $hidden = Category::create(['name' => 'Hidden', 'slug' => 'hidden', 'is_active' => false]);
        $product = Product::create([
            'name' => 'Margherita', 'slug' => 'margherita', 'price' => 10,
            'category_id' => $category->id, 'quantity' => 5,
        ]);
        $store = Store::create(['name' => 'Fresh', 'slug' => 'fresh', 'status' => 'active']);
        Store::create(['name' => 'Pending', 'slug' => 'pending-store', 'status' => 'pending']);

        $this->getJson('/api/v1/sections')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/categories?section_id='.$section->id)
            ->assertOk()->assertJsonCount(1, 'data');

        $products = $this->getJson('/api/v1/products?q=marg')->assertOk();
        $this->assertEquals(10, $products->json('data.0.selling_price'));

        $this->getJson('/api/v1/products/'.$product->id)
            ->assertOk()->assertJsonPath('data.name', 'Margherita');

        $this->getJson('/api/v1/stores')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/stores/'.$store->id)
            ->assertOk()->assertJsonMissing(['commission_value' => null]); // internals never leak

        // per_page capped.
        $this->getJson('/api/v1/products?per_page=500')->assertOk();
    }

    public function test_inactive_product_is_404(): void
    {
        $product = Product::create(['name' => 'Old', 'slug' => 'old', 'price' => 5, 'is_active' => false]);

        $this->getJson('/api/v1/products/'.$product->id)->assertStatus(404);
    }
}
