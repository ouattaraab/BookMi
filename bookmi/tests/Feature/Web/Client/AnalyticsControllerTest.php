<?php

namespace Tests\Feature\Web\Client;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsControllerTest extends TestCase
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

    /** Client authentifié → vue chargée (status 200) */
    public function test_authenticated_client_can_view_analytics(): void
    {
        $client = $this->createClient();

        $response = $this->actingAs($client, 'web')
            ->get('/client/analytics');

        $response->assertStatus(200);
    }

    /** Non authentifié → redirect login */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/client/analytics');

        $response->assertRedirect();
        $this->assertStringContainsString('login', $response->headers->get('Location') ?? '');
    }

    /** Talent ne peut pas accéder à la page analytics client */
    public function test_talent_cannot_access_client_analytics(): void
    {
        $talentUser = User::factory()->create();
        $talentUser->assignRole('talent');

        $response = $this->actingAs($talentUser, 'web')
            ->get('/client/analytics');

        $response->assertStatus(403);
    }
}
