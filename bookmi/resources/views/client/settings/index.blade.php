@extends('layouts.client')

@section('title', 'Paramètres — BookMi Client')

@section('head')
<style>
/* ── Section card ── */
.settings-card {
    border-radius: 1.125rem;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    overflow: hidden;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}
/* ── Option row ── */
.option-row {
    display: flex; align-items: center; gap: 0.875rem;
    padding: 1rem 1.25rem;
    cursor: pointer;
    border-bottom: 1px solid var(--glass-border);
    transition: background 0.15s;
    width: 100%; text-align: left; background: transparent; border-left: none; border-right: none; border-top: none;
    color: inherit;
}
.option-row:last-child { border-bottom: none; }
.option-row:hover { background: rgba(255,255,255,0.04); }
.option-icon {
    width: 2.25rem; height: 2.25rem; border-radius: 0.625rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
/* ── Dark input ── */
.settings-input {
    width: 100%;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 0.75rem;
    padding: 0.7rem 1rem;
    font-size: 0.875rem;
    font-family: 'Nunito', sans-serif;
    color: rgba(255,255,255,0.90);
    outline: none;
    transition: border-color 0.15s;
}
.settings-input::placeholder { color: rgba(255,255,255,0.28); }
.settings-input:focus { border-color: rgba(100,181,246,0.50); box-shadow: 0 0 0 3px rgba(100,181,246,0.10); }
/* ── Info row ── */
.info-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 0.6rem 0;
    font-size: 0.875rem;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}
.info-row:last-child { border-bottom: none; }
/* ── Reveal ── */
.reveal-item { opacity:0; transform:translateY(16px); transition:opacity 0.45s cubic-bezier(0.16,1,0.3,1), transform 0.45s cubic-bezier(0.16,1,0.3,1); }
.reveal-item.visible { opacity:1; transform:none; }
/* ── QR Code white bg ── */
.qr-wrap { display:inline-block; padding:0.875rem; background:white; border-radius:0.75rem; }
</style>
@endsection

@section('content')
@php
    /** @var \App\Models\User $user */
    $user = auth()->user();
    $twoFaEnabled = $user->two_factor_confirmed_at !== null || $user->two_factor_type !== null;
    $twoFaType = $user->two_factor_type ?? null;
