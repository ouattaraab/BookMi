<?php

namespace App\Http\Controllers\Web\Talent;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function reportReview(int $bookingId, int $reviewId, Request $request): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $review = Review::whereHas('bookingRequest', function ($q) {
            $q->whereHas('talentProfile', function ($q2) {
                $q2->where('user_id', auth()->id());
            });
        })->findOrFail($reviewId);

        Log::info('review.reported', [
            'review_id'  => $review->id,
            'booking_id' => $bookingId,
            'reporter'   => auth()->id(),
            'reason'     => $request->reason,
        ]);

        return back()->with('success', "Avis signalé. Notre équipe va l'examiner.");
    }
}
