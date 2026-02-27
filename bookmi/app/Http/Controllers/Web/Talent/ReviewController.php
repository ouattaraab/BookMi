<?php

namespace App\Http\Controllers\Web\Talent;

use App\Enums\ReviewType;
use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
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
}
