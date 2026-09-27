<?php

namespace Tests\Feature\Api;

use App\Models\ItemReview;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart API — public ratings tests (original): product/store aggregates
 * plus the public approved-reviews feeds. Pending reviews stay invisible.
 */
class ApiReviewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_show_carries_rating_aggregates(): void
    {
        $product = Product::create(['name' => 'P', 'slug' => 'p', 'price' => 10]);

        ItemReview::create([
            'product_id' => $product->id, 'author_name' => 'A',
            'rating' => 5, 'comment' => 'Great', 'status' => 'approved',
        ]);
        ItemReview::create([
            'product_id' => $product->id, 'author_name' => 'B',
            'rating' => 3, 'comment' => 'Okay', 'status' => 'approved',
        ]);
        ItemReview::create([
            'product_id' => $product->id, 'author_name' => 'C',
            'rating' => 1, 'comment' => 'Hidden', 'status' => 'pending',
        ]);

        $this->getJson("/api/v1/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.rating_avg', 4)
            ->assertJsonPath('data.rating_count', 2);
    }

    public function test_product_reviews_feed_lists_only_approved(): void
    {
        $product = Product::create(['name' => 'P', 'slug' => 'p', 'price' => 10]);

        ItemReview::create([
            'product_id' => $product->id, 'author_name' => 'A',
            'rating' => 5, 'comment' => 'Great', 'status' => 'approved',
        ]);
        ItemReview::create([
            'product_id' => $product->id, 'author_name' => 'C',
            'rating' => 1, 'comment' => 'Hidden', 'status' => 'pending',
        ]);

        $this->getJson("/api/v1/products/{$product->id}/reviews")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.author_name', 'A');
    }

    public function test_store_show_carries_rating_aggregates_and_feed(): void
    {
        $store = Store::create(['name' => 'S', 'slug' => 's', 'status' => 'active']);
        $product = Product::create([
            'name' => 'P', 'slug' => 'p', 'price' => 10, 'vendor_id' => $store->id,
        ]);

        ItemReview::create([
            'product_id' => $product->id, 'store_id' => $store->id, 'author_name' => 'A',
            'rating' => 4, 'comment' => 'Nice', 'status' => 'approved',
        ]);

        $this->getJson("/api/v1/stores/{$store->id}")
            ->assertOk()
            ->assertJsonPath('data.rating_avg', 4)
            ->assertJsonPath('data.rating_count', 1);

        $this->getJson("/api/v1/stores/{$store->id}/reviews")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
