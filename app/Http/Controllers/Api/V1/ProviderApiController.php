<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProviderBooking;
use App\Models\WalletEntry;
use App\Services\ShopPayments;
use Illuminate\Http\Request;

/*
 * DDE-Mart API — provider bookings + wallet top-up (original).
 * Top-up reuses the gateway redirect flows; verified callbacks credit the wallet.
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
}
