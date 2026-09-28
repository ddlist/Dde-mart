<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduledNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — scheduled pushes (original controller).
 * Queue a broadcast for a future time; the schedule:send command delivers
 * due items and logs outcomes. Cancel only while still scheduled.
 */
class ScheduledNotificationController extends Controller
{
    public function index(): View
    {
        $items = ScheduledNotification::orderBy('send_at')
            ->paginate(15);

        return view('admin.content.scheduled.index', ['items' => $items]);
    }

    public function create(): View
    {
        return view('admin.content.scheduled.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'audience' => ['required', 'string', 'in:customer,driver,vendor,provider,worker,all'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:1000'],
            'send_at' => ['required', 'date', 'after:now'],
        ]);

        ScheduledNotification::create($validated + [
            'status' => 'scheduled',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.scheduled.index')
            ->with('success', 'Push scheduled.');
    }

    public function destroy(ScheduledNotification $scheduledNotification): RedirectResponse
    {
        abort_unless($scheduledNotification->status === 'scheduled', 422, 'Only scheduled items can be cancelled.');

        $scheduledNotification->update(['status' => 'cancelled']);

        return redirect()->route('admin.scheduled.index')
            ->with('success', 'Scheduled push cancelled.');
    }
}
