<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/*
 * DDE-Mart Admin — staff password reset tests (original): link request
 * (signed URL mailed, unknown emails acked silently), reset with a valid
 * signature, rejection without one.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_link_request_mails_user(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'boss@shop.test']);

        $this->post(route('password.email'), ['email' => 'boss@shop.test'])
            ->assertRedirect();

        Mail::assertSent(
            \App\Mail\AdminResetLink::class,
            fn ($mail) => $mail->hasTo('boss@shop.test')
        );

        // Unknown emails get the same ack, no mail.
        Mail::fake();
        $this->post(route('password.email'), ['email' => 'ghost@shop.test'])
            ->assertRedirect();
        Mail::assertNothingSent();
    }

    public function test_reset_with_valid_signature_updates_password(): void
    {
        $user = User::factory()->create(['email' => 'boss@shop.test']);

        $url = URL::temporarySignedRoute(
            'password.reset',
            now()->addHour(),
            ['user' => $user->id]
        );

        $this->get($url)->assertOk();

        $this->post($url, [
            'password' => 'brand-new-secret',
            'password_confirmation' => 'brand-new-secret',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('brand-new-secret', $user->fresh()->password));
    }

    public function test_reset_without_signature_rejected(): void
    {
        $user = User::factory()->create(['email' => 'boss@shop.test']);

        $this->get("/reset-password/{$user->id}")
            ->assertRedirect(route('login'));

        $this->post("/reset-password/{$user->id}", [
            'password' => 'brand-new-secret',
            'password_confirmation' => 'brand-new-secret',
        ])->assertRedirect(route('login'));

        $this->assertFalse(Hash::check('brand-new-secret', $user->fresh()->password));
    }
}
