<?php

namespace App\Http\Controllers\Web\Manager;

use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use App\Models\TalentProfile;
use Illuminate\View\View;

class TalentController extends Controller
{
    public function index(): View
    {
        $talents = TalentProfile::whereHas('managers', fn ($q) => $q->where('users.id', auth()->id()))
            ->with(['user', 'category'])
            ->get();
        return view('manager.talents.index', compact('talents'));
    }

    public function show(int $id): View
    {
        $talent = TalentProfile::whereHas('managers', fn ($q) => $q->where('users.id', auth()->id()))
            ->with(['user', 'category', 'servicePackages'])
            ->findOrFail($id);

        $bookings = BookingRequest::where('talent_profile_id', $id)
            ->with(['client', 'servicePackage'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('manager.talents.show', compact('talent', 'bookings'));
    }
}
