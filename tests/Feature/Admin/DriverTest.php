<?php

namespace Tests\Feature\Admin;

use App\Models\DocumentType;
use App\Models\Driver;
use App\Models\Role;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — drivers + verification tests (original).
 */
class DriverTest extends TestCase
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

    public function test_guest_and_unprivileged_are_blocked(): void
    {
        $this->get(route('admin.drivers.index'))->assertRedirect(route('login'));

        $role = Role::create(['name' => 'Limited', 'slug' => 'limited']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('admin.drivers.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.verifications.queue'))->assertForbidden();
    }

    public function test_driver_crud_and_transitions(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.drivers.store'), [
            'kind' => 'delivery',
            'name' => 'Rider Khan',
            'phone' => '03001112233',
        ])->assertRedirect();

        $driver = Driver::where('name', 'Rider Khan')->firstOrFail();
        $this->assertEquals('pending', $driver->status);

        $this->actingAs($admin)->post(route('admin.drivers.transition', $driver), ['to' => 'suspended'])
            ->assertSessionHas('error');

        $this->actingAs($admin)->post(route('admin.drivers.transition', $driver), ['to' => 'active'])
            ->assertSessionHas('success');
        $this->assertEquals('active', $driver->fresh()->status);
    }

    public function test_document_review_flow(): void
    {
        $admin = $this->superAdmin();
        $driver = Driver::create(['name' => 'D', 'kind' => 'ride']);
        $type = DocumentType::create(['title' => 'License', 'owner_type' => 'driver']);
        $verification = Verification::create([
            'verifiable_type' => Driver::class,
            'verifiable_id' => $driver->id,
            'document_type_id' => $type->id,
            'front_path' => 'https://example.com/license.png',
        ]);

        $this->actingAs($admin)->post(route('admin.verifications.review', $verification), [
            'to' => 'approved', 'note' => 'Looks good',
        ])->assertSessionHas('success');

        $verification->refresh();
        $this->assertEquals('approved', $verification->status);
        $this->assertEquals($admin->id, $verification->reviewed_by);
    }

    public function test_doc_type_delete_blocked_by_history(): void
    {
        $admin = $this->superAdmin();
        $type = DocumentType::create(['title' => 'License', 'owner_type' => 'driver']);
        Verification::create([
            'verifiable_type' => Driver::class, 'verifiable_id' => 999,
            'document_type_id' => $type->id,
        ]);

        $this->actingAs($admin)->delete(route('admin.doc-types.destroy', $type))
            ->assertSessionHas('error');
        $this->assertNotNull($type->fresh());
    }

    public function test_importer_imports_drivers_types_and_queue(): void
    {
        $this->artisan('ddemart:import', [
            '--file' => base_path('tests/Fixtures/legacy-sample.json'),
        ])->assertSuccessful();

        // Only the driver-role user imports (customer skipped).
        $driver = Driver::where('legacy_id', 'du001')->firstOrFail();
        $this->assertEquals('Rider Khan', $driver->name);
        $this->assertEquals('active', $driver->status);
        $this->assertEquals(1, Driver::count());

        $type = DocumentType::where('legacy_id', 'dt001')->firstOrFail();
        $this->assertTrue($type->back_required);

        $verification = Verification::where('verifiable_type', Driver::class)
            ->where('verifiable_id', $driver->id)
            ->firstOrFail();
        $this->assertEquals($type->id, $verification->document_type_id);
        $this->assertEquals('pending', $verification->status);
    }
}
