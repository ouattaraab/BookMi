<?php

namespace App\Http\Controllers\Web\Talent;

use App\Http\Controllers\Controller;
use App\Models\CalendarSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $profile = auth()->user()->talentProfile;
        if (!$profile) {
            return view('talent.coming-soon', ['title' => 'Calendrier', 'description' => 'Configurez votre profil d\'abord.']);
        }

        $month = $request->integer('month', (int) now()->format('m'));
        $year  = $request->integer('year', (int) now()->format('Y'));

        $slots = CalendarSlot::where('talent_profile_id', $profile->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->keyBy(fn ($s) => $s->date->format('Y-m-d'));

        return view('talent.calendar.index', compact('slots', 'month', 'year', 'profile'));
    }

    public function setAvailability(Request $request): RedirectResponse
    {
        $request->validate([
            'date'   => 'required|date|after_or_equal:today',
            'status' => 'required|in:available,blocked,rest',
        ]);

        $profile = auth()->user()->talentProfile;
        CalendarSlot::updateOrCreate(
            ['talent_profile_id' => $profile->id, 'date' => $request->date],
            ['status' => $request->status]
        );

        return back()->with('success', 'Disponibilité mise à jour.');
    }

    public function removeAvailability(int $id): RedirectResponse
    {
        $slot = CalendarSlot::where('talent_profile_id', auth()->user()->talentProfile?->id)->findOrFail($id);
        $slot->delete();
        return back()->with('success', 'Créneau supprimé.');
    }
}
