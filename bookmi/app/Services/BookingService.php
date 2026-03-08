<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\DisputeReason;
use App\Enums\PackageType;
use App\Enums\CalendarSlotStatus;
use App\Events\BookingAccepted;
use App\Events\BookingCancelled;
use App\Events\BookingCreated;
use App\Events\BookingDisputed;
use App\Exceptions\BookingException;
use App\Jobs\GenerateContractPdf;
use App\Jobs\SendPushNotification;
use App\Models\BookingRequest;
use App\Models\BookingStatusLog;
use App\Models\CalendarSlot;
use App\Models\PromoCode;
use App\Models\ServicePackage;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingService
{
    public function __construct(
        private readonly CalendarService $calendarService,
    ) {
    }

    /**
     * Create a new booking request from a client.
     *
     * @param array{talent_profile_id: int, service_package_id: int, event_date: string, start_time?: string|null, event_location: string, message?: string|null, is_express?: bool, travel_cost?: int|null, promo_code?: string|null} $data
     */
    public function createBookingRequest(User $client, array $data): BookingRequest
    {
        $isExpress = (bool) ($data['is_express'] ?? false);
        $startTime = $data['start_time'] ?? null;

        $talentProfile = TalentProfile::find($data['talent_profile_id']);
        if (! $talentProfile) {
            throw BookingException::talentNotFound();
        }

        if ($isExpress && ! $talentProfile->enable_express_booking) {
            throw BookingException::expressBookingNotAvailable();
        }

        $package = ServicePackage::find($data['service_package_id']);
        if (! $package || $package->talent_profile_id !== $talentProfile->id) {
            throw BookingException::packageNotBelongToTalent();
        }

        $isMicro = $package->type instanceof PackageType
            ? $package->type === PackageType::Micro
            : $package->type === PackageType::Micro->value;

        // Micro-service: auto-fill event_date and location; skip calendar availability check
        if ($isMicro) {
            $deliveryDays = max(1, (int) ($package->delivery_days ?? 7));
            $data['event_date']     = $data['event_date'] ?? now()->addDays($deliveryDays)->format('Y-m-d');
            $data['event_location'] = $data['event_location'] ?? 'Livraison digitale';
        }

        $cachetAmount     = $package->cachet_amount;
        $travelCost       = (int) ($data['travel_cost'] ?? 0);
        $commissionRate   = (int) config('bookmi.commission_rate', 15);
        $commissionAmount = (int) round(($cachetAmount * $commissionRate) / 100);
        $expressFee       = $isExpress ? (int) round($cachetAmount * 0.15) : 0;
        $totalAmount      = $cachetAmount + $travelCost + $commissionAmount + $expressFee;

        // Apply promo code discount if provided and valid
        $discountAmount = 0;
        $promoCodeId    = null;
        if (! empty($data['promo_code'])) {
            $promo = PromoCode::where('code', strtoupper($data['promo_code']))->first();
            if ($promo && $promo->isValidFor($totalAmount)) {
                $discountAmount = $promo->calculateDiscount($totalAmount);
                $promoCodeId    = $promo->id;
                $totalAmount    = max(0, $totalAmount - $discountAmount);
            }
        }

        $packageSnapshot = [
            'id'               => $package->id,
            'name'             => $package->name,
            'description'      => $package->description,
            'cachet_amount'    => $package->cachet_amount,
            'duration_minutes' => $package->duration_minutes,
            'inclusions'       => $package->inclusions,
            'type'             => $package->type instanceof \BackedEnum ? $package->type->value : $package->type,
            'delivery_days'    => $package->delivery_days,
        ];

        // Atomic: availability check + insert inside a transaction.
        // For non-micro bookings, lockForUpdate on the talent_profile row serializes
        // concurrent requests for the same talent, closing the TOCTOU window between
        // isDateAvailable() and BookingRequest::create().
        $booking = DB::transaction(function () use (
            $client,
            $data,
            $talentProfile,
            $package,
            $packageSnapshot,
            $isExpress,
            $startTime,
            $isMicro,
            $cachetAmount,
            $travelCost,
            $commissionAmount,
            $totalAmount,
            $expressFee,
            $promoCodeId,
            $discountAmount,
        ) {
            if (! $isMicro) {
                TalentProfile::lockForUpdate()->find($talentProfile->id);
                if (! $this->calendarService->isDateAvailable($talentProfile, $data['event_date'], $startTime)) {
                    throw BookingException::dateUnavailable();
                }
            }

            $booking = BookingRequest::create([
                'client_id'          => $client->id,
                'talent_profile_id'  => $talentProfile->id,
                'service_package_id' => $package->id,
                'package_snapshot'   => $packageSnapshot,
                'event_date'         => $data['event_date'],
                'start_time'         => $startTime,
                'event_location'     => $data['event_location'],
                'event_latitude'     => $data['event_latitude'] ?? null,
                'event_longitude'    => $data['event_longitude'] ?? null,
                'message'            => $data['message'] ?? null,
                'is_express'         => $isExpress,
                'status'             => BookingStatus::Pending,
                'cachet_amount'      => $cachetAmount,
                'travel_cost'        => $travelCost,
                'commission_amount'  => $commissionAmount,
                'total_amount'       => $totalAmount,
                'express_fee'        => $expressFee,
                'promo_code_id'      => $promoCodeId,
                'discount_amount'    => $discountAmount,
            ]);

            if ($promoCodeId !== null) {
                PromoCode::where('id', $promoCodeId)->increment('used_count');
            }

            // Log creation
            $this->logStatusTransition($booking, null, BookingStatus::Pending, $client->id);

            return $booking;
        });

        BookingCreated::dispatch($booking);

        if ($isExpress) {
            $booking = $this->acceptBooking($booking);
        }

        return $booking;
    }

    /**
     * Return paginated bookings for a user (client or talent side).
     *
     * - If the user has a talent profile: returns bookings received for that profile.
     * - Otherwise: returns bookings sent by the user as a client.
     *
     * @param array{status?: string|null, statuses?: list<string>} $filters
     */
    public function getBookingsForUser(User $user, array $filters = []): CursorPaginator
    {
        $query = BookingRequest::with([
            'client:id,first_name,last_name',
            'talentProfile:id,stage_name,slug',
            'servicePackage:id,name,type,description,inclusions,duration_minutes',
        ]);

        $talentProfile = TalentProfile::where('user_id', $user->id)->first();

        if ($talentProfile) {
            $query->where('talent_profile_id', $talentProfile->id);
        } else {
            $query->where('client_id', $user->id);
        }

        if (! empty($filters['statuses'])) {
            $query->whereIn('status', $filters['statuses']);
        } elseif (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest('id')->cursorPaginate(20);
    }

    /**
     * Accept a pending booking request (talent action).
     *
     * Transitions: pending → accepted
     * Side-effects: blocks the calendar slot, dispatches BookingAccepted.
     */
    public function acceptBooking(BookingRequest $booking): BookingRequest
    {
        if (! $booking->status->canTransitionTo(BookingStatus::Accepted)) {
            throw BookingException::invalidStatusTransition();
        }

        $performerId = $booking->talentProfile?->user_id;

        DB::transaction(function () use ($booking, $performerId) {
            // Re-acquire with lock to prevent concurrent double-accept (TOCTOU guard).
            $locked = BookingRequest::where('id', $booking->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->status->canTransitionTo(BookingStatus::Accepted)) {
                throw BookingException::invalidStatusTransition();
            }

            $locked->update(['status' => BookingStatus::Accepted]);

            // Only block the whole day when no start_time is set (date-only booking).
            // Time-based bookings rely on the ±1h buffer check in CalendarService::isDateAvailable().
            if ($locked->start_time === null) {
                CalendarSlot::updateOrCreate(
                    [
                        'talent_profile_id' => $locked->talent_profile_id,
                        'date'              => $locked->event_date->toDateString(),
                    ],
                    ['status' => CalendarSlotStatus::Blocked],
                );
            }

            $this->logStatusTransition($locked, BookingStatus::Pending, BookingStatus::Accepted, $performerId);
        });

        $booking->refresh();
        BookingAccepted::dispatch($booking);

        // Generate contract synchronously so it is available immediately
        // regardless of queue driver (critical on shared hosting).
        // A failure only logs a warning — the admin can regenerate from the panel.
        try {
            GenerateContractPdf::dispatchSync($booking);
        } catch (\Throwable $e) {
            Log::warning('Contract PDF generation failed after acceptance', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);
        }

        return $booking;
    }

    /**
     * Transition a confirmed booking to completed.
     *
     * - Skips silently if already completed or not in Confirmed status.
     * - Updates TalentProfile::total_bookings with live count.
     *
     * NOTE: available_balance is credited by HandleEscrowReleased when the escrow
     * is released (Paid → Confirmed). Do NOT credit it again here to avoid double-crediting.
     */
    public function markCompleted(BookingRequest $booking): void
    {
        DB::transaction(function () use ($booking): void {
            $fresh = BookingRequest::where('id', $booking->id)->lockForUpdate()->first();

            if (! $fresh || $fresh->status === BookingStatus::Completed) {
                return;
            }

            if ($fresh->status !== BookingStatus::Confirmed) {
                return;
            }

            $fresh->update(['status' => BookingStatus::Completed]);

            $count = BookingRequest::where('talent_profile_id', $fresh->talent_profile_id)
                ->whereNotIn('status', [BookingStatus::Cancelled->value, BookingStatus::Rejected->value])
                ->count();

            TalentProfile::where('id', $fresh->talent_profile_id)
                ->update(['total_bookings' => $count]);
        });
    }

    /**
     * Cancel a booking (client action) with graduated refund policy.
     *
     * Policy (from config):
     *   >= J-14 days → full refund (100%)
     *   >= J-7 days  → partial refund (50%)
     *   >= J-2 days  → mediation required (throws)
     *   <  J-2 days  → not allowed (throws)
     */
    public function cancelBooking(BookingRequest $booking): BookingRequest
    {
        if (! $booking->status->canTransitionTo(BookingStatus::Cancelled)) {
            throw BookingException::invalidStatusTransition();
        }

        $cancellation   = config('bookmi.cancellation');
        $fullRefundDays = (int) $cancellation['full_refund_days'];
        $partialDays    = (int) $cancellation['partial_refund_days'];
        $mediationDays  = (int) $cancellation['mediation_only_days'];
        $partialRate    = (int) $cancellation['partial_refund_rate'];

        $daysUntilEvent = now()->startOfDay()->diffInDays(
            $booking->event_date->startOfDay(),
            absolute: false,
        );

        if ($daysUntilEvent < $mediationDays) {
            throw BookingException::cancellationNotAllowed();
        }

        if ($daysUntilEvent < $partialDays) {
            throw BookingException::cancellationRequiresMediation();
        }

        if ($daysUntilEvent >= $fullRefundDays) {
            $refundAmount = $booking->total_amount;
            $policy       = 'full_refund';
        } else {
            $refundAmount = (int) round($booking->total_amount * $partialRate / 100);
            $policy       = 'partial_refund';
        }

        $fromStatus = $booking->status;

        DB::transaction(function () use ($booking, $fromStatus, $refundAmount, $policy) {
            // Re-acquire with lock to prevent concurrent cancel + accept race.
            $locked = BookingRequest::where('id', $booking->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->status->canTransitionTo(BookingStatus::Cancelled)) {
                throw BookingException::invalidStatusTransition();
            }

            $locked->update([
                'status'                      => BookingStatus::Cancelled,
                'refund_amount'               => $refundAmount,
                'cancellation_policy_applied' => $policy,
            ]);

            $this->logStatusTransition($locked, $fromStatus, BookingStatus::Cancelled, $locked->client_id);
        });

        $booking->refresh();
        BookingCancelled::dispatch($booking);

        return $booking;
    }

    /**
     * Reject a pending booking request (talent action).
     *
     * Transitions: pending → rejected
     * Side-effects: stores optional reject_reason, dispatches BookingCancelled.
     *
     * Only pending bookings can be rejected via this endpoint.
     */
    public function rejectBooking(BookingRequest $booking, ?string $reason = null): BookingRequest
    {
        if ($booking->status !== BookingStatus::Pending) {
            throw BookingException::invalidStatusTransition();
        }

        $performerId = $booking->talentProfile?->user_id;

        $booking->update([
            'status'        => BookingStatus::Rejected,
            'reject_reason' => $reason,
        ]);

        $this->logStatusTransition($booking, BookingStatus::Pending, BookingStatus::Rejected, $performerId);

        BookingCancelled::dispatch($booking);

        return $booking;
    }


    /**
     * Open a dispute on a booking (client action).
     *
     * Transitions: pending|accepted|paid|confirmed → disputed
     * Side-effects: FCM to talent + event dispatched for admin notifications.
     */
    public function openDispute(BookingRequest $booking, DisputeReason $reason, ?string $comment = null): BookingRequest
    {
        if (! $booking->status->canTransitionTo(BookingStatus::Disputed)) {
            throw BookingException::invalidStatusTransition();
        }

        $fromStatus = $booking->status;

        $booking->update([
            'status'          => BookingStatus::Disputed,
            'dispute_reason'  => $reason,
            'dispute_comment' => $comment,
            'disputed_at'     => now(),
        ]);

        $this->logStatusTransition($booking, $fromStatus, BookingStatus::Disputed, $booking->client_id);

        // Notify talent via push notification
        $talentUserId = $booking->talentProfile?->user_id;
        if ($talentUserId) {
            SendPushNotification::dispatch(
                $talentUserId,
                'Problème signalé',
                'Un client a signalé un problème sur votre réservation.',
                ['type' => 'dispute_opened', 'booking_id' => (string) $booking->id],
            );
        }

        // Dispatch event so admins receive notifications
        BookingDisputed::dispatch($booking);

        return $booking;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Record a status change in the audit log.
     */
    private function logStatusTransition(
        BookingRequest $booking,
        ?BookingStatus $fromStatus,
        BookingStatus $toStatus,
        ?int $performedById = null,
    ): void {
        BookingStatusLog::create([
            'booking_request_id' => $booking->id,
            'from_status'        => $fromStatus?->value,
            'to_status'          => $toStatus->value,
            'performed_by_id'    => $performedById,
        ]);
    }
}
