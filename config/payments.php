<?php

/*
 * DDE-Mart Admin — payout gateway config (original).
 * All secrets from env. A driver with missing credentials reports unconfigured
 * instead of failing at send time. No other gateways are wired (payfast/paystack/
 * mercadopago/xendit/midtrans/orangepay arrive only with real merchant accounts).
 */
return [
    'default_currency' => env('PAYOUT_CURRENCY', 'USD'),

    'drivers' => [
        'stripe' => [
            'secret' => env('STRIPE_SECRET'),
        ],
        'razorpay' => [
            'key' => env('RAZORPAY_KEY'),
            'secret' => env('RAZORPAY_SECRET'),
        ],
        'paypal' => [
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox|live
        ],
        'paytm' => [
            'merchant_id' => env('PAYTM_MERCHANT_ID'),
            'merchant_key' => env('PAYTM_MERCHANT_KEY'),
            'env' => env('PAYTM_ENV', 'staging'), // staging|production
        ],
        'flutterwave' => [
            'secret' => env('FLW_SECRET_KEY'),
        ],
    ],
];
