<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WebLoginController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactorService) {}

    public function showForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectToDashboard(Auth::user());
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Ces identifiants ne correspondent à aucun compte.',
            ])->onlyInput('email');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check account active
        if (! $user->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'Votre compte a été désactivé.'])->onlyInput('email');
        }

        // Check 2FA (only if explicitly enabled by the user)
        if ($user->two_factor_enabled) {
            $token = $this->twoFactorService->generateChallengeToken($user);

            if ($user->two_factor_method === 'email') {
                $this->twoFactorService->sendEmailOtp($user);
            }

            session([
                '2fa_challenge_token' => $token,
                '2fa_method'          => $user->two_factor_method,
            ]);

            Auth::logout();
            request()->session()->migrate(true);

            return redirect()->route('auth.2fa.challenge');
        }

        $request->session()->regenerate();

        return $this->redirectToDashboard($user);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectToDashboard(\App\Models\User $user): RedirectResponse
    {
        if ($user->hasRole('client'))  return redirect()->route('client.dashboard');
        if ($user->hasRole('talent'))  return redirect()->route('talent.dashboard');
        if ($user->hasRole('manager')) return redirect()->route('manager.dashboard');

        return redirect('/');
    }
}
