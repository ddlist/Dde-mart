<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveOwnerRequest;
use App\Models\Owner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — owners directory (original controller).
 * Store linkage is managed from the store form (owner_id); detail shows stores.
 */
class OwnerController extends Controller
{
    public function index(Request $request): View
    {
        $owners = Owner::withCount('stores')
            ->when($request->filled('search'), fn ($q) => $q
                ->where('name', 'like', '%'.$request->input('search').'%')
                ->orWhere('phone', 'like', '%'.$request->input('search').'%')
                ->orWhere('email', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy('name')
            ->paginate(15)->withQueryString();

        return view('admin.owners.index', ['owners' => $owners]);
    }

    public function create(): View
    {
        return view('admin.owners.form', $this->formData(new Owner()));
    }

    public function store(SaveOwnerRequest $request): RedirectResponse
    {
        $owner = Owner::create($request->validated());

        return redirect()->route('admin.owners.show', $owner)->with('success', "Owner '{$owner->name}' created.");
    }

    public function show(Owner $owner): View
    {
        $owner->load(['stores.section', 'verifications.type']);

        $drivers = \App\Models\Driver::where('owner_id', $owner->id)
            ->orderBy('name')->get();
        $orderCount = \App\Models\Order::whereIn(
            'vendor_id',
            $owner->stores->pluck('id')->all()
        )->count();

        return view('admin.owners.show', compact('owner', 'drivers', 'orderCount'));
    }

    public function edit(Owner $owner): View
    {
        return view('admin.owners.form', $this->formData($owner));
    }

    public function update(SaveOwnerRequest $request, Owner $owner): RedirectResponse
    {
        $owner->update($request->validated());

        return redirect()->route('admin.owners.show', $owner)->with('success', "Owner '{$owner->name}' updated.");
    }

    public function destroy(Owner $owner): RedirectResponse
    {
        if ($owner->stores()->exists()) {
            return redirect()->route('admin.owners.index')->with(
                'error', "Cannot delete '{$owner->name}': {$owner->stores()->count()} store(s) still linked."
            );
        }

        $owner->verifications()->delete();
        $owner->delete();

        return redirect()->route('admin.owners.index')->with('success', "Owner '{$owner->name}' deleted.");
    }

    public function transition(Request $request, Owner $owner): RedirectResponse
    {
        $to = $request->validate(['to' => ['required', 'string']])['to'];

        if (! in_array($to, Owner::STATUSES, true) || ! $owner->canTransitionTo($to)) {
            return redirect()->route('admin.owners.show', $owner)->with('error', "Cannot move owner to [{$to}].");
        }

        $owner->update(['status' => $to]);

        return redirect()->route('admin.owners.show', $owner)->with('success', "Owner moved to {$to}.");
    }

    protected function formData(Owner $owner): array
    {
        return [
            'owner' => $owner,
            'method' => $owner->exists ? 'PUT' : 'POST',
            'action' => $owner->exists
                ? route('admin.owners.update', $owner)
                : route('admin.owners.store'),
        ];
    }
}
