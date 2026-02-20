<?php

namespace App\Http\Controllers\Web\Client;

use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $query = BookingRequest::where('client_id', auth()->id())
            ->with(['talentProfile.user', 'talentProfile.category', 'servicePackage'])
            ->orderByDesc('created_at');

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $bookings = $query->paginate(10)->withQueryString();
        return view('client.bookings.index', compact('bookings'));
    }

    public function show(int $id): View
    {
        $booking = BookingRequest::where('client_id', auth()->id())
            ->with(['talentProfile.user', 'talentProfile.category', 'servicePackage'])
            ->findOrFail($id);
        return view('client.bookings.show', compact('booking'));
    }

    public function cancel(int $id): RedirectResponse
    {
        $booking = BookingRequest::where('client_id', auth()->id())
            ->whereIn('status', ['pending', 'accepted'])
            ->findOrFail($id);
        $booking->update(['status' => 'cancelled']);
        return back()->with('success', 'Réservation annulée avec succès.');
    }

    public function pay(int $id): View
    {
        $booking = BookingRequest::where('client_id', auth()->id())
            ->where('status', 'accepted')
            ->with(['talentProfile.user', 'servicePackage'])
            ->findOrFail($id);
        return view('client.bookings.pay', compact('booking'));
    }

    public function processPayment(int $id, Request $request): RedirectResponse
    {
        return back()->with('info', 'Intégration Paystack en cours de développement.');
    }

    public function paymentCallback(): RedirectResponse
    {
        return redirect()->route('client.bookings')->with('info', 'Callback de paiement traité.');
    }
}
