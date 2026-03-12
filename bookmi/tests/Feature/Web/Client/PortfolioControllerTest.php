<?php

namespace Tests\Feature\Web\Client;

use App\Models\BookingRequest;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('public');
    }

    private function createClient(): User
    {
        $user = User::factory()->create();
        $user->assignRole('client');

        return $user;
    }

    /** Client authentifié avec réservation completed peut soumettre des fichiers */
    public function test_authenticated_client_can_submit_portfolio_for_completed_booking(): void
    {
        $client = $this->createClient();

        $booking = BookingRequest::factory()->completed()->create([
            'client_id' => $client->id,
        ]);

        $response = $this->actingAs($client, 'web')
            ->post("/client/bookings/{$booking->id}/portfolio", [
                'media' => [
                    UploadedFile::fake()->image('photo.jpg'),
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('portfolio_success');
    }

    /** Réservation non completed → 404 */
    public function test_portfolio_submission_for_non_completed_booking_returns_404(): void
    {
        $client = $this->createClient();

        $booking = BookingRequest::factory()->pending()->create([
            'client_id' => $client->id,
        ]);

        $response = $this->actingAs($client, 'web')
            ->post("/client/bookings/{$booking->id}/portfolio", [
                'media' => [
                    UploadedFile::fake()->image('photo.jpg'),
                ],
            ]);

        $response->assertStatus(404);
    }

    /** Non authentifié → redirect login */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $booking = BookingRequest::factory()->completed()->create();

        $response = $this->post("/client/bookings/{$booking->id}/portfolio", [
            'media' => [
                UploadedFile::fake()->image('photo.jpg'),
            ],
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('login', $response->headers->get('Location') ?? '');
    }

    /** Client ne peut pas soumettre de portfolio pour la réservation d'un autre client */
    public function test_client_cannot_submit_portfolio_for_another_clients_booking(): void
    {
        $client = $this->createClient();
        $otherClient = $this->createClient();

        $booking = BookingRequest::factory()->completed()->create([
            'client_id' => $otherClient->id,
        ]);

        $response = $this->actingAs($client, 'web')
            ->post("/client/bookings/{$booking->id}/portfolio", [
                'media' => [
                    UploadedFile::fake()->image('photo.jpg'),
                ],
            ]);

        $response->assertStatus(404);
    }
}
