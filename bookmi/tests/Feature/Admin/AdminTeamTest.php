<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminTeamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function ceo(): User
    {
        $user = User::factory()->admin()->create();
        $user->assignRole('admin_ceo');

        return $user;
    }

    #[Test]
    public function ceo_can_list_admin_team(): void
    {
        $ceo = $this->ceo();

        $this->actingAs($ceo)
            ->getJson('/admin/team')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function ceo_can_create_admin_collaborator(): void
    {
        $ceo = $this->ceo();

        $response = $this->actingAs($ceo)
            ->postJson('/admin/team', [
                'first_name' => 'Alice',
                'last_name'  => 'Comptable',
                'email'      => 'alice@bookmi.ci',
                'phone'      => '+22507111111',
                'password'   => 'secret12345',
                'role'       => 'admin_comptable',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', ['email' => 'alice@bookmi.ci', 'is_admin' => true]);
    }

    #[Test]
    public function collaborator_is_assigned_correct_role(): void
    {
        $ceo = $this->ceo();

        $this->actingAs($ceo)
            ->postJson('/admin/team', [
                'first_name' => 'Bob',
                'last_name'  => 'Modérateur',
                'email'      => 'bob@bookmi.ci',
                'phone'      => '+22507222222',
                'password'   => 'secret12345',
                'role'       => 'admin_moderateur',
            ])
            ->assertStatus(201);

        $user = User::where('email', 'bob@bookmi.ci')->first();
        $this->assertTrue($user->hasRole('admin_moderateur'));
    }

    #[Test]
    public function ceo_can_update_collaborator_role(): void
    {
        $ceo         = $this->ceo();
        $collaborator = User::factory()->admin()->create();
        $collaborator->assignRole('admin_comptable');

        $this->actingAs($ceo)
            ->putJson("/admin/team/{$collaborator->id}", ['role' => 'admin_controleur'])
            ->assertOk();

        $this->assertTrue($collaborator->fresh()->hasRole('admin_controleur'));
    }

    #[Test]
    public function ceo_can_revoke_collaborator_access(): void
    {
        $ceo         = $this->ceo();
        $collaborator = User::factory()->admin()->create();
        $collaborator->assignRole('admin_comptable');

        $this->actingAs($ceo)
            ->deleteJson("/admin/team/{$collaborator->id}")
            ->assertOk();

        $this->assertFalse($collaborator->fresh()->hasAnyRole(['admin_comptable', 'admin_ceo']));
    }

    #[Test]
    public function ceo_cannot_modify_own_role(): void
    {
        $ceo = $this->ceo();

        $this->actingAs($ceo)
            ->putJson("/admin/team/{$ceo->id}", ['role' => 'admin_comptable'])
            ->assertStatus(403);
    }

    #[Test]
    public function non_admin_cannot_manage_team(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/admin/team')
            ->assertStatus(403);
    }
}
