<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\ParcelOrder;
use App\Models\PayoutRequest;
use App\Models\RentalOrder;
use App\Models\Ride;
use App\Models\Verification;
use App\Support\Images;
use Illuminate\Http\Request;

/*
 * DDE-Mart API — driver app surfaces (original). Ability: driver.
 * Jobs across parcel/rental/rides; documents submit; payout requests.
 */
class DriverApiController extends Controller
{
    public function profile(Request $request)
    {
        $driver = $request->user();

        return response()->json(['data' => [
            'id' => $driver->id, 'name' => $driver->name,
            'phone' => $driver->phone, 'kind' => $driver->kind,
            'status' => $driver->status, 'is_online' => (bool) $driver->is_online,
            'photo' => Images::url($driver->photo_path),
            'vehicle_info' => $driver->vehicle_info,
        ]]);
    }

    public function availability(Request $request)
    {
        $validated = $request->validate(['is_online' => ['required', 'boolean']]);
        $request->user()->update(['is_online' => $validated['is_online']]);

        return response()->json(['data' => ['is_online' => (bool) $request->user()->is_online]]);
    }

    public function jobs(Request $request)
    {
        $driver = $request->user();

        $shape = fn ($o, $type) => [
            'type' => $type,
            'id' => $o->id,
            'number' => $o->number,
            'status' => $o->status,
            'total' => (float) $o->total,
        ];

        $mine = collect()
            ->merge(ParcelOrder::where('driver_id', $driver->id)->get()->map(fn ($o) => $shape($o, 'parcel')))
            ->merge(RentalOrder::where('driver_id', $driver->id)->get()->map(fn ($o) => $shape($o, 'rental')))
            ->merge(Ride::where('driver_id', $driver->id)->get()->map(fn ($o) => $shape($o, 'ride')))
            ->sortByDesc('id')->values();

        $pool = collect()
            ->merge(ParcelOrder::whereNull('driver_id')->whereIn('status', ['placed', 'accepted'])->limit(20)->get()->map(fn ($o) => $shape($o, 'parcel')))
            ->merge(RentalOrder::whereNull('driver_id')->whereIn('status', ['placed', 'accepted'])->limit(20)->get()->map(fn ($o) => $shape($o, 'rental')))
            ->merge(Ride::whereNull('driver_id')->whereIn('status', ['placed', 'accepted'])->limit(20)->get()->map(fn ($o) => $shape($o, 'ride')));

        return response()->json(['data' => ['mine' => $mine, 'pool' => $pool->values()]]);
    }

    public function documents(Request $request)
    {
        $driver = $request->user();

        return response()->json(['data' => [
            'required' => DocumentType::where('owner_type', 'driver')->where('is_active', true)
                ->get(['id', 'title', 'front_required', 'back_required']),
            'submitted' => $driver->verifications()->with('type:id,title')->get()
                ->map(fn ($v) => [
                    'id' => $v->id, 'document' => $v->type?->title,
                    'status' => $v->status, 'note' => $v->note,
                ]),
        ]]);
    }

    public function documentSubmit(Request $request)
    {
        $validated = $request->validate([
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'front' => ['nullable', 'image', 'max:5120'],
            'back' => ['nullable', 'image', 'max:5120'],
        ]);

        $type = DocumentType::findOrFail($validated['document_type_id']);
        abort_unless($type->owner_type === 'driver', 422, 'Wrong document audience.');

        if ($type->front_required && ! $request->hasFile('front')) {
            return response()->json(['message' => 'Front image required.'], 422);
        }

        if ($type->back_required && ! $request->hasFile('back')) {
            return response()->json(['message' => 'Back image required.'], 422);
        }

        $verification = $request->user()->verifications()->create([
            'document_type_id' => $type->id,
            'front_path' => $request->file('front')?->store('verifications', 'public'),
            'back_path' => $request->file('back')?->store('verifications', 'public'),
            'status' => 'pending',
        ]);

        return response()->json(['data' => ['id' => $verification->id, 'status' => 'pending']], 201);
    }

    public function payouts(Request $request)
    {
        $rows = PayoutRequest::where('requester_type', 'driver')
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
            'method' => ['required', 'string', 'in:bank,paypal,stripe,razorpay,flutterwave,cash'],
            'method_details' => ['nullable', 'array'],
        ]);

        $payout = PayoutRequest::create([
            'requester_type' => 'driver',
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
