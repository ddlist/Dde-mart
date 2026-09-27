<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart API — zero-discount regression test (original). Imported rows
 * carry discount_price = 0 for "no discount"; the quote must use the full
 * price instead of totalling 0.
 */
class ApiDiscountPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_zero_discount_price_quotes_full_price(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Sara', 'phone' => '03001234567',
        ]);
        $auth = ['Authorization' => 'Bearer '.$response->json('data.token')];

        $product = Product::create([
            'name' => 'P', 'slug' => 'p', 'price' => 199, 'discount_price' => 0,
        ]);

        $this->getJson("/api/v1/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.selling_price', 199);

        $this->postJson('/api/v1/cart/quote', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ], $auth)
            ->assertOk()
            ->assertJsonPath('data.subtotal', 199)
            ->assertJsonPath('data.total', 199);
    }
}
