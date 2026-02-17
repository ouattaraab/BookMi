<?php

namespace Tests\Feature\Api\V1;

use App\Models\IdentityVerification;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_verification_with_valid_document(): void
    {
        Storage::fake('identity_documents');

        $user = User::factory()->create();
        TalentProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/verifications', [
                'document' => UploadedFile::fake()->image('cni.jpg', 800, 600),
                'document_type' => 'cni',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'identity_verification')
            ->assertJsonPath('data.attributes.verification_status', 'pending')
            ->assertJsonPath('data.attributes.document_type', 'cni');

        $this->assertDatabaseHas('identity_verifications', [
            'user_id' => $user->id,
            'document_type' => 'cni',
            'verification_status' => 'pending',
        ]);
    }

    public function test_cannot_submit_without_authentication(): void
    {
        $response = $this->postJson('/api/v1/verifications', [
            'document' => UploadedFile::fake()->image('cni.jpg'),
            'document_type' => 'cni',
        ]);

        $response->assertStatus(401);
    }

    public function test_cannot_submit_without_talent_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/verifications', [
                'document' => UploadedFile::fake()->image('cni.jpg', 800, 600),
                'document_type' => 'cni',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VERIFICATION_NO_TALENT_PROFILE');
    }

    public function test_cannot_submit_duplicate_pending_verification(): void
    {
        Storage::fake('identity_documents');

        $user = User::factory()->create();
        TalentProfile::factory()->create(['user_id' => $user->id]);
        IdentityVerification::factory()->pending()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/verifications', [
                'document' => UploadedFile::fake()->image('cni.jpg', 800, 600),
                'document_type' => 'cni',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VERIFICATION_ALREADY_PENDING');
    }

    public function test_can_resubmit_after_rejection(): void
    {
        Storage::fake('identity_documents');

        $user = User::factory()->create();
        TalentProfile::factory()->create(['user_id' => $user->id]);
        IdentityVerification::factory()->rejected()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/verifications', [
                'document' => UploadedFile::fake()->image('cni.jpg', 800, 600),
                'document_type' => 'cni',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.attributes.verification_status', 'pending');
    }

    public function test_validation_fails_with_invalid_file_type(): void
    {
        $user = User::factory()->create();
        TalentProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/verifications', [
                'document' => UploadedFile::fake()->create('document.txt', 100, 'text/plain'),
                'document_type' => 'cni',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_validation_fails_with_missing_document(): void
    {
        $user = User::factory()->create();
        TalentProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/verifications', [
                'document_type' => 'cni',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_user_can_check_own_verification_status(): void
    {
        $user = User::factory()->create();
        IdentityVerification::factory()->pending()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/verifications/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.type', 'identity_verification')
            ->assertJsonPath('data.attributes.verification_status', 'pending');
    }

    public function test_returns_404_when_no_verification_exists(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/verifications/me');

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'VERIFICATION_NOT_FOUND');
    }
}
