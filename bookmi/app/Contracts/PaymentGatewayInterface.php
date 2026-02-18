<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Name of this gateway (e.g. 'paystack', 'cinetpay').
     */
    public function name(): string;

    /**
     * Initiate a charge (mobile money or card).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initiateCharge(array $payload): array;

    /**
     * Verify a transaction by its gateway reference.
     *
     * @return array<string, mixed>
     */
    public function verifyTransaction(string $reference): array;

    /**
     * Initiate a transfer (talent payout).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initiateTransfer(array $payload): array;
}
