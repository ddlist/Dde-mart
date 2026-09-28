<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — wallet ledger + referrals (original controller, read-only).
 * Entries are system-written; manual adjustments arrive with the finance follow-up.
 */
class WalletController extends Controller
{
    public function entries(Request $request): View
    {
        $entries = \App\Models\WalletEntry::query()
            ->when($request->filled('search'), fn ($q) => $q
                ->where('note', 'like', '%'.$request->input('search').'%')
                ->orWhere('owner_ref', 'like', '%'.$request->input('search').'%')
                ->orWhere('order_ref', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('type'), fn ($q) => $q->where('owner_type', $request->input('type')))
            ->orderByDesc('id')
            ->paginate(20)->withQueryString();

        $totals = \App\Models\WalletEntry::selectRaw(
            "owner_type, COALESCE(SUM(amount),0) as balance"
        )->groupBy('owner_type')->pluck('balance', 'owner_type')->all();

        return view('admin.finance.wallet', compact('entries', 'totals'));
    }

    /** Per-role payment summary: wallet balance, pending and paid payouts. */
    public function summary(): View
    {
        $roles = ['driver', 'vendor', 'owner', 'provider', 'customer'];
        $summary = [];

        foreach ($roles as $role) {
            $summary[$role] = [
                'wallet' => (float) \App\Models\WalletEntry::where('owner_type', $role)->sum('amount'),
                'pending' => (float) \App\Models\PayoutRequest::where('requester_type', $role)
                    ->where('status', 'pending')->sum('amount'),
                'paid' => (float) \App\Models\PayoutRequest::where('requester_type', $role)
                    ->where('status', 'paid')->sum('amount'),
            ];
        }

        return view('admin.finance.summary', ['summary' => $summary]);
    }

    /** Manual wallet adjustment (credit or debit) for any role ledger. */
    public function adjustStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'owner_type' => ['required', 'string', 'in:driver,vendor,owner,provider,customer'],
            'owner_ref' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'direction' => ['required', 'in:credit,debit'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        \App\Models\WalletEntry::create([
            'owner_type' => $validated['owner_type'],
            'owner_ref' => $validated['owner_ref'],
            'amount' => $validated['direction'] === 'debit' ? -abs($validated['amount']) : abs($validated['amount']),
            'kind' => 'adjustment',
            'method' => 'manual',
            'status' => 'success',
            'note' => $validated['note'] ?? 'Manual adjustment by staff',
            'occurred_at' => now(),
        ]);

        return redirect()->route('admin.wallet.index')
            ->with('success', 'Wallet adjusted.');
    }

    public function referrals(Request $request): View
    {
        $referrals = \App\Models\Referral::query()
            ->when($request->filled('search'), fn ($q) => $q
                ->where('code', 'like', '%'.$request->input('search').'%')
                ->orWhere('referrer_ref', 'like', '%'.$request->input('search').'%'))
            ->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('admin.finance.referrals', compact('referrals'));
    }
}
