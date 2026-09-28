<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\ScheduledNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — scheduled push UI tests (original): queue + cancel.
 */
class ScheduledNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function staff(): User
    {
        $role = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_super' => true],
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_schedule_and_cancel(): void
    {
        $admin = $this->staff();

        $this->actingAs($admin)->get(route('admin.scheduled.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.scheduled.create'))->assertOk();

        $this->actingAs($admin)->post(route('admin.scheduled.store'), [
            'audience' => 'customer',
            'subject' => 'Weekend sale',
            'message' => 'Up to 50% off',
            'send_at' => now()->addHour()->format('Y-m-d\TH:i'),
        ])->assertRedirect(route('admin.scheduled.index'));

        $item = ScheduledNotification::firstOrFail();
        $this->assertSame('scheduled', $item->status);

        $this->actingAs($admin)
            ->delete(route('admin.scheduled.destroy', $item))
            ->assertRedirect(route('admin.scheduled.index'));

        $this->assertSame('cancelled', $item->fresh()->status);
    }

    public function test_cannot_cancel_sent_item(): void
    {
        $admin = $this->staff();
        $item = ScheduledNotification::create([
            'audience' => 'all', 'subject' => 'x', 'message' => 'y',
            'send_at' => now()->subHour(), 'status' => 'sent',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.scheduled.destroy', $item))
            ->assertStatus(422);
    }
}
