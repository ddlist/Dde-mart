<?php

namespace Tests\Feature\Shop;

use App\Models\Customer;
use App\Models\OtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/*
 * DDE-Mart storefront tests (original): forgot/reset password flow.
 */
class ShopPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_reset_flow(): void
    {
        Customer::create(['name' => 'Sara', 'phone' => '03001234567', 'password' => 'oldsecret123']);

        $this->get(route('shop.forgot'))->assertOk()->assertSee('Forgot password');
        $this->get(route('shop.login'))->assertOk()->assertSee('Forgot password');

        $this->post(route('shop.forgot.send'), ['phone' => '03009999999'])
            ->assertRedirect(route('shop.forgot'))
            ->assertSessionHas('error');

        $this->post(route('shop.forgot.send'), ['phone' => '03001234567'])
            ->assertRedirect(route('shop.reset', ['phone' => '03001234567']));
        $this->assertTrue(OtpCode::where('phone', '03001234567')->exists());

        // Wrong code keeps the old password.
        $this->post(route('shop.reset.store'), [
            'phone' => '03001234567', 'code' => '000000',
            'password' => 'newsecret123', 'password_confirmation' => 'newsecret123',
        ])->assertRedirect(route('shop.reset', ['phone' => '03001234567']))
            ->assertSessionHas('error');
        $this->assertTrue(Hash::check('oldsecret123', Customer::firstOrFail()->password));

        // Known-code row exercises the real happy path end to end.
        OtpCode::create([
            'phone' => '03001234567', 'code' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->post(route('shop.reset.store'), [
            'phone' => '03001234567', 'code' => '123456',
            'password' => 'newsecret123', 'password_confirmation' => 'newsecret123',
        ])->assertRedirect(route('shop.home'))->assertSessionHas('success');

        $this->assertTrue(Hash::check('newsecret123', Customer::firstOrFail()->password));
        $this->assertAuthenticatedAs(Customer::firstOrFail(), 'customer');
    }
}
