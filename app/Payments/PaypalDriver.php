<?php

namespace App\Payments;

use App\Models\PayoutRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

/*
 * DDE-Mart Admin — PayPal payouts driver (original, REST, verified TLS).
 * Expects method_details.email (recipient). Two-step: OAuth token, then batch.
 */
class PaypalDriver implements PaymentGateway
{
    public function __construct(protected array $config) {}

    public function baseUrl(): string
    {
        return ($this->config['mode'] ?? 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->config['client_id']) && ! empty($this->config['client_secret']);
    }

    public function payout(PayoutRequest $payout): PaymentResult
    {
        if (! $this->isConfigured()) {
            throw new DriverNotConfigured('PayPal client credentials are not configured.');
        }

        $email = $payout->method_details['email'] ?? null;

        if (! $email) {
            return new PaymentResult(false, 'PayPal recipient email is required in method details.');
        }

        try {
            $token = Http::asForm()
                ->withBasicAuth($this->config['client_id'], $this->config['client_secret'])
                ->post("{$this->baseUrl()}/v1/oauth2/token", ['grant_type' => 'client_credentials'])
                ->throw()
                ->json('access_token');

            $response = Http::withToken($token)
                ->post("{$this->baseUrl()}/v1/payments/payouts", [
                    'sender_batch_header' => [
                        'sender_batch_id' => "payout_{$payout->id}_".time(),
                        'email_subject' => 'You have a payout!',
                    ],
                    'items' => [[
                        'recipient_type' => 'EMAIL',
                        'receiver' => $email,
                        'amount' => [
                            'currency' => strtoupper(config('payments.default_currency', 'USD')),
                            'value' => number_format($payout->amount, 2, '.', ''),
                        ],
                        'sender_item_id' => (string) $payout->id,
                    ]],
                ])
                ->throw()
                ->json();

            $batchId = $response['batch_header']['payout_batch_id'] ?? null;

            if ($batchId) {
                return new PaymentResult(true, 'Payout batch accepted.', 'processing', $response);
            }

            return new PaymentResult(false, 'PayPal rejected the batch.', 'failed', (array) $response);
        } catch (Throwable $e) {
            return new PaymentResult(false, $e->getMessage(), 'failed');
        }
    }
}
