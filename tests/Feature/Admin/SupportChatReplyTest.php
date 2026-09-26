<?php

namespace Tests\Feature\Admin;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — staff chat reply tests (original). Two-sided support:
 * staff answers land in the thread and surface to the customer API.
 */
class SupportChatReplyTest extends TestCase
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

    public function test_staff_reply_lands_and_shows(): void
    {
        $admin = $this->superAdmin();
        $thread = ChatThread::create(['audience' => 'customer', 'subject' => 'Late', 'last_message' => 'Where?']);
        ChatMessage::create(['thread_id' => $thread->id, 'sender_ref' => 'customer', 'body' => 'Where?']);

        $this->actingAs($admin)
            ->post(route('admin.chats.reply', $thread), ['message' => 'On its way!'])
            ->assertRedirect(route('admin.chats.show', $thread))
            ->assertSessionHas('success');

        $this->assertEquals('On its way!', $thread->fresh()->last_message);

        $reply = ChatMessage::where('thread_id', $thread->id)->orderByDesc('id')->firstOrFail();
        $this->assertEquals('On its way!', $reply->body);
        $this->assertStringStartsWith('admin:', $reply->sender_ref);

        // The reply renders on the thread page and is visible to the customer.
        $this->actingAs($admin)->get(route('admin.chats.show', $thread))
            ->assertOk()->assertSee('On its way!');
    }

    public function test_reply_to_closed_thread_rejected(): void
    {
        $admin = $this->superAdmin();
        $thread = ChatThread::create(['audience' => 'customer', 'subject' => 'Old', 'status' => 'closed']);

        $this->actingAs($admin)
            ->post(route('admin.chats.reply', $thread), ['message' => 'Too late'])
            ->assertStatus(422);
        $this->assertEquals(0, ChatMessage::count());
    }

    public function test_guest_and_unprivileged_blocked(): void
    {
        $thread = ChatThread::create(['audience' => 'customer', 'subject' => 'Late']);

        $this->post(route('admin.chats.reply', $thread), ['message' => 'Hi'])
            ->assertRedirect(route('login'));

        $role = Role::create(['name' => 'Limited', 'slug' => 'limited']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->post(route('admin.chats.reply', $thread), ['message' => 'Hi'])
            ->assertForbidden();
    }
}
