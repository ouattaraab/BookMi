<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\AdminException;
use App\Models\Review;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReviewModerationController extends BaseController
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    /**
     * POST /api/v1/reviews/{review}/report  (authenticated user)
     */
    public function report(Request $request, Review $review): JsonResponse
    {
        $data = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $review->update([
            'is_reported'   => true,
            'report_reason' => $data['reason'],
            'reported_at'   => now(),
        ]);

        return $this->successResponse(['message' => 'Signalement enregistré.']);
    }

    /**
     * GET /api/v1/admin/reviews/reported
     */
    public function reported(): JsonResponse
    {
        $reviews = Review::where('is_reported', true)
            ->with([
                'reviewer:id,first_name,last_name',
                'reviewee:id,first_name,last_name',
                'bookingRequest:id,event_date',
            ])
            ->latest('reported_at')
            ->paginate(20);

        return $this->paginatedResponse($reviews);
    }

    /**
     * POST /api/v1/admin/reviews/{review}/approve
     * Keep the review (clear the report flag).
     */
    public function approve(Request $request, Review $review): JsonResponse
    {
        if (! $review->is_reported) {
            throw AdminException::reviewNotReported();
        }

        $review->update([
            'is_reported'   => false,
            'report_reason' => null,
            'reported_at'   => null,
        ]);

        $this->audit->log('review.approved', $review, ['admin_id' => $request->user()->id]);

        return $this->successResponse(['message' => 'Avis conservé.']);
    }

    /**
     * DELETE /api/v1/admin/reviews/{review}
     */
    public function destroy(Request $request, Review $review): JsonResponse
    {
        $data = $request->validate(['reason' => 'required|string|max:500']);

        $this->audit->log('review.deleted', $review, [
            'reason'   => $data['reason'],
            'admin_id' => $request->user()->id,
        ]);

        $review->delete();

        return $this->successResponse(['message' => 'Avis supprimé.']);
    }

    /**
     * PATCH /api/v1/admin/reviews/{review}
     * Mask inappropriate content.
     */
    public function update(Request $request, Review $review): JsonResponse
    {
        $data = $request->validate([
            'comment' => 'required|string|max:2000',
            'reason'  => 'required|string|max:500',
        ]);

        $review->update([
            'comment'       => $data['comment'],
            'is_reported'   => false,
            'report_reason' => null,
            'reported_at'   => null,
        ]);

        $this->audit->log('review.edited', $review, [
            'reason'   => $data['reason'],
            'admin_id' => $request->user()->id,
        ]);

        return $this->successResponse($review);
    }
}
