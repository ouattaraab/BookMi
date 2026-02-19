<?php

namespace Tests\Feature\Admin;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Models\AdminAlert;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->admin()->create();
        $user->assignRole('admin_ceo');

        return $user;
    }

    private function makeAlert(string $status = 'open'): AdminAlert
    {
        return AdminAlert::create([
            'type'        => AlertType::LowRating,
            'severity'    => AlertSeverity::Warning,
            'title'       => 'Test alert',
            'description' => 'Test description',
            'status'      => $status,
        ]);
    }

    #[Test]
    public function admin_can_list_alerts(): void
    {
        $admin = $this->admin();
        $this->makeAlert();
        $this->makeAlert('resolved');

        $this->actingAs($admin)
            ->getJson('/admin/alerts')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function admin_can_filter_alerts_by_status(): void
    {
        $admin = $this->admin();
        $this->makeAlert('open');
        $this->makeAlert('resolved');

        $this->actingAs($admin)
            ->getJson('/admin/alerts?status=open')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function admin_can_resolve_an_alert(): void
    {
        $admin = $this->admin();
        $alert = $this->makeAlert();

        $this->actingAs($admin)
            ->postJson("/admin/alerts/{$alert->id}/resolve")
            ->assertOk();

        $this->assertDatabaseHas('admin_alerts', [
            'id'     => $alert->id,
            'status' => 'resolved',
        ]);
    }

    #[Test]
    public function admin_can_dismiss_an_alert(): void
    {
        $admin = $this->admin();
        $alert = $this->makeAlert();

        $this->actingAs($admin)
            ->postJson("/admin/alerts/{$alert->id}/dismiss")
            ->assertOk();

        $this->assertDatabaseHas('admin_alerts', [
            'id'     => $alert->id,
            'status' => 'dismissed',
        ]);
    }

    #[Test]
    public function cannot_resolve_already_closed_alert(): void
    {
        $admin = $this->admin();
        $alert = $this->makeAlert('resolved');

        $this->actingAs($admin)
            ->postJson("/admin/alerts/{$alert->id}/resolve")
            ->assertStatus(422);
    }
}
