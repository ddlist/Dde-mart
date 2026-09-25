<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CabType;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\Destination;
use App\Models\Driver;
use App\Models\Ride;
use App\Models\Section;
use App\Support\ImageUploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — rides/cab vertical (original controller).
 * Fleet masters (makes/models/types/destinations) + ride pipeline. No ride deletes.
 */
class RideController extends Controller
{
    // -- Masters ---------------------------------------------------------

    public function fleet(): View
    {
        return view('admin.transport.fleet', [
            'makes' => CarMake::withCount('models')->orderBy('name')->get(),
            'types' => CabType::orderBy('name')->paginate(10, ['*'], 'types'),
            'destinations' => Destination::orderBy('title')->paginate(10, ['*'], 'destinations'),
            'sections' => Section::orderBy('name')->get(),
        ]);
    }

    public function makeStore(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150']]);
        CarMake::create($data);

        return redirect()->route('admin.fleet.index')->with('success', 'Make added.');
    }

    public function makeDestroy(CarMake $make): RedirectResponse
    {
        if ($make->models()->exists()) {
            return redirect()->route('admin.fleet.index')->with('error', 'Make has models — remove them first.');
        }

        $make->delete();

        return redirect()->route('admin.fleet.index')->with('success', 'Make deleted.');
    }

    public function modelStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'car_make_id' => ['required', 'integer', 'exists:car_makes,id'],
        ]);
        CarModel::create($data);

        return redirect()->route('admin.fleet.index')->with('success', 'Model added.');
    }

    public function modelDestroy(CarModel $model): RedirectResponse
    {
        $model->delete();

        return redirect()->route('admin.fleet.index')->with('success', 'Model deleted.');
    }

    public function typeStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'name' => ['required', 'string', 'max:150'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'base_fare' => ['nullable', 'numeric', 'min:0'],
            'per_km_fare' => ['nullable', 'numeric', 'min:0'],
            'min_fare' => ['nullable', 'numeric', 'min:0'],
            'icon' => ['nullable', 'image', 'max:2048'],
        ]);

        $validated['icon_path'] = ImageUploads::store($request->file('icon'), 'cab');

        CabType::create($validated);

        return redirect()->route('admin.fleet.index')->with('success', 'Cab type added.');
    }

    public function typeDestroy(CabType $type): RedirectResponse
    {
        ImageUploads::delete($type->icon_path);
        $type->delete();

        return redirect()->route('admin.fleet.index')->with('success', 'Cab type deleted.');
    }

    public function destinationStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'title' => ['required', 'string', 'max:150'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $validated['image_path'] = ImageUploads::store($request->file('image'), 'destinations');

        Destination::create($validated);

        return redirect()->route('admin.fleet.index')->with('success', 'Destination added.');
    }

    public function destinationDestroy(Destination $destination): RedirectResponse
    {
        ImageUploads::delete($destination->image_path);
        $destination->delete();

        return redirect()->route('admin.fleet.index')->with('success', 'Destination deleted.');
    }

    // -- Rides -----------------------------------------------------------

    public function rides(Request $request): View
    {
        $rides = Ride::with(['driver'])
            ->when($request->filled('search'), fn ($q) => $q
                ->where('number', 'like', '%'.$request->input('search').'%')
                ->orWhere('customer_name', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.transport.rides', ['rides' => $rides, 'statuses' => Ride::STATUSES]);
    }

    public function rideShow(Ride $ride): View
    {
        $ride->load(['driver', 'cabType', 'history.changedBy']);
        $drivers = Driver::where('status', 'active')->orderBy('name')->get();

        return view('admin.transport.ride-show', [
            'ride' => $ride,
            'drivers' => $drivers,
            'allowed' => Ride::TRANSITIONS[$ride->status] ?? [],
            'statuses' => Ride::STATUSES,
        ]);
    }

    public function rideTransition(Request $request, Ride $ride): RedirectResponse
    {
        $validated = $request->validate(['to' => ['required', 'string'], 'note' => ['nullable', 'string', 'max:500']]);

        if (! array_key_exists($validated['to'], Ride::STATUSES) || ! $ride->canTransitionTo($validated['to'])) {
            return redirect()->route('admin.rides.show', $ride)->with('error', "Cannot move ride to [{$validated['to']}].");
        }

        $from = $ride->status;
        $updates = ['status' => $validated['to']];

        if ($validated['to'] === 'ongoing' && ! $ride->started_at) {
            $updates['started_at'] = now();
        }

        if ($validated['to'] === 'completed' && ! $ride->ended_at) {
            $updates['ended_at'] = now();
        }

        $ride->update($updates);
        $ride->history()->create([
            'from_status' => $from, 'to_status' => $validated['to'],
            'changed_by' => $request->user()->id, 'note' => $validated['note'] ?? null,
        ]);

        return redirect()->route('admin.rides.show', $ride)
            ->with('success', 'Ride moved to '.Ride::STATUSES[$validated['to']].'.');
    }

    public function rideAssign(Request $request, Ride $ride): RedirectResponse
    {
        $validated = $request->validate(['driver_id' => ['required', 'integer', 'exists:drivers,id']]);
        $ride->update(['driver_id' => $validated['driver_id']]);

        return redirect()->route('admin.rides.show', $ride)->with('success', 'Driver assigned.');
    }
}
