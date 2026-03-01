<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Http\Requests\Api\RejectBookingRequestRequest;
use App\Http\Requests\Api\StoreBookingRequestRequest;
use App\Http\Resources\BookingRequestResource;
use App\Jobs\GenerateContractPdf;
use App\Models\BookingRequest;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        $booking->load($this->detailRelations());

        return $this->successResponse(new BookingRequestResource($booking));
    }

    /**
     * POST /api/v1/booking_requests/{booking}/accept
     */
    public function accept(BookingRequest $booking): JsonResponse
    {
        $this->authorize('accept', $booking);

        $booking = $this->bookingService->acceptBooking($booking);

        $booking->load($this->detailRelations());

        return $this->successResponse(new BookingRequestResource($booking));
    }

    /**
     * POST /api/v1/booking_requests/{booking}/reject
     */
    public function reject(RejectBookingRequestRequest $request, BookingRequest $booking): JsonResponse
    {
        $this->authorize('reject', $booking);

        $booking = $this->bookingService->rejectBooking($booking, $request->validated('reason'));

        $booking->load($this->detailRelations());

        return $this->successResponse(new BookingRequestResource($booking));
    }

    /**
     * POST /api/v1/booking_requests/{booking}/cancel
     */
    public function cancel(BookingRequest $booking): JsonResponse
    {
        $this->authorize('cancel', $booking);

        $booking = $this->bookingService->cancelBooking($booking);

        $booking->load($this->detailRelations());

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
     * GET /api/v1/booking_requests/{booking}/receipt
     * Returns a short-lived download URL (cache token, 10 min) for the PDF receipt.
     * The download URL itself is public but single-use.
     */
    public function receipt(BookingRequest $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        if (! in_array($booking->status, [
            BookingStatus::Paid,
            BookingStatus::Confirmed,
            BookingStatus::Completed,
        ], true)) {
            return $this->errorResponse(
                'RECEIPT_NOT_AVAILABLE',
                'Le reçu n\'est disponible qu\'après paiement.',
                404
            );
        }

        $token = Str::uuid()->toString();
        Cache::put("pdf_download:{$token}", [
            'type'       => 'receipt',
            'booking_id' => $booking->id,
        ], now()->addMinutes(10));

        return $this->successResponse([
            'receipt_url' => url("/api/v1/dl/{$token}"),
        ]);
    }

    /**
     * GET /api/v1/booking_requests/{booking}/contract-url
     * Returns a short-lived download URL (cache token, 10 min) for the PDF contract.
     */
    public function contractUrl(BookingRequest $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        if (! in_array($booking->status, [
            BookingStatus::Paid,
            BookingStatus::Confirmed,
            BookingStatus::Completed,
        ], true)) {
            return $this->errorResponse(
                'CONTRACT_NOT_AVAILABLE',
                'Le contrat n\'est disponible qu\'après paiement.',
                404
            );
        }

        if (! $booking->contract_path || ! Storage::disk('local')->exists($booking->contract_path)) {
            throw BookingException::contractNotReady();
        }

        $token = Str::uuid()->toString();
        Cache::put("pdf_download:{$token}", [
            'type'       => 'contract',
            'booking_id' => $booking->id,
        ], now()->addMinutes(10));

        return $this->successResponse([
            'contract_url' => url("/api/v1/dl/{$token}"),
        ]);
    }

    /**
     * POST /api/v1/booking_requests/{booking}/dispute
     */
    public function dispute(BookingRequest $booking): JsonResponse
    {
        $this->authorize('openDispute', $booking);

        $booking = $this->bookingService->openDispute($booking);

        $booking->load($this->detailRelations());

        return $this->successResponse(new BookingRequestResource($booking));
    }

    // ── Admin contract management ─────────────────────────────────────────

    /**
     * POST /api/v1/admin/booking_requests/{booking}/contract/regenerate
     * Admin — regenerate (or generate for first time) the contract PDF.
     */
    public function adminRegenerateContract(BookingRequest $booking): JsonResponse
    {
        GenerateContractPdf::dispatch($booking)->onQueue('media');

        return $this->successResponse(null, 'Génération du contrat lancée en arrière-plan.');
    }

    /**
     * DELETE /api/v1/admin/booking_requests/{booking}/contract
     * Admin — delete the stored contract PDF and clear the path.
     */
    public function adminDeleteContract(BookingRequest $booking): JsonResponse
    {
        if ($booking->contract_path && Storage::disk('local')->exists($booking->contract_path)) {
            Storage::disk('local')->delete($booking->contract_path);
        }

        $booking->update(['contract_path' => null]);

        return $this->successResponse(null, 'Contrat supprimé.');
    }

    /**
     * GET /api/v1/booking_requests/export
     *
     * Returns a CSV file of the current user's bookings.
     * Optional query params: ?status=completed&from=2025-01-01&to=2025-12-31
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = $request->user();

        $query = \App\Models\BookingRequest::with($this->bookingRelations())
            ->where(function ($q) use ($user) {
                $q->where('client_id', $user->id)
                    ->orWhereHas('talentProfile', fn ($t) => $t->where('user_id', $user->id));
            });

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->query('to'));
        }

        $bookings = $query->orderByDesc('created_at')->get();

        $filename = 'bookings_' . now()->format('Ymd_His') . '.csv';

        // Pre-process rows so the closure only outputs plain arrays
        $rows = [];
        foreach ($bookings as $b) {
            /** @var \App\Models\BookingRequest $b */
            $rows[] = [
                $b->id,
                $b->created_at?->format('d/m/Y H:i'),
                $b->getRawOriginal('status'),
                $b->talentProfile->stage_name ?? 'N/A',
                $b->servicePackage->name ?? 'N/A',
                $b->getRawOriginal('event_date'),
                $b->event_location,
                $b->total_amount,
            ];
        }

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            // BOM for Excel UTF-8 compatibility
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID', 'Date création', 'Statut', 'Talent', 'Package', 'Date événement', 'Lieu', 'Total (FCFA)']);

            foreach ($rows as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Minimal relations for list items.
     *
     * @return array<int, string>
     */
    private function bookingRelations(): array
    {
        return [
            'client:id,first_name,last_name',
            'talentProfile:id,user_id,stage_name,slug',
            'talentProfile.user:id,avatar',
            'servicePackage:id,name,type,description,inclusions,duration_minutes',
        ];
    }

    /**
     * Full relations for detail view — includes status history.
     *
     * @return array<int, string>
     */
    private function detailRelations(): array
    {
        return [
            ...$this->bookingRelations(),
            'statusLogs',
            'statusLogs.performer:id,first_name,last_name',
            'rescheduleRequests',
        ];
    }
}
