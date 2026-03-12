<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        // Role must be assigned under the api guard (Filament canAccessPanel check)
        $admin->assignRole('admin_ceo');

        return $admin;
    }

    /** Admin peut accéder à la liste des transactions */
    public function test_admin_can_access_transactions_resource(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->get('/admin/transactions');

        $response->assertStatus(200);
    }

    /** Admin peut accéder à la liste des signalements */
    public function test_admin_can_access_reports_resource(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->get('/admin/reports');

        $response->assertStatus(200);
    }

    /** Admin peut accéder à la liste des conversations */
    public function test_admin_can_access_conversations_resource(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->get('/admin/conversations');

        $response->assertStatus(200);
    }

    /** Utilisateur non admin est redirigé */
    public function test_non_admin_cannot_access_filament_resources(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/admin/transactions');

        // Filament redirects non-admins to login or returns 403
        $this->assertContains($response->getStatusCode(), [302, 403]);
    }
}
