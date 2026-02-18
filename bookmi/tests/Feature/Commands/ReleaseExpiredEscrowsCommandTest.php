<?php

namespace Tests\Feature\Commands;

use App\Enums\BookingStatus;
use App\Enums\EscrowStatus;
use App\Enums\TransactionStatus;
use App\Events\EscrowReleased;
use App\Models\BookingRequest;
use App\Models\EscrowHold;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ReleaseExpiredEscrowsCommandTest extends TestCase
{
    use RefreshDatabase;

    private function createEscrowHold(BookingRequest $booking, string $status, \DateTimeInterface $releaseAt): EscrowHold
    {
        $transaction = Transaction::create([
            'booking_request_id' => $booking->id,
            'payment_method'     => 'card',
            'amount'             => $booking->total_amount,
            'currency'           => 'XOF',
            'gateway'            => 'paystack',
            'status'             => TransactionStatus::Succeeded->value,
            'idempotency_key'    => 'cmd-test-key-' . uniqid(),
            'initiated_at'       => now(),
        ]);

        return EscrowHold::create([
            'transaction_id'       => $transaction->id,
            'booking_request_id'   => $booking->id,
            'cachet_amount'        => $booking->cachet_amount,
            'commission_amount'    => $booking->commission_amount,
            'total_amount'         => $booking->total_amount,
            'status'               => $status,
            'held_at'              => now()->subHours(50),
            'release_scheduled_at' => $releaseAt,
        ]);
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    public function test_command_releases_expired_holds(): void
    {
        Event::fake([EscrowReleased::class]);

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->paid()->create(['client_id' => $client->id]);
        $hold    = $this->createEscrowHold($booking, EscrowStatus::Held->value, now()->subMinutes(1));

        $this->artisan('escrow:release-expired')
            ->assertSuccessful();

        $this->assertDatabaseHas('escrow_holds', [
            'id'     => $hold->id,
            'status' => EscrowStatus::Released->value,
        ]);
        $this->assertNotNull(EscrowHold::find($hold->id)->released_at);
        $this->assertDatabaseHas('booking_requests', [
            'id'     => $booking->id,
            'status' => BookingStatus::Confirmed->value,
        ]);

        Event::assertDispatched(EscrowReleased::class);
    }

    public function test_command_skips_future_holds(): void
    {
        Event::fake([EscrowReleased::class]);

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->paid()->create(['client_id' => $client->id]);
        $hold    = $this->createEscrowHold($booking, EscrowStatus::Held->value, now()->addHours(24));

        $this->artisan('escrow:release-expired')
            ->assertSuccessful();

        // Hold not yet expired — must remain held
        $this->assertDatabaseHas('escrow_holds', [
            'id'     => $hold->id,
            'status' => EscrowStatus::Held->value,
        ]);

        Event::assertNotDispatched(EscrowReleased::class);
    }

    public function test_command_skips_already_released_holds(): void
    {
        Event::fake([EscrowReleased::class]);

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->paid()->create(['client_id' => $client->id]);
        // Already released — should not be picked up (status != 'held')
        $this->createEscrowHold($booking, EscrowStatus::Released->value, now()->subHours(1));

        $this->artisan('escrow:release-expired')
            ->assertSuccessful();

        Event::assertNotDispatched(EscrowReleased::class);
    }

    public function test_command_releases_multiple_expired_holds(): void
    {
        Event::fake([EscrowReleased::class]);

        $client   = User::factory()->create();
        $booking1 = BookingRequest::factory()->paid()->create(['client_id' => $client->id]);
        $booking2 = BookingRequest::factory()->paid()->create(['client_id' => $client->id]);

        $this->createEscrowHold($booking1, EscrowStatus::Held->value, now()->subMinutes(5));
        $this->createEscrowHold($booking2, EscrowStatus::Held->value, now()->subMinutes(10));

        $this->artisan('escrow:release-expired')
            ->assertSuccessful();

        $this->assertDatabaseHas('booking_requests', ['id' => $booking1->id, 'status' => BookingStatus::Confirmed->value]);
        $this->assertDatabaseHas('booking_requests', ['id' => $booking2->id, 'status' => BookingStatus::Confirmed->value]);

        Event::assertDispatchedTimes(EscrowReleased::class, 2);
    }

    public function test_command_outputs_no_holds_message_when_nothing_to_release(): void
    {
        $this->artisan('escrow:release-expired')
            ->expectsOutput('No expired escrow holds to release.')
            ->assertSuccessful();
    }
}
