<?php

namespace Tests\Feature\Shop;

use App\Models\Category;
use App\Models\Customer;
use App\Models\ParcelCategory;
use App\Models\ParcelWeight;
use App\Models\Product;
use App\Models\Section;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/*
 * DDE-Mart storefront — render guard (original). Mirrors UiRenderTest:
 * every parameter-less GET shop page must 200 with no raw component tags.
 */
class ShopRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_shop_pages_render(): void
    {
        $customer = Customer::create(['name' => 'Sara', 'phone' => '03001234567', 'password' => 'secret123']);
        Section::create(['name' => 'Food', 'slug' => 'food']);
        Category::create(['name' => 'Pizza', 'slug' => 'pizza']);
        Product::create(['name' => 'P', 'slug' => 'p', 'price' => 10]);
        Store::create(['name' => 'S', 'slug' => 's', 'status' => 'active']);
        ParcelCategory::create(['name' => 'Docs', 'slug' => 'docs']);
        ParcelWeight::create(['title' => '5 KG', 'delivery_charge' => 50]);

        $this->post(route('shop.login.attempt'), [
            'phone' => '03001234567', 'password' => 'secret123',
        ]);

        $failures = [];

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = $route->uri();

            if (! str_starts_with($uri, 'shop') || str_contains($uri, '{')) {
                continue;
            }

            $response = $this->get('/'.$uri);
            $status = $response->getStatusCode();

            // Redirects are valid (guest/auth gates, empty cart) — only real
            // failures and uncompiled tags are regressions.
            if (! in_array($status, [200, 302], true)) {
                $failures[] = "{$uri} → HTTP {$status}";
                continue;
            }

            if ($status !== 200) {
                continue;
            }

            if (str_contains($response->getContent(), '<x-')) {
                $failures[] = "{$uri} → raw <x- component tag in output";
            }
        }

        $this->assertSame([], $failures, "Shop render problems:\n".implode("\n", $failures));
    }
}
