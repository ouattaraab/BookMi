<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Name of this gateway (e.g. 'paystack', 'cinetpay').
     */
    public function name(): string;

    /**
     * Initiate a charge (mobile money — POST /charge).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initiateCharge(array $payload): array;

    /**
     * Initialize a transaction (card / bank_transfer — POST /transaction/initialize).
     * Returns authorization_url for redirect-based 3D Secure flow.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>  { authorization_url, access_code, reference }
     */
    public function initializeTransaction(array $payload): array;

    /**
     * Verify a transaction by its gateway reference.
     *
     * @return array<string, mixed>
     */
    public function verifyTransaction(string $reference): array;

    /**
     * Submit OTP to complete a mobile money charge.
     *
     * @return array<string, mixed>
     */
    public function submitOtp(string $reference, string $otp): array;

    /**
     * Initiate a transfer (talent payout).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initiateTransfer(array $payload): array;
}
