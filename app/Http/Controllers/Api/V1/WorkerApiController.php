<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\ProviderBooking;
use Illuminate\Http\Request;

/*
 * DDE-Mart API — handyman (provider worker) surfaces (original).
 * Ability: worker. Bookings assigned to me, machine transitions, payouts.
 */
class WorkerApiController extends Controller
{
    /** Bookings assigned to this worker (upcoming first). */
    public function jobs(Request $request)
    {
        $jobs = ProviderBooking::where('worker_id', $request->user()->id)
            ->with(['service:id,title', 'provider:id,name'])
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json([
            'data' => $jobs->map(fn ($b) => [
                'id' => $b->id, 'number' => $b->number,
                'customer' => $b->customer_name,
                'address' => $b->address,
                'total' => (float) $b->total,
                'status' => $b->status,
                'scheduled_at' => $b->scheduled_at?->toIso8601String(),
                'service' => $b->service?->only(['id', 'title']),
                'provider' => $b->provider?->only(['id', 'name']),
            ]),
            'meta' => ['current_page' => $jobs->currentPage(), 'last_page' => $jobs->lastPage(), 'total' => $jobs->total()],
        ]);
    }

    public function job(Request $request, ProviderBooking $booking)
    {
        abort_unless($booking->worker_id === $request->user()->id, 404);
        $booking->load(['service', 'provider', 'history']);

        return response()->json(['data' => [
            'id' => $booking->id, 'number' => $booking->number, 'status' => $booking->status,
            'customer' => $booking->customer_name, 'total' => (float) $booking->total,
            'address' => $booking->address, 'notes' => $booking->notes,
            'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
            'timeline' => $booking->history->map(fn ($h) => [
                'from' => $h->from_status, 'to' => $h->to_status,
                'at' => $h->created_at?->toIso8601String(),
            ]),
        ]]);
    }

    /** Advance an assigned booking through its machine. */
    public function jobTransition(Request $request, ProviderBooking $booking)
    {
        $validated = $request->validate([
            'to' => ['required', 'string', 'in:accepted,ongoing,completed,cancelled'],
        ]);

        abort_unless($booking->worker_id === $request->user()->id, 404);
        abort_unless($booking->canTransitionTo($validated['to']), 422, 'Illegal transition.');

        $from = $booking->status;
        $booking->update(['status' => $validated['to']]);
        $booking->history()->create(['from_status' => $from, 'to_status' => $validated['to']]);

        return response()->json(['data' => ['status' => $validated['to']]]);
    }

    public function payouts(Request $request)
    {
        $rows = PayoutRequest::where('requester_type', 'worker')
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
            'amount' => ['required', 'numeric', 'min:' . max(1, (int) \App\Models\Setting::get('min_withdrawal', 1))],
            'method' => ['required', 'string', 'in:'.implode(',', PayoutRequest::METHODS)],
            'method_details' => ['nullable', 'array'],
        ]);

        $payout = PayoutRequest::create([
            'requester_type' => 'worker',
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
