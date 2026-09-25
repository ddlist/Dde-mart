<?php

namespace Tests\Feature\Shop;

use App\Models\Category;
use App\Models\Product;
use App\Models\Section;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart storefront tests (original): home, location, catalog, cart.
 */
class ShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_with_content(): void
    {
        $section = Section::create(['name' => 'Food', 'slug' => 'food']);
        $store = Store::create(['name' => 'Fresh', 'slug' => 'fresh', 'status' => 'active']);
        Product::create(['name' => 'Margherita', 'slug' => 'margherita', 'price' => 10]);

        $response = $this->get(route('shop.home'))->assertOk();

        $response->assertSee('Food');
        $response->assertSee('Fresh');
        $response->assertSee('Margherita');
        $this->assertStringNotContainsString('<x-', $response->getContent());
    }

    public function test_location_and_section_context(): void
    {
        $section = Section::create(['name' => 'Food', 'slug' => 'food']);
        Product::create(['name' => 'In', 'slug' => 'in', 'price' => 5, 'section_id' => $section->id]);
        Product::create(['name' => 'Out', 'slug' => 'out', 'price' => 5]);

        $this->post(route('shop.location.store'), [
            'label' => 'Home', 'latitude' => 24.86, 'longitude' => 67.0,
            'section_id' => $section->id,
        ])->assertRedirect(route('shop.home'));

        $home = $this->get(route('shop.home'))->assertOk();
        $home->assertSee('In');
        $home->assertDontSee('>Out<');
    }

    public function test_catalog_pages(): void
    {
        $category = Category::create(['name' => 'Pizza', 'slug' => 'pizza']);
        $store = Store::create(['name' => 'Fresh', 'slug' => 'fresh', 'status' => 'active']);
        $product = Product::create([
            'name' => 'Margherita', 'slug' => 'margherita', 'price' => 10,
            'category_id' => $category->id, 'vendor_id' => $store->id,
        ]);
        $addon = $product->addons()->create(['name' => 'Cheese', 'price' => 2]);

        $this->get(route('shop.categories.show', $category->slug))->assertOk()->assertSee('Margherita');
        $this->get(route('shop.stores.show', $store->slug))->assertOk()->assertSee('Fresh');

        $detail = $this->get(route('shop.products.show', $product->slug))->assertOk();
        $detail->assertSee('Cheese')->assertSee('Add to cart');

        $this->get(route('shop.search', ['q' => 'margh']))->assertOk()->assertSee('Margherita');
    }

    public function test_cart_add_update_remove_with_server_totals(): void
    {
        $product = Product::create(['name' => 'P', 'slug' => 'p', 'price' => 100]);
        $addon = $product->addons()->create(['name' => 'X', 'price' => 10]);

        $this->post(route('shop.cart.add'), [
            'product_id' => $product->id, 'quantity' => 2, 'addons' => [$addon->id],
        ])->assertRedirect(route('shop.cart'));

        $cart = $this->get(route('shop.cart'))->assertOk();
        $cart->assertSee('220'); // (100+10)*2, server-computed

        // Tampered totals can't sneak in — page recomputes from session ids.
        $key = array_key_first(session('cart'));
        $this->post(route('shop.cart.update'), ['key' => $key, 'quantity' => 1])
            ->assertRedirect(route('shop.cart'));
        $this->get(route('shop.cart'))->assertOk()->assertSee('110');

        $this->post(route('shop.cart.remove'), ['key' => $key])
            ->assertRedirect(route('shop.cart'));
        $this->get(route('shop.cart'))->assertOk()->assertSee('Your cart is empty');
    }

    public function test_cart_coupon_persists(): void
    {
        \App\Models\Coupon::create(['code' => 'FLAT5', 'discount_type' => 'fixed', 'discount_value' => 5]);
        $product = Product::create(['name' => 'P', 'slug' => 'p', 'price' => 100]);

        $this->post(route('shop.cart.add'), ['product_id' => $product->id]);
        $this->post(route('shop.cart.coupon'), ['coupon_code' => 'flat5'])
            ->assertRedirect(route('shop.cart'));

        $this->get(route('shop.cart'))->assertOk()->assertSee('FLAT5');
    }
}
