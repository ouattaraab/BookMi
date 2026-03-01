<?php

namespace App\Http\Controllers\Web\Talent;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactorService)
    {
    }

    public function index(): View
    {
        $user   = auth()->user();
        $qrCode = null;
        $secret = null;

        if ($tempSecret = session('2fa_setup_secret')) {
            $secret = $tempSecret;
            $qrCode = $this->twoFactorService->getQrCodeSvg($user, $tempSecret);
        }

        $notifPrefs = $user->notification_preferences ?? \App\Models\User::defaultNotificationPreferences();

        return view('talent.settings.index', compact('user', 'qrCode', 'secret', 'notifPrefs'));
    }

    public function setupTotp(Request $request): RedirectResponse
    {
        $data = $this->twoFactorService->setupTotp(auth()->user());
        session(['2fa_setup_secret' => $data['secret']]);
        return redirect()->route('talent.settings');
    }

    public function enableTotp(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|string']);
        $secret = session('2fa_setup_secret');
        if (!$secret) {
            return back()->with('error', 'Session expirée, veuillez recommencer.');
        }

        try {
            $this->twoFactorService->enableTotp(auth()->user(), $request->code);
            session()->forget('2fa_setup_secret');
            return back()->with('success', '2FA TOTP activée avec succès !');
        } catch (\Exception $e) {
            return back()->with('error', 'Code invalide. Vérifiez votre application authenticator.');
        }
    }

    public function setupEmail(Request $request): RedirectResponse
    {
        $this->twoFactorService->setupEmail(auth()->user());
        return back()->with('success', 'Code envoyé par email.');
    }

    public function enableEmail(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|string']);
        try {
            $this->twoFactorService->enableEmail(auth()->user(), $request->code);
            return back()->with('success', '2FA Email activée avec succès !');
        } catch (\Exception $e) {
            return back()->with('error', 'Code invalide ou expiré.');
        }
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate(['password' => 'required|string']);
        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->with('error', 'Mot de passe incorrect.');
        }
        $this->twoFactorService->disable(auth()->user());
        return back()->with('success', '2FA désactivée.');
    }

    public function updateNotificationPreferences(Request $request): RedirectResponse
    {
        $keys = ['new_message', 'booking_updates', 'new_review', 'follow_update', 'admin_broadcast'];
        $prefs = [];
        foreach ($keys as $key) {
            $prefs[$key] = $request->boolean($key);
        }
        $user = auth()->user();
        $user->update(['notification_preferences' => array_merge(
            $user->notification_preferences ?? \App\Models\User::defaultNotificationPreferences(),
            $prefs,
        )]);
        return back()->with('success', 'Préférences de notifications mises à jour.');
    }
}
