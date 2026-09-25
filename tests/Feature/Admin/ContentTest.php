<?php

namespace Tests\Feature\Admin;

use App\Models\EmailTemplate;
use App\Models\Language;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\Order;
use App\Models\Page;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\Zone;
use App\Services\FcmSender;
use App\Services\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — geo + content tests (D9, original).
 */
class ContentTest extends TestCase
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

    public function test_zone_crud_and_coverage_math(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.zones.store'), [
            'name' => 'Downtown',
            'latitude' => 24.8615,
            'longitude' => 67.0099,
            'radius_km' => 5,
        ])->assertRedirect(route('admin.zones.index'));

        $zone = Zone::where('name', 'Downtown')->firstOrFail();

        // Center is covered; a far point is not.
        $this->assertTrue($zone->covers(24.8615, 67.0099));
        $this->assertFalse($zone->covers(25.5, 68.0));
        $this->assertNull($zone->distanceTo(null, null));

        // Zones without a center never cover.
        $zoneless = Zone::create(['name' => 'Nowhere']);
        $this->assertFalse($zoneless->covers(24.8615, 67.0099));
    }

    public function test_settings_fallback_and_update_allowlist(): void
    {
        // Config default before anything is stored.
        $this->assertEquals('DDE-Mart', Setting::get('site_name'));

        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
            'settings' => [
                'site_name' => 'DDE-Mart PK',
                'order_auto_cancel_minutes' => '45',
                'stripe_secret' => 'sk_evil', // not allowlisted → ignored
            ],
        ]);

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertEquals('DDE-Mart PK', Setting::get('site_name'));
        $this->assertEquals('45', Setting::get('order_auto_cancel_minutes'));
        $this->assertNull(Setting::where('key', 'stripe_secret')->first());

        // Saved site name surfaces in the sidebar brand.
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('DDE-Mart PK');
    }

    public function test_broadcast_logs_record_with_fake_fcm(): void
    {
        $fake = new class extends FcmSender
        {
            public array $sent = [];

            public function isConfigured(): bool
            {
                return true;
            }

            public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
            {
                $this->sent[] = compact('topic', 'title', 'body');

                return true;
            }
        };
        app()->instance(FcmSender::class, $fake);

        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.notifications.store'), [
            'audience' => 'customer',
            'subject' => 'Weekend Sale',
            'message' => 'Flat 20% off',
        ])->assertRedirect(route('admin.notifications.index'));

        $record = Notification::firstOrFail();
        $this->assertEquals('sent', $record->status);
        $this->assertEquals([['topic' => 'customer', 'title' => 'Weekend Sale', 'body' => 'Flat 20% off']], $fake->sent);
    }

    public function test_broadcast_without_fcm_is_logged_as_failed(): void
    {
        $admin = $this->superAdmin();

        // No FIREBASE_CREDENTIALS in testing → sender reports unconfigured.
        $this->assertFalse(app(FcmSender::class)->isConfigured());

        $this->actingAs($admin)->post(route('admin.notifications.store'), [
            'audience' => 'driver',
            'subject' => 'Hello',
            'message' => 'Test',
        ])->assertSessionHas('error');

        $this->assertEquals('failed', Notification::firstOrFail()->status);
    }

    public function test_order_transition_renders_template_and_logs_push(): void
    {
        $admin = $this->superAdmin();
        NotificationTemplate::create([
            'key' => 'order.accepted',
            'audience' => 'customer',
            'subject' => 'Order :order_number accepted',
            'body' => 'Thanks :customer, total :total (:status)',
        ]);

        $order = Order::create([
            'customer_name' => 'Sara', 'payment_method' => 'cod',
            'subtotal' => 100, 'total' => 100,
        ]);

        OrderStatus::transition($order, Order::ACCEPTED, $admin);

        $push = Notification::where('subject', 'like', '%accepted%')->firstOrFail();
        $this->assertEquals('customer', $push->audience);
        $this->assertStringContainsString('Sara', $push->message);
        $this->assertStringContainsString($order->fresh()->number, $push->subject);
    }

    public function test_pages_emails_languages_crud(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.pages.store'), [
            'name' => 'Terms & Conditions', 'body' => 'Be nice.',
        ])->assertRedirect(route('admin.pages.index'));
        $this->assertEquals('terms-conditions', Page::firstOrFail()->slug);

        $this->actingAs($admin)->post(route('admin.emails.store'), [
            'key' => 'welcome', 'subject' => 'Welcome :customer', 'body' => 'Hi :customer',
            'send_to_admin' => '1',
        ])->assertRedirect(route('admin.emails.index'));

        $rendered = EmailTemplate::where('key', 'welcome')->firstOrFail()->render(['customer' => 'Ali']);
        $this->assertEquals('Welcome Ali', $rendered['subject']);

        $this->actingAs($admin)->post(route('admin.languages.store'), [
            'code' => 'EN', 'name' => 'English', 'is_default' => '1',
        ])->assertRedirect(route('admin.languages.index'));

        $lang = Language::where('code', 'en')->firstOrFail();
        $this->assertTrue($lang->is_default);

        // Default language is protected.
        $this->actingAs($admin)->delete(route('admin.languages.destroy', $lang))
            ->assertSessionHas('error');
    }

    public function test_content_gates_deny_unprivileged(): void
    {
        $role = Role::create(['name' => 'Limited', 'slug' => 'limited']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('admin.zones.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.notifications.index'))->assertForbidden();
    }
}
