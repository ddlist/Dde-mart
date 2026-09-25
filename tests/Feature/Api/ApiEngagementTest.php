<?php

namespace Tests\Feature\Api;

use App\Models\Banner;
use App\Models\Language;
use App\Models\Notification;
use App\Models\OnboardingSlide;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\PushToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart API — engagement tests (original): feeds, tokens, reviews.
 */
class ApiEngagementTest extends TestCase
{
    use RefreshDatabase;

    protected function customer(array $overrides = []): array
    {
        $response = $this->postJson('/api/v1/auth/register', array_merge([
            'name' => 'Sara', 'phone' => '03001234567',
        ], $overrides));

        return ['token' => $response->json('data.token')];
    }

    protected function auth(array $customer): array
    {
        return ['Authorization' => 'Bearer '.$customer['token']];
    }

    public function test_public_feeds(): void
    {
        Page::create(['name' => 'Terms', 'slug' => 'terms', 'body' => 'Rules']);
        Page::create(['name' => 'Draft', 'slug' => 'draft', 'body' => 'x', 'is_active' => false]);
        Banner::create(['title' => 'Sale']);
        Language::create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        OnboardingSlide::create(['title' => 'Welcome', 'audience' => 'customer']);
        Notification::create(['audience' => 'customer', 'subject' => 'Hi', 'message' => 'Hello']);
        Notification::create(['audience' => 'driver', 'subject' => 'D', 'message' => 'Driver only']);

        $this->getJson('/api/v1/pages')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/pages/terms')->assertOk()->assertJsonPath('data.body', 'Rules');
        $this->getJson('/api/v1/pages/nope')->assertStatus(404);
        $this->getJson('/api/v1/banners')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/languages')->assertOk()->assertJsonPath('data.0.code', 'en');
        $this->getJson('/api/v1/onboarding?audience=customer')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/settings')->assertOk()->assertJsonPath('data.site_name', 'DDE-Mart');

        // Only customer-scoped notifications leak to apps.
        $feed = $this->getJson('/api/v1/notifications')->assertOk();
        $feed->assertJsonCount(1, 'data');
        $feed->assertJsonPath('data.0.subject', 'Hi');
    }

    public function test_push_token_register_idempotent(): void
    {
        $auth = $this->auth($this->customer());

        $this->postJson('/api/v1/push-tokens', ['token' => 'abc', 'platform' => 'android'], $auth)
            ->assertOk();
        $this->postJson('/api/v1/push-tokens', ['token' => 'abc', 'platform' => 'ios'], $auth)
            ->assertOk();

        $this->assertEquals(1, PushToken::count());
        $this->assertEquals('ios', PushToken::firstOrFail()->platform);

        $this->deleteJson('/api/v1/push-tokens', ['token' => 'abc'], $auth)->assertOk();
        $this->assertEquals(0, PushToken::count());

        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->postJson('/api/v1/push-tokens', ['token' => 'x'])->assertStatus(401);
    }

    public function test_review_submit_flow(): void
    {
        $auth = $this->auth($this->customer());
        $product = Product::create(['name' => 'P', 'slug' => 'p', 'price' => 10]);

        $this->postJson('/api/v1/checkout', [
            'items' => [['product_id' => $product->id]], 'payment_method' => 'cod',
        ], $auth)->assertCreated();

        $order = Order::firstOrFail();

        // Not completed yet → rejected.
        $this->postJson('/api/v1/reviews', [
            'order_id' => $order->id, 'product_id' => $product->id, 'rating' => 5,
        ], $auth)->assertStatus(422);

        $order->update(['status' => 'completed']);

        $this->postJson('/api/v1/reviews', [
            'order_id' => $order->id, 'product_id' => $product->id,
            'rating' => 5, 'comment' => 'Great',
        ], $auth)->assertCreated()->assertJsonPath('data.status', 'pending');

        // Duplicate rejected.
        $this->postJson('/api/v1/reviews', [
            'order_id' => $order->id, 'product_id' => $product->id, 'rating' => 4,
        ], $auth)->assertStatus(422);

        $this->getJson('/api/v1/reviews', $auth)->assertOk()->assertJsonCount(1, 'data');
    }
}
