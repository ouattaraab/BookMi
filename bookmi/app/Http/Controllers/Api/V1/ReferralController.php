<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __construct(private readonly ReferralService $referralService)
    {
    }

    /**
     * GET /api/v1/referrals
     *
     * Returns the current user's referral code + stats.
     */
    public function show(Request $request): JsonResponse
    {
        $stats = $this->referralService->getStats($request->user());

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * POST /api/v1/referrals/apply
     *
     * Apply a referral code (can be called after registration if missed at sign-up).
     */
    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        $user = $request->user();

        // Already applied a code
        if ($user->referred_by_code !== null) {
            return response()->json([
                'error' => [
                    'code'    => 'REFERRAL_ALREADY_APPLIED',
                    'message' => 'Vous avez déjà utilisé un code de parrainage.',
                ],
            ], 422);
        }

        $this->referralService->applyCode($user, $validated['code']);

        if ($user->referred_by_code === null) {
            return response()->json([
                'error' => [
                    'code'    => 'REFERRAL_CODE_INVALID',
                    'message' => 'Code de parrainage invalide ou inexistant.',
                ],
            ], 422);
        }

        return response()->json([
            'data' => ['message' => 'Code de parrainage appliqué avec succès.'],
        ]);
    }
}
