<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentMethod;
use App\Enums\PayoutStatus;
use App\Exceptions\PaymentException;
use App\Models\EscrowHold;
use App\Models\Payout;
use App\Models\TalentProfile;
use Illuminate\Support\Facades\DB;

class PayoutService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
    ) {
    }

    /**
     * Process a talent payout for a released escrow hold.
     *
     * Flow:
     * 1. Load talent profile and validate payout method is configured.
     * 2. Create a Paystack Transfer Recipient for this payout (or re-use if cached).
     * 3. Initiate the transfer.
     * 4. Create the Payout record with gateway reference.
     *
     * @throws PaymentException if gateway fails or payout method not configured
     */
    public function processPayout(EscrowHold $hold): Payout
    {
        // M1 fix: eager-load via Eloquent, not via BelongsTo fluent builder
        $hold->loadMissing('bookingRequest');
        $booking       = $hold->bookingRequest;
        $talentProfile = TalentProfile::findOrFail($booking->talent_profile_id);

        if (! $talentProfile->payout_method || ! $talentProfile->payout_details) {
            throw PaymentException::unsupportedMethod('payout_method_not_configured');
        }

        $payoutMethod  = PaymentMethod::from($talentProfile->payout_method);
        $payoutDetails = (array) $talentProfile->payout_details;

        // ── Create transfer recipient (idempotent — re-use stored recipient_code) ──
        $recipientCode = $payoutDetails['recipient_code'] ?? null;

        if (! $recipientCode) {
            $recipientPayload = $this->buildRecipientPayload($payoutMethod, $payoutDetails, $talentProfile);
            $recipient        = $this->gateway->createTransferRecipient($recipientPayload);
            $recipientCode    = $recipient['recipient_code'];

            // Cache the recipient_code to avoid re-creating on retry
            $payoutDetails['recipient_code'] = $recipientCode;
            $talentProfile->update(['payout_details' => $payoutDetails]);
        }

        // ── Create Payout record (Pending) — idempotency guard ──
        $payout = DB::transaction(function () use ($hold, $talentProfile, $payoutMethod, $payoutDetails) {
            // H1 fix: prevent duplicate Payout records if job is retried
            $existing = Payout::where('escrow_hold_id', $hold->id)
                ->lockForUpdate()
                ->whereNotIn('status', [PayoutStatus::Failed->value])
                ->first();

            if ($existing) {
                return $existing;
            }

            return Payout::create([
                'talent_profile_id' => $talentProfile->id,
                'escrow_hold_id'    => $hold->id,
                'amount'            => $hold->cachet_amount,
                'payout_method'     => $payoutMethod->value,
                'payout_details'    => $payoutDetails,
                'gateway'           => $this->gateway->name(),
                'status'            => PayoutStatus::Pending->value,
            ]);
        });

        // ── Initiate transfer OUTSIDE DB transaction ──
        try {
            $transfer = $this->gateway->initiateTransfer([
                'source'    => 'balance',
                'amount'    => $hold->cachet_amount,
                'recipient' => $recipientCode,
                'reason'    => "Paiement BookMi — réservation #{$hold->booking_request_id}",
            ]);

            $payout->update([
                'status'            => PayoutStatus::Processing->value,
                'gateway_reference' => $transfer['transfer_code'] ?? null,
                'processed_at'      => now(),
            ]);
        } catch (PaymentException $e) {
            $payout->update(['status' => PayoutStatus::Failed->value]);
            throw $e;
        }

        return $payout->fresh();
    }

    /** @param array<string, mixed> $payoutDetails */
    private function buildRecipientPayload(
        PaymentMethod $method,
        array $payoutDetails,
        TalentProfile $talentProfile,
    ): array {
        $name = $talentProfile->stage_name ?? 'Talent BookMi';

        if ($method->isMobileMoney()) {
            return [
                'type'           => 'mobile_money',
                'name'           => $name,
                'account_number' => $payoutDetails['phone'] ?? '',
                'bank_code'      => $this->mobileMoneybankCode($method),
                'currency'       => 'XOF',
            ];
        }

        // Bank transfer
        return [
            'type'           => 'nuban',
            'name'           => $name,
            'account_number' => $payoutDetails['account_number'] ?? '',
            'bank_code'      => $payoutDetails['bank_code'] ?? '',
            'currency'       => 'XOF',
        ];
    }

    private function mobileMoneybankCode(PaymentMethod $method): string
    {
        return match ($method) {
            PaymentMethod::OrangeMoney => 'ORAGEMONEY',
            PaymentMethod::Wave        => 'WAVE',
            PaymentMethod::MtnMomo     => 'MTNCMRN',
            PaymentMethod::MoovMoney   => 'MOOV',
            default                    => 'ORAGEMONEY',
        };
    }
}
