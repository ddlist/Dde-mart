<?php

namespace Tests\Feature\Admin;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Complaint;
use App\Models\ContentBlock;
use App\Models\OnboardingSlide;
use App\Models\Role;
use App\Models\ScheduledNotification;
use App\Models\SosAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — support + engagement smalls tests (original).
 */
class SupportTest extends TestCase
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

    public function test_complaint_and_sos_flows(): void
    {
        $admin = $this->superAdmin();
        $complaint = Complaint::create(['title' => 'Late', 'description' => 'Cold']);

        $this->actingAs($admin)->post(route('admin.complaints.resolve', $complaint), ['to' => 'resolved'])
            ->assertSessionHas('success');
        $this->assertEquals('resolved', $complaint->fresh()->status);

        $alert = SosAlert::create(['order_ref' => 'o1', 'latitude' => 24.86, 'longitude' => 67.0]);

        $this->actingAs($admin)->post(route('admin.sos.resolve', $alert))
            ->assertSessionHas('success');
        $this->assertEquals('resolved', $alert->fresh()->status);
    }

    public function test_chat_thread_view_and_close(): void
    {
        $admin = $this->superAdmin();
        $thread = ChatThread::create(['audience' => 'admin', 'subject' => 'o1', 'last_message' => 'Hii']);
        ChatMessage::create(['thread_id' => $thread->id, 'sender_ref' => 'u1', 'body' => 'Hii']);

        $this->actingAs($admin)->get(route('admin.chats.show', $thread))
            ->assertOk()->assertSee('Hii');

        $this->actingAs($admin)->post(route('admin.chats.close', $thread))
            ->assertSessionHas('success');
        $this->assertEquals('closed', $thread->fresh()->status);
    }

    public function test_slides_blocks_scheduled(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.slides.store'), [
            'title' => 'Welcome', 'audience' => 'customer',
        ])->assertRedirect(route('admin.slides.index'));
        $this->assertNotNull(OnboardingSlide::where('title', 'Welcome')->first());

        $block = ContentBlock::create(['key' => 'footer_about', 'title' => 'About', 'body' => 'Hi']);

        $this->actingAs($admin)->put(route('admin.blocks.update', $block), [
            'title' => 'About us', 'body' => 'Hello',
        ])->assertSessionHas('success');
        $this->assertEquals('Hello', $block->fresh()->body);

        $this->actingAs($admin)->post(route('admin.scheduled.store'), [
            'audience' => 'customer', 'subject' => 'Sale', 'message' => 'Soon',
            'send_at' => now()->addHour()->format('Y-m-d\TH:i'),
        ])->assertRedirect(route('admin.scheduled.index'));
        $this->assertNotNull(ScheduledNotification::where('subject', 'Sale')->first());
    }

    public function test_manual_email_compose_and_send(): void
    {
        $admin = $this->superAdmin();
        \App\Models\EmailTemplate::create(['key' => 'hello', 'subject' => 'Hi :name', 'body' => 'Dear :name']);

        // Preview renders placeholders.
        $preview = $this->actingAs($admin)->get(route('admin.email.compose', [
            'template_id' => \App\Models\EmailTemplate::first()->id, 'values' => "name=Ali",
        ]))->assertOk();
        $preview->assertSee('Hi Ali');

        $this->actingAs($admin)->post(route('admin.email.send'), [
            'to' => 'a@example.com', 'subject' => 'Hi', 'body' => 'Hello',
        ])->assertRedirect(route('admin.email.compose'));

        $this->assertNotNull(
            \App\Models\Notification::where('subject', 'Email: Hi')->first(),
            'Email send is logged in notifications.'
        );
    }

    public function test_scheduled_command_sends_due(): void
    {
        ScheduledNotification::create([
            'audience' => 'customer', 'subject' => 'Due', 'message' => 'Now',
            'send_at' => now()->subMinute(),
        ]);
        ScheduledNotification::create([
            'audience' => 'customer', 'subject' => 'Later', 'message' => 'Future',
            'send_at' => now()->addDay(),
        ]);

        $this->artisan('schedule:send')->assertSuccessful();

        $this->assertEquals('failed', ScheduledNotification::where('subject', 'Due')->firstOrFail()->status); // no FCM creds in tests
        $this->assertEquals('scheduled', ScheduledNotification::where('subject', 'Later')->firstOrFail()->status);
    }

    public function test_ops_pages_render(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->get(route('admin.ops.map'))->assertOk();
        $this->actingAs($admin)->get(route('admin.ops.maintenance'))->assertOk()->assertSee('live');
    }

    public function test_gates_deny_unprivileged(): void
    {
        $role = Role::create(['name' => 'Limited', 'slug' => 'limited']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('admin.complaints.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.sos.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.slides.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.ops.map'))->assertForbidden();
    }

    public function test_importer_imports_support_batch(): void
    {
        $this->artisan('ddemart:import', [
            '--file' => base_path('tests/Fixtures/legacy-sample.json'),
        ])->assertSuccessful();

        $this->assertEquals('Late delivery', Complaint::where('legacy_id', 'cm001')->firstOrFail()->title);
        $this->assertEquals('open', SosAlert::where('legacy_id', 'so001')->firstOrFail()->status);

        $thread = ChatThread::where('legacy_id', 'admin:th001')->firstOrFail();
        $this->assertEquals('Hii', $thread->messages()->firstOrFail()->body);

        $this->assertEquals('Welcome', OnboardingSlide::where('legacy_id', 'ob001')->firstOrFail()->title);
    }
}
