<?php

namespace App\Http\Controllers\Web\Client;

use App\Http\Controllers\Controller;
use App\Models\PushNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $notifications = PushNotification::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(20);

        $unreadCount = PushNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->count();

        return view('client.notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markRead(int $id): RedirectResponse
    {
        PushNotification::where('id', $id)
            ->where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }

    public function markAllRead(): RedirectResponse
    {
        PushNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }
}
