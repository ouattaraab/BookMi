<?php

namespace Tests\Feature\Web\Client;

use App\Models\BookingRequest;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function createClient(): User
    {
        $user = User::factory()->create();
        $user->assignRole('client');

        return $user;
    }

    private function validPayload(): array
    {
        return [
            'reason'      => 'quality_issue',
            'description' => 'Le talent n\'était pas au niveau attendu.',
        ];
    }

    /** Client peut signaler une réservation completed → redirect avec report_success */
    public function test_client_can_report_completed_booking(): void
    {
        $client = $this->createClient();

        $booking = BookingRequest::factory()->completed()->create([
            'client_id' => $client->id,
        ]);

        $response = $this->actingAs($client, 'web')
            ->post("/client/bookings/{$booking->id}/report", $this->validPayload());

        $response->assertRedirect();
        $response->assertSessionHas('report_success');

        $this->assertDatabaseHas('reports', [
            'booking_request_id' => $booking->id,
            'reporter_id'        => $client->id,
            'reason'             => 'quality_issue',
        ]);
    }

    /** Double signalement → erreur validation */
    public function test_duplicate_report_returns_validation_error(): void
    {
        $client = $this->createClient();

        $booking = BookingRequest::factory()->completed()->create([
            'client_id' => $client->id,
        ]);

        Report::factory()->create([
            'booking_request_id' => $booking->id,
            'reporter_id'        => $client->id,
        ]);

        $response = $this->actingAs($client, 'web')
            ->post("/client/bookings/{$booking->id}/report", $this->validPayload());

        $response->assertRedirect();
        $response->assertSessionHasErrors(['report']);
    }

    /** Réservation avec statut non éligible (pending) → 404 */
    public function test_report_for_pending_booking_returns_404(): void
    {
        $client = $this->createClient();

        $booking = BookingRequest::factory()->pending()->create([
            'client_id' => $client->id,
        ]);

        $response = $this->actingAs($client, 'web')
            ->post("/client/bookings/{$booking->id}/report", $this->validPayload());

        $response->assertStatus(404);
    }

    /** Non authentifié → redirect login */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $booking = BookingRequest::factory()->completed()->create();

        $response = $this->post("/client/bookings/{$booking->id}/report", $this->validPayload());

        $response->assertRedirect();
        $this->assertStringContainsString('login', $response->headers->get('Location') ?? '');
    }

    /** Client peut aussi signaler une réservation disputed */
    public function test_client_can_report_disputed_booking(): void
    {
        $client = $this->createClient();

        $booking = BookingRequest::factory()->disputed()->create([
            'client_id' => $client->id,
        ]);

        $response = $this->actingAs($client, 'web')
            ->post("/client/bookings/{$booking->id}/report", $this->validPayload());

        $response->assertRedirect();
        $response->assertSessionHas('report_success');
    }
}