@endphp
<div class="space-y-7 max-w-2xl">

    {{-- Flash --}}
    @if(session('success'))
    <div class="p-3 rounded-xl text-sm font-medium reveal-item" style="background:rgba(76,175,80,0.12);border:1px solid rgba(76,175,80,0.25);color:rgba(134,239,172,0.95)">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="p-3 rounded-xl text-sm font-medium reveal-item" style="background:rgba(244,67,54,0.12);border:1px solid rgba(244,67,54,0.25);color:rgba(252,165,165,0.95)">{{ session('error') }}</div>
    @endif
    @if(session('info'))
    <div class="p-3 rounded-xl text-sm font-medium reveal-item" style="background:rgba(33,150,243,0.12);border:1px solid rgba(33,150,243,0.25);color:rgba(147,197,253,0.95)">{{ session('info') }}</div>
    @endif

    {{-- Header --}}
    <div class="reveal-item">
        <h1 class="section-title">Paramètres</h1>
        <p class="section-sub">Gérez la sécurité de votre compte</p>
    </div>

    {{-- ── 2FA Section ── --}}
    <div class="settings-card reveal-item" style="transition-delay:0.06s">

        {{-- Card header --}}
        <div class="flex items-center gap-3 px-5 py-4" style="border-bottom:1px solid var(--glass-border)">
            <div class="option-icon" style="background:rgba(33,150,243,0.10);border:1px solid rgba(33,150,243,0.18)">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#64B5F6" stroke-width="2"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div>
                <h2 class="font-black text-sm" style="color:var(--text)">Double authentification (2FA)</h2>
                <p class="text-xs" style="color:var(--text-muted)">Sécurisez davantage votre compte</p>
            </div>
        </div>

        <div class="p-5 space-y-5">

            @if($twoFaEnabled)

                {{-- 2FA active status --}}
                <div class="flex items-center justify-between p-4 rounded-xl" style="background:rgba(76,175,80,0.08);border:1px solid rgba(76,175,80,0.20)">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background:#4CAF50">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <p class="font-bold text-sm" style="color:rgba(134,239,172,0.95)">2FA Activée</p>
                            <p class="text-xs" style="color:rgba(134,239,172,0.65)">
                                @if($twoFaType === 'totp') Application TOTP (Google Authenticator, etc.)
                                @elseif($twoFaType === 'email') Vérification par email
                                @else Activée
                                @endif
                            </p>
                        </div>
                    </div>
                    <span class="badge-status" style="background:#4CAF50;color:white;font-size:0.68rem">Activée</span>
                </div>

                {{-- Disable 2FA --}}
                <div x-data="{ open: false }">
                    <button @click="open = !open"
                        class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-semibold transition-colors"
                        style="background:rgba(244,67,54,0.06);border:1px solid rgba(244,67,54,0.20);color:rgba(252,165,165,0.85)">
                        <span>Désactiver la double authentification</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="transition-transform" :class="open ? 'rotate-180' : ''"><path d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-transition class="mt-3 p-4 rounded-xl space-y-3" style="background:rgba(244,67,54,0.06);border:1px solid rgba(244,67,54,0.15)">
                        <p class="text-sm" style="color:rgba(252,165,165,0.80)">Confirmez votre mot de passe pour désactiver la 2FA.</p>
                        <form action="{{ route('client.settings.2fa.disable') }}" method="POST" class="space-y-3">
                            @csrf
                            <input type="password" name="password" placeholder="Votre mot de passe"
                                class="settings-input" required>
                            <button type="submit"
                                class="w-full py-2.5 rounded-xl text-sm font-bold text-white transition-all hover:opacity-90"
                                style="background:#f44336;box-shadow:0 3px 10px rgba(244,67,54,0.30)">
                                Confirmer la désactivation
                            </button>
                        </form>
                    </div>
                </div>

            @else

                {{-- Warning badge --}}
                <div class="flex items-start gap-3 p-3.5 rounded-xl" style="background:rgba(255,152,0,0.08);border:1px solid rgba(255,152,0,0.20)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#FF9800" stroke-width="2" class="flex-shrink-0 mt-0.5"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p class="text-sm" style="color:rgba(253,186,116,0.90)">La double authentification n'est pas activée. Renforcez la sécurité de votre compte.</p>
                </div>

                {{-- TOTP option --}}
                <div x-data="{ open: {{ $qrCode ? 'true' : 'false' }} }" class="overflow-hidden rounded-xl" style="border:1px solid var(--glass-border)">
                    <button @click="open = !open" class="option-row" style="border-bottom:0">
                        <div class="option-icon" style="background:rgba(33,150,243,0.10);border:1px solid rgba(33,150,243,0.20)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#64B5F6" stroke-width="2"><path d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="flex-1 text-left">
                            <p class="font-bold text-sm" style="color:var(--text)">Application Authenticator (TOTP)</p>
                            <p class="text-xs" style="color:var(--text-muted)">Google Authenticator, Authy, etc.</p>
                        </div>
                        <span class="badge-status mr-2" style="background:rgba(33,150,243,0.15);color:#64B5F6;border:1px solid rgba(100,181,246,0.25);font-size:0.65rem">Recommandé</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="transition-transform flex-shrink-0" :class="open ? 'rotate-180' : ''" style="color:var(--text-faint)"><path d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-transition class="p-5 space-y-4" style="border-top:1px solid var(--glass-border)">
                        @if($qrCode)
                            <p class="text-sm" style="color:var(--text-muted)">Scannez ce QR code avec votre application authenticator :</p>
                            <div class="flex justify-center">
                                <div class="qr-wrap">{!! $qrCode !!}</div>
                            </div>
                            @if($secret)
                            <div class="p-3 rounded-xl" style="background:rgba(255,255,255,0.04);border:1px solid var(--glass-border)">
                                <p class="text-xs mb-1.5" style="color:var(--text-muted)">Clé secrète (si vous ne pouvez pas scanner) :</p>
                                <code class="text-sm font-mono font-black tracking-widest" style="color:var(--blue-light)">{{ $secret }}</code>
                            </div>
                            @endif
                            <form action="{{ route('client.settings.2fa.enable.totp') }}" method="POST" class="space-y-3">
                                @csrf
                                <div>
                                    <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted)">Code de vérification (6 chiffres)</label>
                                    <input type="text" name="code" placeholder="000000" maxlength="6" inputmode="numeric"
                                        class="settings-input text-center text-lg font-mono tracking-widest" required>
                                </div>
                                <button type="submit"
                                    class="w-full py-3 rounded-xl text-sm font-black text-white transition-all hover:opacity-90 active:scale-98"
                                    style="background:linear-gradient(135deg,#1565C0,#2196F3);box-shadow:0 4px 14px rgba(33,150,243,0.32)">
                                    Confirmer et activer
                                </button>
                            </form>
                        @else
                            <p class="text-sm" style="color:var(--text-muted)">Cliquez pour générer un QR code à scanner avec votre application.</p>
                            <form action="{{ route('client.settings.2fa.setup.totp') }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="w-full py-3 rounded-xl text-sm font-black text-white transition-all hover:opacity-90"
                                    style="background:linear-gradient(135deg,#1565C0,#2196F3);box-shadow:0 4px 14px rgba(33,150,243,0.30)">
                                    Configurer l'application Authenticator
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Email 2FA option --}}
                <div x-data="{ open: false }" class="overflow-hidden rounded-xl" style="border:1px solid var(--glass-border)">
                    <button @click="open = !open" class="option-row" style="border-bottom:0">
                        <div class="option-icon" style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.10)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="rgba(255,255,255,0.55)" stroke-width="2"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="flex-1 text-left">
                            <p class="font-bold text-sm" style="color:var(--text)">Vérification par Email</p>
                            <p class="text-xs" style="color:var(--text-muted)">Recevez un code sur {{ $user->email }}</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="transition-transform flex-shrink-0" :class="open ? 'rotate-180' : ''" style="color:var(--text-faint)"><path d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-transition class="p-5 space-y-4" style="border-top:1px solid var(--glass-border)">
                        <p class="text-sm" style="color:var(--text-muted)">Un code de vérification sera envoyé à <strong style="color:var(--text)">{{ $user->email }}</strong>.</p>

                        <form action="{{ route('client.settings.2fa.setup.email') }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="w-full py-2.5 rounded-xl text-sm font-bold transition-all hover:opacity-80"
                                style="background:var(--glass-bg);border:1px solid var(--glass-border);color:var(--text)">
                                Envoyer le code par email
                            </button>
                        </form>

                        <form action="{{ route('client.settings.2fa.enable.email') }}" method="POST" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted)">Code reçu par email (6 chiffres)</label>
                                <input type="text" name="code" placeholder="000000" maxlength="6" inputmode="numeric"
                                    class="settings-input text-center text-lg font-mono tracking-widest" required>
                            </div>
                            <button type="submit"
                                class="w-full py-3 rounded-xl text-sm font-black text-white transition-all hover:opacity-90"
                                style="background:linear-gradient(135deg,#2e7d32,#4CAF50);box-shadow:0 4px 14px rgba(76,175,80,0.28)">
                                Confirmer et activer
                            </button>
                        </form>
                    </div>
                </div>

            @endif

        </div>
    </div>

    {{-- ── Account info ── --}}
    <div class="settings-card p-5 reveal-item" style="transition-delay:0.12s">
        <h3 class="font-black text-sm mb-4" style="color:var(--text)">Informations du compte</h3>
        <div class="space-y-0">
            @foreach([
                ['Nom', trim($user->first_name . ' ' . $user->last_name)],
                ['Email', $user->email],
                ...(($user->phone) ? [['Téléphone', $user->phone]] : []),
                ['Membre depuis', $user->created_at->format('d/m/Y')],
            ] as [$label, $value])
            <div class="info-row">
                <span style="color:var(--text-muted)">{{ $label }}</span>
                <span class="font-semibold" style="color:var(--text)">{{ $value }}</span>
            </div>
            @endforeach
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const io = new IntersectionObserver(entries => {
        entries.forEach(e => { if(e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); } });
    }, { threshold: 0.04 });
    document.querySelectorAll('.reveal-item').forEach(el => io.observe(el));
});
</script>
@endsection
