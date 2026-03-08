<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BookmiException;
use App\Http\Requests\Api\StoreTalentProfileRequest;
use App\Http\Requests\Api\UpdateAutoReplyRequest;
use App\Http\Requests\Api\UpdatePayoutMethodRequest;
use App\Http\Requests\Api\UpdateTalentProfileRequest;
use App\Http\Resources\TalentProfileResource;
use App\Jobs\NotifyTalentFollowers;
use App\Models\BookingRequest;
use App\Models\TalentProfile;
use App\Services\AdminNotificationService;
use App\Services\TalentProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TalentProfileController extends BaseController
{
    public function __construct(
        private readonly TalentProfileService $service,
    ) {
    }

    public function store(StoreTalentProfileRequest $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();
            $profile = $this->service->createProfile($user->id, $request->validated());

            return $this->successResponse(
                new TalentProfileResource($profile->load('category', 'subcategory', 'categories')),
                201,
            );
        } catch (BookmiException $e) {
            return $this->errorResponse(
                $e->getErrorCode(),
                $e->getMessage(),
                $e->getStatusCode(),
                $e->getDetails(),
            );
        }
    }

    public function showOwn(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $profile = $this->service->getByUserId($user->id);

        if (! $profile) {
            return $this->errorResponse(
                'TALENT_PROFILE_NOT_FOUND',
                'Aucun profil talent trouvé pour cet utilisateur.',
                404,
            );
        }

        // Dynamic count: exclude only cancelled/rejected so accepted/paid/confirmed count too
        $profile->total_bookings = BookingRequest::where('talent_profile_id', $profile->id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->count();

        // Recalculate talent_level dynamically from the dynamic count
        $profile->talent_level = match (true) {
            $profile->total_bookings >= 51 => 'elite',
            $profile->total_bookings >= 21 => 'populaire',
            $profile->total_bookings >= 6  => 'confirme',
            default                         => 'nouveau',
        };

        return $this->successResponse(
            new TalentProfileResource($profile->load('category', 'subcategory', 'categories', 'managers:id,first_name,last_name,email')),
        );
    }

    public function update(UpdateTalentProfileRequest $request, TalentProfile $talentProfile): JsonResponse
    {
        $profile = $this->service->updateProfile($talentProfile, $request->validated());

        if ($profile->followers()->exists()) {
            NotifyTalentFollowers::dispatch(
                $profile->id,
                $profile->stage_name,
                "Mise à jour de {$profile->stage_name}",
                'Un artiste que vous suivez a mis à jour son profil.',
            );
        }

        return $this->successResponse(
            new TalentProfileResource($profile->load('category', 'subcategory', 'categories')),
        );
    }

    /**
     * GET /v1/talent_profiles/me/payout_method
     *
     * Retourne le compte de paiement actuel du talent et son statut de validation.
     */
    public function getPayoutMethod(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $profile = $this->service->getByUserId($user->id);

        if (! $profile) {
            return $this->errorResponse(
                'TALENT_PROFILE_NOT_FOUND',
                'Aucun profil talent trouvé pour cet utilisateur.',
                404,
            );
        }

        return $this->successResponse([
            'payout_method'                  => $profile->payout_method,
            'payout_details'                 => $profile->payout_details,
            'payout_method_verified_at'      => $profile->payout_method_verified_at?->toISOString(),
            'payout_method_status'           => $profile->payout_method_status,
            'payout_method_rejection_reason' => $profile->payout_method_rejection_reason,
            'available_balance'              => $profile->available_balance,
        ]);
    }

    /**
     * PATCH /v1/talent_profiles/me/payout_method
     *
     * Talent configures their payout method and details.
     * Resets verification status and notifies admins.
     */
    public function updatePayoutMethod(UpdatePayoutMethodRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $profile = $this->service->getByUserId($user->id);

        if (! $profile) {
            return $this->errorResponse(
                'TALENT_PROFILE_NOT_FOUND',
                'Aucun profil talent trouvé pour cet utilisateur.',
                404,
            );
        }

        $profile->update([
            'payout_method'                  => $request->validated('payout_method'),
            'payout_details'                 => $request->validated('payout_details'),
            'payout_method_verified_at'      => null, // Reset validation on account change
            'payout_method_verified_by'      => null,
            'payout_method_status'           => 'pending',
            'payout_method_rejection_reason' => null,
        ]);

        $profile->refresh();

        // Notifier les admins qu'un compte est à valider (email + push in-app)
        AdminNotificationService::payoutMethodAdded($profile);

        return $this->successResponse([
            'payout_method' => $profile->payout_method,
            'payout_details' => $profile->payout_details,
            'payout_method_verified_at' => null,
            'available_balance' => $profile->available_balance,
        ]);
    }

    /**
     * PATCH /v1/talent_profiles/me/info
     *
     * Talent updates their public profile info (bio + social links).
     */
    public function updateInfo(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $profile = $this->service->getByUserId($user->id);

        if (! $profile) {
            return $this->errorResponse(
                'TALENT_PROFILE_NOT_FOUND',
                'Aucun profil talent trouvé pour cet utilisateur.',
                404,
            );
        }

        $validated = $request->validate([
            'bio' => ['nullable', 'string', 'max:1000'],
            'social_links' => ['nullable', 'array'],
            'social_links.instagram' => ['nullable', 'url'],
            'social_links.facebook' => ['nullable', 'url'],
            'social_links.youtube' => ['nullable', 'url'],
            'social_links.tiktok' => ['nullable', 'url'],
            'social_links.twitter' => ['nullable', 'url'],
            'is_group' => ['nullable', 'boolean'],
            'group_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'collective_name' => ['nullable', 'string', 'max:100'],
            'category_ids' => ['sometimes', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ]);

        // Sync categories when provided
        $categoryIds = isset($validated['category_ids']) && is_array($validated['category_ids'])
            ? array_values(array_map('intval', $validated['category_ids']))
            : null;
        unset($validated['category_ids']);

        if ($categoryIds !== null) {
            $validated['category_id'] = $categoryIds[0];
        }

        $profile->update($validated);

        if ($categoryIds !== null) {
            $profile->categories()->sync($categoryIds);
        }

        if ($profile->followers()->exists()) {
            NotifyTalentFollowers::dispatch(
                $profile->id,
                $profile->stage_name,
                "Nouveau contenu de {$profile->stage_name}",
                'Un artiste que vous suivez a partagé du nouveau contenu.',
            );
        }

        return $this->successResponse(
            new TalentProfileResource($profile->fresh()->load('category', 'subcategory', 'categories')),
        );
    }

    /**
     * PUT /v1/talent_profiles/me/auto_reply
     *
     * Talent configures their automatic reply message.
     */
    public function updateAutoReply(UpdateAutoReplyRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $profile = $this->service->getByUserId($user->id);

        if (! $profile) {
            return $this->errorResponse(
                'TALENT_PROFILE_NOT_FOUND',
                'Aucun profil talent trouvé pour cet utilisateur.',
                404,
            );
        }

        $profile->update([
            'auto_reply_message' => $request->validated('auto_reply_message'),
            'auto_reply_is_active' => $request->validated('auto_reply_is_active'),
        ]);

        return $this->successResponse([
            'auto_reply_message' => $profile->fresh()->auto_reply_message,
            'auto_reply_is_active' => $profile->fresh()->auto_reply_is_active,
        ]);
    }

    /**
     * DELETE /v1/talent_profiles/me/payout_method
     *
     * Talent removes their payout account.
     */
    public function deletePayoutMethod(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $profile = $this->service->getByUserId($user->id);

        if (! $profile) {
            return $this->errorResponse(
                'TALENT_PROFILE_NOT_FOUND',
                'Aucun profil talent trouvé pour cet utilisateur.',
                404,
            );
        }

        $profile->update([
            'payout_method'                  => null,
            'payout_details'                 => null,
            'payout_method_verified_at'      => null,
            'payout_method_verified_by'      => null,
            'payout_method_status'           => null,
            'payout_method_rejection_reason' => null,
        ]);

        return response()->json(null, 204);
    }

    public function destroy(Request $request, TalentProfile $talentProfile): JsonResponse
    {
        $this->authorize('delete', $talentProfile);

        $this->service->deleteProfile($talentProfile);

        return response()->json(null, 204);
    }
}
