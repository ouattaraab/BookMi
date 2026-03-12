<?php

namespace Tests\Feature\Web\Talent;

use App\Models\BookingRequest;
use App\Models\Review;
use App\Models\TalentProfile;
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

    private function createTalentWithProfile(): array
    {
        $user = User::factory()->create();
        $user->assignRole('talent');
        $profile = TalentProfile::factory()->create(['user_id' => $user->id]);

        return [$user, $profile];
    }

    /** Talent peut signaler un avis sur sa propre réservation → redirect avec success */
    public function test_talent_can_report_review_on_own_booking(): void
    {
        [$talentUser, $talentProfile] = $this->createTalentWithProfile();
        $client = User::factory()->create();

        $booking = BookingRequest::factory()->completed()->create([
            'talent_profile_id' => $talentProfile->id,
            'client_id'         => $client->id,
        ]);

        $review = Review::factory()->create([
            'booking_request_id' => $booking->id,
            'reviewer_id'        => $client->id,
            'reviewee_id'        => $talentUser->id,
        ]);

        $response = $this->actingAs($talentUser, 'web')
            ->post("/talent/bookings/{$booking->id}/reviews/{$review->id}/report", [
                'reason' => 'Cet avis est mensonger et diffamatoire.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** Talent ne peut pas signaler un avis d'un autre talent → 404 */
    public function test_talent_cannot_report_review_on_other_talents_booking(): void
    {
        [$talentUser] = $this->createTalentWithProfile();

        // Another talent with their own booking and review
        [$otherTalentUser, $otherTalentProfile] = $this->createTalentWithProfile();
        $client = User::factory()->create();

        $booking = BookingRequest::factory()->completed()->create([
            'talent_profile_id' => $otherTalentProfile->id,
            'client_id'         => $client->id,
        ]);

        $review = Review::factory()->create([
            'booking_request_id' => $booking->id,
            'reviewer_id'        => $client->id,
            'reviewee_id'        => $otherTalentUser->id,
        ]);

        $response = $this->actingAs($talentUser, 'web')
            ->post("/talent/bookings/{$booking->id}/reviews/{$review->id}/report", [
                'reason' => 'Cet avis ne me concerne pas.',
            ]);

        $response->assertStatus(404);
    }

    /** Non authentifié → redirect login */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->post('/talent/bookings/1/reviews/1/report', [
            'reason' => 'Avis signalé.',
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('login', $response->headers->get('Location') ?? '');
    }
}
