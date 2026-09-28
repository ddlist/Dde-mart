<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use App\Models\WalletEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — finance extras tests (original): payment summary,
 * manual wallet adjustments, saved withdraw methods.
 */
class FinanceExtrasTest extends TestCase
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

    public function test_summary_and_adjustment(): void
    {
        $admin = $this->staff();

        $this->actingAs($admin)->get(route('admin.wallet.summary'))
            ->assertOk()
            ->assertSee('Driver');

        $this->actingAs($admin)->post(route('admin.wallet.adjust'), [
            'owner_type' => 'driver',
            'owner_ref' => '03099',
            'amount' => 150,
            'direction' => 'credit',
            'note' => 'Bonus',
        ])->assertRedirect(route('admin.wallet.index'));

        $this->assertEquals(150, WalletEntry::where('owner_ref', '03099')->sum('amount'));

        $this->actingAs($admin)->post(route('admin.wallet.adjust'), [
            'owner_type' => 'driver',
            'owner_ref' => '03099',
            'amount' => 50,
            'direction' => 'debit',
        ])->assertRedirect(route('admin.wallet.index'));

        $this->assertEquals(100, WalletEntry::where('owner_ref', '03099')->sum('amount'));
    }

    public function test_withdraw_method_crud(): void
    {
        $admin = $this->staff();

        $this->actingAs($admin)->post(route('admin.payout-methods.store'), [
            'requester_type' => 'driver',
            'requester_ref' => '03099',
            'method' => 'bank',
            'bank_name' => 'Bank',
            'is_default' => '1',
        ])->assertRedirect(route('admin.payout-methods.index'));

        $method = \App\Models\PayoutMethod::where('requester_ref', '03099')->firstOrFail();
        $this->assertTrue((bool) $method->is_default);

        $this->actingAs($admin)
            ->delete(route('admin.payout-methods.destroy', $method))
            ->assertRedirect(route('admin.payout-methods.index'));

        $this->assertDatabaseMissing('payout_methods', ['id' => $method->id]);
    }
}
