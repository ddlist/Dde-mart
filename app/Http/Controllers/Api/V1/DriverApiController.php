<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChatThread;
use App\Models\DocumentType;
use App\Models\Order;
use App\Models\ParcelOrder;
use App\Models\PayoutRequest;
use App\Models\RentalOrder;
use App\Models\Ride;
use App\Models\Verification;
use App\Support\Images;
use Illuminate\Http\Request;

/*
 * DDE-Mart API — driver app surfaces (original). Ability: driver.
 * Jobs across food/parcel/rental/rides; documents submit; payout requests.
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

    /** Edit own name + vehicle info (phone is the login identity: immutable). */
    public function profileUpdate(Request $request)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:200'],
            'vehicle_info' => ['nullable', 'string', 'max:500'],
        ]);

        $request->user()->update($validated);

        return response()->json(['data' => ['updated' => true]]);
    }

    public function availability(Request $request)
    {
        $validated = $request->validate(['is_online' => ['required', 'boolean']]);
        $request->user()->update(['is_online' => $validated['is_online']]);

        return response()->json(['data' => ['is_online' => (bool) $request->user()->is_online]]);
    }

    /** Live position ping (drives dispatch proximity + freshness). */
    public function location(Request $request)
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $request->user()->update([
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'location_updated_at' => now(),
        ]);

        return response()->json(['data' => ['recorded' => true]]);
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
            ->merge(Order::where('driver_id', $driver->id)->where('type', 'food')->get()->map(fn ($o) => $shape($o, 'food')))
            ->merge(ParcelOrder::where('driver_id', $driver->id)->get()->map(fn ($o) => $shape($o, 'parcel')))
            ->merge(RentalOrder::where('driver_id', $driver->id)->get()->map(fn ($o) => $shape($o, 'rental')))
            ->merge(Ride::where('driver_id', $driver->id)->get()->map(fn ($o) => $shape($o, 'ride')))
            ->sortByDesc('id')->values();

        $pool = collect()
            ->merge(Order::whereNull('driver_id')->where('type', 'food')->where('status', 'accepted')->limit(20)->get()->map(fn ($o) => $shape($o, 'food')))
            ->merge(ParcelOrder::whereNull('driver_id')->whereIn('status', ['placed', 'accepted'])->limit(20)->get()->map(fn ($o) => $shape($o, 'parcel')))
            ->merge(RentalOrder::whereNull('driver_id')->whereIn('status', ['placed', 'accepted'])->limit(20)->get()->map(fn ($o) => $shape($o, 'rental')))
            ->merge(Ride::whereNull('driver_id')->whereIn('status', ['placed', 'accepted'])->limit(20)->get()->map(fn ($o) => $shape($o, 'ride')));

        return response()->json(['data' => ['mine' => $mine, 'pool' => $pool->values()]]);
    }

    /** One job with customer/contact/bill detail. Visible when assigned to
     * me or sitting unassigned in the pool; anything else is 404. */
    public function jobShow(Request $request, string $type, int $id)
    {
        $driver = $request->user();

        $order = match ($type) {
            'food' => Order::with(['items', 'history'])->find($id),
            'parcel' => ParcelOrder::with(['history'])->find($id),
            'rental' => RentalOrder::find($id),
            'ride' => Ride::find($id),
            default => null,
        };
        abort_unless($order, 404);
        abort_unless(
            $order->driver_id === null || $order->driver_id === $driver->id,
            404
        );

        $timeline = isset($order->history)
            ? $order->history->map(fn ($h) => [
                'to' => $h->to_status, 'at' => $h->created_at?->toIso8601String(),
            ])->values()
            : [];

        $data = [
            'type' => $type, 'id' => $order->id, 'number' => $order->number,
            'status' => $order->status,
            'payment_method' => $order->payment_method ?? null,
            'notes' => $order->notes ?? null,
            'subtotal' => (float) ($order->subtotal ?? 0),
            'discount' => (float) ($order->discount ?? 0),
            'total' => (float) $order->total,
            'timeline' => $timeline,
        ];

        if ($type === 'food') {
            $data += [
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'address' => $order->address,
                'delivery_charge' => (float) ($order->delivery_charge ?? 0),
                'tax' => (float) ($order->tax ?? 0),
                'items' => $order->items->map(fn ($item) => [
                    'name' => $item->name, 'quantity' => $item->quantity,
                    'extras' => $item->extras ?? [],
                    'subtotal' => (float) $item->subtotal,
                ]),
            ];
        }

        if ($type === 'parcel') {
            $data += [
                'sender_name' => $order->sender_name,
                'sender_phone' => $order->sender_phone,
                'sender_address' => $order->sender_address,
                'receiver_name' => $order->receiver_name,
                'receiver_phone' => $order->receiver_phone,
                'receiver_address' => $order->receiver_address,
                'distance_km' => $order->distance_km !== null
                    ? (float) $order->distance_km : null,
            ];
        }

        if ($type === 'rental' || $type === 'ride') {
            $data += [
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'source' => $order->source,
                'destination' => $order->destination,
                'distance_km' => $order->distance_km !== null
                    ? (float) $order->distance_km : null,
            ];
        }

        return response()->json(['data' => $data]);
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

    /** Accept an unassigned job (claims it + moves to accepted). */
    public function jobAccept(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:food,parcel,rental,ride'],
            'id' => ['required', 'integer'],
        ]);

        $order = $this->findJob($validated['type'], $validated['id']);
        abort_unless($order, 404);

        if ($validated['type'] === 'food') {
            return $this->acceptFoodJob($request, $order);
        }

        abort_unless($order->driver_id === null && in_array($order->status, ['placed', 'accepted'], true), 422, 'Job unavailable.');

        $from = $order->status;
        $order->update(['driver_id' => $request->user()->id, 'status' => 'accepted']);
        $order->history()->create(['from_status' => $from, 'to_status' => 'accepted']);

        return response()->json(['data' => ['status' => 'accepted']]);
    }

    /**
     * Food claim: either an outstanding dispatch offer to this driver, or a
     * pool pickup of a vendor-accepted order (manual mode). Status stays
     * `accepted`; the claim is recorded on the timeline.
     */
    protected function acceptFoodJob(Request $request, Order $order)
    {
        $driverId = $request->user()->id;

        if ($order->driver_id === $driverId && $order->dispatch_expires_at) {
            abort_unless($order->dispatch_expires_at->isFuture(), 422, 'Offer expired.');

            $order->update(['dispatch_expires_at' => null]);
            // changed_by stays null: the history FK targets staff users, not drivers.
            $order->history()->create([
                'from_status' => $order->status, 'to_status' => $order->status,
                'note' => "Dispatch offer accepted by driver #{$driverId}",
            ]);

            return response()->json(['data' => ['status' => $order->status]]);
        }

        abort_unless(
            $order->driver_id === null && $order->status === Order::ACCEPTED,
            422, 'Job unavailable.'
        );

        $order->update(['driver_id' => $driverId]);
        $order->history()->create([
            'from_status' => Order::ACCEPTED, 'to_status' => Order::ACCEPTED,
            'note' => "Claimed from pool by driver #{$driverId}",
        ]);

        return response()->json(['data' => ['status' => Order::ACCEPTED]]);
    }

    /** Advance an assigned job through its machine. */
    public function jobTransition(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:food,parcel,rental,ride'],
            'id' => ['required', 'integer'],
            'to' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $order = $this->findJob($validated['type'], $validated['id']);
        abort_unless($order, 404);
        abort_unless($order->driver_id === $request->user()->id, 403, 'Not your job.');
        abort_unless($order->canTransitionTo($validated['to']), 422, 'Illegal transition.');

        $from = $order->status;
        $updates = ['status' => $validated['to']];

        if ($validated['to'] === 'ongoing' && empty($order->started_at)) {
            $updates['started_at'] = now();
        }

        if ($validated['to'] === 'completed' && empty($order->ended_at)) {
            $updates['ended_at'] = now();
        }

        $order->update($updates);
        $order->history()->create([
            'from_status' => $from, 'to_status' => $validated['to'],
            'note' => $validated['note'] ?? null,
        ]);

        return response()->json(['data' => ['status' => $validated['to']]]);
    }

    protected function findJob(string $type, int $id)
    {
        return match ($type) {
            'food' => Order::where('type', 'food')->find($id),
            'parcel' => ParcelOrder::find($id),
            'rental' => RentalOrder::find($id),
            'ride' => Ride::find($id),
        };
    }

    /** Support threads linked to this driver (customer opened, driver answers). */
    public function chatThreads(Request $request)
    {
        $threads = ChatThread::where('driver_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) $request->input('per_page', 15))));

        return response()->json([
            'data' => $threads->map(fn ($t) => [
                'id' => $t->id, 'subject' => $t->subject, 'status' => $t->status,
                'order_ref' => $t->order_ref, 'last_message' => $t->last_message,
            ]),
            'meta' => ['current_page' => $threads->currentPage(), 'last_page' => $threads->lastPage(), 'total' => $threads->total()],
        ]);
    }

    public function chatShow(Request $request, ChatThread $thread)
    {
        abort_unless($thread->driver_id === $request->user()->id, 404);
        $thread->load(['messages']);

        return response()->json(['data' => [
            'id' => $thread->id, 'subject' => $thread->subject, 'status' => $thread->status,
            'order_ref' => $thread->order_ref,
            'messages' => $thread->messages->map(fn ($m) => [
                'id' => $m->id,
                'from_me' => str_starts_with($m->sender_ref ?? '', 'driver:'),
                'body' => $m->body,
                'at' => $m->sent_at?->toIso8601String(),
            ]),
        ]]);
    }

    public function chatReply(Request $request, ChatThread $thread)
    {
        abort_unless($thread->driver_id === $request->user()->id, 404);
        abort_unless($thread->status === 'open', 422, 'Thread is closed.');

        $validated = $request->validate(['message' => ['required', 'string', 'max:2000']]);

        $message = $thread->messages()->create([
            'sender_ref' => 'driver:'.$request->user()->id,
            'body' => $validated['message'],
            'sent_at' => now(),
        ]);
        $thread->update(['last_message' => substr($validated['message'], 0, 500)]);

        return response()->json(['data' => ['message_id' => $message->id]], 201);
    }
}
