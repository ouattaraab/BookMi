<?php

namespace App\Http\Controllers\Web\Manager;

use App\Http\Controllers\Controller;
use App\Models\ManagerInvitation;
use App\Services\ManagerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManagerInvitationWebController extends Controller
{
    public function __construct(
        private readonly ManagerService $managerService,
    ) {
    }

    public function show(string $token): View
    {
        $invitation = ManagerInvitation::with(['talentProfile.user'])
            ->where('token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        return view('manager.invitations.respond', compact('invitation'));
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $invitation = ManagerInvitation::with(['talentProfile.user'])
            ->where('token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        $data = $request->validate([
            'action'  => ['required', 'in:accept,reject'],
            'comment' => [
                'nullable',
                'string',
                'max:500',
                \Illuminate\Validation\Rule::requiredIf($request->input('action') === 'reject'),
            ],
        ]);

        // Link the manager_id if the current authenticated user is the invitee
        if (auth()->check() && strtolower(auth()->user()->email) === $invitation->manager_email) {
            if (! $invitation->manager_id) {
                $invitation->update(['manager_id' => auth()->id()]);
            }
        }

        if ($data['action'] === 'accept') {
            $this->managerService->acceptInvitation($invitation->fresh(), $data['comment'] ?? null);

            if (auth()->check()) {
                return redirect()->route('manager.dashboard')
                    ->with('success', 'Vous avez accepté l\'invitation. Bienvenue dans l\'équipe !');
            }

            return redirect()->route('login')
                ->with('success', 'Invitation acceptée ! Connectez-vous pour accéder à votre espace manager.');
        }

        $this->managerService->rejectInvitation($invitation, $data['comment']);
        return redirect()->route('home')
            ->with('info', 'Vous avez refusé l\'invitation.');
    }
}
