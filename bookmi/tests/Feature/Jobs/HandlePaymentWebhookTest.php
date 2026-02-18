<?php

namespace Tests\Feature\Jobs;

use App\Enums\BookingStatus;
use App\Enums\EscrowStatus;
use App\Enums\TransactionStatus;
use App\Events\PaymentReceived;
use App\Jobs\HandlePaymentWebhook;
use App\Models\BookingRequest;
use App\Models\EscrowHold;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class HandlePaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────

    private function createProcessingTransaction(string $reference = 'pstk_ref_001'): Transaction
    {
        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        return Transaction::create([
            'booking_request_id' => $booking->id,
            'payment_method'     => 'orange_money',
            'amount'             => $booking->total_amount,
            'currency'           => 'XOF',
            'gateway'            => 'paystack',
            'status'             => TransactionStatus::Processing->value,
            'idempotency_key'    => $reference,
            'gateway_reference'  => $reference,
            'initiated_at'       => now(),
        ]);
    }

    private function chargeData(string $reference = 'pstk_ref_001'): array
    {
        return ['reference' => $reference, 'status' => 'success', 'amount' => 11_500_000];
    }

    // ── charge.success ────────────────────────────────────────────────────

    public function test_charge_success_marks_transaction_succeeded_and_booking_paid(): void
    {
        Event::fake([PaymentReceived::class]);

        $transaction = $this->createProcessingTransaction();

        (new HandlePaymentWebhook('charge.success', $this->chargeData()))->handle();

        $this->assertDatabaseHas('transactions', [
            'id'     => $transaction->id,
            'status' => TransactionStatus::Succeeded->value,
        ]);

        // L2 fix: verify completed_at is set (NFR32 — traçabilité)
        $this->assertNotNull(
            Transaction::find($transaction->id)->completed_at,
            'completed_at doit être renseigné après charge.success'
        );

        $this->assertDatabaseHas('booking_requests', [
            'id'     => $transaction->booking_request_id,
            'status' => BookingStatus::Paid->value,
        ]);
    }

    public function test_charge_success_creates_escrow_hold_with_48h_release(): void
    {
        Event::fake([PaymentReceived::class]);

        $transaction = $this->createProcessingTransaction();
        $booking     = BookingRequest::find($transaction->booking_request_id);

        (new HandlePaymentWebhook('charge.success', $this->chargeData()))->handle();

        $this->assertDatabaseHas('escrow_holds', [
            'transaction_id'     => $transaction->id,
            'booking_request_id' => $booking->id,
            'total_amount'       => $booking->total_amount,
            'status'             => EscrowStatus::Held->value,
        ]);

        $escrow = EscrowHold::where('transaction_id', $transaction->id)->firstOrFail();
        $this->assertNotNull($escrow->release_scheduled_at);
        // Doit être ~48h dans le futur (on tolère ±1h pour l'exécution des tests)
        $diffHours = now()->diffInHours($escrow->release_scheduled_at, absolute: true);
        $this->assertGreaterThanOrEqual(47, $diffHours);
        $this->assertLessThanOrEqual(49, $diffHours);
    }

    public function test_charge_success_dispatches_payment_received_event(): void
    {
        Event::fake([PaymentReceived::class]);

        $this->createProcessingTransaction();

        (new HandlePaymentWebhook('charge.success', $this->chargeData()))->handle();

        Event::assertDispatched(PaymentReceived::class);
    }

    /** NFR35 — idempotency: même webhook reçu deux fois → un seul escrow, un seul événement. */
    public function test_charge_success_is_idempotent_when_webhook_received_twice(): void
    {
        Event::fake([PaymentReceived::class]);

        $this->createProcessingTransaction();

        $job = new HandlePaymentWebhook('charge.success', $this->chargeData());
        $job->handle();
        $job->handle(); // second appel — doit être un no-op

        $this->assertDatabaseCount('escrow_holds', 1);
        Event::assertDispatchedTimes(PaymentReceived::class, 1);
    }

    public function test_charge_success_with_missing_reference_is_ignored(): void
    {
        Event::fake([PaymentReceived::class]);

        (new HandlePaymentWebhook('charge.success', []))->handle();

        $this->assertDatabaseCount('escrow_holds', 0);
        Event::assertNotDispatched(PaymentReceived::class);
    }

    public function test_charge_success_with_unknown_reference_is_ignored(): void
    {
        Event::fake([PaymentReceived::class]);

        (new HandlePaymentWebhook('charge.success', ['reference' => 'unknown_xyz']))->handle();

        $this->assertDatabaseCount('escrow_holds', 0);
        Event::assertNotDispatched(PaymentReceived::class);
    }

    // ── charge.failed ─────────────────────────────────────────────────────

    public function test_charge_failed_marks_transaction_failed(): void
    {
        $transaction = $this->createProcessingTransaction();

        (new HandlePaymentWebhook('charge.failed', $this->chargeData()))->handle();

        $this->assertDatabaseHas('transactions', [
            'id'     => $transaction->id,
            'status' => TransactionStatus::Failed->value,
        ]);
    }

    public function test_charge_failed_is_idempotent(): void
    {
        $transaction = $this->createProcessingTransaction();

        $job = new HandlePaymentWebhook('charge.failed', $this->chargeData());
        $job->handle();
        $job->handle(); // no-op

        $this->assertDatabaseHas('transactions', [
            'id'     => $transaction->id,
            'status' => TransactionStatus::Failed->value,
        ]);
    }

    // L3 fix: charge.failed avec référence manquante
    public function test_charge_failed_with_missing_reference_is_ignored(): void
    {
        (new HandlePaymentWebhook('charge.failed', []))->handle();

        $this->assertDatabaseCount('transactions', 0); // rien n'a été créé
    }

    // ── unknown event ─────────────────────────────────────────────────────

    public function test_unknown_event_is_silently_ignored(): void
    {
        Event::fake([PaymentReceived::class]);

        $this->createProcessingTransaction();

        (new HandlePaymentWebhook('refund.processed', $this->chargeData()))->handle();

        // La transaction reste en processing
        $this->assertDatabaseMissing('transactions', ['status' => TransactionStatus::Succeeded->value]);
        $this->assertDatabaseCount('escrow_holds', 0);
        Event::assertNotDispatched(PaymentReceived::class);
    }
}
