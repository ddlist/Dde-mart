<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use App\Models\GiftOrder;
use App\Models\WalletEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

/*
 * DDE-Mart storefront — gift cards (original).
 * Buy (wallet/COD) issues a code; redeeming credits the wallet.
 */
class GiftController extends Controller
{
    public function index(): View
    {
        $cards = GiftCard::where('is_active', true)->orderBy('amount')->get();

        $mine = GiftOrder::where('buyer_ref', Auth::guard('customer')->user()->phone)
            ->orderByDesc('id')->limit(10)->get();

        return view('shop.gifts', compact('cards', 'mine'));
    }

    public function buy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'gift_id' => ['required', 'integer', 'exists:gift_cards,id'],
            'payment_method' => ['required', 'string', 'in:cod,wallet'],
        ]);

        $card = GiftCard::findOrFail($validated['gift_id']);
        abort_unless($card->is_active, 422, 'Card unavailable.');

        $customer = Auth::guard('customer')->user();

        if ($validated['payment_method'] === 'wallet') {
            $balance = (float) WalletEntry::where('owner_type', 'customer')
                ->where('owner_ref', $customer->phone)->sum('amount');

            if ($balance < (float) $card->amount) {
                return redirect()->route('shop.gifts')->with('error', 'Insufficient wallet balance.');
            }

            WalletEntry::create([
                'owner_type' => 'customer', 'owner_ref' => $customer->phone,
                'amount' => -$card->amount, 'kind' => 'order', 'method' => 'wallet',
                'status' => 'success', 'note' => "Gift card {$card->title}",
                'occurred_at' => now(),
            ]);
        }

        $order = GiftOrder::create([
            'gift_id' => $card->id,
            'code' => 'GIFT-'.strtoupper(Str::random(8)),
            'buyer_ref' => $customer->phone,
            'amount' => $card->amount,
            'status' => 'active',
            'expires_at' => now()->addDays($card->expiry_days),
        ]);

        return redirect()->route('shop.gifts')->with('success', "Gift code {$order->code} issued.");
    }

    public function redeem(Request $request): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:50']]);

        $order = GiftOrder::where('code', strtoupper($validated['code']))->first();

        if (! $order || $order->status !== 'active') {
            return redirect()->route('shop.gifts')->with('error', 'Invalid or used code.');
        }

        if ($order->expires_at && $order->expires_at->isPast()) {
            $order->update(['status' => 'expired']);

            return redirect()->route('shop.gifts')->with('error', 'Code expired.');
        }

        $customer = Auth::guard('customer')->user();
        $order->update(['status' => 'redeemed']);

        WalletEntry::create([
            'owner_type' => 'customer', 'owner_ref' => $customer->phone,
            'amount' => $order->amount, 'kind' => 'topup', 'method' => 'gift',
            'status' => 'success', 'note' => "Redeemed {$order->code}",
            'occurred_at' => now(),
        ]);

        return redirect()->route('shop.gifts')->with('success', "Wallet credited {$order->amount}.");
    }
}
