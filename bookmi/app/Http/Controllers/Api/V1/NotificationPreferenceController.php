<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends BaseController
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $prefs = $user->notification_preferences ?? \App\Models\User::defaultNotificationPreferences();

        return $this->successResponse($prefs);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'new_message'     => 'sometimes|boolean',
            'booking_updates' => 'sometimes|boolean',
            'new_review'      => 'sometimes|boolean',
            'follow_update'   => 'sometimes|boolean',
            'admin_broadcast' => 'sometimes|boolean',
        ]);

        $user = $request->user();
        $current = $user->notification_preferences ?? \App\Models\User::defaultNotificationPreferences();
        $merged  = array_merge($current, $validated);

        $user->update(['notification_preferences' => $merged]);

        return $this->successResponse($merged);
    }
}
