<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\PromoCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoCodeController extends BaseController
{
    /**
     * POST /api/v1/promo_codes/validate
     * Validate a promo code for a given booking amount and return the discount info.
     */
    public function validate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code'           => ['required', 'string', 'max:50'],
            'booking_amount' => ['required', 'integer', 'min:1'],
        ]);

        $promo = PromoCode::where('code', strtoupper($validated['code']))->first();

        if (! $promo || ! $promo->isValidFor($validated['booking_amount'])) {
            return $this->errorResponse(
                'PROMO_INVALID',
                "Ce code promo est invalide, expiré ou ne s'applique pas à ce montant.",
                422
            );
        }

        $discountAmount = $promo->calculateDiscount($validated['booking_amount']);

        return $this->successResponse([
            'code'            => $promo->code,
            'type'            => $promo->type,
            'value'           => $promo->value,
            'discount_amount' => $discountAmount,
            'final_amount'    => $validated['booking_amount'] - $discountAmount,
        ]);
    }
}
