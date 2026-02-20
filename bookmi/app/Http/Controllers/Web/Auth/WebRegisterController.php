<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WebRegisterController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

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

        // Store phone in session for OTP verification
        session(['pending_phone_verify' => $user->phone]);

        return redirect()->route('auth.verify-phone')
            ->with('success', 'Compte créé ! Veuillez vérifier votre numéro de téléphone.');
    }
}
