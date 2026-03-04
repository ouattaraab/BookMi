<?php

namespace App\Http\Controllers\Web\Talent;

use App\Exceptions\BookmiException;
use App\Http\Controllers\Controller;
use App\Models\ManagerInvitation;
use App\Services\ManagerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManagerController extends Controller
{
    public function __construct(
        private readonly ManagerService $managerService,
    ) {
    }

    public function index(): View
    {
        $user    = auth()->user();
        $talent  = $user->talentProfile;

        $invitations = $talent
            ? ManagerInvitation::with('manager:id,first_name,last_name,email')
                ->where('talent_profile_id', $talent->id)
                ->orderByRaw("FIELD(status, 'pending', 'accepted', 'rejected')")
                ->orderByDesc('invited_at')
                ->get()
            : collect();

        return view('talent.managers.index', compact('invitations', 'talent'));
    }

    public function invite(Request $request): RedirectResponse
    {
        $data   = $request->validate(['email' => ['required', 'email']]);
        $talent = auth()->user()->talentProfile;

        if (! $talent) {
            return back()->with('error', 'Profil talent introuvable.');
        }

        try {
            $this->managerService->inviteManager($talent, $data['email']);
            return back()->with('success', "Invitation envoyée à {$data['email']}. Le manager recevra un email.");
        } catch (BookmiException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function cancel(int $id): RedirectResponse
    {
        $talent     = auth()->user()->talentProfile;
        $invitation = ManagerInvitation::findOrFail($id);

        if (! $talent || $invitation->talent_profile_id !== $talent->id) {
            abort(403);
        }

        if ($invitation->status->value !== 'pending') {
            return back()->with('error', "Cette invitation ne peut plus être annulée.");
        }

        $invitation->delete();

        return back()->with('success', 'Invitation annulée.');
    }

    public function removeManager(int $id): RedirectResponse
    {
        $talent     = auth()->user()->talentProfile;
        $invitation = ManagerInvitation::findOrFail($id);

        if (! $talent || $invitation->talent_profile_id !== $talent->id) {
            abort(403);
        }

        // Detach from pivot
        if ($invitation->manager_id) {
            $talent->managers()->detach($invitation->manager_id);
        }

        $invitation->delete();

        return back()->with('success', 'Manager retiré de votre équipe.');
    }
}
