<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\SosAlert;
use Illuminate\Http\Request;

/*
 * DDE-Mart API — safety inbox filing (original). Customers file complaints
 * and raise SOS; drivers raise SOS. Staff triage from the admin inbox.
 */
class SafetyApiController extends Controller
{
    public function complaintStore(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:2000'],
            'order_ref' => ['nullable', 'string', 'max:50'],
        ]);

        $customer = $request->user();

        $complaint = Complaint::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'customer_name' => $customer->name,
            'order_ref' => $validated['order_ref'] ?? null,
            'status' => 'open',
            'occurred_at' => now(),
        ]);

        return response()->json(['data' => ['id' => $complaint->id, 'status' => 'open']], 201);
    }

    public function complaintMine(Request $request)
    {
        $complaints = Complaint::where('customer_name', $request->user()->name)
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json([
            'data' => $complaints->map(fn ($c) => [
                'id' => $c->id, 'title' => $c->title, 'status' => $c->status,
                'created_at' => $c->created_at?->toIso8601String(),
            ]),
            'meta' => ['current_page' => $complaints->currentPage(), 'last_page' => $complaints->lastPage(), 'total' => $complaints->total()],
        ]);
    }

    /** SOS from a customer or driver app. Role is inferred from the token ability. */
    public function sosRaise(Request $request)
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'order_ref' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();
        $abilities = $user->currentAccessToken()?->abilities ?? [];
        $reporterType = in_array('driver', $abilities, true) ? 'driver' : 'customer';

        $alert = SosAlert::create([
            'order_ref' => $validated['order_ref'] ?? null,
            'reporter_type' => $reporterType,
            'reporter_ref' => $user->phone,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'status' => 'open',
            'occurred_at' => now(),
        ]);

        return response()->json(['data' => ['id' => $alert->id, 'status' => 'open']], 201);
    }
}
