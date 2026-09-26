<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatThread;
use App\Models\Complaint;
use App\Models\SosAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — support inbox (original controller).
 * Complaints (resolve/dismiss), SOS alerts (resolve-only), chat threads (staff reply).
 */
class SupportController extends Controller
{
    public function complaints(Request $request): View
    {
        $complaints = Complaint::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.support.complaints', compact('complaints'));
    }

    public function complaintResolve(Request $request, Complaint $complaint): RedirectResponse
    {
        $to = $request->validate(['to' => ['required', 'in:resolved,dismissed']])['to'];
        $complaint->update(['status' => $to]);

        return redirect()->route('admin.complaints.index')->with('success', "Complaint {$to}.");
    }

    public function sos(Request $request): View
    {
        $alerts = SosAlert::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.support.sos', compact('alerts'));
    }

    public function sosResolve(SosAlert $alert): RedirectResponse
    {
        $alert->update(['status' => 'resolved']);

        return redirect()->route('admin.sos.index')->with('success', 'Alert resolved.');
    }

    public function chats(Request $request): View
    {
        $threads = ChatThread::withCount('messages')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.support.chats', compact('threads'));
    }

    public function chatShow(ChatThread $thread): View
    {
        $thread->load(['messages']);

        return view('admin.support.chat-show', ['thread' => $thread]);
    }

    public function chatClose(ChatThread $thread): RedirectResponse
    {
        $thread->update(['status' => 'closed']);

        return redirect()->route('admin.chats.show', $thread)->with('success', 'Thread closed.');
    }

    public function chatReply(Request $request, ChatThread $thread): RedirectResponse
    {
        abort_unless($thread->status === 'open', 422, 'Thread is closed.');

        $validated = $request->validate(['message' => ['required', 'string', 'max:2000']]);

        $thread->messages()->create([
            'sender_ref' => 'admin:'.$request->user()->id,
            'body' => $validated['message'],
            'sent_at' => now(),
        ]);
        $thread->update(['last_message' => substr($validated['message'], 0, 500)]);

        return redirect()->route('admin.chats.show', $thread)->with('success', 'Reply sent.');
    }
}
