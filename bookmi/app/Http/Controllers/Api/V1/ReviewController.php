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
            punctualityScore: $request->validated('punctuality_score') !== null
                ? (int) $request->validated('punctuality_score') : null,
            qualityScore: $request->validated('quality_score') !== null
                ? (int) $request->validated('quality_score') : null,
            professionalismScore: $request->validated('professionalism_score') !== null
                ? (int) $request->validated('professionalism_score') : null,
            contractRespectScore: $request->validated('contract_respect_score') !== null
                ? (int) $request->validated('contract_respect_score') : null,
        );

        return response()->json([
            'data' => [
                'id'                     => $review->id,
                'booking_request_id'     => $review->booking_request_id,
                'reviewer_id'            => $review->reviewer_id,
                'reviewee_id'            => $review->reviewee_id,
                'type'                   => $review->type->value,
                'rating'                 => $review->rating,
                'punctuality_score'      => $review->punctuality_score,
                'quality_score'          => $review->quality_score,
                'professionalism_score'  => $review->professionalism_score,
                'contract_respect_score' => $review->contract_respect_score,
                'comment'                => $review->comment,
                'created_at'             => $review->created_at?->toISOString(),
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
                'id'                     => $r->id,
                'type'                   => $r->type->value,
                'rating'                 => $r->rating,
                'punctuality_score'      => $r->punctuality_score,
                'quality_score'          => $r->quality_score,
                'professionalism_score'  => $r->professionalism_score,
                'contract_respect_score' => $r->contract_respect_score,
                'comment'                => $r->comment,
                'reply'                  => $r->reply,
                'reply_at'               => $r->reply_at instanceof \Carbon\Carbon ? $r->reply_at->toISOString() : null,
                'reviewer_id'            => $r->reviewer_id,
                'reviewee_id' => $r->reviewee_id,
                'created_at'  => $r->created_at?->toISOString(),
            ]);

        return response()->json(['data' => $reviews]);
    }

    /**
     * POST /reviews/{review}/reply
     *
     * Talent replies to a client_to_talent review. Only the reviewee (talent) may
     * reply, and only once.
     */
    public function reply(Request $request, Review $review): JsonResponse
    {
        $request->validate([
            'reply' => ['required', 'string', 'max:1000'],
        ], [
            'reply.required' => 'La réponse ne peut pas être vide.',
            'reply.max'      => 'La réponse ne doit pas dépasser 1000 caractères.',
        ]);

        $user = $request->user();

        if ($review->reviewee_id !== $user->id) {
            return response()->json([
                'error' => [
                    'code'    => 'FORBIDDEN',
                    'message' => 'Seul le destinataire de l\'avis peut y répondre.',
                ],
            ], 403);
        }

        if ($review->type !== ReviewType::ClientToTalent) {
            return response()->json([
                'error' => [
                    'code'    => 'INVALID_REVIEW_TYPE',
                    'message' => 'Seuls les avis clients peuvent recevoir une réponse.',
                ],
            ], 422);
        }

        if ($review->reply !== null) {
            return response()->json([
                'error' => [
                    'code'    => 'ALREADY_REPLIED',
                    'message' => 'Une réponse a déjà été publiée pour cet avis.',
                ],
            ], 422);
        }

        $review->update([
            'reply'    => $request->string('reply')->trim()->value(),
            'reply_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'reply'    => $review->reply,
                'reply_at' => $review->reply_at instanceof \Carbon\Carbon ? $review->reply_at->toISOString() : null,
            ],
        ]);
    }
}
