<?php

namespace App\Payments;

use App\Models\PayoutRequest;
use Stripe\StripeClient;
use Throwable;

/*
 * DDE-Mart Admin — Stripe transfer driver (original).
 * Expects method_details.account_id (connected account). Verified TLS via SDK.
 */
class StripeDriver implements PaymentGateway
{
    public function __construct(protected array $config) {}

    public function isConfigured(): bool
    {
        return ! empty($this->config['secret']);
    }

    public function payout(PayoutRequest $payout): PaymentResult
    {
        if (! $this->isConfigured()) {
            throw new DriverNotConfigured('Stripe secret is not configured.');
        }

        $accountId = $payout->method_details['account_id'] ?? null;

        if (! $accountId) {
            return new PaymentResult(false, 'Stripe account_id is required in method details.');
        }

        try {
            $stripe = new StripeClient($this->config['secret']);

            $response = $stripe->transfers->create([
                'amount' => (int) bcmul((string) $payout->amount, '100'),
                'currency' => strtolower(config('payments.default_currency', 'USD')),
                'destination' => $accountId,
                'transfer_group' => "payout_{$payout->id}",
            ]);

            $data = $response->toArray();

            if (isset($data['id'], $data['balance_transaction'])) {
                return new PaymentResult(true, 'Payout processed.', 'success', $data);
            }

            return new PaymentResult(false, "No such destination: '{$accountId}'.", 'failed', $data);
        } catch (Throwable $e) {
            return new PaymentResult(false, $e->getMessage(), 'failed');
        }
    }
}
