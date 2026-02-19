<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminAuditTest extends TestCase
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

    private function createLog(User $causer, string $action): void
    {
        ActivityLog::create([
            'causer_id' => $causer->id,
            'action'    => $action,
        ]);
    }

    #[Test]
    public function admin_can_view_audit_trail(): void
    {
        $admin = $this->admin();
        $this->createLog($admin, 'dispute.resolved');
        $this->createLog($admin, 'user.suspended');

        $this->actingAs($admin)
            ->getJson('/admin/audit')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function admin_can_filter_audit_by_action(): void
    {
        $admin = $this->admin();
        $this->createLog($admin, 'dispute.resolved');
        $this->createLog($admin, 'user.suspended');

        $this->actingAs($admin)
            ->getJson('/admin/audit?action=dispute')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function admin_can_filter_audit_by_causer(): void
    {
        $admin = $this->admin();
        $other = User::factory()->admin()->create();

        $this->createLog($admin, 'dispute.resolved');
        $this->createLog($other, 'user.suspended');

        $this->actingAs($admin)
            ->getJson("/admin/audit?causer_id={$admin->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function non_admin_cannot_view_audit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/admin/audit')
            ->assertStatus(403);
    }
}
