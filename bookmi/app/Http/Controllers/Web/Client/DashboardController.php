<?php

namespace App\Http\Controllers\Web\Client;

use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $bookings = BookingRequest::where('client_id', $user->id)
            ->with('talentProfile.user')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $rawStats = BookingRequest::where('client_id', $user->id)
            ->selectRaw(
                'COUNT(*) AS total,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS pending,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS confirmed,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS completed',
                ['pending', 'confirmed', 'completed']
            )
            ->first();

        $stats = [
            'total'     => (int) ($rawStats->total ?? 0),
            'pending'   => (int) ($rawStats->pending ?? 0),
            'confirmed' => (int) ($rawStats->confirmed ?? 0),
            'completed' => (int) ($rawStats->completed ?? 0),
        ];

        return view('client.dashboard', compact('bookings', 'stats'));
    }
}
