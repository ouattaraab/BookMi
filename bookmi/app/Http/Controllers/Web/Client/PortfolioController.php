<?php

namespace App\Http\Controllers\Web\Client;

use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function store(int $bookingId, Request $request): RedirectResponse
    {
        $request->validate([
            'media'   => ['required', 'array', 'max:5'],
            'media.*' => ['file', 'mimes:jpeg,jpg,png,gif,webp,mp4,mov', 'max:51200'],
        ]);

        $booking = BookingRequest::where('client_id', auth()->id())
            ->where('status', 'completed')
            ->findOrFail($bookingId);

        foreach ($request->file('media', []) as $file) {
            $path = $file->store("portfolio/booking-{$bookingId}", 'public');
            $type = str_starts_with($file->getMimeType() ?? '', 'video') ? 'video' : 'image';
            $booking->talentProfile->portfolioItems()->create([
                'talent_profile_id'   => $booking->talent_profile_id,
                'booking_request_id'  => $booking->id,
                'media_type'          => $type,
                'original_path'       => $path,
                'submitted_by_client' => true,
                'submitted_by_user_id' => auth()->id(),
                'is_approved'         => false,
            ]);
        }

        return back()->with('portfolio_success', 'Vos médias ont été soumis pour validation.');
    }
}
