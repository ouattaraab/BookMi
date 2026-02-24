<?php

namespace App\Http\Controllers\Web\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AdminFcmController extends Controller
{
    /**
     * Store or update the FCM device token for the authenticated admin.
     *
     * POST /admin/fcm-token
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string|max:255']);

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $user->update(['fcm_token' => $request->string('token')]);

        return response()->json(['ok' => true]);
    }
}
