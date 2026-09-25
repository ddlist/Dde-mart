<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
