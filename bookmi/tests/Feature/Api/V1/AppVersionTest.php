<?php

namespace Tests\Feature\Api\V1;

use App\Models\PlatformSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppVersionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        // Seed default platform settings
        PlatformSetting::set('maintenance_enabled', 'false', 'bool');
        PlatformSetting::set('app_version_required', '1.0.5', 'string');
        PlatformSetting::set('app_update_type', 'forced', 'string');
        PlatformSetting::set('app_update_message', 'Fix critique', 'string');
        PlatformSetting::set('app_update_features', '["Fix paiement","Nouveau profil"]', 'json');
        PlatformSetting::set('play_store_url', 'https://play.google.com/test', 'string');
        PlatformSetting::set('app_store_url', 'https://apps.apple.com/test', 'string');
    }

    public function test_version_endpoint_returns_expected_structure(): void
    {
        $this->getJson('/api/v1/app/version')
            ->assertOk()
            ->assertJsonStructure([
                'maintenance',
                'maintenance_message',
                'maintenance_end_at',
                'version_required',
                'update_type',
                'update_message',
                'features',
                'store_urls' => ['android', 'ios'],
            ]);
    }

    public function test_version_required_is_correctly_returned(): void
    {
        $this->getJson('/api/v1/app/version')
            ->assertOk()
            ->assertJsonPath('version_required', '1.0.5')
            ->assertJsonPath('update_type', 'forced');
    }

    public function test_features_array_is_returned(): void
    {
        $this->getJson('/api/v1/app/version')
            ->assertOk()
            ->assertJsonCount(2, 'features')
            ->assertJsonPath('features.0', 'Fix paiement');
    }

    public function test_store_urls_are_returned(): void
    {
        $this->getJson('/api/v1/app/version')
            ->assertOk()
            ->assertJsonPath('store_urls.android', 'https://play.google.com/test')
            ->assertJsonPath('store_urls.ios', 'https://apps.apple.com/test');
    }

    public function test_maintenance_false_by_default(): void
    {
        $this->getJson('/api/v1/app/version')
            ->assertOk()
            ->assertJsonPath('maintenance', false);
    }

    public function test_maintenance_true_when_enabled(): void
    {
        PlatformSetting::set('maintenance_enabled', 'true', 'bool');

        $this->getJson('/api/v1/app/version')
            ->assertOk()
            ->assertJsonPath('maintenance', true);
    }
}
