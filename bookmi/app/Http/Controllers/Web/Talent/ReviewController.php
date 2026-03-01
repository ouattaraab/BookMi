<?php

namespace App\Http\Controllers\Web\Talent;

use App\Enums\ReviewType;
use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviewService)
    {
    }

    /**
     * POST /bookings/{id}/reviews/{reviewId}/reply
     *
     * Talent replies to a client_to_talent review from its booking detail page.
     */
    public function reply(int $id, int $reviewId, Request $request): RedirectResponse
    {
        $request->validate([
            'reply' => ['required', 'string', 'max:1000'],
        ], [
            'reply.required' => 'La réponse ne peut pas être vide.',
            'reply.max'      => 'La réponse ne doit pas dépasser 1000 caractères.',
        ]);

        $profile = auth()->user()->talentProfile;

        $booking = BookingRequest::where('talent_profile_id', $profile?->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->findOrFail($id);

        $review = Review::where('booking_request_id', $booking->id)
            ->where('id', $reviewId)
            ->where('type', ReviewType::ClientToTalent->value)
            ->whereNull('reply')
            ->firstOrFail();

        if ($review->reviewee_id !== auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas répondre à cet avis.');
        }

        $review->update([
            'reply'    => trim($request->string('reply')->value()),
            'reply_at' => now(),
        ]);

        return back()->with('success', 'Votre réponse a été publiée.');
    }

    /**
     * POST /bookings/{id}/review
     *
     * Talent submits a talent_to_client review for a completed booking.
     */
    public function reviewClient(int $id, Request $request): RedirectResponse
    {
        $request->validate([
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $profile = auth()->user()->talentProfile;

        $booking = BookingRequest::where('talent_profile_id', $profile?->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->findOrFail($id);

        // Ensure no talent_to_client review already exists
        $alreadyReviewed = Review::where('booking_request_id', $booking->id)
            ->where('type', ReviewType::TalentToClient->value)
            ->exists();

        if ($alreadyReviewed) {
            return back()->with('info', 'Vous avez déjà évalué ce client.');
        }

        $this->reviewService->submit(
            booking: $booking,
            reviewer: auth()->user(),
            type: ReviewType::TalentToClient,
            rating: (int) $request->input('rating'),
            comment: $request->filled('comment') ? trim($request->string('comment')->value()) : null,
        );

        return back()->with('success', 'Votre évaluation du client a été publiée.');
    }
}
