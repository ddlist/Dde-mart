<?php

namespace Tests\Feature\Api;

use App\Models\Owner;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/*
 * DDE-Mart API — vendor stores surface (original): owned stores list with
 * product counts. Regression: Store::products() must exist for withCount.
 */
class ApiVendorStoresTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_returns_owned_with_counts(): void
    {
        $owner = Owner::create(['name' => 'T', 'phone' => '0300999888', 'status' => 'active']);
        $other = Owner::create(['name' => 'O', 'phone' => '0300777666', 'status' => 'active']);
        $store = Store::create([
            'name' => 'S1', 'slug' => 's1', 'status' => 'approved', 'owner_id' => $owner->id,
        ]);
        Store::create([
            'name' => 'S2', 'slug' => 's2', 'status' => 'approved', 'owner_id' => $other->id,
        ]);

        Sanctum::actingAs($owner, ['owner']);

        $data = $this->getJson('/api/v1/vendor/stores')->assertOk()->json('data');

        $this->assertCount(1, $data);
        $this->assertSame($store->id, $data[0]['id']);
        $this->assertSame('S1', $data[0]['name']);
        $this->assertArrayHasKey('products_count', $data[0]);
    }
}
