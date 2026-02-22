<?php
namespace App\Http\Controllers\Web\Talent;

use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    private function profile()
    {
        return auth()->user()->talentProfile;
    }

    public function index(Request $request): View
    {
        $profile = $this->profile();
        if (!$profile) return view('talent.coming-soon', ['title' => 'Réservations', 'description' => 'Configurez votre profil d\'abord.']);

        $query = BookingRequest::where('talent_profile_id', $profile->id)
            ->with(['client', 'servicePackage'])
            ->orderByDesc('created_at');

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $bookings = $query->paginate(10)->withQueryString();
        return view('talent.bookings.index', compact('bookings'));
    }

    public function show(int $id): View
    {
        $profile = $this->profile();
        $booking = BookingRequest::where('talent_profile_id', $profile?->id)
            ->with(['client', 'servicePackage'])
            ->findOrFail($id);
        return view('talent.bookings.show', compact('booking'));
    }

    public function accept(int $id): RedirectResponse
    {
        $booking = BookingRequest::where('talent_profile_id', $this->profile()?->id)
            ->where('status', 'pending')
            ->findOrFail($id);
        $booking->update(['status' => 'accepted']);
        ActivityLogger::log('booking.accepted', $booking, ['client_email' => $booking->client?->email ?? $booking->client_name]);
        return back()->with('success', 'Réservation acceptée.');
    }

    public function reject(int $id): RedirectResponse
    {
        $booking = BookingRequest::where('talent_profile_id', $this->profile()?->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->findOrFail($id);
        $booking->update(['status' => 'cancelled']);
        ActivityLogger::log('booking.rejected', $booking, ['client_email' => $booking->client?->email ?? $booking->client_name]);
        return back()->with('success', 'Réservation refusée.');
    }

    public function complete(int $id): RedirectResponse
    {
        $booking = BookingRequest::where('talent_profile_id', $this->profile()?->id)
            ->where('status', 'confirmed')
            ->findOrFail($id);
        $booking->update(['status' => 'completed']);
        ActivityLogger::log('booking.completed', $booking);
        return back()->with('success', 'Réservation marquée comme terminée.');
    }
}
