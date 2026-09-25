<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — maintenance mode + live map (original controller).
 * Maintenance wraps artisan down/up (secret-protected bypass). The map page
 * embeds Google Maps when a key exists, else lists zones/stores coordinates.
 */
class OpsController extends Controller
{
    public function maintenance(): View
    {
        $down = app()->maintenanceMode()->active();

        return view('admin.ops.maintenance', ['down' => $down]);
    }

    public function maintenanceToggle(Request $request): RedirectResponse
    {
        $validated = $request->validate(['mode' => ['required', 'in:up,down']]);

        if ($validated['mode'] === 'down') {
            Artisan::call('down', ['--secret' => 'dde-ops', '--render' => 'errors.503']);
        } else {
            Artisan::call('up');
        }

        return redirect()->route('admin.ops.maintenance')
            ->with('success', $validated['mode'] === 'down' ? 'Panel is now in maintenance mode.' : 'Panel is live.');
    }

    public function locale(Request $request): RedirectResponse
    {
        $validated = $request->validate(['locale' => ['required', 'string', 'max:10']]);
        $request->session()->put('panel_locale', $validated['locale']);

        return redirect()->back()->with('success', 'Locale preference saved.');
    }

    public function map(): View
    {
        return view('admin.ops.map', [
            'key' => config('services.maps.key'),
            'stores' => \App\Models\Store::whereNotNull('latitude')->get(['name', 'latitude', 'longitude', 'status']),
            'zones' => \App\Models\Zone::whereNotNull('latitude')->get(['name', 'latitude', 'longitude', 'radius_km']),
        ]);
    }
}
