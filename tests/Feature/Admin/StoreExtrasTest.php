<?php

namespace Tests\Feature\Admin;

use App\Models\Owner;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
 * DDE-Mart Admin — store extras tests (original): detail page, gallery,
 * working hours, offer slots, owner bank fields.
 */
class StoreExtrasTest extends TestCase
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

    protected function store(): Store
    {
        $owner = Owner::create(['name' => 'O', 'phone' => '03001']);

        return Store::create(['name' => 'S', 'status' => 'active', 'owner_id' => $owner->id]);
    }

    public function test_show_page_renders(): void
    {
        $this->actingAs($this->staff())
            ->get(route('admin.stores.show', $this->store()))
            ->assertOk();
    }

    public function test_gallery_hours_offers_flow(): void
    {
        Storage::fake('public');
        $admin = $this->staff();
        $store = $this->store();

        $this->actingAs($admin)->post(route('admin.stores.gallery.store', $store), [
            'photos' => [UploadedFile::fake()->image('a.jpg')],
        ])->assertRedirect(route('admin.stores.show', $store));
        $this->assertEquals(1, $store->images()->count());

        $this->actingAs($admin)->post(route('admin.stores.hours.store', $store), [
            'hours' => [['day' => 1, 'opens_at' => '09:00', 'closes_at' => '18:00']],
        ])->assertRedirect(route('admin.stores.show', $store));
        $this->assertEquals(1, $store->hours()->count());

        $this->actingAs($admin)->post(route('admin.stores.offers.store', $store), [
            'day' => 1, 'opens_at' => '12:00', 'closes_at' => '14:00',
            'discount' => 10, 'discount_type' => 'percentage',
        ])->assertRedirect(route('admin.stores.show', $store));
        $this->assertEquals(1, $store->offers()->count());

        $offer = $store->offers()->firstOrFail();
        $this->actingAs($admin)
            ->delete(route('admin.stores.offers.destroy', [$store, $offer]))
            ->assertRedirect(route('admin.stores.show', $store));
        $this->assertEquals(0, $store->offers()->count());
    }

    public function test_owner_bank_fields_save(): void
    {
        $admin = $this->staff();
        $owner = Owner::create(['name' => 'O', 'phone' => '03002']);

        $this->actingAs($admin)->put(route('admin.owners.update', $owner), [
            'name' => 'O', 'bank_name' => 'Bank', 'bank_account' => '123',
        ])->assertRedirect();

        $this->assertEquals('Bank', $owner->fresh()->bank_name);
    }
}
