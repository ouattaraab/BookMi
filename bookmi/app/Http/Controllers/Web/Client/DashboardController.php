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

        $stats = [
            'total'     => BookingRequest::where('client_id', $user->id)->count(),
            'pending'   => BookingRequest::where('client_id', $user->id)->where('status', 'pending')->count(),
            'confirmed' => BookingRequest::where('client_id', $user->id)->where('status', 'confirmed')->count(),
            'completed' => BookingRequest::where('client_id', $user->id)->where('status', 'completed')->count(),
        ];

        return view('client.dashboard', compact('bookings', 'stats'));
    }
}
