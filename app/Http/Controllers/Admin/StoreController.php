<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveStoreRequest;
use App\Models\Owner;
use App\Models\Section;
use App\Models\Store;
use App\Models\SubscriptionPlan;
use App\Models\Zone;
use App\Support\ImageUploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — stores (original controller).
 * CRUD + approval workflow. Delete blocked while products attach (vendor catalog
 * must move first). Product assignment happens on the product form (D6).
 */
class StoreController extends Controller
{
    public function index(Request $request): View
    {
        $stores = Store::with(['section', 'zone'])->withCount('products')
            ->when($request->filled('search'), fn ($q) => $q
                ->where('name', 'like', '%'.$request->input('search').'%')
                ->orWhere('owner_name', 'like', '%'.$request->input('search').'%')
                ->orWhere('phone', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('section'), fn ($q) => $q->where('section_id', (int) $request->input('section')))
            ->orderBy('name')
            ->paginate(15)->withQueryString();

        return view('admin.stores.index', [
            'stores' => $stores,
            'statuses' => Store::STATUSES,
            'sections' => Section::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.stores.form', $this->formData(new Store()));
    }

    public function store(SaveStoreRequest $request): RedirectResponse
    {
        $store = Store::create($this->payload($request));

        return redirect()->route('admin.stores.index')->with('success', "Store '{$store->name}' created.");
    }

    public function edit(Store $store): View
    {
        $store->loadCount('products');

        return view('admin.stores.form', $this->formData($store));
    }

    public function update(SaveStoreRequest $request, Store $store): RedirectResponse
    {
        $store->update($this->payload($request, $store));

        return redirect()->route('admin.stores.index')->with('success', "Store '{$store->name}' updated.");
    }

    public function destroy(Store $store): RedirectResponse
    {
        if ($store->products()->exists()) {
            return redirect()->route('admin.stores.index')->with(
                'error', "Cannot delete '{$store->name}': {$store->products()->count()} product(s) still assigned. Reassign them first."
            );
        }

        ImageUploads::delete($store->image_path);
        $store->delete();

        return redirect()->route('admin.stores.index')->with('success', "Store '{$store->name}' deleted.");
    }

    public function transition(Request $request, Store $store): RedirectResponse
    {
        $to = $request->validate(['to' => ['required', 'string']])['to'];

        if (! in_array($to, Store::STATUSES, true) || ! $store->canTransitionTo($to)) {
            return redirect()->route('admin.stores.edit', $store)->with('error', "Cannot move store to [{$to}].");
        }

        $store->update(['status' => $to]);

        return redirect()->route('admin.stores.edit', $store)->with('success', "Store moved to {$to}.");
    }

    protected function formData(Store $store): array
    {
        return [
            'store' => $store,
            'sections' => Section::orderBy('name')->get(),
            'zones' => Zone::orderBy('name')->get(),
            'owners' => Owner::orderBy('name')->get(),
            'plans' => SubscriptionPlan::orderBy('name')->get(),
            'method' => $store->exists ? 'PUT' : 'POST',
            'action' => $store->exists
                ? route('admin.stores.update', $store)
                : route('admin.stores.store'),
        ];
    }

    protected function payload(SaveStoreRequest $request, ?Store $store = null): array
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        $data['is_open'] = $request->boolean('is_open');
        $data['self_delivery'] = $request->boolean('self_delivery');

        if ($request->boolean('remove_image')) {
            ImageUploads::delete($store?->image_path);
            $data['image_path'] = null;
        } else {
            $data['image_path'] = ImageUploads::replace($request->file('image'), $store?->image_path, 'stores');
        }

        return $data;
    }
}
