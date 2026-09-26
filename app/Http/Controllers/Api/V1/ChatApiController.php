<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatThread;
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
        ]);

        $thread = isset($validated['thread_id'])
            ? ChatThread::findOrFail($validated['thread_id'])
            : ChatThread::create([
                'audience' => 'customer',
                'subject' => $validated['subject'] ?? 'Support request',
                'status' => 'open',
            ]);

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
}
