<?php

namespace App\Payments;

use Illuminate\Support\Manager;

/*
 * DDE-Mart Admin — gateway manager (original).
 * Resolves `stripe|razorpay|paypal|paytm|flutterwave` drivers; bank/cash are
 * manual methods with no driver. Unconfigured drivers throw DriverNotConfigured.
 */
class PaymentManager extends Manager
{
    public function getDefaultDriver()
    {
        return 'stripe';
    }

    public function available(): array
    {
        $ready = [];

        foreach (['stripe', 'razorpay', 'paypal', 'paytm', 'flutterwave'] as $driver) {
            if ($this->driver($driver)->isConfigured()) {
                $ready[] = $driver;
            }
        }

        return $ready;
    }

    protected function createStripeDriver(): PaymentGateway
    {
        return new StripeDriver(config('payments.drivers.stripe', []));
    }

    protected function createRazorpayDriver(): PaymentGateway
    {
        return new RazorpayDriver(config('payments.drivers.razorpay', []));
    }

    protected function createPaypalDriver(): PaymentGateway
    {
        return new PaypalDriver(config('payments.drivers.paypal', []));
    }

    protected function createPaytmDriver(): PaymentGateway
    {
        return new PaytmDriver(config('payments.drivers.paytm', []));
    }

    protected function createFlutterwaveDriver(): PaymentGateway
    {
        return new FlutterwaveDriver(config('payments.drivers.flutterwave', []));
    }
}
