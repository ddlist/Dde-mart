<?php

namespace App\Services;

use App\Payments\DriverNotConfigured;
use App\Payments\PaymentManager;
use Illuminate\Support\Facades\Http;
use Stripe\StripeClient;
use Throwable;

/*
 * DDE-Mart storefront — online charge starter (original service).
 * Redirect-based flows only (no card JS): Stripe Checkout, Razorpay Payment Links,
 * PayPal Orders. Each returns [redirect_url, reference] or throws DriverNotConfigured.
 * Verification happens on return via the same drivers/clients.
 */
class ShopPayments
{
    public function __construct(protected PaymentManager $manager) {}

    /** Payment methods offered at checkout: cod/wallet always, gateways if configured. */
    public function methods(): array
    {
        $methods = [
            ['key' => 'cod', 'label' => 'Cash on delivery'],
            ['key' => 'wallet', 'label' => 'Wallet balance'],
        ];

        $labels = ['stripe' => 'Card (Stripe)', 'razorpay' => 'Razorpay', 'paypal' => 'PayPal'];

        foreach ($this->manager->available() as $driver) {
            if (isset($labels[$driver])) {
                $methods[] = ['key' => $driver, 'label' => $labels[$driver]];
            }
        }

        return $methods;
    }

    public function start(string $method, array $quote, string $successUrl, string $cancelUrl): array
    {
        return match ($method) {
            'stripe' => $this->startStripe($quote, $successUrl, $cancelUrl),
            'razorpay' => $this->startRazorpay($quote, $successUrl),
            'paypal' => $this->startPaypal($quote, $successUrl, $cancelUrl),
            default => throw new DriverNotConfigured("No online flow for [{$method}]."),
        };
    }

    protected function startStripe(array $quote, string $successUrl, string $cancelUrl): array
    {
        $secret = config('payments.drivers.stripe.secret');

        if (! $secret) {
            throw new DriverNotConfigured('Stripe secret is not configured.');
        }

        $currency = strtolower(config('payments.default_currency', 'USD'));

        $items = array_map(fn ($line) => [
            'price_data' => [
                'currency' => $currency,
                'unit_amount' => (int) bcmul((string) $line['subtotal'], '100'),
                'product_data' => ['name' => substr($line['name'], 0, 120)],
            ],
            'quantity' => 1,
        ], $quote['lines']);

        // Stripe forbids negative line amounts: fold fees/tax into a fee line,
        // and spread discounts across the largest lines first.
        $adjust = round($quote['total'] - $quote['subtotal'], 2);

        if ($adjust > 0) {
            $items[] = [
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => (int) bcmul((string) $adjust, '100'),
                    'product_data' => ['name' => 'Fees & tax'],
                ],
                'quantity' => 1,
            ];
        } elseif ($adjust < 0) {
            $left = abs($adjust);

            foreach ($items as $i => $line) {
                if ($left <= 0) {
                    break;
                }

                $take = min($line['price_data']['unit_amount'], (int) bcmul((string) $left, '100'));
                $items[$i]['price_data']['unit_amount'] -= $take;
                $left = round($left - $take / 100, 2);
            }
        }

        $stripe = new StripeClient($secret);

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => $items,
            'success_url' => $successUrl.'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
        ]);

        return [$session->url, $session->id];
    }

    public function verifyStripe(string $sessionId): bool
    {
        $secret = config('payments.drivers.stripe.secret');

        if (! $secret) {
            return false;
        }

        try {
            $session = (new StripeClient($secret))->checkout->sessions->retrieve($sessionId);

            return $session->payment_status === 'paid';
        } catch (Throwable) {
            return false;
        }
    }

    protected function startRazorpay(array $quote, string $callbackUrl): array
    {
        $key = config('payments.drivers.razorpay.key');
        $secret = config('payments.drivers.razorpay.secret');

        if (! $key || ! $secret) {
            throw new DriverNotConfigured('Razorpay key/secret are not configured.');
        }

        $response = Http::withBasicAuth($key, $secret)->post('https://api.razorpay.com/v1/payment_links', [
            'amount' => (int) bcmul((string) $quote['total'], '100'),
            'currency' => 'INR',
            'description' => 'DDE-Mart order',
            'callback_url' => $callbackUrl,
            'callback_method' => 'get',
        ])->throw()->json();

        if (empty($response['short_url'])) {
            throw new DriverNotConfigured('Razorpay refused the payment link.');
        }

        return [$response['short_url'], $response['id']];
    }

    public function verifyRazorpay(string $linkId): bool
    {
        $key = config('payments.drivers.razorpay.key');
        $secret = config('payments.drivers.razorpay.secret');

        if (! $key || ! $secret) {
            return false;
        }

        try {
            $link = Http::withBasicAuth($key, $secret)
                ->get("https://api.razorpay.com/v1/payment_links/{$linkId}")
                ->throw()->json();

            return ($link['status'] ?? null) === 'paid';
        } catch (Throwable) {
            return false;
        }
    }

    protected function startPaypal(array $quote, string $successUrl, string $cancelUrl): array
    {
        $cfg = config('payments.drivers.paypal', []);
        $base = ($cfg['mode'] ?? 'sandbox') === 'live'
            ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        if (empty($cfg['client_id']) || empty($cfg['client_secret'])) {
            throw new DriverNotConfigured('PayPal credentials are not configured.');
        }

        $token = Http::asForm()
            ->withBasicAuth($cfg['client_id'], $cfg['client_secret'])
            ->post("{$base}/v1/oauth2/token", ['grant_type' => 'client_credentials'])
            ->throw()->json('access_token');

        $order = Http::withToken($token)->post("{$base}/v2/checkout/orders", [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => strtoupper(config('payments.default_currency', 'USD')),
                    'value' => number_format($quote['total'], 2, '.', ''),
                ],
            ]],
            'application_context' => ['return_url' => $successUrl, 'cancel_url' => $cancelUrl],
        ])->throw()->json();

        foreach ($order['links'] ?? [] as $link) {
            if (($link['rel'] ?? null) === 'approve') {
                return [$link['href'], $order['id']];
            }
        }

        throw new DriverNotConfigured('PayPal returned no approval link.');
    }

    public function verifyPaypal(string $orderId): bool
    {
        $cfg = config('payments.drivers.paypal', []);
        $base = ($cfg['mode'] ?? 'sandbox') === 'live'
            ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        if (empty($cfg['client_id']) || empty($cfg['client_secret'])) {
            return false;
        }

        try {
            $token = Http::asForm()
                ->withBasicAuth($cfg['client_id'], $cfg['client_secret'])
                ->post("{$base}/v1/oauth2/token", ['grant_type' => 'client_credentials'])
                ->throw()->json('access_token');

            $capture = Http::withToken($token)
                ->post("{$base}/v2/checkout/orders/{$orderId}/capture")
                ->throw()->json();

            return ($capture['status'] ?? null) === 'COMPLETED';
        } catch (Throwable) {
            return false;
        }
    }
}
