<?php

namespace App\Http\Controllers\Web\Client;

use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function store(int $bookingId, Request $request): RedirectResponse
    {
        $request->validate([
            'reason'      => ['required', 'string', 'in:no_show,late_arrival,quality_issue,payment_issue,inappropriate_behaviour,other'],
            'description' => ['required', 'string', 'max:1000'],
        ]);

        $booking = BookingRequest::where('client_id', auth()->id())
            ->whereIn('status', ['completed', 'disputed'])
            ->findOrFail($bookingId);

        // Prevent duplicate reports from same user on same booking
        if (Report::where('booking_request_id', $booking->id)
            ->where('reporter_id', auth()->id())
            ->exists()) {
            return back()->withErrors(['report' => 'Vous avez déjà signalé cette réservation.']);
        }

        Report::create([
            'booking_request_id' => $booking->id,
            'reporter_id'        => auth()->id(),
            'reason'             => $request->reason,
            'description'        => $request->description,
            'status'             => 'pending',
        ]);

        return back()->with('report_success', 'Votre signalement a été transmis à notre équipe.');
    }
}
