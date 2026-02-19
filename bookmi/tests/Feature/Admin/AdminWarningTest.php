<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminWarningTest extends TestCase
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

    #[Test]
    public function admin_can_list_users(): void
    {
        $admin = $this->admin();
        User::factory()->count(3)->create();

        $this->actingAs($admin)
            ->getJson('/admin/users')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function admin_can_view_user_detail(): void
    {
        $admin  = $this->admin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->getJson("/admin/users/{$target->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $target->id);
    }

    #[Test]
    public function admin_can_issue_formal_warning(): void
    {
        $admin  = $this->admin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->postJson("/admin/users/{$target->id}/warnings", [
                'reason'  => 'Comportement inapproprié',
                'details' => 'Détails de l\'avertissement.',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('admin_warnings', [
            'user_id'      => $target->id,
            'issued_by_id' => $admin->id,
            'reason'       => 'Comportement inapproprié',
        ]);
    }

    #[Test]
    public function admin_can_suspend_a_user(): void
    {
        $admin  = $this->admin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->postJson("/admin/users/{$target->id}/suspend", [
                'reason' => 'Fraude détectée',
            ])
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id'           => $target->id,
            'is_suspended' => true,
        ]);
    }

    #[Test]
    public function admin_can_unsuspend_a_user(): void
    {
        $admin  = $this->admin();
        $target = User::factory()->create(['is_suspended' => true]);

        $this->actingAs($admin)
            ->postJson("/admin/users/{$target->id}/unsuspend")
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id'           => $target->id,
            'is_suspended' => false,
        ]);
    }

    #[Test]
    public function admin_cannot_suspend_another_admin(): void
    {
        $admin  = $this->admin();
        $target = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson("/admin/users/{$target->id}/suspend", ['reason' => 'Test'])
            ->assertStatus(403);
    }

    #[Test]
    public function suspend_revokes_all_tokens(): void
    {
        $admin  = $this->admin();
        $target = User::factory()->create();
        $target->createToken('test-token');

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->actingAs($admin)
            ->postJson("/admin/users/{$target->id}/suspend", ['reason' => 'Test'])
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
