<?php

namespace Tests\Feature\Admin;

use App\Enums\VerificationStatus;
use App\Models\IdentityVerification;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_pending_verifications(): void
    {
        $admin = User::factory()->admin()->create();
        IdentityVerification::factory()->pending()->count(3)->create();
        IdentityVerification::factory()->approved()->create();

        $response = $this->actingAs($admin)
            ->getJson('/admin/verifications');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/admin/verifications');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_admin_routes(): void
    {
        $response = $this->getJson('/admin/verifications');

        $response->assertStatus(401);
    }

    public function test_admin_can_view_verification_details(): void
    {
        $admin = User::factory()->admin()->create();
        $verification = IdentityVerification::factory()->pending()->create();

        $response = $this->actingAs($admin)
            ->getJson("/admin/verifications/{$verification->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.type', 'identity_verification')
            ->assertJsonPath('data.attributes.has_document', true);
    }

    public function test_admin_can_stream_encrypted_document(): void
    {
        Storage::fake('identity_documents');

        $admin = User::factory()->admin()->create();

        $originalContent = 'fake-image-binary-content';
        $encrypted = Crypt::encrypt($originalContent, serialize: false);
        $storedPath = 'identity/test-doc.enc';
        Storage::disk('identity_documents')->put($storedPath, $encrypted);

        $verification = IdentityVerification::factory()->create([
            'stored_path' => $storedPath,
            'original_mime' => 'image/jpeg',
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/verifications/{$verification->id}/document");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'image/jpeg');

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
        $response->assertHeader('Content-Disposition', 'attachment');
    }

    public function test_admin_can_approve_verification(): void
    {
        Storage::fake('identity_documents');

        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $talentProfile = TalentProfile::factory()->create([
            'user_id' => $user->id,
            'is_verified' => false,
            'bio' => 'Ma bio',
        ]);

        $storedPath = 'identity/test-doc.enc';
        Storage::disk('identity_documents')->put($storedPath, 'encrypted-content');

        $verification = IdentityVerification::factory()->create([
            'user_id' => $user->id,
            'stored_path' => $storedPath,
            'verification_status' => VerificationStatus::PENDING,
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/admin/verifications/{$verification->id}/review", [
                'decision' => 'approved',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.verification_status', 'approved');

        $talentProfile->refresh();
        $this->assertTrue($talentProfile->is_verified);
        Storage::disk('identity_documents')->assertMissing($storedPath);
    }

    public function test_admin_can_reject_verification(): void
    {
        Storage::fake('identity_documents');

        $admin = User::factory()->admin()->create();

        $storedPath = 'identity/test-doc.enc';
        Storage::disk('identity_documents')->put($storedPath, 'encrypted-content');

        $verification = IdentityVerification::factory()->create([
            'stored_path' => $storedPath,
            'verification_status' => VerificationStatus::PENDING,
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/admin/verifications/{$verification->id}/review", [
                'decision' => 'rejected',
                'rejection_reason' => 'Document illisible',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.verification_status', 'rejected')
            ->assertJsonPath('data.attributes.rejection_reason', 'Document illisible');

        Storage::disk('identity_documents')->assertMissing($storedPath);
    }

    public function test_cannot_review_already_reviewed_verification(): void
    {
        $admin = User::factory()->admin()->create();
        $verification = IdentityVerification::factory()->approved()->create();

        $response = $this->actingAs($admin)
            ->postJson("/admin/verifications/{$verification->id}/review", [
                'decision' => 'approved',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VERIFICATION_ALREADY_REVIEWED');
    }

    public function test_rejection_requires_reason(): void
    {
        $admin = User::factory()->admin()->create();
        $verification = IdentityVerification::factory()->pending()->create();

        $response = $this->actingAs($admin)
            ->postJson("/admin/verifications/{$verification->id}/review", [
                'decision' => 'rejected',
            ]);

        $response->assertStatus(422);
    }

    public function test_approval_updates_profile_completion_percentage(): void
    {
        Storage::fake('identity_documents');

        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        TalentProfile::factory()->create([
            'user_id' => $user->id,
            'bio' => 'Ma bio',
            'is_verified' => false,
            'profile_completion_percentage' => 20,
        ]);

        $storedPath = 'identity/test-doc.enc';
        Storage::disk('identity_documents')->put($storedPath, 'encrypted-content');

        $verification = IdentityVerification::factory()->create([
            'user_id' => $user->id,
            'stored_path' => $storedPath,
            'verification_status' => VerificationStatus::PENDING,
        ]);

        $this->actingAs($admin)
            ->postJson("/admin/verifications/{$verification->id}/review", [
                'decision' => 'approved',
            ]);

        $user->refresh();
        $this->assertTrue($user->talentProfile->is_verified);
        $this->assertEquals(40, $user->talentProfile->profile_completion_percentage);
    }

    public function test_review_creates_audit_log(): void
    {
        Storage::fake('identity_documents');

        $admin = User::factory()->admin()->create();

        $storedPath = 'identity/test-doc.enc';
        Storage::disk('identity_documents')->put($storedPath, 'encrypted-content');

        $verification = IdentityVerification::factory()->create([
            'stored_path' => $storedPath,
            'verification_status' => VerificationStatus::PENDING,
        ]);

        $this->actingAs($admin)
            ->postJson("/admin/verifications/{$verification->id}/review", [
                'decision' => 'approved',
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'causer_id' => $admin->id,
            'subject_type' => IdentityVerification::class,
            'subject_id' => $verification->id,
            'action' => 'identity_verification.approved',
        ]);
    }
}
