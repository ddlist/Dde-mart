<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveZoneRequest;
use App\Models\Zone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/* DDE-Mart Admin — delivery zones (original controller). */
class ZoneController extends Controller
{
    public function index(): View
    {
        $zones = Zone::orderBy('name')->paginate(15);

        return view('admin.content.zones.index', compact('zones'));
    }

    public function create(): View
    {
        return view('admin.content.zones.form', $this->formData(new Zone()));
    }

    public function store(SaveZoneRequest $request): RedirectResponse
    {
        $zone = Zone::create($this->payload($request));

        return redirect()->route('admin.zones.index')->with('success', "Zone '{$zone->name}' created.");
    }

    public function edit(Zone $zone): View
    {
        return view('admin.content.zones.form', $this->formData($zone));
    }

    public function update(SaveZoneRequest $request, Zone $zone): RedirectResponse
    {
        $zone->update($this->payload($request));

        return redirect()->route('admin.zones.index')->with('success', "Zone '{$zone->name}' updated.");
    }

    public function destroy(Zone $zone): RedirectResponse
    {
        $zone->delete();

        return redirect()->route('admin.zones.index')->with('success', "Zone '{$zone->name}' deleted.");
    }

    protected function formData(Zone $zone): array
    {
        return [
            'zone' => $zone,
            'method' => $zone->exists ? 'PUT' : 'POST',
            'action' => $zone->exists
                ? route('admin.zones.update', $zone)
                : route('admin.zones.store'),
        ];
    }

    protected function payload(SaveZoneRequest $request): array
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
