<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\ProviderBooking;
use App\Models\ProviderService;
use App\Models\ProviderWorker;
use Illuminate\Http\Request;

/*
 * DDE-Mart API — provider surfaces (original). Bookings inbox with
 * transitions plus own service/worker catalog management.
 */
class ProviderApiController extends Controller
{
    public function bookings(Request $request)
    {
        $bookings = ProviderBooking::where('provider_id', $request->user()->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json([
            'data' => $bookings->map(fn ($b) => [
                'id' => $b->id, 'number' => $b->number,
                'customer' => $b->customer_name, 'total' => (float) $b->total,
                'status' => $b->status,
                'scheduled_at' => $b->scheduled_at?->toIso8601String(),
            ]),
            'meta' => ['current_page' => $bookings->currentPage(), 'last_page' => $bookings->lastPage(), 'total' => $bookings->total()],
        ]);
    }

    public function booking(ProviderBooking $booking, Request $request)
    {
        abort_unless($booking->provider_id === $request->user()->id, 404);
        $booking->load(['service', 'worker', 'history']);

        return response()->json(['data' => [
            'id' => $booking->id, 'number' => $booking->number, 'status' => $booking->status,
            'total' => (float) $booking->total,
            'address' => $booking->address,
            'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
            'timeline' => $booking->history->map(fn ($h) => [
                'from' => $h->from_status, 'to' => $h->to_status,
                'at' => $h->created_at?->toIso8601String(),
            ]),
        ]]);
    }

    /** Move an owned booking through its status machine. */
    public function bookingTransition(Request $request, ProviderBooking $booking)
    {
        $validated = $request->validate([
            'to' => ['required', 'string', 'in:accepted,ongoing,completed,cancelled,rejected'],
        ]);

        abort_unless($booking->provider_id === $request->user()->id, 404);
        abort_unless($booking->canTransitionTo($validated['to']), 422, 'Illegal transition.');

        $from = $booking->status;
        $booking->update(['status' => $validated['to']]);
        $booking->history()->create(['from_status' => $from, 'to_status' => $validated['to']]);

        return response()->json(['data' => ['status' => $validated['to']]]);
    }

    /** Own services (bookable catalog). */
    public function services(Request $request)
    {
        $services = ProviderService::where('provider_id', $request->user()->id)
            ->with('category:id,title')
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json([
            'data' => $services->map(fn ($s) => [
                'id' => $s->id, 'title' => $s->title,
                'price' => (float) $s->price,
                'discount_price' => $s->discount_price === null ? null : (float) $s->discount_price,
                'is_active' => (bool) $s->is_active,
                'category' => $s->category?->only(['id', 'title']),
            ]),
            'meta' => ['current_page' => $services->currentPage(), 'last_page' => $services->lastPage(), 'total' => $services->total()],
        ]);
    }

    public function serviceStore(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:provider_categories,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'price_unit' => ['nullable', 'string', 'max:50'],
        ]);

        $service = ProviderService::create($validated + ['provider_id' => $request->user()->id]);

        return response()->json(['data' => ['id' => $service->id, 'title' => $service->title]], 201);
    }

    public function serviceUpdate(Request $request, ProviderService $service)
    {
        abort_unless($service->provider_id === $request->user()->id, 404);

        $validated = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:provider_categories,id'],
            'title' => ['sometimes', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['sometimes', 'numeric', 'min:0', 'max:1000000'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'price_unit' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('discount_price', $validated) && $validated['discount_price'] !== null) {
            $price = $validated['price'] ?? $service->price;
            abort_unless($validated['discount_price'] < $price, 422, 'Discount must be below price.');
        }

        $service->update($validated);

        return response()->json(['data' => ['id' => $service->id, 'is_active' => (bool) $service->fresh()->is_active]]);
    }

    public function serviceToggle(Request $request, ProviderService $service)
    {
        abort_unless($service->provider_id === $request->user()->id, 404);

        $service->update(['is_active' => ! $service->is_active]);

        return response()->json(['data' => ['is_active' => (bool) $service->fresh()->is_active]]);
    }

    /** Own workers (staff who fulfil bookings). */
    public function workers(Request $request)
    {
        $workers = ProviderWorker::where('provider_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json([
            'data' => $workers->map(fn ($w) => [
                'id' => $w->id, 'name' => $w->name, 'phone' => $w->phone,
                'is_active' => (bool) $w->is_active,
            ]),
            'meta' => ['current_page' => $workers->currentPage(), 'last_page' => $workers->lastPage(), 'total' => $workers->total()],
        ]);
    }

    public function workerStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:200'],
        ]);

        $worker = ProviderWorker::create($validated + ['provider_id' => $request->user()->id]);

        return response()->json(['data' => ['id' => $worker->id, 'name' => $worker->name]], 201);
    }

    public function workerUpdate(Request $request, ProviderWorker $worker)
    {
        abort_unless($worker->provider_id === $request->user()->id, 404);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:200'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $worker->update($validated);

        return response()->json(['data' => ['id' => $worker->id, 'is_active' => (bool) $worker->fresh()->is_active]]);
    }

    public function workerToggle(Request $request, ProviderWorker $worker)
    {
        abort_unless($worker->provider_id === $request->user()->id, 404);

        $worker->update(['is_active' => ! $worker->is_active]);

        return response()->json(['data' => ['is_active' => (bool) $worker->fresh()->is_active]]);
    }

    public function payouts(Request $request)
    {
        $rows = PayoutRequest::where('requester_type', 'provider')
            ->where('requester_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json([
            'data' => $rows->map(fn ($p) => [
                'id' => $p->id, 'amount' => (float) $p->amount,
                'method' => $p->method, 'status' => $p->status,
            ]),
            'meta' => ['current_page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'total' => $rows->total()],
        ]);
    }

    public function payoutRequest(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', 'string', 'in:'.implode(',', PayoutRequest::METHODS)],
            'method_details' => ['nullable', 'array'],
        ]);

        $payout = PayoutRequest::create([
            'requester_type' => 'provider',
            'requester_id' => $request->user()->id,
            'requester_name' => $request->user()->name,
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'method_details' => $validated['method_details'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json(['data' => ['id' => $payout->id, 'status' => 'pending']], 201);
    }
}
