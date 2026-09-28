<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — settings tests (original): typed keys, per-audience
 * maintenance in app-config, payout minimum enforcement.
 */
class SettingsTest extends TestCase
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

    public function test_typed_keys_save_and_render(): void
    {
        $this->actingAs($this->staff());

        $this->put('/admin/settings', ['settings' => [
            'site_name' => 'Shop',
            'dispatch_radius_km' => '7',
            'business_model' => 'subscription',
            'unknown_key' => 'nope',
        ]])->assertRedirect('/admin/settings');

        $this->assertEquals('Shop', Setting::get('site_name'));
        $this->assertEquals('7', Setting::get('dispatch_radius_km'));
        $this->assertEquals('subscription', Setting::get('business_model'));
        $this->assertNull(Setting::where('key', 'unknown_key')->value('value'));

        // Bool absent from input stores as off.
        $this->put('/admin/settings', ['settings' => ['site_name' => 'Shop']])
            ->assertRedirect('/admin/settings');
        $this->assertEquals('0', Setting::get('dispatch_auto', '0'));

        // Invalid select option is ignored (keeps the earlier value).
        $this->put('/admin/settings', ['settings' => ['business_model' => 'barter']])
            ->assertRedirect('/admin/settings');
        $this->assertEquals('subscription', Setting::get('business_model'));
    }

    public function test_app_config_merges_audience_maintenance(): void
    {
        Setting::set('apps_maintenance', '0');
        Setting::set('maint_driver', '1');

        $this->getJson('/api/v1/app-config')
            ->assertOk()
            ->assertJsonPath('data.maintenance', false);

        $this->getJson('/api/v1/app-config?audience=driver')
            ->assertOk()
            ->assertJsonPath('data.maintenance', true);

        $this->getJson('/api/v1/app-config?audience=customer')
            ->assertOk()
            ->assertJsonPath('data.maintenance', false);
    }
}
