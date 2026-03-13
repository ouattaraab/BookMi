<?php

namespace App\Http\Controllers\Web\Client;

use App\Enums\ExperienceBookingStatus;
use App\Enums\ExperienceStatus;
use App\Http\Controllers\Controller;
use App\Models\ExperienceBooking;
use App\Models\PrivateExperience;
use Illuminate\Database\QueryException;
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
        $seats  = (int) $validated['seats_count'];
        $expId  = (int) $validated['experience_id'];

        try {
            DB::transaction(function () use ($expId, $seats, $client): void {
                // lockForUpdate INSIDE transaction — verrou réel
                $experience = PrivateExperience::where('id', $expId)
                    ->where('status', ExperienceStatus::Published->value)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (ExperienceBooking::where('private_experience_id', $expId)
                    ->where('client_id', $client->id)
                    ->exists()) {
                    abort(422, 'Vous êtes déjà inscrit à cet événement.');
                }

                if ($experience->seats_available < $seats) {
                    abort(422, 'Désolé, il ne reste que ' . $experience->seats_available . ' place(s) disponible(s).');
                }

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

                $experience->increment('booked_seats', $seats);
                $experience->refresh();

                if ($experience->booked_seats >= $experience->max_seats) {
                    $experience->update(['status' => ExperienceStatus::Full->value]);
                }
            });
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->with('error', 'Vous êtes déjà inscrit à cet événement.');
            }
            throw $e;
        }

        return back()->with('success', 'Votre place est réservée ! L\'équipe BookMi vous contactera pour finaliser votre inscription.');
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

        DB::transaction(function () use ($booking): void {
            $booking->update([
                'status'       => ExperienceBookingStatus::Cancelled->value,
                'cancelled_at' => now(),
            ]);

            // lockForUpdate pour éviter la lecture d'un état périmé
            /** @var PrivateExperience|null $exp */
            $exp = $booking->experience()->lockForUpdate()->first();

            if ($exp instanceof PrivateExperience) {
                $exp->decrement('booked_seats', $booking->seats_count);
                $exp->refresh();

                if ($exp->status === ExperienceStatus::Full) {
                    $exp->update(['status' => ExperienceStatus::Published->value]);
                }
            }
        });

        return back()->with('info', 'Votre inscription a été annulée.');
    }
}
