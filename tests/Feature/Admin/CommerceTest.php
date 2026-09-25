<?php

namespace Tests\Feature\Admin;

use App\Models\Disbursement;
use App\Models\FilterPreset;
use App\Models\GiftOrder;
use App\Models\ItemReview;
use App\Models\PayoutRequest;
use App\Models\PlanSubscription;
use App\Models\ReviewCriterion;
use App\Models\Role;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — commerce follow-ups tests (original).
 */
class CommerceTest extends TestCase
{
    use RefreshDatabase;

    protected function superAdmin(): User
    {
        $role = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_super' => true],
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_review_moderation_and_criteria(): void
    {
        $admin = $this->superAdmin();
        $review = ItemReview::create(['author_name' => 'Zoya', 'rating' => 4, 'comment' => 'Tasty']);

        $this->actingAs($admin)->post(route('admin.reviews.moderate', $review), ['to' => 'approved'])
            ->assertSessionHas('success');
        $this->assertEquals('approved', $review->fresh()->status);

        $this->actingAs($admin)->post(route('admin.review-criteria.store'), ['title' => 'Freshness'])
            ->assertRedirect(route('admin.review-criteria.index'));
        $this->assertNotNull(ReviewCriterion::where('title', 'Freshness')->first());
    }

    public function test_gift_redeem_flow(): void
    {
        $admin = $this->superAdmin();
        $gift = GiftOrder::create(['code' => 'GIFT-1', 'amount' => 25]);

        $this->actingAs($admin)->post(route('admin.gift-orders.redeem', $gift))
            ->assertSessionHas('success');
        $this->assertEquals('redeemed', $gift->fresh()->status);

        // Double redeem refused.
        $this->actingAs($admin)->post(route('admin.gift-orders.redeem', $gift))
            ->assertSessionHas('error');
    }

    public function test_disbursement_batch_cascades(): void
    {
        $admin = $this->superAdmin();
        $p1 = PayoutRequest::create(['requester_type' => 'vendor', 'amount' => 100, 'status' => 'approved']);
        $p2 = PayoutRequest::create(['requester_type' => 'driver', 'amount' => 50, 'status' => 'pending']);

        $response = $this->actingAs($admin)->post(route('admin.disbursements.store'), [
            'method' => 'bank', 'payouts' => [$p1->id, $p2->id],
        ]);

        // Only the approved one attaches.
        $batch = Disbursement::firstOrFail();
        $response->assertRedirect(route('admin.disbursements.show', $batch));
        $this->assertEquals([$p1->id], $batch->payouts()->pluck('payout_requests.id')->all());

        $this->actingAs($admin)->post(route('admin.disbursements.pay', $batch))
            ->assertSessionHas('success');

        $this->assertEquals('paid', $batch->fresh()->status);
        $this->assertEquals('paid', $p1->fresh()->status);
        $this->assertEquals('pending', $p2->fresh()->status); // untouched
    }

    public function test_story_and_preset_gates(): void
    {
        $role = Role::create(['name' => 'Limited', 'slug' => 'limited']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('admin.stories.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.subscriptions.index'))->assertForbidden();
    }

    public function test_importer_imports_commerce_batch(): void
    {
        $this->artisan('ddemart:import', [
            '--file' => base_path('tests/Fixtures/legacy-sample.json'),
        ])->assertSuccessful();

        $this->assertEquals('Freshness', ReviewCriterion::where('legacy_id', 'ra001')->firstOrFail()->title);

        $review = ItemReview::where('legacy_id', 'rv001')->firstOrFail();
        $this->assertEquals(4, $review->rating);
        $this->assertEquals(['Freshness' => 5], $review->criteria_scores);
        $this->assertNotNull($review->product_id); // linked via pr001 key
        $this->assertNotNull($review->store_id); // linked via vd001

        $sub = PlanSubscription::where('legacy_id', 'sh001')->firstOrFail();
        $this->assertEquals('owner', $sub->subscriber_type);
        $this->assertNotNull($sub->subscriber_id);

        $gift = GiftOrder::where('legacy_id', 'gp001')->firstOrFail();
        $this->assertEquals('GIFT-1', $gift->code);
        $this->assertNotNull($gift->gift_id);

        $story = Story::where('legacy_id', 'st001')->firstOrFail();
        $this->assertEquals('https://example.com/v.mp4', $story->video_url);
        $this->assertNotNull($story->store_id);

        $this->assertTrue(FilterPreset::where('name', 'Outdoor Seating')->exists());
        $this->assertTrue(FilterPreset::where('name', 'Free Wi-Fi')->exists());
    }
}
