<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendNotificationRequest;
use App\Models\Notification;
use App\Services\FcmSender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — broadcasts (original controller).
 * Every send is logged in `notifications` with its outcome, whether or not
 * FCM credentials are configured.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = Notification::with(['sender'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.content.notifications.index', compact('notifications'));
    }

    public function create(): View
    {
        return view('admin.content.notifications.form', ['fcm' => app(FcmSender::class)->isConfigured()]);
    }

    public function store(SendNotificationRequest $request, FcmSender $sender): RedirectResponse
    {
        $record = Notification::create([
            'audience' => $request->input('audience'),
            'subject' => $request->input('subject'),
            'message' => $request->input('message'),
            'status' => 'queued',
            'sent_by' => $request->user()->id,
        ]);

        $topic = $record->audience === 'all' ? 'customer' : $record->audience;
        $ok = $sender->sendToTopic($topic, $record->subject, $record->message);

        $record->update([
            'status' => $ok ? 'sent' : 'failed',
            'failure' => $ok ? null : 'FCM not configured or send failed',
        ]);

        return redirect()->route('admin.notifications.index')->with(
            $ok ? 'success' : 'error',
            $ok ? 'Broadcast sent.' : 'Saved, but FCM is not configured — nothing was delivered.'
        );
    }
}
