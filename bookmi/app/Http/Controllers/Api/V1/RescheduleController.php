<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\StoreRescheduleRequestRequest;
use App\Http\Resources\RescheduleRequestResource;
use App\Models\BookingRequest;
use App\Models\RescheduleRequest;
use App\Services\RescheduleService;
use Illuminate\Http\JsonResponse;

class RescheduleController extends BaseController
{
    public function __construct(
        private readonly RescheduleService $rescheduleService,
    ) {
    }

    /**
     * POST /api/v1/booking_requests/{booking}/reschedule
     */
    public function store(StoreRescheduleRequestRequest $request, BookingRequest $booking): JsonResponse
    {
        $this->authorize('create', [RescheduleRequest::class, $booking]);

        $reschedule = $this->rescheduleService->createReschedule(
            $booking,
            $request->user(),
            $request->validated(),
        );

        $reschedule->load(['requestedBy:id,name']);

        return $this->successResponse(new RescheduleRequestResource($reschedule), 201);
    }

    /**
     * POST /api/v1/reschedule_requests/{reschedule}/accept
     */
    public function accept(RescheduleRequest $reschedule): JsonResponse
    {
        $this->authorize('respond', $reschedule);

        $reschedule = $this->rescheduleService->acceptReschedule($reschedule);

        $reschedule->load(['requestedBy:id,name']);

        return $this->successResponse(new RescheduleRequestResource($reschedule));
    }

    /**
     * POST /api/v1/reschedule_requests/{reschedule}/reject
     */
    public function reject(RescheduleRequest $reschedule): JsonResponse
    {
        $this->authorize('respond', $reschedule);

        $reschedule = $this->rescheduleService->rejectReschedule($reschedule);

        $reschedule->load(['requestedBy:id,name']);

        return $this->successResponse(new RescheduleRequestResource($reschedule));
    }
}
