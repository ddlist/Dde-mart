<?php

namespace Tests\Feature\Admin;

use App\Models\PayoutRequest;
use App\Models\Role;
use App\Models\User;
use App\Payments\DriverNotConfigured;
use App\Payments\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
 * DDE-Mart Admin — gateway manager + payout execution tests (D8c, original).
 */
class PaymentsTest extends TestCase
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

    public function test_unconfigured_drivers_report_honestly(): void
    {
        $manager = app(PaymentManager::class);

        $this->assertEquals([], $manager->available());

        $this->expectException(DriverNotConfigured::class);
        $manager->driver('stripe')->payout(new PayoutRequest());
    }

    public function test_execute_requires_approved_status(): void
    {
        $admin = $this->superAdmin();
        $payout = PayoutRequest::create(['requester_type' => 'vendor', 'amount' => 100, 'method' => 'paypal']);

        $this->actingAs($admin)->post(route('admin.payouts.execute', $payout))
            ->assertSessionHas('error');
        $this->assertEquals('pending', $payout->fresh()->status);
    }

    public function test_execute_refuses_manual_methods(): void
    {
        $admin = $this->superAdmin();
        $payout = PayoutRequest::create(['requester_type' => 'vendor', 'amount' => 100, 'method' => 'bank', 'status' => 'approved']);

        $this->actingAs($admin)->post(route('admin.payouts.execute', $payout))
            ->assertSessionHas('error');
    }

    public function test_execute_reports_unconfigured_gateway(): void
    {
        $admin = $this->superAdmin();
        $payout = PayoutRequest::create([
            'requester_type' => 'vendor', 'amount' => 100, 'method' => 'paypal',
            'method_details' => ['email' => 'v@example.com'], 'status' => 'approved',
        ]);

        $this->actingAs($admin)->post(route('admin.payouts.execute', $payout))
            ->assertSessionHas('error');
        $this->assertEquals('approved', $payout->fresh()->status); // untouched
    }

    public function test_paypal_payout_success_marks_paid(): void
    {
        config()->set('payments.drivers.paypal', [
            'client_id' => 'id', 'client_secret' => 'secret', 'mode' => 'sandbox',
        ]);

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'tok']),
            'https://api-m.sandbox.paypal.com/v1/payments/payouts' => Http::response([
                'batch_header' => ['payout_batch_id' => 'B1'],
            ]),
        ]);

        $admin = $this->superAdmin();
        $payout = PayoutRequest::create([
            'requester_type' => 'vendor', 'amount' => 100, 'method' => 'paypal',
            'method_details' => ['email' => 'v@example.com'], 'status' => 'approved',
        ]);

        $this->actingAs($admin)->post(route('admin.payouts.execute', $payout))
            ->assertSessionHas('success');

        $payout->refresh();
        $this->assertEquals('paid', $payout->status);
        $this->assertNotNull($payout->paid_at);
    }

    public function test_paypal_failure_keeps_approved_and_records_note(): void
    {
        config()->set('payments.drivers.paypal', [
            'client_id' => 'id', 'client_secret' => 'secret', 'mode' => 'sandbox',
        ]);

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'tok']),
            'https://api-m.sandbox.paypal.com/v1/payments/payouts' => Http::response(['error' => 'denied'], 400),
        ]);

        $admin = $this->superAdmin();
        $payout = PayoutRequest::create([
            'requester_type' => 'vendor', 'amount' => 100, 'method' => 'paypal',
            'method_details' => ['email' => 'v@example.com'], 'status' => 'approved',
        ]);

        $this->actingAs($admin)->post(route('admin.payouts.execute', $payout))
            ->assertSessionHas('error');

        $payout->refresh();
        $this->assertEquals('approved', $payout->status);
        $this->assertStringContainsString('Gateway error', $payout->admin_note);
    }

    public function test_flutterwave_validates_details_without_http(): void
    {
        config()->set('payments.drivers.flutterwave', ['secret' => 'sk']);
        Http::preventStrayRequests();

        $manager = app(PaymentManager::class);
        $payout = new PayoutRequest(['amount' => 10, 'method_details' => []]);

        $result = $manager->driver('flutterwave')->payout($payout);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('bank_code', $result->message);
    }
}
