<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveParcelCategoryRequest;
use App\Http\Requests\Admin\SaveParcelWeightRequest;
use App\Models\ParcelCategory;
use App\Models\ParcelOrder;
use App\Models\ParcelWeight;
use App\Models\Section;
use App\Support\ImageUploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — parcel vertical (original controller).
 * Categories + weight slabs + orders with enforced transitions. No order deletes.
 */
class ParcelController extends Controller
{
    // -- Categories ------------------------------------------------------

    public function categories(): View
    {
        $categories = ParcelCategory::with(['section'])->orderBy('sort_order')
            ->paginate(15)->withQueryString();

        return view('admin.transport.parcel-categories', compact('categories'));
    }

    public function categoryCreate(): View
    {
        return view('admin.transport.parcel-category-form', $this->catForm(new ParcelCategory()));
    }

    public function categoryStore(SaveParcelCategoryRequest $request): RedirectResponse
    {
        $category = ParcelCategory::create($this->catPayload($request));

        return redirect()->route('admin.parcel-categories.index')->with('success', "Category '{$category->name}' created.");
    }

    public function categoryEdit(ParcelCategory $category): View
    {
        return view('admin.transport.parcel-category-form', $this->catForm($category));
    }

    public function categoryUpdate(SaveParcelCategoryRequest $request, ParcelCategory $category): RedirectResponse
    {
        $category->update($this->catPayload($request, $category));

        return redirect()->route('admin.parcel-categories.index')->with('success', "Category '{$category->name}' updated.");
    }

    public function categoryDestroy(ParcelCategory $category): RedirectResponse
    {
        ImageUploads::delete($category->image_path);
        $category->delete();

        return redirect()->route('admin.parcel-categories.index')->with('success', "Category '{$category->name}' deleted.");
    }

    protected function catForm(ParcelCategory $category): array
    {
        return [
            'category' => $category,
            'sections' => Section::orderBy('name')->get(),
            'method' => $category->exists ? 'PUT' : 'POST',
            'action' => $category->exists
                ? route('admin.parcel-categories.update', $category)
                : route('admin.parcel-categories.store'),
        ];
    }

    protected function catPayload(SaveParcelCategoryRequest $request, ?ParcelCategory $category = null): array
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->boolean('remove_image')) {
            ImageUploads::delete($category?->image_path);
            $data['image_path'] = null;
        } else {
            $data['image_path'] = ImageUploads::replace($request->file('image'), $category?->image_path, 'parcel');
        }

        return $data;
    }

    // -- Weight slabs ----------------------------------------------------

    public function weights(): View
    {
        $weights = ParcelWeight::orderBy('sort_order')->paginate(15);

        return view('admin.transport.parcel-weights', compact('weights'));
    }

    public function weightStore(SaveParcelWeightRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $weight = ParcelWeight::create($data);

        return redirect()->route('admin.parcel-weights.index')->with('success', "Slab '{$weight->title}' created.");
    }

    public function weightUpdate(SaveParcelWeightRequest $request, ParcelWeight $weight): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $weight->update($data);

        return redirect()->route('admin.parcel-weights.index')->with('success', "Slab '{$weight->title}' updated.");
    }

    public function weightDestroy(ParcelWeight $weight): RedirectResponse
    {
        $weight->delete();

        return redirect()->route('admin.parcel-weights.index')->with('success', "Slab '{$weight->title}' deleted.");
    }

    // -- Orders ----------------------------------------------------------

    public function orders(Request $request): View
    {
        $orders = ParcelOrder::with(['category'])
            ->when($request->filled('search'), fn ($q) => $q
                ->where('number', 'like', '%'.$request->input('search').'%')
                ->orWhere('sender_name', 'like', '%'.$request->input('search').'%')
                ->orWhere('receiver_name', 'like', '%'.$request->input('search').'%')
                ->orWhere('receiver_phone', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.transport.parcel-orders', ['orders' => $orders, 'statuses' => ParcelOrder::STATUSES]);
    }

    public function orderShow(ParcelOrder $parcelOrder): View
    {
        $parcelOrder->load(['category', 'weight', 'driver', 'history.changedBy']);

        return view('admin.transport.parcel-show', [
            'order' => $parcelOrder,
            'allowed' => ParcelOrder::TRANSITIONS[$parcelOrder->status] ?? [],
            'statuses' => ParcelOrder::STATUSES,
        ]);
    }

    public function orderTransition(Request $request, ParcelOrder $parcelOrder): RedirectResponse
    {
        $validated = $request->validate(['to' => ['required', 'string'], 'note' => ['nullable', 'string', 'max:500']]);

        if (! array_key_exists($validated['to'], ParcelOrder::STATUSES) || ! $parcelOrder->canTransitionTo($validated['to'])) {
            return redirect()->route('admin.parcel-orders.show', $parcelOrder)
                ->with('error', "Cannot move parcel to [{$validated['to']}].");
        }

        $from = $parcelOrder->status;
        $parcelOrder->update(['status' => $validated['to']]);
        $parcelOrder->history()->create([
            'from_status' => $from, 'to_status' => $validated['to'],
            'changed_by' => $request->user()->id, 'note' => $validated['note'] ?? null,
        ]);

        return redirect()->route('admin.parcel-orders.show', $parcelOrder)
            ->with('success', 'Parcel moved to '.ParcelOrder::STATUSES[$validated['to']].'.');
    }
}
