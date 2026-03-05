<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ConsentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateConsentsRequest;
use App\Models\UserConsent;
use App\Services\ConsentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsentController extends Controller
{
    public function __construct(private readonly ConsentService $consentService)
    {
    }

    /**
     * GET /api/v1/consents — current user's consent list.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Latest consent record per type
        $consents = UserConsent::where('user_id', $user->id)
            ->orderByDesc('id')
            ->get()
            ->unique('consent_type')
            ->values()
            ->map(function (UserConsent $c): array {
                /** @var ConsentType $type */
                $type = $c->consent_type;

                return [
                    'type'             => $type->value,
                    'label'            => $type->label(),
                    'status'           => $c->status,
                    'consented_at'     => $c->consented_at?->toIso8601String(),
                    'withdrawn_at'     => $c->withdrawn_at?->toIso8601String(),
                    'document_version' => $c->document_version,
                ];
            });

        return response()->json([
            'data' => [
                'consents'             => $consents,
                'cgu_version_accepted' => $user->cgu_version_accepted,
                'current_cgu_version'  => config('bookmi.consent.cgu_version'),
            ],
        ]);
    }

    /**
     * PATCH /api/v1/consents/update — update opt-in consents.
     */
    public function update(UpdateConsentsRequest $request): JsonResponse
    {
        $this->consentService->updateOptIns(
            $request->user(),
            $request->input('consents', []),
            $request,
        );

        return response()->json(['message' => 'Consentements mis à jour.']);
    }

    /**
     * POST /api/v1/consents/reconsent — accept updated CGU.
     */
    public function reconsent(Request $request): JsonResponse
    {
        $request->validate([
            'consents' => ['required', 'array'],
            'consents.' . ConsentType::CguUpdate->value     => ['required', 'accepted'],
            'consents.' . ConsentType::DataProcessing->value => ['required', 'accepted'],
        ]);

        $this->consentService->recordConsents(
            $request->user(),
            array_merge($request->input('consents', []), [
                ConsentType::CguPrivacy->value => true, // mark as accepted for version tracking
            ]),
            $request,
        );

        return response()->json([
            'message'          => 'Re-consentement enregistré.',
            'cgu_version'      => config('bookmi.consent.cgu_version'),
        ]);
    }
}
