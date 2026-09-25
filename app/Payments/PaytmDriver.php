<?php

namespace App\Payments;

use App\Models\PayoutRequest;
use Illuminate\Support\Facades\Http;
use paytm\paytmchecksum\PaytmChecksum;
use Throwable;

/*
 * DDE-Mart Admin — Paytm transfer driver (original, REST, verified TLS).
 * Checksum via paytm/paytmchecksum; staging vs production hosts.
 */
class PaytmDriver implements PaymentGateway
{
    public function __construct(protected array $config) {}

    public function baseUrl(): string
    {
        return ($this->config['env'] ?? 'staging') === 'production'
            ? 'https://securegw.paytm.in'
            : 'https://securegw-stage.paytm.in';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->config['merchant_id']) && ! empty($this->config['merchant_key']);
    }

    public function payout(PayoutRequest $payout): PaymentResult
    {
        if (! $this->isConfigured()) {
            throw new DriverNotConfigured('Paytm merchant credentials are not configured.');
        }

        $account = $payout->method_details['account'] ?? null;

        if (! $account) {
            return new PaymentResult(false, 'Paytm beneficiary account is required in method details.');
        }

        try {
            $body = [
                'mid' => $this->config['merchant_id'],
                'orderId' => "payout_{$payout->id}_".time(),
                'amount' => number_format($payout->amount, 2, '.', ''),
                'beneficiary' => $account,
            ];

            $checksum = PaytmChecksum::generateSignature(json_encode($body), $this->config['merchant_key']);

            $response = Http::post("{$this->baseUrl()}/disbursement/api/transfer", [
                'head' => ['signature' => $checksum],
                'body' => $body,
            ])->throw()->json();

            if (($response['body']['resultInfo']['resultStatus'] ?? null) === 'S') {
                return new PaymentResult(true, 'Payout accepted.', 'processing', $response);
            }

            return new PaymentResult(
                false,
                $response['body']['resultInfo']['resultMsg'] ?? 'Paytm rejected the transfer.',
                'failed',
                $response,
            );
        } catch (Throwable $e) {
            return new PaymentResult(false, $e->getMessage(), 'failed');
        }
    }
}
