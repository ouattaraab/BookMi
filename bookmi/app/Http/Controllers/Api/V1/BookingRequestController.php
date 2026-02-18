<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Http\Requests\Api\RejectBookingRequestRequest;
use App\Http\Requests\Api\StoreBookingRequestRequest;
use App\Http\Resources\BookingRequestResource;
use App\Models\BookingRequest;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingRequestController extends BaseController
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {
    }

    /**
     * GET /api/v1/booking_requests
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');

        if ($status !== null && ! in_array($status, array_column(BookingStatus::cases(), 'value'), strict: true)) {
            return $this->errorResponse('BOOKING_INVALID_STATUS', 'Le statut fourni est invalide.', 422);
        }

        $paginator = $this->bookingService->getBookingsForUser(
            $request->user(),
            ['status' => $status],
        );

        $paginator->through(fn ($booking) => new BookingRequestResource($booking));

        return $this->paginatedResponse($paginator);
    }

    /**
     * POST /api/v1/booking_requests
     */
    public function store(StoreBookingRequestRequest $request): JsonResponse
    {
        $booking = $this->bookingService->createBookingRequest(
            $request->user(),
            $request->validated(),
        );

        $booking->load([
            'client:id,name',
            'talentProfile:id,stage_name',
            'servicePackage:id,name,type,description,inclusions,duration_minutes',
        ]);

        return $this->successResponse(new BookingRequestResource($booking), 201);
    }

    /**
     * GET /api/v1/booking_requests/{booking}
     */
    public function show(BookingRequest $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        $booking->load([
            'client:id,name',
            'talentProfile:id,stage_name',
            'servicePackage:id,name,type,description,inclusions,duration_minutes',
        ]);

        return $this->successResponse(new BookingRequestResource($booking));
    }

    /**
     * POST /api/v1/booking_requests/{booking}/accept
     */
    public function accept(BookingRequest $booking): JsonResponse
    {
        $this->authorize('accept', $booking);

        $booking = $this->bookingService->acceptBooking($booking);

        $booking->load([
            'client:id,name',
            'talentProfile:id,stage_name',
            'servicePackage:id,name,type,description,inclusions,duration_minutes',
        ]);

        return $this->successResponse(new BookingRequestResource($booking));
    }

    /**
     * POST /api/v1/booking_requests/{booking}/reject
     */
    public function reject(RejectBookingRequestRequest $request, BookingRequest $booking): JsonResponse
    {
        $this->authorize('reject', $booking);

        $booking = $this->bookingService->rejectBooking($booking, $request->validated('reason'));

        $booking->load([
            'client:id,name',
            'talentProfile:id,stage_name',
            'servicePackage:id,name,type,description,inclusions,duration_minutes',
        ]);

        return $this->successResponse(new BookingRequestResource($booking));
    }
}
