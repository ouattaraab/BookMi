<?php

namespace Tests\Unit\Services;

use App\Enums\VerificationStatus;
use App\Exceptions\BookmiException;
use App\Models\IdentityVerification;
use App\Models\TalentProfile;
use App\Models\User;
use App\Services\IdentityVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdentityVerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private IdentityVerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(IdentityVerificationService::class);
    }

    public function test_submit_stores_encrypted_file(): void
    {
        Storage::fake('identity_documents');

        $user = User::factory()->create();
        TalentProfile::factory()->create(['user_id' => $user->id]);
        $document = UploadedFile::fake()->image('cni.jpg', 800, 600);

        $verification = $this->service->submit($user->id, $document, 'cni');

        $this->assertInstanceOf(IdentityVerification::class, $verification);
        $this->assertEquals(VerificationStatus::PENDING, $verification->verification_status);
        $this->assertEquals('cni', $verification->document_type);
        $this->assertNotNull($verification->stored_path);
        Storage::disk('identity_documents')->assertExists($verification->stored_path);
    }

    public function test_submit_fails_if_pending_exists(): void
    {
        $user = User::factory()->create();
        TalentProfile::factory()->create(['user_id' => $user->id]);
        IdentityVerification::factory()->pending()->create(['user_id' => $user->id]);

        $this->expectException(BookmiException::class);

        $document = UploadedFile::fake()->image('cni.jpg');
        $this->service->submit($user->id, $document, 'cni');
    }

    public function test_submit_fails_without_talent_profile(): void
    {
        $user = User::factory()->create();

        $this->expectException(BookmiException::class);

        $document = UploadedFile::fake()->image('cni.jpg');
        $this->service->submit($user->id, $document, 'cni');
    }

    public function test_review_approve_sets_verified_and_deletes_file(): void
    {
        Storage::fake('identity_documents');

        $user = User::factory()->create();
        $talentProfile = TalentProfile::factory()->create([
            'user_id' => $user->id,
            'is_verified' => false,
        ]);
        $admin = User::factory()->admin()->create();

        $storedPath = 'identity/test-doc.enc';
        Storage::disk('identity_documents')->put($storedPath, 'encrypted-content');

        $verification = IdentityVerification::factory()->create([
            'user_id' => $user->id,
            'stored_path' => $storedPath,
            'verification_status' => VerificationStatus::PENDING,
        ]);

        $this->actingAs($admin);

        $result = $this->service->review($verification, 'approved', $admin->id);

        $this->assertEquals(VerificationStatus::APPROVED, $result->verification_status);
        $this->assertNotNull($result->verified_at);
        Storage::disk('identity_documents')->assertMissing($storedPath);

        $talentProfile->refresh();
        $this->assertTrue($talentProfile->is_verified);
    }

    public function test_review_reject_saves_reason_and_deletes_file(): void
    {
        Storage::fake('identity_documents');

        $admin = User::factory()->admin()->create();

        $storedPath = 'identity/test-doc.enc';
        Storage::disk('identity_documents')->put($storedPath, 'encrypted-content');

        $verification = IdentityVerification::factory()->create([
            'stored_path' => $storedPath,
            'verification_status' => VerificationStatus::PENDING,
        ]);

        $this->actingAs($admin);

        $result = $this->service->review($verification, 'rejected', $admin->id, 'Document illisible');

        $this->assertEquals(VerificationStatus::REJECTED, $result->verification_status);
        $this->assertEquals('Document illisible', $result->rejection_reason);
        $this->assertNull($result->verified_at);
        Storage::disk('identity_documents')->assertMissing($storedPath);
    }

    public function test_review_fails_on_already_reviewed(): void
    {
        $admin = User::factory()->admin()->create();

        $verification = IdentityVerification::factory()->approved()->create();

        $this->actingAs($admin);

        $this->expectException(BookmiException::class);

        $this->service->review($verification, 'approved', $admin->id);
    }

    public function test_recalculate_completion_adds_verification_bonus(): void
    {
        $user = User::factory()->create();
        $talentProfile = TalentProfile::factory()->create([
            'user_id' => $user->id,
            'bio' => 'Ma bio',
            'is_verified' => true,
            'profile_completion_percentage' => 0,
        ]);

        $talentProfileService = app(\App\Services\TalentProfileService::class);
        $updated = $talentProfileService->recalculateCompletion($talentProfile);

        $this->assertEquals(40, $updated->profile_completion_percentage);
    }

    public function test_profile_update_preserves_verification_completion_bonus(): void
    {
        $user = User::factory()->create();
        $talentProfile = TalentProfile::factory()->create([
            'user_id' => $user->id,
            'bio' => 'Ma bio initiale',
            'is_verified' => true,
            'profile_completion_percentage' => 40,
        ]);

        $talentProfileService = app(\App\Services\TalentProfileService::class);
        $updated = $talentProfileService->updateProfile($talentProfile, ['bio' => 'Ma nouvelle bio']);

        $this->assertEquals(40, $updated->profile_completion_percentage);
    }

    public function test_get_document_content_decrypts_file(): void
    {
        Storage::fake('identity_documents');

        $originalContent = 'fake-image-binary-content';
        $encrypted = Crypt::encrypt($originalContent, serialize: false);
        $storedPath = 'identity/test-doc.enc';
        Storage::disk('identity_documents')->put($storedPath, $encrypted);

        $verification = IdentityVerification::factory()->create([
            'stored_path' => $storedPath,
        ]);

        $decrypted = $this->service->getDocumentContent($verification);

        $this->assertEquals($originalContent, $decrypted);
    }
}
