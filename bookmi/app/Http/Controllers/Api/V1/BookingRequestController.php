<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Http\Requests\Api\RejectBookingRequestRequest;
use App\Http\Requests\Api\StoreBookingRequestRequest;
use App\Http\Resources\BookingRequestResource;
use App\Models\BookingRequest;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

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
        $statusParam = $request->query('status');
        $filter      = [];

        if ($statusParam !== null) {
            $parts      = array_values(array_filter(array_map('trim', explode(',', $statusParam))));
            $validValues = array_column(BookingStatus::cases(), 'value');

            foreach ($parts as $s) {
                if (! in_array($s, $validValues, strict: true)) {
                    return $this->errorResponse('BOOKING_INVALID_STATUS', 'Le statut fourni est invalide.', 422);
                }
            }

            $filter = count($parts) === 1
                ? ['status'   => $parts[0]]
                : ['statuses' => $parts];
        }

        $paginator = $this->bookingService->getBookingsForUser(
            $request->user(),
            $filter,
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

        $booking->load($this->bookingRelations());

        return $this->successResponse(new BookingRequestResource($booking), 201);
    }

    /**
     * GET /api/v1/booking_requests/{booking}
     */
    public function show(BookingRequest $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        $booking->load($this->bookingRelations());

        return $this->successResponse(new BookingRequestResource($booking));
    }

    /**
     * POST /api/v1/booking_requests/{booking}/accept
     */
    public function accept(BookingRequest $booking): JsonResponse
    {
        $this->authorize('accept', $booking);

        $booking = $this->bookingService->acceptBooking($booking);

        $booking->load($this->bookingRelations());

        return $this->successResponse(new BookingRequestResource($booking));
    }

    /**
     * POST /api/v1/booking_requests/{booking}/reject
     */
    public function reject(RejectBookingRequestRequest $request, BookingRequest $booking): JsonResponse
    {
        $this->authorize('reject', $booking);

        $booking = $this->bookingService->rejectBooking($booking, $request->validated('reason'));

        $booking->load($this->bookingRelations());

        return $this->successResponse(new BookingRequestResource($booking));
    }

    /**
     * POST /api/v1/booking_requests/{booking}/cancel
     */
    public function cancel(BookingRequest $booking): JsonResponse
    {
        $this->authorize('cancel', $booking);

        $booking = $this->bookingService->cancelBooking($booking);

        $booking->load($this->bookingRelations());

        return $this->successResponse(new BookingRequestResource($booking));
    }

    /**
     * GET /api/v1/booking_requests/{booking}/contract
     */
    public function contract(BookingRequest $booking): Response
    {
        $this->authorize('downloadContract', $booking);

        if (! $booking->contract_path || ! Storage::disk('local')->exists($booking->contract_path)) {
            throw BookingException::contractNotReady();
        }

        $content  = Storage::disk('local')->get($booking->contract_path);
        $filename = "contrat-booking-{$booking->id}.pdf";

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function bookingRelations(): array
    {
        return [
            'client:id,first_name,last_name',
            'talentProfile:id,stage_name,slug',
            'servicePackage:id,name,type,description,inclusions,duration_minutes',
        ];
    }
}
