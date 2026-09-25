<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/*
 * DDE-Mart Admin — UI regression guard (original).
 * Renders every parameter-less GET page and fails if any Blade component tag
 * survives uncompiled (e.g. nested-quote attribute bug) or the page errors.
 */
class UiRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_pages_render_without_raw_component_tags(): void
    {
        $role = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_super' => true],
        );
        $admin = User::factory()->create(['role_id' => $role->id]);

        $failures = [];

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = $route->uri();

            if (! str_starts_with($uri, 'admin') || str_contains($uri, '{')) {
                continue;
            }

            if (str_contains($uri, 'export')) {
                continue; // file download, not a Blade page
            }

            $response = $this->actingAs($admin)->get('/'.$uri);

            if ($response->getStatusCode() !== 200) {
                $failures[] = "{$uri} → HTTP {$response->getStatusCode()}";
                continue;
            }

            $type = $response->headers->get('Content-Type', '');

            if (! str_contains($type, 'text/html')) {
                continue; // e.g. CSV export stream — not a Blade page
            }

            if (str_contains($response->getContent(), '<x-')) {
                $failures[] = "{$uri} → raw <x- component tag in output";
            }
        }

        $this->assertSame([], $failures, "UI render problems:\n".implode("\n", $failures));
    }
}
