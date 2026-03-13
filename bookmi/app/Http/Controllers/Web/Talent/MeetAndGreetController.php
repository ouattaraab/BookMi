<?php

namespace App\Http\Controllers\Web\Talent;

use App\Enums\ExperienceBookingStatus;
use App\Enums\ExperienceStatus;
use App\Http\Controllers\Controller;
use App\Models\PrivateExperience;
use App\Models\TalentProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Interface talent pour gérer ses Meet & Greet.
 */
class MeetAndGreetController extends Controller
{
    private function talentProfile(): TalentProfile
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return TalentProfile::where('user_id', $user->id)->firstOrFail();
    }

    public function index(): View
    {
        $profile     = $this->talentProfile();
        $experiences = PrivateExperience::where('talent_profile_id', $profile->id)
            ->withCount('bookings')
            ->orderByDesc('event_date')
            ->get();

        return view('talent.meet-and-greet.index', compact('experiences'));
    }

    public function create(): View
    {
        return view('talent.meet-and-greet.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'          => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string', 'max:3000'],
            'event_date'     => ['required', 'date', 'after:today'],
            'event_time'     => ['required', 'date_format:H:i'],
            'venue_address'  => ['nullable', 'string', 'max:255'],
            'total_price'    => ['required', 'integer', 'min:1000'],
            'max_seats'      => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $profile   = $this->talentProfile();
        $eventDate = $validated['event_date'] . ' ' . $validated['event_time'];

        PrivateExperience::create([
            'talent_profile_id' => $profile->id,
            'title'             => $validated['title'],
            'description'       => $validated['description'] ?? null,
            'event_date'        => $eventDate,
            'venue_address'     => $validated['venue_address'] ?? null,
            'total_price'       => $validated['total_price'],
            'max_seats'         => $validated['max_seats'],
            'commission_rate'   => (int) config('bookmi.commission_rate', 15),
            'status'            => ExperienceStatus::Draft->value,
        ]);

        return redirect()->route('talent.meet-and-greet.index')
            ->with('success', 'Votre Meet & Greet a été créé. Il est en brouillon — contactez l\'équipe BookMi pour le publier.');
    }

    public function show(int $id): View
    {
        $profile    = $this->talentProfile();
        $experience = PrivateExperience::where('id', $id)
            ->where('talent_profile_id', $profile->id)
            ->with(['bookings.client'])
            ->firstOrFail();

        // Statistiques financières
        $confirmedBookings = $experience->bookings->where('status', 'confirmed');
        $totalCollected    = $confirmedBookings->sum('total_amount');
        $totalCommission   = $confirmedBookings->sum('commission_amount');
        $talentNet         = $totalCollected - $totalCommission;

        return view('talent.meet-and-greet.show', compact(
            'experience',
            'totalCollected',
            'totalCommission',
            'talentNet'
        ));
    }

    public function cancel(int $id, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cancelled_reason' => ['required', 'string', 'max:1000'],
        ]);

        $profile    = $this->talentProfile();
        $experience = PrivateExperience::where('id', $id)
            ->where('talent_profile_id', $profile->id)
            ->whereIn('status', [ExperienceStatus::Draft->value, ExperienceStatus::Published->value, ExperienceStatus::Full->value])
            ->firstOrFail();

        DB::transaction(function () use ($experience, $validated): void {
            // Annuler toutes les réservations actives des participants
            $experience->bookings()
                ->whereIn('status', [ExperienceBookingStatus::Pending->value, ExperienceBookingStatus::Confirmed->value])
                ->update([
                    'status'           => ExperienceBookingStatus::Cancelled->value,
                    'cancelled_at'     => now(),
                    'cancelled_reason' => "Événement annulé par l'artiste.",
                ]);

            $experience->update([
                'status'           => ExperienceStatus::Cancelled->value,
                'cancelled_reason' => $validated['cancelled_reason'],
            ]);
        });

        return redirect()->route('talent.meet-and-greet.index')
            ->with('warning', 'L\'événement a été annulé. Les réservations des participants ont été annulées.');
    }
}
