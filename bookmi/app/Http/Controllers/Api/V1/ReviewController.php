<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ReviewType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SubmitReviewRequest;
use App\Models\BookingRequest;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService,
    ) {
    }

    /**
     * POST /booking_requests/{booking}/reviews
     */
    public function store(SubmitReviewRequest $request, BookingRequest $booking): JsonResponse
    {
        $user   = $request->user();
        $type   = ReviewType::from($request->validated('type'));
        $rating = (int) $request->validated('rating');

        // Verify the reviewer is a participant of the booking
        if (! $booking->isOwnedByUser($user)) {
            return response()->json([
                'error' => [
                    'code'    => 'FORBIDDEN',
                    'message' => 'You are not a participant in this booking.',
                ],
            ], 403);
        }

        $review = $this->reviewService->submit(
            booking: $booking,
            reviewer: $user,
            type: $type,
            rating: $rating,
            comment: $request->validated('comment'),
        );

        return response()->json([
            'data' => [
                'id'                 => $review->id,
                'booking_request_id' => $review->booking_request_id,
                'reviewer_id'        => $review->reviewer_id,
                'reviewee_id'        => $review->reviewee_id,
                'type'               => $review->type->value,
                'rating'             => $review->rating,
                'comment'            => $review->comment,
                'created_at'         => $review->created_at?->toISOString(),
            ],
        ], 201);
    }

    /**
     * GET /booking_requests/{booking}/reviews
     */
    public function index(Request $request, BookingRequest $booking): JsonResponse
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

        $reviews = Review::where('booking_request_id', $booking->id)
            ->get()
            ->map(fn ($r) => [
                'id'          => $r->id,
                'type'        => $r->type->value,
                'rating'      => $r->rating,
                'comment'     => $r->comment,
                'reviewer_id' => $r->reviewer_id,
                'reviewee_id' => $r->reviewee_id,
                'created_at'  => $r->created_at?->toISOString(),
            ]);

        return response()->json(['data' => $reviews]);
    }
}
