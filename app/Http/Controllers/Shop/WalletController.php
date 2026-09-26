<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\WalletEntry;
use App\Services\ShopPayments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/*
 * DDE-Mart storefront — wallet (original). Balance + ledger plus gateway
 * top-up (redirect flows shared with checkout, verified on return).
 */
class WalletController extends Controller
{
    protected function balanceOf($customer): float
    {
        return (float) WalletEntry::where('owner_type', 'customer')
            ->where(function ($q) use ($customer) {
                $q->where('owner_ref', $customer->phone);

                if ($customer->legacy_id) {
                    $q->orWhere('owner_ref', $customer->legacy_id);
                }
            })->sum('amount');
    }

    public function index(ShopPayments $payments): View
    {
        $customer = Auth::guard('customer')->user();

        $entries = WalletEntry::where('owner_type', 'customer')
            ->where(function ($q) use ($customer) {
                $q->where('owner_ref', $customer->phone);

                if ($customer->legacy_id) {
                    $q->orWhere('owner_ref', $customer->legacy_id);
                }
            })->orderByDesc('id')->paginate(15);

        $methods = array_values(array_filter(
            $payments->methods(),
            fn ($m) => ! in_array($m['key'], ['cod', 'wallet'], true),
        ));

        return view('shop.wallet', [
            'balance' => $this->balanceOf($customer),
            'entries' => $entries,
            'methods' => $methods,
        ]);
    }

    /** Start a gateway top-up; the amount rides in session until verified. */
    public function topup(Request $request, ShopPayments $payments): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:100000'],
            'method' => ['required', 'string', 'in:stripe,razorpay,paypal'],
        ]);

        $quote = [
            'lines' => [[
                'name' => 'Wallet top-up',
                'subtotal' => round((float) $validated['amount'], 2),
            ]],
            'subtotal' => round((float) $validated['amount'], 2),
            'total' => round((float) $validated['amount'], 2),
        ];

        try {
            [$url, $reference] = $payments->start(
                $validated['method'],
                $quote,
                route('shop.wallet.callback', ['method' => $validated['method']]),
                route('shop.wallet', ['cancelled' => 1]),
            );
        } catch (\App\Payments\DriverNotConfigured $e) {
            return redirect()->route('shop.wallet')->with('error', $e->getMessage());
        }

        session(['shop.topup' => [
            'method' => $validated['method'],
            'reference' => $reference,
            'amount' => round((float) $validated['amount'], 2),
        ]]);

        return redirect()->away($url);
    }

    /** Gateway return: verify, then credit once (idempotent-ish). */
    public function callback(Request $request, string $method, ShopPayments $payments): RedirectResponse
    {
        $pending = session('shop.topup');

        if (! $pending || ($pending['method'] ?? null) !== $method) {
            return redirect()->route('shop.wallet')->with('error', 'Top-up session expired.');
        }

        $reference = (string) ($request->input('session_id') ?? $request->input('razorpay_payment_link_id') ?? $request->input('token') ?? '');

        $paid = match ($method) {
            'stripe' => $payments->verifyStripe($reference),
            'razorpay' => $payments->verifyRazorpay($reference),
            'paypal' => $payments->verifyPaypal($reference),
            default => false,
        };

        if (! $paid) {
            return redirect()->route('shop.wallet')->with('error', 'Payment not confirmed. No charge was applied to your wallet.');
        }

        $customer = Auth::guard('customer')->user();

        $entry = WalletEntry::firstOrCreate(
            ['note' => "Top-up {$pending['reference']}"],
            [
                'owner_type' => 'customer',
                'owner_ref' => $customer->phone,
                'amount' => $pending['amount'],
                'kind' => 'topup',
                'method' => $method,
                'status' => 'success',
                'occurred_at' => now(),
            ],
        );

        session()->forget('shop.topup');

        return redirect()->route('shop.wallet')
            ->with('success', "Wallet credited with {$entry->amount}.");
    }
}
