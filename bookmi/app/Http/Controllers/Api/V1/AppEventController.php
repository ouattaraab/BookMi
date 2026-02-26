<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\AppEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppEventController extends BaseController
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'events'              => 'required|array|max:50',
            'events.*.event_type' => 'required|in:page_view,button_tap',
            'events.*.event_name' => 'required|string|max:100',
            'events.*.session_id' => 'required|uuid',
            'platform'            => 'required|in:android,ios',
            'app_version'         => 'required|string|max:20',
        ]);

        $userId  = $request->user()?->id;
        $now     = now();
        $rows    = [];

        foreach ($validated['events'] as $event) {
            $rows[] = [
                'user_id'     => $userId,
                'event_type'  => $event['event_type'],
                'event_name'  => $event['event_name'],
                'session_id'  => $event['session_id'],
                'platform'    => $validated['platform'],
                'app_version' => $validated['app_version'],
                'created_at'  => $now,
            ];
        }

        AppEvent::insert($rows);

        return $this->successResponse(null, 201);
    }
}
