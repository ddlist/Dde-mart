<?php

namespace App\Payments;

/*
 * DDE-Mart Admin — normalized gateway result (original value object).
 */
class PaymentResult
{
    public function __construct(
        public bool $success,
        public string $message,
        public string $status = 'pending', // pending|processing|success|failed
        public array $raw = [],
    ) {}
}
