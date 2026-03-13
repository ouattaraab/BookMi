<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ExperienceBooking;
use App\Models\PrivateExperience;
use Illuminate\View\View;

/**
 * Public (guest + client) — fiche Meet & Greet avec formulaire d'inscription.
 */
class MeetAndGreetController extends Controller
{
    public function show(int $id): View
    {
        $experience = PrivateExperience::with(['talentProfile.user', 'talentProfile.category'])
            ->whereIn('status', \App\Enums\ExperienceStatus::visibleOnPublic())
            ->findOrFail($id);

        /** @var ExperienceBooking|null $myBooking */
        $myBooking = null;
        if (auth()->check()) {
            $myBooking = ExperienceBooking::where('private_experience_id', $experience->id)
                ->where('client_id', auth()->id())
                ->first();
        }

        return view('meet-and-greet.show', compact('experience', 'myBooking'));
    }
}
