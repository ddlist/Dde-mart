<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\ProviderBooking;
use App\Models\ProviderCategory;
use App\Models\ProviderService;
use App\Models\ProviderWorker;
use App\Models\Section;
use App\Support\ImageUploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — on-demand services vertical (original controller).
 * Providers (role=provider accounts) + nested categories + services + workers +
 * bookings with enforced transitions. No booking deletes.
 */
class ServiceController extends Controller
{
    // -- Providers -------------------------------------------------------

    public function providers(Request $request): View
    {
        $providers = Provider::withCount(['services', 'workers'])
            ->when($request->filled('search'), fn ($q) => $q
                ->where('name', 'like', '%'.$request->input('search').'%')
                ->orWhere('phone', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy('name')
            ->paginate(15)->withQueryString();

        return view('admin.services.providers', ['providers' => $providers]);
    }

    public function providerShow(Provider $provider): View
    {
        $provider->load(['services.category', 'workers']);

        return view('admin.services.provider-show', ['provider' => $provider]);
    }

    public function providerTransition(Request $request, Provider $provider): RedirectResponse
    {
        $to = $request->validate(['to' => ['required', 'string']])['to'];

        if (! in_array($to, Provider::STATUSES, true) || ! $provider->canTransitionTo($to)) {
            return redirect()->route('admin.providers.show', $provider)->with('error', "Cannot move provider to [{$to}].");
        }

        $provider->update(['status' => $to]);

        return redirect()->route('admin.providers.show', $provider)->with('success', "Provider moved to {$to}.");
    }

    // -- Categories (nested) ----------------------------------------------

    public function categories(): View
    {
        $categories = ProviderCategory::with(['parent', 'section', 'children'])
            ->whereNull('parent_id')->orderBy('title')->get();
        $sections = Section::orderBy('name')->get();
        $parents = ProviderCategory::orderBy('title')->get();

        return view('admin.services.categories', compact('categories', 'sections', 'parents'));
    }

    public function categoryStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'parent_id' => ['nullable', 'integer', 'exists:provider_categories,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $validated['level'] = $validated['parent_id'] ? 1 : 0;
        $validated['image_path'] = ImageUploads::store($request->file('image'), 'provider-cats');

        ProviderCategory::create($validated);

        return redirect()->route('admin.provider-categories.index')->with('success', 'Category added.');
    }

    public function categoryDestroy(ProviderCategory $category): RedirectResponse
    {
        if ($category->children()->exists()) {
            return redirect()->route('admin.provider-categories.index')
                ->with('error', 'Category has subcategories — remove them first.');
        }

        ImageUploads::delete($category->image_path);
        $category->delete();

        return redirect()->route('admin.provider-categories.index')->with('success', 'Category deleted.');
    }

    // -- Services & workers -----------------------------------------------

    public function services(Request $request): View
    {
        $services = ProviderService::with(['provider', 'category'])
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->input('search').'%'))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.services.services', compact('services'));
    }

    public function serviceToggle(ProviderService $service): RedirectResponse
    {
        $service->update(['is_active' => ! $service->is_active]);

        return redirect()->route('admin.provider-services.index')
            ->with('success', $service->is_active ? 'Service activated.' : 'Service hidden.');
    }

    public function workers(Request $request): View
    {
        $workers = ProviderWorker::with(['provider'])
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->input('search').'%'))
            ->orderBy('name')
            ->paginate(15)->withQueryString();

        return view('admin.services.workers', compact('workers'));
    }

    public function workerToggle(ProviderWorker $worker): RedirectResponse
    {
        $worker->update(['is_active' => ! $worker->is_active]);

        return redirect()->route('admin.provider-workers.index')->with('success', 'Worker updated.');
    }

    // -- Bookings ----------------------------------------------------------

    public function bookings(Request $request): View
    {
        $bookings = ProviderBooking::with(['provider'])
            ->when($request->filled('search'), fn ($q) => $q
                ->where('number', 'like', '%'.$request->input('search').'%')
                ->orWhere('customer_name', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.services.bookings', ['bookings' => $bookings, 'statuses' => ProviderBooking::STATUSES]);
    }

    public function bookingShow(ProviderBooking $booking): View
    {
        $booking->load(['provider', 'service', 'worker', 'history.changedBy']);
        $workers = ProviderWorker::where('provider_id', $booking->provider_id)->orderBy('name')->get();

        return view('admin.services.booking-show', [
            'booking' => $booking,
            'workers' => $workers,
            'allowed' => ProviderBooking::TRANSITIONS[$booking->status] ?? [],
            'statuses' => ProviderBooking::STATUSES,
        ]);
    }

    public function bookingTransition(Request $request, ProviderBooking $booking): RedirectResponse
    {
        $validated = $request->validate(['to' => ['required', 'string'], 'note' => ['nullable', 'string', 'max:500']]);

        if (! array_key_exists($validated['to'], ProviderBooking::STATUSES) || ! $booking->canTransitionTo($validated['to'])) {
            return redirect()->route('admin.provider-bookings.show', $booking)
                ->with('error', "Cannot move booking to [{$validated['to']}].");
        }

        $from = $booking->status;
        $booking->update(['status' => $validated['to']]);
        $booking->history()->create([
            'from_status' => $from, 'to_status' => $validated['to'],
            'changed_by' => $request->user()->id, 'note' => $validated['note'] ?? null,
        ]);

        return redirect()->route('admin.provider-bookings.show', $booking)
            ->with('success', 'Booking moved to '.ProviderBooking::STATUSES[$validated['to']].'.');
    }

    public function bookingAssign(Request $request, ProviderBooking $booking): RedirectResponse
    {
        $validated = $request->validate(['worker_id' => ['required', 'integer', 'exists:provider_workers,id']]);
        $booking->update(['worker_id' => $validated['worker_id']]);

        return redirect()->route('admin.provider-bookings.show', $booking)->with('success', 'Worker assigned.');
    }
}
