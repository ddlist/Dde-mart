<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Payments\DriverNotConfigured;
use App\Payments\PaymentManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/*
 * DDE-Mart Admin — payout request workflow (original controller).
 * Unifies the four legacy *-payout controllers. List + detail + transitions only:
 * pending → approved → paid, or pending → rejected. No deletes (money trail).
 * Live gateway execution is deferred to D8c (PaymentGateway seam exists).
 */
class PayoutRequestController extends Controller
{
    public function index(Request $request): View
    {
        $payouts = PayoutRequest::with(['handler'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('requester_type', $request->input('type')))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.finance.payouts.index', [
            'payouts' => $payouts,
            'statuses' => array_keys(PayoutRequest::TRANSITIONS),
            'types' => PayoutRequest::REQUESTERS,
        ]);
    }

    public function show(PayoutRequest $payout): View
    {
        $payout->load(['handler']);

        return view('admin.finance.payouts.show', [
            'payout' => $payout,
            'allowed' => PayoutRequest::TRANSITIONS[$payout->status] ?? [],
        ]);
    }

    public function transition(Request $request, PayoutRequest $payout): RedirectResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'string'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $payout->transition($validated['to'], $request->user(), $validated['admin_note'] ?? null);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('admin.payouts.show', $payout)->with('error', $e->getMessage());
        }

        return redirect()->route('admin.payouts.show', $payout)
            ->with('success', "Payout moved to {$validated['to']}.");
    }

    /**
     * Execute an approved payout through its gateway (D8c).
     * Success → paid; failure → back with the gateway message recorded.
     */
    public function execute(Request $request, PayoutRequest $payout, PaymentManager $manager): RedirectResponse
    {
        if ($payout->status !== 'approved') {
            return redirect()->route('admin.payouts.show', $payout)
                ->with('error', 'Only approved payouts can be executed.');
        }

        if (in_array($payout->method, ['bank', 'cash'], true)) {
            return redirect()->route('admin.payouts.show', $payout)
                ->with('error', 'Bank/cash payouts are manual — mark paid instead.');
        }

        try {
            $result = $manager->driver($payout->method)->payout($payout);
        } catch (DriverNotConfigured $e) {
            return redirect()->route('admin.payouts.show', $payout)->with('error', $e->getMessage());
        }

        if ($result->success) {
            $payout->transition('paid', $request->user(), "Gateway: {$result->message}");

            return redirect()->route('admin.payouts.show', $payout)
                ->with('success', "Gateway accepted the payout ({$result->status}).");
        }

        $payout->update(['admin_note' => trim(($payout->admin_note ? $payout->admin_note."\n" : '')."Gateway error: {$result->message}")]);

        return redirect()->route('admin.payouts.show', $payout)
            ->with('error', "Gateway refused: {$result->message}");
    }
}
