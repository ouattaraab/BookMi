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

            // Case 1: Already authenticated as manager → go straight to dashboard
            if (auth()->check() && auth()->user()->hasRole('manager')) {
                return redirect()->route('manager.dashboard')
                    ->with('success', 'Vous avez accepté l\'invitation. Bienvenue dans l\'équipe !');
            }

            // Case 2: Email already has an account on the platform → redirect to login
            $existingUser = \App\Models\User::where('email', $invitation->manager_email)->first();
            if ($existingUser) {
                return redirect()->route('login')
                    ->with('success', 'Invitation acceptée ! Connectez-vous pour accéder à votre espace manager.');
            }

            // Case 3: No account yet → store token in session, redirect to register pre-filled
            session(['manager_invitation_token' => $invitation->token]);

            return redirect()->route('register', [
                'email' => $invitation->manager_email,
                'role'  => 'manager',
            ])->with('info', 'Invitation acceptée ! Créez votre compte pour accéder à votre espace manager.');
        }

        $this->managerService->rejectInvitation($invitation, $data['comment']);
        return redirect()->route('home')
            ->with('info', 'Vous avez refusé l\'invitation.');
    }
}
