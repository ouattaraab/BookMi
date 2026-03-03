<?php

namespace Tests\Feature\Api\V1;

use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    // ── /api/v1/app/version is always accessible ──────────────────────────────

    public function test_app_version_endpoint_accessible_when_maintenance_off(): void
    {
        PlatformSetting::set('maintenance_enabled', 'false', 'bool');

        $this->getJson('/api/v1/app/version')
            ->assertOk()
            ->assertJsonStructure([
                'maintenance',
                'version_required',
                'update_type',
                'store_urls' => ['android', 'ios'],
            ]);
    }

    public function test_app_version_endpoint_accessible_when_maintenance_on(): void
    {
        PlatformSetting::set('maintenance_enabled', 'true', 'bool');

        $this->getJson('/api/v1/app/version')
            ->assertOk()
            ->assertJsonPath('maintenance', true);

        PlatformSetting::set('maintenance_enabled', 'false', 'bool');
    }

    // ── Maintenance OFF → normal API access ───────────────────────────────────

    public function test_api_accessible_when_maintenance_disabled(): void
    {
        PlatformSetting::set('maintenance_enabled', 'false', 'bool');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/me')
            ->assertOk();
    }

    // ── Maintenance ON → 503 for regular users ────────────────────────────────

    public function test_api_returns_503_when_maintenance_enabled(): void
    {
        PlatformSetting::set('maintenance_enabled', 'true', 'bool');
        PlatformSetting::set('maintenance_message', 'Site en maintenance.', 'string');

        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->getJson('/api/v1/me')
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'MAINTENANCE')
            ->assertJsonPath('error.details.maintenance', true);

        PlatformSetting::set('maintenance_enabled', 'false', 'bool');
    }

    // ── Maintenance ON → admin bypasses ──────────────────────────────────────

    public function test_admin_bypasses_maintenance_mode(): void
    {
        PlatformSetting::set('maintenance_enabled', 'true', 'bool');

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->getJson('/api/v1/me')
            ->assertOk();

        PlatformSetting::set('maintenance_enabled', 'false', 'bool');
    }

    // ── Unauthenticated user gets 503 during maintenance ─────────────────────

    public function test_unauthenticated_request_gets_503_during_maintenance(): void
    {
        PlatformSetting::set('maintenance_enabled', 'true', 'bool');

        $this->getJson('/api/v1/talents')
            ->assertStatus(503);

        PlatformSetting::set('maintenance_enabled', 'false', 'bool');
    }
}
