<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\CabType;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Banner;
use App\Models\Driver;
use App\Models\Owner;
use App\Models\ParcelCategory;
use App\Models\ParcelWeight;
use App\Models\Product;
use App\Models\Provider;
use App\Models\ProviderCategory;
use App\Models\RentalPackage;
use App\Models\RentalVehicleType;
use App\Models\Section;
use App\Models\Store;
use Illuminate\Database\Seeder;

/*
 * DDE-Mart — demo dataset for on-device testing (original seeder).
 * Run explicitly: `php artisan db:seed --class=DemoSeeder`.
 * NEVER called from DatabaseSeeder (production must stay clean).
 * Idempotent via slugs/codes/phones — safe to re-run.
 *
 * Demo logins (OTP codes appear in logs / debug_code outside production):
 *   driver   0301111111 (delivery, online, positioned by Demo Store)
 *   provider 0302222222 · worker 0303333333 · vendor/owner 0304444444
 *   customer self-registers in the app.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $food = Section::updateOrCreate(
            ['slug' => 'food'],
            ['name' => 'Food', 'service_type' => 'food', 'is_active' => true]
        );
        $grocery = Section::updateOrCreate(
            ['slug' => 'grocery'],
            ['name' => 'Grocery', 'service_type' => 'grocery', 'is_active' => true]
        );

        $pizza = Category::updateOrCreate(
            ['slug' => 'pizza'],
            ['name' => 'Pizza', 'section_id' => $food->id, 'is_active' => true]
        );
        $burgers = Category::updateOrCreate(
            ['slug' => 'burgers'],
            ['name' => 'Burgers', 'section_id' => $food->id, 'is_active' => true]
        );
        $fresh = Category::updateOrCreate(
            ['slug' => 'fresh'],
            ['name' => 'Fresh', 'section_id' => $grocery->id, 'is_active' => true]
        );

        Brand::updateOrCreate(['slug' => 'demo'], ['name' => 'Demo']);

        $owner = Owner::updateOrCreate(
            ['phone' => '0304444444'],
            ['name' => 'Demo Owner', 'status' => 'active']
        );

        $store = Store::updateOrCreate(
            ['slug' => 'demo-store'],
            [
                'name' => 'Demo Store', 'status' => 'active', 'is_open' => true,
                'owner_id' => $owner->id, 'section_id' => $food->id,
                'address' => 'Demo Street 1', 'latitude' => 31.5204, 'longitude' => 74.3587,
            ]
        );

        $menu = [
            ['Margherita', 'margherita', $pizza->id, 499, 449],
            ['Chicken Fajita', 'chicken-fajita', $pizza->id, 699, null],
            ['Zinger Burger', 'zinger-burger', $burgers->id, 450, 399],
            ['Fresh Milk 1L', 'fresh-milk', $fresh->id, 180, null],
        ];

        foreach ($menu as [$name, $slug, $categoryId, $price, $discount]) {
            $product = Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name, 'price' => $price, 'discount_price' => $discount,
                    'quantity' => 50, 'is_active' => true,
                    'vendor_id' => $store->id, 'section_id' => $food->id,
                    'category_id' => $categoryId,
                ]
            );

            if ($slug === 'margherita') {
                $product->addons()->updateOrCreate(
                    ['name' => 'Extra cheese'],
                    ['price' => 100]
                );
            }
        }

        Coupon::updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'description' => '10% off demo orders', 'discount_type' => 'percentage',
                'discount_value' => 10, 'max_discount' => 200,
                'scope' => 'food', 'is_public' => true, 'is_active' => true,
            ]
        );

        Banner::updateOrCreate(
            ['title' => 'Demo launch'],
            ['section_id' => $food->id, 'position' => 'home', 'is_active' => true]
        );

        ParcelCategory::updateOrCreate(['slug' => 'docs'], ['name' => 'Documents']);
        ParcelWeight::updateOrCreate(
            ['title' => 'Up to 5 KG'],
            ['delivery_charge' => 150]
        );

        $sedan = RentalVehicleType::updateOrCreate(
            ['slug' => 'sedan'],
            ['name' => 'Sedan', 'is_active' => true]
        );
        RentalPackage::updateOrCreate(
            ['name' => 'City 4h'],
            ['vehicle_type_id' => $sedan->id, 'base_fare' => 2000, 'is_active' => true]
        );

        CabType::updateOrCreate(
            ['slug' => 'mini'],
            [
                'name' => 'Mini', 'capacity' => 4,
                'base_fare' => 150, 'per_km_fare' => 40, 'min_fare' => 200,
                'is_active' => true,
            ]
        );

        Driver::updateOrCreate(
            ['phone' => '0301111111'],
            [
                'name' => 'Demo Rider', 'kind' => 'delivery', 'status' => 'active',
                'is_online' => true, 'latitude' => 31.5210, 'longitude' => 74.3590,
                'location_updated_at' => now(),
            ]
        );

        $provider = Provider::updateOrCreate(
            ['phone' => '0302222222'],
            ['name' => 'Demo Fixers', 'status' => 'active']
        );
        $cat = ProviderCategory::updateOrCreate(['title' => 'Plumbing']);
        $provider->services()->updateOrCreate(
            ['title' => 'Tap repair'],
            ['price' => 500, 'category_id' => $cat->id, 'is_active' => true]
        );
        $provider->workers()->updateOrCreate(
            ['phone' => '0303333333'],
            ['name' => 'Demo Handyman', 'is_active' => true]
        );
    }
}
