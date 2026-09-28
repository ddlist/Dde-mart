<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveRentalPackageRequest;
use App\Http\Requests\Admin\SaveRentalTypeRequest;
use App\Models\RentalOrder;
use App\Models\RentalPackage;
use App\Models\RentalVehicleType;
use App\Models\Section;
use App\Support\ImageUploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — rental vertical (original controller).
 * Vehicle types + packages + orders with enforced transitions. No order deletes.
 */
class RentalController extends Controller
{
    // -- Vehicle types ---------------------------------------------------

    public function types(): View
    {
        $types = RentalVehicleType::with(['section'])->withCount('packages')
            ->orderBy('name')->paginate(15);

        return view('admin.transport.rental-types', compact('types'));
    }

    public function typeCreate(): View
    {
        return view('admin.transport.rental-type-form', $this->typeForm(new RentalVehicleType()));
    }

    public function typeStore(SaveRentalTypeRequest $request): RedirectResponse
    {
        $type = RentalVehicleType::create($this->typePayload($request));

        return redirect()->route('admin.rental-types.index')->with('success', "Type '{$type->name}' created.");
    }

    public function typeEdit(RentalVehicleType $type): View
    {
        return view('admin.transport.rental-type-form', $this->typeForm($type));
    }

    public function typeUpdate(SaveRentalTypeRequest $request, RentalVehicleType $type): RedirectResponse
    {
        $type->update($this->typePayload($request, $type));

        return redirect()->route('admin.rental-types.index')->with('success', "Type '{$type->name}' updated.");
    }

    public function typeDestroy(RentalVehicleType $type): RedirectResponse
    {
        if ($type->packages()->exists()) {
            return redirect()->route('admin.rental-types.index')->with(
                'error', "Cannot delete '{$type->name}': packages still use it."
            );
        }

        ImageUploads::delete($type->icon_path);
        $type->delete();

        return redirect()->route('admin.rental-types.index')->with('success', "Type '{$type->name}' deleted.");
    }

    protected function typeForm(RentalVehicleType $type): array
    {
        return [
            'type' => $type,
            'sections' => Section::orderBy('name')->get(),
            'method' => $type->exists ? 'PUT' : 'POST',
            'action' => $type->exists
                ? route('admin.rental-types.update', $type)
                : route('admin.rental-types.store'),
        ];
    }

    protected function typePayload(SaveRentalTypeRequest $request, ?RentalVehicleType $type = null): array
    {
        $data = $request->safe()->except(['icon', 'remove_icon']);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->boolean('remove_icon')) {
            ImageUploads::delete($type?->icon_path);
            $data['icon_path'] = null;
        } else {
            $data['icon_path'] = ImageUploads::replace($request->file('icon'), $type?->icon_path, 'rental');
        }

        return $data;
    }

    // -- Packages --------------------------------------------------------

    public function packages(): View
    {
        $packages = RentalPackage::with(['vehicleType'])->orderBy('sort_order')->paginate(15);

        return view('admin.transport.rental-packages', compact('packages'));
    }

    public function packageCreate(): View
    {
        return view('admin.transport.rental-package-form', $this->packageForm(new RentalPackage()));
    }

    public function packageStore(SaveRentalPackageRequest $request): RedirectResponse
    {
        $package = RentalPackage::create($this->packagePayload($request));

        return redirect()->route('admin.rental-packages.index')->with('success', "Package '{$package->name}' created.");
    }

    public function packageEdit(RentalPackage $package): View
    {
        return view('admin.transport.rental-package-form', $this->packageForm($package));
    }

    public function packageUpdate(SaveRentalPackageRequest $request, RentalPackage $package): RedirectResponse
    {
        $package->update($this->packagePayload($request));

        return redirect()->route('admin.rental-packages.index')->with('success', "Package '{$package->name}' updated.");
    }

    public function packageDestroy(RentalPackage $package): RedirectResponse
    {
        $package->delete();

        return redirect()->route('admin.rental-packages.index')->with('success', "Package '{$package->name}' deleted.");
    }

    protected function packageForm(RentalPackage $package): array
    {
        return [
            'package' => $package,
            'types' => RentalVehicleType::orderBy('name')->get(),
            'sections' => Section::orderBy('name')->get(),
            'method' => $package->exists ? 'PUT' : 'POST',
            'action' => $package->exists
                ? route('admin.rental-packages.update', $package)
                : route('admin.rental-packages.store'),
        ];
    }

    protected function packagePayload(SaveRentalPackageRequest $request): array
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    // -- Orders ----------------------------------------------------------

    public function orders(Request $request): View
    {
        $orders = RentalOrder::with(['package'])
            ->when($request->filled('search'), fn ($q) => $q
                ->where('number', 'like', '%'.$request->input('search').'%')
                ->orWhere('customer_name', 'like', '%'.$request->input('search').'%')
                ->orWhere('customer_phone', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.transport.rental-orders', ['orders' => $orders, 'statuses' => RentalOrder::STATUSES]);
    }

    public function orderShow(RentalOrder $rentalOrder): View
    {
        $rentalOrder->load(['package.vehicleType', 'vehicleType', 'driver', 'history.changedBy']);

        $drivers = \App\Models\Driver::where('status', 'active')
            ->orderBy('name')->get(['id', 'name', 'phone', 'is_online']);

        return view('admin.transport.rental-show', [
            'order' => $rentalOrder,
            'allowed' => RentalOrder::TRANSITIONS[$rentalOrder->status] ?? [],
            'statuses' => RentalOrder::STATUSES,
            'drivers' => $drivers,
        ]);
    }

    /** Manual driver assignment with push. */
    public function orderAssign(Request $request, RentalOrder $rentalOrder): RedirectResponse
    {
        $validated = $request->validate(['driver_id' => ['required', 'integer', 'exists:drivers,id']]);
        $rentalOrder->update(['driver_id' => $validated['driver_id']]);

        app(\App\Services\WorkforceNotifier::class)->rentalAssigned($rentalOrder->fresh());

        return redirect()->route('admin.rental-orders.show', $rentalOrder)
            ->with('success', 'Driver assigned.');
    }

    public function orderTransition(Request $request, RentalOrder $rentalOrder): RedirectResponse
    {
        $validated = $request->validate(['to' => ['required', 'string'], 'note' => ['nullable', 'string', 'max:500']]);

        if (! array_key_exists($validated['to'], RentalOrder::STATUSES) || ! $rentalOrder->canTransitionTo($validated['to'])) {
            return redirect()->route('admin.rental-orders.show', $rentalOrder)
                ->with('error', "Cannot move rental to [{$validated['to']}].");
        }

        $from = $rentalOrder->status;
        $updates = ['status' => $validated['to']];

        if ($validated['to'] === 'ongoing' && ! $rentalOrder->started_at) {
            $updates['started_at'] = now();
        }

        if ($validated['to'] === 'completed' && ! $rentalOrder->ended_at) {
            $updates['ended_at'] = now();
        }

        $rentalOrder->update($updates);
        $rentalOrder->history()->create([
            'from_status' => $from, 'to_status' => $validated['to'],
            'changed_by' => $request->user()->id, 'note' => $validated['note'] ?? null,
        ]);

        return redirect()->route('admin.rental-orders.show', $rentalOrder)
            ->with('success', 'Rental moved to '.RentalOrder::STATUSES[$validated['to']].'.');
    }
}
