<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\WithdrawalStatus;
use App\Http\Requests\Api\StoreWithdrawalRequestRequest;
use App\Models\TalentProfile;
use App\Models\WithdrawalRequest;
use App\Services\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawalRequestController extends BaseController
{
    /**
     * GET /api/v1/me/withdrawal_requests
     *
     * Liste les demandes de reversement du talent authentifié.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $profile = TalentProfile::where('user_id', $user->id)->first();

        if (! $profile) {
            return $this->errorResponse('TALENT_PROFILE_NOT_FOUND', 'Aucun profil talent trouvé.', 404);
        }

        $requests = WithdrawalRequest::where('talent_profile_id', $profile->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->successResponse([
            'data' => $requests->map(fn (WithdrawalRequest $r) => $this->formatRequest($r)),
            'total' => $requests->total(),
            'page' => $requests->currentPage(),
        ]);
    }

    /**
     * POST /api/v1/me/withdrawal_requests
     *
     * Crée une demande de reversement.
     *
     * Prérequis :
     * - Le talent doit avoir un compte validé par l'admin (payout_method_verified_at non null)
     * - Le montant demandé doit être <= available_balance
     * - Aucune demande en cours (status pending/approved/processing)
     */
    public function store(StoreWithdrawalRequestRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $profile = TalentProfile::where('user_id', $user->id)->first();

        if (! $profile) {
            return $this->errorResponse('TALENT_PROFILE_NOT_FOUND', 'Aucun profil talent trouvé.', 404);
        }

        // Le compte doit être validé
        if (! $profile->payout_method_verified_at) {
            return $this->errorResponse(
                'PAYOUT_METHOD_NOT_VERIFIED',
                'Votre compte de paiement n\'a pas encore été validé par l\'administration.',
                422,
            );
        }

        // Aucune demande active en cours
        $hasActiveRequest = WithdrawalRequest::where('talent_profile_id', $profile->id)
            ->whereIn('status', [
                WithdrawalStatus::Pending->value,
                WithdrawalStatus::Approved->value,
                WithdrawalStatus::Processing->value,
            ])
            ->exists();

        if ($hasActiveRequest) {
            return $this->errorResponse(
                'WITHDRAWAL_REQUEST_IN_PROGRESS',
                'Vous avez déjà une demande de reversement en cours.',
                422,
            );
        }

        $amount = (int) $request->validated('amount');

        // Pre-check (fast path, before acquiring lock).
        if ($amount > $profile->available_balance) {
            return $this->errorResponse(
                'INSUFFICIENT_BALANCE',
                'Le montant demandé dépasse votre solde disponible.',
                422,
                ['available_balance' => $profile->available_balance],
            );
        }

        // lockForUpdate on talent_profile sérialise les demandes concurrentes
        // et re-vérifie le solde à l'intérieur de la transaction pour fermer
        // la fenêtre TOCTOU entre le pre-check et le decrement.
        $insufficientBalance = false;
        $withdrawalRequest = DB::transaction(function () use ($profile, $amount, &$insufficientBalance) {
            $locked = TalentProfile::lockForUpdate()->find($profile->id);

            if ($amount > $locked->available_balance) {
                $insufficientBalance = true;

                return null;
            }

            $locked->decrement('available_balance', $amount);

            return WithdrawalRequest::create([
                'talent_profile_id' => $locked->id,
                'amount'            => $amount,
                'status'            => WithdrawalStatus::Pending->value,
                'payout_method'     => $locked->payout_method,
                'payout_details'    => $locked->payout_details,
            ]);
        });

        if ($insufficientBalance) {
            return $this->errorResponse(
                'INSUFFICIENT_BALANCE',
                'Le montant demandé dépasse votre solde disponible.',
                422,
            );
        }

        /** @var WithdrawalRequest $withdrawalRequest */

        Log::channel('financial')->info('withdrawal.requested', [
            'withdrawal_id'     => $withdrawalRequest->id,
            'talent_profile_id' => $profile->id,
            'user_id'           => $user->id,
            'amount'            => $withdrawalRequest->amount,
            'payout_method'     => $withdrawalRequest->payout_method?->value,
            'ip'                => $request->ip(),
        ]);

        // Notifier les admins (email + push in-app)
        AdminNotificationService::withdrawalRequested($withdrawalRequest);

        return $this->successResponse($this->formatRequest($withdrawalRequest), 201);
    }

    /** @return array<string, mixed> */
    private function formatRequest(WithdrawalRequest $r): array
    {
        return [
            'id' => $r->id,
            'amount' => $r->amount,
            'status' => $r->status->value,
            'status_label' => $r->status->label(),
            'payout_method' => $r->payout_method?->value,
            'payout_details' => $r->payout_details,
            'note' => $r->note,
            'processed_at' => $r->processed_at?->toISOString(),
            'created_at' => $r->created_at?->toISOString(),
        ];
    }
}
