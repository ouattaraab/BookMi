<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\ReviewType;
use App\Models\BookingRequest;
use App\Models\Review;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    /**
     * Submit a review for a completed booking.
     *
     * Rules:
     *  - Booking must be Completed.
     *  - Only one review per type per booking (enforced at DB + service level).
     *  - Client submits client_to_talent; talent submits talent_to_client.
     *  - After a client_to_talent review, recompute TalentProfile::average_rating.
     *
     * @throws ValidationException
     */
    public function submit(
        BookingRequest $booking,
        User $reviewer,
        ReviewType $type,
        int $rating,
        ?string $comment = null,
        ?int $punctualityScore = null,
        ?int $qualityScore = null,
        ?int $professionalismScore = null,
        ?int $contractRespectScore = null,
    ): Review {
        $this->assertBookingCompleted($booking);
        $this->assertReviewerIsAuthorized($booking, $reviewer, $type);
        $this->assertNotAlreadyReviewed($booking, $type);

        $reviewee = $this->resolveReviewee($booking, $type);

        return DB::transaction(function () use (
            $booking,
            $reviewer,
            $reviewee,
            $type,
            $rating,
            $comment,
            $punctualityScore,
            $qualityScore,
            $professionalismScore,
            $contractRespectScore,
        ) {
            $review = Review::create([
                'booking_request_id'     => $booking->id,
                'reviewer_id'            => $reviewer->id,
                'reviewee_id'            => $reviewee->id,
                'type'                   => $type,
                'rating'                 => $rating,
                'punctuality_score'      => $punctualityScore,
                'quality_score'          => $qualityScore,
                'professionalism_score'  => $professionalismScore,
                'contract_respect_score' => $contractRespectScore,
                'comment'                => $comment,
            ]);

            if ($type === ReviewType::ClientToTalent) {
                $this->recomputeTalentRating($booking->talent_profile_id);
            }

            return $review;
        });
    }

    /**
     * @throws ValidationException
     */
    private function assertBookingCompleted(BookingRequest $booking): void
    {
        $reviewable = [BookingStatus::Confirmed, BookingStatus::Completed];
        if (! in_array($booking->status, $reviewable, strict: true)) {
            throw ValidationException::withMessages([
                'booking' => 'Reviews can only be submitted for confirmed or completed bookings.',
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function assertReviewerIsAuthorized(BookingRequest $booking, User $reviewer, ReviewType $type): void
    {
        $isClient = $booking->client_id === $reviewer->id;
        $isTalent = TalentProfile::where('id', $booking->talent_profile_id)
            ->where('user_id', $reviewer->id)
            ->exists();

        if ($type === ReviewType::ClientToTalent && ! $isClient) {
            throw ValidationException::withMessages([
                'type' => 'Only the client can submit a client_to_talent review.',
            ]);
        }

        if ($type === ReviewType::TalentToClient && ! $isTalent) {
            throw ValidationException::withMessages([
                'type' => 'Only the talent can submit a talent_to_client review.',
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function assertNotAlreadyReviewed(BookingRequest $booking, ReviewType $type): void
    {
        $exists = Review::where('booking_request_id', $booking->id)
            ->where('type', $type)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'type' => 'A review of this type has already been submitted for this booking.',
            ]);
        }
    }

    private function resolveReviewee(BookingRequest $booking, ReviewType $type): User
    {
        if ($type === ReviewType::ClientToTalent) {
            // Reviewee is the talent's user
            /** @var TalentProfile $profile */
            $profile = TalentProfile::findOrFail($booking->talent_profile_id);
            return User::findOrFail($profile->user_id);
        }

        // Reviewee is the client
        return User::findOrFail($booking->client_id);
    }

    private function recomputeTalentRating(int $talentProfileId): void
    {
        $avg = Review::where('type', ReviewType::ClientToTalent)
            ->whereHas('bookingRequest', fn ($q) => $q->where('talent_profile_id', $talentProfileId))
            ->avg('rating');

        TalentProfile::where('id', $talentProfileId)->update([
            'average_rating' => round((float) $avg, 2),
        ]);
    }
}
