<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveDriverRequest;
use App\Models\Driver;
use App\Models\Owner;
use App\Models\Store;
use App\Models\Zone;
use App\Support\ImageUploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — drivers incl. delivery riders and fleet (original controller).
 * Detail page hosts document review + status transitions.
 */
class DriverController extends Controller
{
    public function index(Request $request): View
    {
        $drivers = Driver::with(['zone', 'store', 'owner'])->withCount('verifications')
            ->when($request->filled('search'), fn ($q) => $q
                ->where('name', 'like', '%'.$request->input('search').'%')
                ->orWhere('phone', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('kind'), fn ($q) => $q->where('kind', $request->input('kind')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->input('scope') === 'fleet', fn ($q) => $q->whereNotNull('owner_id'))
            ->when($request->input('scope') === 'store', fn ($q) => $q->whereNotNull('store_id'))
            ->orderBy('name')
            ->paginate(15)->withQueryString();

        return view('admin.drivers.index', ['drivers' => $drivers]);
    }

    public function create(): View
    {
        return view('admin.drivers.form', $this->formData(new Driver()));
    }

    public function store(SaveDriverRequest $request): RedirectResponse
    {
        $driver = Driver::create($this->payload($request));

        return redirect()->route('admin.drivers.show', $driver)->with('success', "Driver '{$driver->name}' created.");
    }

    public function show(Driver $driver): View
    {
        $driver->load(['zone', 'store', 'owner', 'verifications.type', 'verifications.reviewer']);

        return view('admin.drivers.show', ['driver' => $driver]);
    }

    public function edit(Driver $driver): View
    {
        return view('admin.drivers.form', $this->formData($driver));
    }

    public function update(SaveDriverRequest $request, Driver $driver): RedirectResponse
    {
        $driver->update($this->payload($request, $driver));

        return redirect()->route('admin.drivers.show', $driver)->with('success', "Driver '{$driver->name}' updated.");
    }

    public function destroy(Driver $driver): RedirectResponse
    {
        ImageUploads::delete($driver->photo_path);
        $driver->verifications()->delete();
        $driver->delete();

        return redirect()->route('admin.drivers.index')->with('success', "Driver '{$driver->name}' deleted.");
    }

    public function transition(Request $request, Driver $driver): RedirectResponse
    {
        $to = $request->validate(['to' => ['required', 'string']])['to'];

        if (! in_array($to, Driver::STATUSES, true) || ! $driver->canTransitionTo($to)) {
            return redirect()->route('admin.drivers.show', $driver)->with('error', "Cannot move driver to [{$to}].");
        }

        $driver->update(['status' => $to]);

        return redirect()->route('admin.drivers.show', $driver)->with('success', "Driver moved to {$to}.");
    }

    protected function formData(Driver $driver): array
    {
        return [
            'driver' => $driver,
            'zones' => Zone::orderBy('name')->get(),
            'stores' => Store::orderBy('name')->get(),
            'owners' => Owner::orderBy('name')->get(),
            'method' => $driver->exists ? 'PUT' : 'POST',
            'action' => $driver->exists
                ? route('admin.drivers.update', $driver)
                : route('admin.drivers.store'),
        ];
    }

    protected function payload(SaveDriverRequest $request, ?Driver $driver = null): array
    {
        $data = $request->safe()->except(['photo', 'remove_photo']);

        if ($request->boolean('remove_photo')) {
            ImageUploads::delete($driver?->photo_path);
            $data['photo_path'] = null;
        } else {
            $data['photo_path'] = ImageUploads::replace($request->file('photo'), $driver?->photo_path, 'drivers');
        }

        return $data;
    }
}
