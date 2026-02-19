<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\ReportReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SubmitReportRequest;
use App\Models\BookingRequest;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    /**
     * POST /booking_requests/{booking}/reports
     *
     * Any participant (client or talent) can file a problem report.
     * If the booking is Paid or Confirmed, it transitions to Disputed.
     */
    public function store(SubmitReportRequest $request, BookingRequest $booking): JsonResponse
    {
        $user = $request->user();

        if (! $booking->isOwnedByUser($user)) {
            return response()->json([
                'error' => [
                    'code'    => 'FORBIDDEN',
                    'message' => 'You are not a participant in this booking.',
                ],
            ], 403);
        }

        $disputeStatuses = [BookingStatus::Paid, BookingStatus::Confirmed];
        $canDispute = in_array($booking->status, $disputeStatuses, strict: true);

        if (! $canDispute && $booking->status !== BookingStatus::Completed) {
            throw ValidationException::withMessages([
                'booking' => 'Reports can only be filed for paid, confirmed, or completed bookings.',
            ]);
        }

        $reason = ReportReason::from($request->validated('reason'));

        $report = DB::transaction(function () use ($booking, $user, $reason, $request, $canDispute) {
            $report = Report::create([
                'booking_request_id' => $booking->id,
                'reporter_id'        => $user->id,
                'reason'             => $reason,
                'description'        => $request->validated('description'),
                'status'             => 'pending',
            ]);

            // Transition booking to Disputed if it was active
            if ($canDispute) {
                $booking->update(['status' => BookingStatus::Disputed]);
            }

            return $report;
        });

        return response()->json([
            'data' => [
                'id'                 => $report->id,
                'booking_request_id' => $report->booking_request_id,
                'reporter_id'        => $report->reporter_id,
                'reason'             => $report->reason->value,
                'description'        => $report->description,
                'status'             => $report->status,
                'created_at'         => $report->created_at?->toISOString(),
            ],
            'message' => 'Signalement enregistré. Notre équipe examinera le dossier.',
        ], 201);
    }
}
