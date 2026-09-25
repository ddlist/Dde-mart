<?php

namespace App\Payments;

use App\Models\PayoutRequest;
use Razorpay\Api\Api;
use Throwable;

/*
 * DDE-Mart Admin — Razorpay transfer driver (original).
 * Expects method_details.account_id (linked account). Amounts in paise.
 */
class RazorpayDriver implements PaymentGateway
{
    public function __construct(protected array $config) {}

    public function isConfigured(): bool
    {
        return ! empty($this->config['key']) && ! empty($this->config['secret']);
    }

    public function payout(PayoutRequest $payout): PaymentResult
    {
        if (! $this->isConfigured()) {
            throw new DriverNotConfigured('Razorpay key/secret are not configured.');
        }

        $accountId = $payout->method_details['account_id'] ?? null;

        if (! $accountId) {
            return new PaymentResult(false, 'Razorpay account_id is required in method details.');
        }

        try {
            $api = new Api($this->config['key'], $this->config['secret']);

            $response = $api->transfer->create([
                'account' => $accountId,
                'amount' => (int) bcmul((string) $payout->amount, '100'),
                'currency' => 'INR',
            ]);

            $data = json_decode(json_encode($response), true);

            if (isset($data['status'], $data['id'])) {
                return new PaymentResult(true, 'Payout processed.', 'processing', $data);
            }

            return new PaymentResult(false, $data['error']['description'] ?? 'Transfer failed.', 'failed', $data);
        } catch (Throwable $e) {
            return new PaymentResult(false, $e->getMessage(), 'failed');
        }
    }
}
