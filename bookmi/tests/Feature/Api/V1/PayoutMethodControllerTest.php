<?php

namespace Tests\Feature\Api\V1;

use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Tests\TestCase;

class PayoutMethodControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
    }

    // ── AC1: configure payout method ──────────────────────────────────────

    public function test_talent_can_configure_orange_money_payout_method(): void
    {
        $user    = User::factory()->create();
        $profile = TalentProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/talent_profiles/me/payout_method', [
                'payout_method'  => 'orange_money',
                'payout_details' => ['phone' => '+22601234567'],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.payout_method', 'orange_money')
            ->assertJsonPath('data.payout_details.phone', '+22601234567');

        $this->assertDatabaseHas('talent_profiles', [
            'id'            => $profile->id,
            'payout_method' => 'orange_money',
        ]);
    }

    public function test_talent_can_configure_bank_transfer_payout_method(): void
    {
        $user    = User::factory()->create();
        $profile = TalentProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/talent_profiles/me/payout_method', [
                'payout_method'  => 'bank_transfer',
                'payout_details' => [
                    'account_number' => '0123456789',
                    'bank_code'      => 'CI001',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.payout_method', 'bank_transfer');
    }

    public function test_payout_method_requires_phone_for_mobile_money(): void
    {
        $user = User::factory()->create();
        TalentProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/talent_profiles/me/payout_method', [
                'payout_method'  => 'wave',
                'payout_details' => [], // phone missing
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_payout_method_rejects_invalid_method(): void
    {
        $user = User::factory()->create();
        TalentProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/talent_profiles/me/payout_method', [
                'payout_method'  => 'crypto',
                'payout_details' => ['wallet' => '0x123'],
            ]);

        $response->assertStatus(422);
    }

    public function test_payout_method_returns_404_when_no_talent_profile(): void
    {
        $user = User::factory()->create();
        // No TalentProfile created for this user

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/talent_profiles/me/payout_method', [
                'payout_method'  => 'orange_money',
                'payout_details' => ['phone' => '+22601234567'],
            ]);

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'TALENT_PROFILE_NOT_FOUND');
    }

    public function test_payout_method_returns_401_when_unauthenticated(): void
    {
        $response = $this->patchJson('/api/v1/talent_profiles/me/payout_method', [
            'payout_method'  => 'orange_money',
            'payout_details' => ['phone' => '+22601234567'],
        ]);

        $response->assertStatus(401);
    }
}
