<?php

namespace Tests\Feature\Admin;

use App\Models\Provider;
use App\Models\ProviderWorker;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — provider/worker CRUD tests (original): create/edit
 * with bank, commission, salary and address fields.
 */
class ServicePeopleTest extends TestCase
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

    public function test_provider_create_edit_with_bank_and_commission(): void
    {
        $admin = $this->staff();

        $this->actingAs($admin)->post(route('admin.providers.store'), [
            'name' => 'Fixers', 'phone' => '03010',
            'bank_name' => 'Bank', 'commission_type' => 'fixed',
            'commission_value' => 50,
        ])->assertRedirect();

        $provider = Provider::where('phone', '03010')->firstOrFail();
        $this->assertEquals('Bank', $provider->bank_name);
        $this->assertEquals(50, (float) $provider->commission_value);

        $this->actingAs($admin)->put(route('admin.providers.update', $provider), [
            'name' => 'Fixers', 'phone' => '03010',
            'commission_type' => 'percentage', 'commission_value' => 10,
        ])->assertRedirect();

        $this->assertEquals('percentage', $provider->fresh()->commission_type);
    }

    public function test_worker_create_edit_with_salary_and_address(): void
    {
        $admin = $this->staff();
        $provider = Provider::create(['name' => 'Fixers', 'phone' => '03011']);

        $this->actingAs($admin)->post(route('admin.provider-workers.store'), [
            'provider_id' => $provider->id, 'name' => 'Ali', 'phone' => '03012',
            'salary' => 50000, 'address' => 'Street 1',
        ])->assertRedirect();

        $worker = ProviderWorker::where('phone', '03012')->firstOrFail();
        $this->assertEquals(50000, (float) $worker->salary);

        $this->actingAs($admin)->put(route('admin.provider-workers.update', $worker), [
            'name' => 'Ali', 'phone' => '03012', 'salary' => 55000,
        ])->assertRedirect();

        $this->assertEquals(55000, (float) $worker->fresh()->salary);
    }
}
