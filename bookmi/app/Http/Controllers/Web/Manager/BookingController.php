<?php
namespace App\Http\Controllers\Web\Manager;

use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use App\Models\TalentProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    private function managedProfileIds()
    {
        return TalentProfile::whereHas('managers', fn ($q) => $q->where('users.id', auth()->id()))->pluck('id');
    }

    public function index(Request $request): View
    {
        $profileIds = $this->managedProfileIds();
        $query = BookingRequest::whereIn('talent_profile_id', $profileIds)
            ->with(['talentProfile.user', 'client', 'servicePackage'])
            ->orderByDesc('created_at');

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }
        if ($talentId = $request->integer('talent_id')) {
            $query->where('talent_profile_id', $talentId);
        }

        $bookings = $query->paginate(15)->withQueryString();
        $talents  = TalentProfile::whereIn('id', $profileIds)->with('user')->get();

        return view('manager.bookings.index', compact('bookings', 'talents'));
    }

    public function show(int $id): View
    {
        $profileIds = $this->managedProfileIds();
        $booking = BookingRequest::whereIn('talent_profile_id', $profileIds)
            ->with(['talentProfile.user', 'client', 'servicePackage'])
            ->findOrFail($id);
        return view('manager.bookings.show', compact('booking'));
    }
}
