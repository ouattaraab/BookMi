<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthService;
use App\Exceptions\AuthException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WebPhoneVerifyController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function showOtp(): View|RedirectResponse
    {
        if (! session('pending_phone_verify')) {
            return redirect()->route('login');
        }

        return view('auth.verify-phone', [
            'phone' => session('pending_phone_verify'),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $phone = session('pending_phone_verify');
        if (! $phone) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        try {
            $this->authService->verifyOtp($phone, $request->string('code')->value());
        } catch (AuthException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        $user = User::where('phone', $phone)->firstOrFail();
        session()->forget('pending_phone_verify');

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectToDashboard($user);
    }

    public function resend(Request $request): RedirectResponse
    {
        $phone = session('pending_phone_verify');
        if (! $phone) {
            return redirect()->route('login');
        }

        try {
            $this->authService->resendOtp($phone);
        } catch (AuthException $e) {
            return back()->withErrors(['resend' => $e->getMessage()]);
        }

        return back()->with('success', 'Code renvoyé avec succès.');
    }

    private function redirectToDashboard(User $user): RedirectResponse
    {
        if ($user->hasRole('client'))  return redirect()->route('client.dashboard');
        if ($user->hasRole('talent'))  return redirect()->route('talent.dashboard');
        if ($user->hasRole('manager')) return redirect()->route('manager.dashboard');

        return redirect('/');
    }
}
