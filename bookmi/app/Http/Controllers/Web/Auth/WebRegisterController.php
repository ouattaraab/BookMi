<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WebRegisterController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function showForm(): View|RedirectResponse
    {
        if (Auth::check() && !auth()->user()->is_admin) {
            return redirect('/');
        }

        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name'  => ['required', 'string', 'max:60'],
            'email'      => ['required', 'email', 'unique:users,email'],
            'phone'      => ['required', 'string', 'regex:/^\+?[0-9\s\-]{8,20}$/'],
            'password'   => ['required', 'string', 'min:8', 'confirmed'],
            'role'       => ['required', 'in:client,talent'],
        ]);

        $user = $this->authService->register($validated);

        // Auto-connexion immédiate — la vérification OTP est désactivée
        Auth::login($user);
        $request->session()->regenerate();

        ActivityLogger::log('auth.register', $user, ['role' => $validated['role']], $user->id);

        return $this->redirectToDashboard($user);
    }

    private function redirectToDashboard(User $user): RedirectResponse
    {
        if ($user->hasRole('client', 'api')) {
            return redirect()->route('client.dashboard');
        }
        if ($user->hasRole('talent', 'api')) {
            return redirect()->route('talent.dashboard');
        }
        if ($user->hasRole('manager', 'api')) {
            return redirect()->route('manager.dashboard');
        }

        return redirect('/');
    }
}
