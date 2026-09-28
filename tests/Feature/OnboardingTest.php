<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart — public vendor onboarding tests (original): pending
 * owner + store pair, duplicate phones rejected.
 */
class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_onboarding_creates_pending_pair(): void
    {
        $this->get(route('onboarding.create'))->assertOk();

        $this->post(route('onboarding.store'), [
            'name' => 'New Owner',
            'phone' => '0300999001',
            'store_name' => 'New Shop',
        ])->assertRedirect(route('onboarding.done'));

        $this->assertDatabaseHas('owners', ['phone' => '0300999001', 'status' => 'pending']);
        $this->assertDatabaseHas('stores', ['name' => 'New Shop', 'status' => 'pending']);

        // Duplicate phone rejected.
        $this->post(route('onboarding.store'), [
            'name' => 'Clone',
            'phone' => '0300999001',
            'store_name' => 'Clone Shop',
        ])->assertSessionHasErrors('phone');
    }
}
