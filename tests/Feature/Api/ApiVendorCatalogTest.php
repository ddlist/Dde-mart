<?php

namespace Tests\Feature\Api;

use App\Models\Owner;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
 * DDE-Mart API — vendor catalog write tests (original): product create/update
 * scoped to owned stores, image upload supported.
 */
class ApiVendorCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function vendorToken(string $phone = '03005'): string
    {
        Owner::create(['name' => 'V', 'phone' => $phone, 'status' => 'active']);
        $req = $this->postJson('/api/v1/work/auth/otp/request', ['phone' => $phone, 'role' => 'vendor']);

        return $this->postJson('/api/v1/work/auth/otp/verify', [
            'phone' => $phone, 'role' => 'vendor', 'code' => $req->json('data.debug_code'),
        ])->json('data.token');
    }

    public function test_product_create_update_scoped(): void
    {
        Storage::fake('public');
        $auth = ['Authorization' => 'Bearer '.$this->vendorToken()];

        $owner = Owner::where('phone', '03005')->firstOrFail();
        $store = Store::create(['name' => 'Mine', 'slug' => 'mine', 'status' => 'active', 'owner_id' => $owner->id]);
        $rival = Owner::create(['name' => 'R', 'phone' => '03006', 'status' => 'active']);
        $foreign = Store::create(['name' => 'Theirs', 'slug' => 'theirs', 'status' => 'active', 'owner_id' => $rival->id]);

        // Foreign store is invisible.
        $this->postJson('/api/v1/vendor/products', [
            'store_id' => $foreign->id, 'name' => 'Hijack', 'price' => 10,
        ], $auth)->assertStatus(404);
        $this->assertEquals(0, Product::count());

        $create = $this->postJson('/api/v1/vendor/products', [
            'store_id' => $store->id, 'name' => 'Margherita', 'price' => 500,
            'discount_price' => 450, 'quantity' => 20,
        ], $auth)->assertCreated();
        $productId = $create->json('data.id');

        $product = Product::findOrFail($productId);
        $this->assertEquals($store->id, $product->vendor_id);
        $this->assertEquals('margherita', $product->slug);

        // Discount at/above price rejected.
        $this->putJson("/api/v1/vendor/products/{$productId}", [
            'discount_price' => 500,
        ], $auth)->assertStatus(422);

        // Image upload stores on the public disk.
        $this->putJson("/api/v1/vendor/products/{$productId}", [
            'price' => 550,
            'image' => UploadedFile::fake()->image('pizza.jpg'),
        ], $auth)->assertOk();
        $this->assertNotNull(Product::find($productId)->image_path);

        Auth::forgetGuards();
        $this->postJson('/api/v1/vendor/products', [
            'store_id' => $store->id, 'name' => 'X', 'price' => 1,
        ])->assertStatus(401);
    }
}
