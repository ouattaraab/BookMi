<?php

namespace App\Http\Controllers\Web\Client;

use App\Enums\ExperienceBookingStatus;
use App\Enums\ExperienceStatus;
use App\Http\Controllers\Controller;
use App\Models\ExperienceBooking;
use App\Models\PrivateExperience;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExperienceBookingController extends Controller
{
    /**
     * Réserver une place sur un Meet & Greet.
     */
    public function book(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'experience_id' => ['required', 'integer', 'exists:private_experiences,id'],
            'seats_count'   => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        /** @var \App\Models\User $client */
        $client = auth()->user();

        $experience = PrivateExperience::where('id', $validated['experience_id'])
            ->where('status', ExperienceStatus::Published->value)
            ->lockForUpdate()
            ->firstOrFail();

        // Déjà inscrit ?
        if (ExperienceBooking::where('private_experience_id', $experience->id)
            ->where('client_id', $client->id)
            ->exists()) {
            return back()->with('error', 'Vous êtes déjà inscrit à cet événement.');
        }

        $seats = (int) $validated['seats_count'];

        if ($experience->seats_available < $seats) {
            return back()->with('error', 'Désolé, il ne reste que ' . $experience->seats_available . ' place(s) disponible(s).');
        }

        DB::transaction(function () use ($experience, $seats, $client) {
            $pricePerSeat     = $experience->price_per_seat;
            $totalAmount      = $pricePerSeat * $seats;
            $commissionAmount = (int) round($totalAmount * $experience->commission_rate / 100);

            ExperienceBooking::create([
                'private_experience_id' => $experience->id,
                'client_id'             => $client->id,
                'seats_count'           => $seats,
                'price_per_seat'        => $pricePerSeat,
                'total_amount'          => $totalAmount,
                'commission_amount'     => $commissionAmount,
                'status'                => ExperienceBookingStatus::Pending->value,
            ]);

            // Incrémenter les places réservées atomiquement
            $experience->increment('booked_seats', $seats);

            // Passer en 'full' si toutes les places sont prises
            $experience->refresh();
            if ($experience->booked_seats >= $experience->max_seats) {
                $experience->update(['status' => ExperienceStatus::Full->value]);
            }
        });

        return back()->with('success', '🎉 Votre place est réservée ! L\'équipe BookMi vous contactera pour finaliser votre inscription.');
    }

    /**
     * Annuler sa propre réservation.
     */
    public function cancel(int $bookingId): RedirectResponse
    {
        /** @var \App\Models\User $client */
        $client = auth()->user();

        $booking = ExperienceBooking::where('id', $bookingId)
            ->where('client_id', $client->id)
            ->where('status', ExperienceBookingStatus::Pending->value)
            ->firstOrFail();

        DB::transaction(function () use ($booking) {
            $booking->update([
                'status'       => ExperienceBookingStatus::Cancelled->value,
                'cancelled_at' => now(),
            ]);
            // Libérer les places
            $booking->experience()->decrement('booked_seats', $booking->seats_count);
            // Si l'expérience était "full", la repasser en "published"
            /** @var PrivateExperience|null $exp */
            $exp = $booking->experience()->first();
            if ($exp instanceof PrivateExperience && $exp->status === ExperienceStatus::Full) {
                $exp->update(['status' => ExperienceStatus::Published->value]);
            }
        });

        return back()->with('info', 'Votre inscription a été annulée.');
    }
}
