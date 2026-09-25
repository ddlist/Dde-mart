<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiftOrder;
use App\Models\PlanSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — subscription history + gift ledger (original controller).
 * Both read-only except gift redeem.
 */
class SubscriptionLedgerController extends Controller
{
    public function subscriptions(Request $request): View
    {
        $subscriptions = PlanSubscription::with(['plan'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.finance.subscriptions', compact('subscriptions'));
    }

    public function gifts(Request $request): View
    {
        $gifts = GiftOrder::with(['gift'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.finance.gifts', compact('gifts'));
    }

    public function redeem(GiftOrder $gift): RedirectResponse
    {
        if ($gift->status !== 'active') {
            return redirect()->route('admin.gift-orders.index')
                ->with('error', 'Only active codes can be redeemed.');
        }

        $gift->update(['status' => 'redeemed']);

        return redirect()->route('admin.gift-orders.index')->with('success', "Code {$gift->code} redeemed.");
    }
}
