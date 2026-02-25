<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\PushNotificationResource;
use App\Models\PushNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends BaseController
{
    /**
     * GET /api/v1/notifications
     * List all push notifications for the authenticated user (paginated, most recent first).
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = PushNotification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->paginatedResponse(
            $paginator->through(fn ($n) => new PushNotificationResource($n)),
        );
    }

    /**
     * POST /api/v1/notifications/{notification}/read
     * Mark a notification as read.
     */
    public function markRead(Request $request, PushNotification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return $this->errorResponse('FORBIDDEN', 'Access denied.', 403);
        }

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return $this->successResponse(new PushNotificationResource($notification->fresh()));
    }

    /**
     * POST /api/v1/notifications/read-all
     * Mark all unread notifications as read for the authenticated user.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $count = PushNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->successResponse(['marked_read' => $count]);
    }

    /**
     * GET /api/v1/me/broadcasts
     * Returns the latest admin_broadcast push notifications for the authenticated user.
     */
    public function broadcasts(Request $request): JsonResponse
    {
        $items = PushNotification::where('user_id', $request->user()->id)
            ->where('data->type', 'admin_broadcast')
            ->orderByDesc('created_at')
            ->take(30)
            ->get();

        return $this->successResponse(
            $items->map(fn ($n) => new PushNotificationResource($n))->values(),
        );
    }

    /**
     * PUT /api/v1/me/fcm_token
     * Register or update the FCM device token for the authenticated user.
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => ['required', 'string', 'min:10', 'max:512'],
        ]);

        $request->user()->update(['fcm_token' => $request->string('fcm_token')->toString()]);

        return $this->successResponse(['fcm_token' => $request->user()->fresh()->fcm_token]);
    }
}
