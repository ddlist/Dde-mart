<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayoutMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — saved withdraw methods (original controller).
 * Staff-kept payout destinations per requester; apps read them later.
 */
class PayoutMethodController extends Controller
{
    public function index(Request $request): View
    {
        $methods = PayoutMethod::query()
            ->when($request->filled('search'), fn ($q) => $q
                ->where('requester_ref', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('type'), fn ($q) => $q->where('requester_type', $request->input('type')))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.finance.payout-methods', ['methods' => $methods]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'requester_type' => ['required', 'string', 'in:driver,vendor,owner,provider,customer'],
            'requester_ref' => ['required', 'string', 'max:100'],
            'method' => ['required', 'string', 'in:'.implode(',', PayoutMethod::METHODS)],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'bank_account' => ['nullable', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['is_default'])) {
            PayoutMethod::where('requester_type', $validated['requester_type'])
                ->where('requester_ref', $validated['requester_ref'])
                ->update(['is_default' => false]);
        }

        PayoutMethod::create([
            'requester_type' => $validated['requester_type'],
            'requester_ref' => $validated['requester_ref'],
            'method' => $validated['method'],
            'details' => array_filter([
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_account' => $validated['bank_account'] ?? null,
            ]),
            'is_default' => ! empty($validated['is_default']),
        ]);

        return redirect()->route('admin.payout-methods.index')
            ->with('success', 'Withdraw method saved.');
    }

    public function destroy(PayoutMethod $payoutMethod): RedirectResponse
    {
        $payoutMethod->delete();

        return redirect()->route('admin.payout-methods.index')
            ->with('success', 'Withdraw method removed.');
    }
}
