<?php

namespace App\Payments;

use App\Models\PayoutRequest;

/*
 * DDE-Mart Admin — payout gateway seam (original contract).
 * Replaces the gateway code embedded in legacy UserController (per-request secrets,
 * raw curl). D8c will add verified-HTTP drivers (Stripe/PayPal/Razorpay/…) wired
 * through config/payments.php with credentials from env — never from request input.
 */
interface PaymentGateway
{
    /** Credentials present (no network call). */
    public function isConfigured(): bool;

    /** Execute a payout. Returns a normalized result array. */
    public function payout(PayoutRequest $payout): PaymentResult;
}
