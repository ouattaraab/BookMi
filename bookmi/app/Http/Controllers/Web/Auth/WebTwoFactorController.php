<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WebTwoFactorController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactorService) {}

    public function showChallenge(): View|RedirectResponse
    {
        if (! session('2fa_challenge_token')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor', [
            'method' => session('2fa_method', 'totp'),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $token = session('2fa_challenge_token');
        if (! $token) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $this->twoFactorService->validateChallengeToken($token);
        if (! $user) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Session expirée. Veuillez vous reconnecter.']);
        }

        $code = $request->string('code')->value();
        $valid = false;

        if ($user->two_factor_method === 'totp') {
            $valid = $this->twoFactorService->verifyTotp($user->two_factor_secret, $code);
        } else {
            $valid = $this->twoFactorService->verifyEmailOtp($user, $code);
        }

        if (! $valid) {
            // Re-generate challenge token so the user can retry
            $newToken = $this->twoFactorService->generateChallengeToken($user);
            session(['2fa_challenge_token' => $newToken]);

            return back()->withErrors(['code' => 'Code invalide. Veuillez réessayer.']);
        }

        session()->forget(['2fa_challenge_token', '2fa_method']);

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectToDashboard($user);
    }

    private function redirectToDashboard(User $user): RedirectResponse
    {
        if ($user->hasRole('client'))  return redirect()->route('client.dashboard');
        if ($user->hasRole('talent'))  return redirect()->route('talent.dashboard');
        if ($user->hasRole('manager')) return redirect()->route('manager.dashboard');

        return redirect('/');
    }
}
