<?php

namespace Tests\Feature\Jobs;

use App\Enums\EscrowStatus;
use App\Enums\PayoutStatus;
use App\Enums\TransactionStatus;
use App\Events\EscrowReleased;
use App\Jobs\ProcessPayout;
use App\Models\BookingRequest;
use App\Models\EscrowHold;
use App\Models\Payout;
use App\Models\TalentProfile;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessPayoutJobTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createEscrowHoldWithTalent(array $talentOverrides = []): array
    {
        $client  = User::factory()->create();
        $talent  = User::factory()->create();
        $profile = TalentProfile::factory()->create(array_merge([
            'user_id'        => $talent->id,
            'payout_method'  => 'orange_money',
            'payout_details' => ['phone' => '+22601234567'],
        ], $talentOverrides));

        $booking = BookingRequest::factory()->create([
            'client_id'         => $client->id,
            'talent_profile_id' => $profile->id,
            'status'            => 'confirmed',
        ]);

        $transaction = Transaction::create([
            'booking_request_id' => $booking->id,
            'payment_method'     => 'card',
            'amount'             => $booking->total_amount,
            'currency'           => 'XOF',
            'gateway'            => 'paystack',
            'status'             => TransactionStatus::Succeeded->value,
            'idempotency_key'    => 'payout-test-key-' . uniqid(),
            'initiated_at'       => now(),
        ]);

        $hold = EscrowHold::create([
            'transaction_id'       => $transaction->id,
            'booking_request_id'   => $booking->id,
            'cachet_amount'        => $booking->cachet_amount,
            'commission_amount'    => $booking->commission_amount,
            'total_amount'         => $booking->total_amount,
            'status'               => EscrowStatus::Released->value,
            'held_at'              => now()->subHours(49),
            'release_scheduled_at' => now()->subHours(1),
            'released_at'          => now(),
        ]);

        return [$profile, $booking, $hold, $transaction];
    }

    private function fakePaystackRecipient(string $recipientCode = 'RCP_test001'): void
    {
        Http::fake([
            'https://api.paystack.co/transferrecipient' => Http::response([
                'status'  => true,
                'message' => 'Transfer Recipient created successfully',
                'data'    => ['recipient_code' => $recipientCode, 'type' => 'mobile_money'],
            ], 200),
        ]);
    }

    private function fakePaystackTransfer(string $transferCode = 'TRF_test001'): void
    {
        Http::fake([
            'https://api.paystack.co/transferrecipient' => Http::response([
                'status' => true, 'data' => ['recipient_code' => 'RCP_test001'],
            ], 200),
            'https://api.paystack.co/transfer' => Http::response([
                'status'  => true,
                'message' => 'Transfer has been queued',
                'data'    => ['transfer_code' => $transferCode, 'status' => 'pending'],
            ], 200),
        ]);
    }

    // ── AC1: payout dispatched on EscrowReleased ──────────────────────────────

    public function test_escrow_released_event_dispatches_process_payout_job_with_delay(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        [$profile, $booking, $hold] = $this->createEscrowHoldWithTalent();

        EscrowReleased::dispatch($hold);

        \Illuminate\Support\Facades\Queue::assertPushed(ProcessPayout::class, function (ProcessPayout $job) use ($hold) {
            return $job->escrowHoldId === $hold->id;
        });
    }

    // ── AC2: payout processing ────────────────────────────────────────────────

    public function test_process_payout_job_creates_payout_and_initiates_transfer(): void
    {
        $this->fakePaystackTransfer('TRF_integration_001');

        [$profile, $booking, $hold] = $this->createEscrowHoldWithTalent();

        (new ProcessPayout($hold->id))->handle(app(\App\Services\PayoutService::class));

        $this->assertDatabaseHas('payouts', [
            'escrow_hold_id'    => $hold->id,
            'talent_profile_id' => $profile->id,
            'amount'            => $hold->cachet_amount,
            'status'            => PayoutStatus::Processing->value,
            'gateway_reference' => 'TRF_integration_001',
        ]);
    }

    public function test_process_payout_caches_recipient_code_on_talent_profile(): void
    {
        $this->fakePaystackTransfer();

        [$profile, $booking, $hold] = $this->createEscrowHoldWithTalent();

        (new ProcessPayout($hold->id))->handle(app(\App\Services\PayoutService::class));

        $profile->refresh();
        $this->assertArrayHasKey('recipient_code', $profile->payout_details);
    }

    public function test_process_payout_reuses_cached_recipient_code(): void
    {
        Http::fake([
            'https://api.paystack.co/transfer' => Http::response([
                'status' => true,
                'data'   => ['transfer_code' => 'TRF_cached_001', 'status' => 'pending'],
            ], 200),
        ]);

        [$profile, $booking, $hold] = $this->createEscrowHoldWithTalent([
            'payout_details' => ['phone' => '+22601234567', 'recipient_code' => 'RCP_cached'],
        ]);

        (new ProcessPayout($hold->id))->handle(app(\App\Services\PayoutService::class));

        $this->assertDatabaseHas('payouts', [
            'escrow_hold_id' => $hold->id,
            'status'         => PayoutStatus::Processing->value,
        ]);

        // createTransferRecipient was NOT called (only transfer)
        Http::assertSentCount(1);
    }

    public function test_process_payout_marks_failed_when_transfer_gateway_errors(): void
    {
        Http::fake([
            'https://api.paystack.co/transferrecipient' => Http::response([
                'status' => true, 'data' => ['recipient_code' => 'RCP_test_fail'],
            ], 200),
            'https://api.paystack.co/transfer' => Http::response(
                ['status' => false, 'message' => 'Insufficient balance'],
                400
            ),
        ]);

        [$profile, $booking, $hold] = $this->createEscrowHoldWithTalent();

        try {
            (new ProcessPayout($hold->id))->handle(app(\App\Services\PayoutService::class));
            $this->fail('Expected PaymentException was not thrown');
        } catch (\App\Exceptions\PaymentException $e) {
            $this->assertDatabaseHas('payouts', [
                'escrow_hold_id' => $hold->id,
                'status'         => PayoutStatus::Failed->value,
            ]);
        }
    }

    public function test_process_payout_skips_when_escrow_hold_not_found(): void
    {
        Http::preventStrayRequests();

        (new ProcessPayout(99999))->handle(app(\App\Services\PayoutService::class));

        $this->assertDatabaseCount('payouts', 0);

        Http::assertNothingSent();
    }

    public function test_process_payout_fails_when_payout_method_not_configured(): void
    {
        Http::preventStrayRequests();

        [$profile, $booking, $hold] = $this->createEscrowHoldWithTalent([
            'payout_method'  => null,
            'payout_details' => null,
        ]);

        $this->expectException(\App\Exceptions\PaymentException::class);

        (new ProcessPayout($hold->id))->handle(app(\App\Services\PayoutService::class));
    }
}
