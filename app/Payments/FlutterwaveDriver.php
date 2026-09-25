<?php

namespace App\Payments;

use App\Models\PayoutRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

/*
 * DDE-Mart Admin — Flutterwave transfer driver (original, REST, verified TLS).
 * Expects method_details {bank_code, account_number}.
 */
class FlutterwaveDriver implements PaymentGateway
{
    public function __construct(protected array $config) {}

    public function isConfigured(): bool
    {
        return ! empty($this->config['secret']);
    }

    public function payout(PayoutRequest $payout): PaymentResult
    {
        if (! $this->isConfigured()) {
            throw new DriverNotConfigured('Flutterwave secret key is not configured.');
        }

        $details = $payout->method_details ?? [];

        if (empty($details['bank_code']) || empty($details['account_number'])) {
            return new PaymentResult(false, 'Flutterwave bank_code + account_number are required in method details.');
        }

        try {
            $response = Http::withToken($this->config['secret'])
                ->post('https://api.flutterwave.com/v3/transfers', [
                    'account_bank' => $details['bank_code'],
                    'account_number' => $details['account_number'],
                    'amount' => (float) $payout->amount,
                    'currency' => 'NGN',
                    'reference' => "payout_{$payout->id}_".time(),
                    'narration' => "Payout #{$payout->id}",
                ])
                ->throw()
                ->json();

            if (($response['status'] ?? null) === 'success') {
                return new PaymentResult(true, 'Payout accepted.', 'processing', $response);
            }

            return new PaymentResult(false, $response['message'] ?? 'Flutterwave rejected the transfer.', 'failed', (array) $response);
        } catch (Throwable $e) {
            return new PaymentResult(false, $e->getMessage(), 'failed');
        }
    }
}
