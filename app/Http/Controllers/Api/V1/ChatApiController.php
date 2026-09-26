<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Order;
use App\Models\ParcelOrder;
use App\Models\ProviderBooking;
use App\Models\RentalOrder;
use App\Models\Ride;
use Illuminate\Http\Request;

/*
 * DDE-Mart API — support chat (original). Threads list + open thread with
 * message send. Staff reply from the panel inbox (read-only there for now).
 */
class ChatApiController extends Controller
{
    public function threads(Request $request)
    {
        // Threads are keyed loosely; customers see their own + broadcast threads.
        $threads = ChatThread::where('status', 'open')
            ->orderByDesc('id')
            ->limit(20)->get(['id', 'audience', 'subject', 'last_message']);

        return response()->json(['data' => $threads]);
    }

    public function show(ChatThread $thread)
    {
        $thread->load(['messages']);

        return response()->json(['data' => [
            'id' => $thread->id,
            'subject' => $thread->subject,
            'status' => $thread->status,
            'messages' => $thread->messages->map(fn ($m) => [
                'id' => $m->id,
                'from_me' => $m->sender_ref === 'customer',
                'body' => $m->body,
                'at' => $m->sent_at?->toIso8601String(),
            ]),
        ]]);
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'thread_id' => ['nullable', 'integer', 'exists:chat_threads,id'],
            'subject' => ['nullable', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:2000'],
            'order_ref' => ['nullable', 'string', 'max:50'],
        ]);

        $thread = isset($validated['thread_id'])
            ? ChatThread::findOrFail($validated['thread_id'])
            : ChatThread::create(array_merge(
                [
                    'audience' => 'customer',
                    'subject' => $validated['subject'] ?? 'Support request',
                    'status' => 'open',
                ],
                $this->linksFor($validated['order_ref'] ?? null),
            ));

        abort_unless($thread->status === 'open', 422, 'Thread is closed.');

        $message = $thread->messages()->create([
            'sender_ref' => 'customer',
            'body' => $validated['message'],
            'sent_at' => now(),
        ]);

        $thread->update(['last_message' => substr($validated['message'], 0, 500)]);

        return response()->json(['data' => [
            'thread_id' => $thread->id, 'message_id' => $message->id,
        ]], 201);
    }

    /**
     * Resolve an order/booking number to workforce links so vendor/driver/
     * provider inboxes pick the thread up. Unknown refs stay unlinked.
     */
    protected function linksFor(?string $ref): array
    {
        if (! $ref) {
            return [];
        }

        $links = ['order_ref' => $ref];

        $order = Order::where('number', $ref)->first();

        if ($order) {
            return $links + ['vendor_id' => $order->vendor_id, 'driver_id' => $order->driver_id];
        }

        foreach (['parcel' => ParcelOrder::class, 'rental' => RentalOrder::class, 'ride' => Ride::class] as $job) {
            $row = $job::where('number', $ref)->first();

            if ($row) {
                return $links + ['driver_id' => $row->driver_id];
            }
        }

        $booking = ProviderBooking::where('number', $ref)->first();

        if ($booking) {
            return $links + ['provider_id' => $booking->provider_id];
        }

        return $links;
    }
}
