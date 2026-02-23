@extends('layouts.client')

@section('title', 'Paramètres — BookMi Client')

@section('head')
<style>
main.page-content { background: #F2EFE9 !important; }

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(22px); }
    to   { opacity: 1; transform: translateY(0); }
}
.dash-fade { opacity: 0; animation: fadeUp 0.6s cubic-bezier(0.16,1,0.3,1) forwards; }

/* ── Settings card ── */
.settings-card-l {
    background: #FFFFFF;
    border-radius: 18px;
    border: 1px solid #E5E1DA;
    box-shadow: 0 2px 12px rgba(26,39,68,0.06);
    overflow: hidden;
}

/* ── Option row (bouton cliquable) ── */
.option-row-l {
    display: flex; align-items: center; gap: 14px;
    padding: 16px 24px;
    cursor: pointer;
    border-bottom: 1px solid #EAE7E0;
    transition: background 0.15s;
    width: 100%; text-align: left;
    background: transparent; border-left: none; border-right: none; border-top: none;
    color: inherit;
}
.option-row-l:last-child { border-bottom: none; }
.option-row-l:hover { background: #FAF9F6; }

/* ── Option icon ── */
.option-icon-l {
    width: 40px; height: 40px; border-radius: 11px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

/* ── Light input ── */
.settings-input-l {
    width: 100%;
    background: #F9F8F5;
    border: 1.5px solid #E5E1DA;
    border-radius: 12px;
    padding: 11px 16px;
    font-size: 0.875rem;
    font-family: 'Nunito', sans-serif;
    color: #1A2744;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
    box-sizing: border-box;
}
.settings-input-l::placeholder { color: #B0A89E; }
.settings-input-l:focus {
    border-color: #FF6B35;
    box-shadow: 0 0 0 3px rgba(255,107,53,0.10);
    background: #FFFFFF;
}

/* ── Info row ── */
.info-row-l {
    display: flex; justify-content: space-between; align-items: center;
    padding: 13px 0;
    font-size: 0.875rem;
    border-bottom: 1px solid #EAE7E0;
}
.info-row-l:last-child { border-bottom: none; }

/* ── QR code white bg ── */
.qr-wrap { display: inline-block; padding: 12px; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(26,39,68,0.08); }
</style>
@endsection

@section('content')
@php
    /** @var \App\Models\User $user */
    $user = auth()->user();
    $twoFaEnabled = $user->two_factor_confirmed_at !== null || $user->two_factor_type !== null;
    $twoFaType = $user->two_factor_type ?? null;
@endphp
<div style="font-family:'Nunito',sans-serif;color:#1A2744;max-width:720px;">

    {{-- Flash --}}
    @if(session('success'))
    <div class="dash-fade" style="animation-delay:0ms;margin-bottom:16px;padding:14px 18px;border-radius:12px;font-size:0.875rem;font-weight:600;background:#F0FDF4;border:1px solid #86EFAC;color:#15803D;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="dash-fade" style="animation-delay:0ms;margin-bottom:16px;padding:14px 18px;border-radius:12px;font-size:0.875rem;font-weight:600;background:#FEF2F2;border:1px solid #FCA5A5;color:#991B1B;">{{ session('error') }}</div>
    @endif
    @if(session('info'))
    <div class="dash-fade" style="animation-delay:0ms;margin-bottom:16px;padding:14px 18px;border-radius:12px;font-size:0.875rem;font-weight:600;background:#EFF6FF;border:1px solid #93C5FD;color:#1D4ED8;">{{ session('info') }}</div>
    @endif

    {{-- Header --}}
    <div class="dash-fade" style="animation-delay:0ms;margin-bottom:28px;">
        <h1 style="font-size:1.8rem;font-weight:900;color:#1A2744;letter-spacing:-0.025em;margin:0 0 5px 0;line-height:1.15;">Paramètres</h1>
        <p style="font-size:0.875rem;color:#8A8278;font-weight:500;margin:0;">Gérez votre profil et la sécurité de votre compte</p>
    </div>

    {{-- ── Profile Section ── --}}
    <div class="dash-fade settings-card-l" style="animation-delay:40ms;margin-bottom:24px;">
        <div style="display:flex;align-items:center;gap:14px;padding:20px 24px;border-bottom:1px solid #EAE7E0;">
            <div class="option-icon-l" style="background:#FFF3EE;border:1px solid #FECDBB;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#FF6B35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div>
                <h2 style="font-weight:900;font-size:0.95rem;color:#1A2744;margin:0 0 2px 0;">Mon profil</h2>
                <p style="font-size:0.75rem;color:#8A8278;margin:0;font-weight:500;">Photo de profil et informations personnelles</p>
            </div>
        </div>

        <form action="{{ route('client.settings.profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div style="padding:24px;display:flex;flex-direction:column;gap:20px;">

                {{-- Avatar --}}
                <div style="display:flex;align-items:center;gap:20px;">
                    <div style="position:relative;flex-shrink:0;">
                        @if($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="Avatar"
                                style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #FF6B35;box-shadow:0 4px 12px rgba(255,107,53,0.2);">
                        @else
                            <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#FF6B35,#FF8C42);display:flex;align-items:center;justify-content:center;border:3px solid rgba(255,107,53,0.3);box-shadow:0 4px 12px rgba(255,107,53,0.2);">
                                <span style="font-size:1.8rem;font-weight:900;color:white;">{{ strtoupper(substr($user->first_name, 0, 1)) . strtoupper(substr($user->last_name, 0, 1)) }}</span>
                            </div>
                        @endif
                    </div>
                    <div style="flex:1;">
                        <p style="font-size:0.875rem;font-weight:800;color:#1A2744;margin:0 0 4px 0;">{{ trim($user->first_name . ' ' . $user->last_name) }}</p>
                        <p style="font-size:0.75rem;color:#8A8278;margin:0 0 12px 0;">{{ $user->email }}</p>
                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                            <label for="avatar-input" style="padding:7px 14px;border-radius:9px;font-size:0.75rem;font-weight:700;background:#FF6B35;color:white;cursor:pointer;border:none;box-shadow:0 2px 8px rgba(255,107,53,0.25);transition:opacity 0.15s;" onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                                Changer la photo
                            </label>
                            @if($user->avatar)
                            <form action="{{ route('client.settings.avatar.delete') }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Supprimer la photo de profil ?')"
                                    style="padding:7px 14px;border-radius:9px;font-size:0.75rem;font-weight:700;background:#FEF2F2;color:#991B1B;border:1.5px solid #FCA5A5;cursor:pointer;transition:background 0.15s;"
                                    onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='#FEF2F2'">
                                    Supprimer
                                </button>
                            </form>
                            @endif
                        </div>
                        <input id="avatar-input" type="file" name="avatar" accept="image/*" style="display:none;"
                            onchange="previewAvatar(this)">
                        <p id="avatar-filename" style="font-size:0.7rem;color:#8A8278;margin:6px 0 0 0;display:none;"></p>
                    </div>
                </div>

                {{-- Divider --}}
                <div style="border-top:1px solid #EAE7E0;"></div>

                {{-- First name / Last name --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:800;color:#1A2744;margin-bottom:8px;letter-spacing:0.02em;">Prénom</label>
                        <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}"
                            class="settings-input-l" placeholder="Prénom" maxlength="60">
                        @error('first_name')<p style="color:#EF4444;font-size:0.7rem;margin:4px 0 0 0;">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:800;color:#1A2744;margin-bottom:8px;letter-spacing:0.02em;">Nom</label>
                        <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}"
                            class="settings-input-l" placeholder="Nom" maxlength="60">
                        @error('last_name')<p style="color:#EF4444;font-size:0.7rem;margin:4px 0 0 0;">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Email / Phone (read-only) --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:800;color:#8A8278;margin-bottom:8px;letter-spacing:0.02em;">Email <span style="font-weight:500;font-size:0.65rem;">(non modifiable)</span></label>
                        <input type="text" value="{{ $user->email }}" class="settings-input-l" disabled style="opacity:0.6;cursor:not-allowed;">
                    </div>
                    @if($user->phone)
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:800;color:#8A8278;margin-bottom:8px;letter-spacing:0.02em;">Téléphone <span style="font-weight:500;font-size:0.65rem;">(non modifiable)</span></label>
                        <input type="text" value="{{ $user->phone }}" class="settings-input-l" disabled style="opacity:0.6;cursor:not-allowed;">
                    </div>
                    @endif
                </div>

                <button type="submit"
                    style="align-self:flex-start;padding:12px 28px;border-radius:12px;font-size:0.875rem;font-weight:900;color:white;background:linear-gradient(135deg,#FF6B35,#FF8C42);border:none;cursor:pointer;box-shadow:0 4px 14px rgba(255,107,53,0.30);transition:opacity 0.15s;"
                    onmouseover="this.style.opacity='0.88'" onmouseout="this.style.opacity='1'">
                    Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>

    <script>
    function previewAvatar(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const label = document.getElementById('avatar-filename');
            label.textContent = '📎 ' + file.name;
            label.style.display = 'block';
            // Preview: update the avatar display
            const reader = new FileReader();
            reader.onload = function(e) {
                const avatarContainer = input.closest('[style*="position:relative"]');
                const existing = avatarContainer.querySelector('img, div');
                if (existing) {
                    existing.style.display = 'none';
                }
                const preview = document.createElement('img');
                preview.src = e.target.result;
                preview.style = 'width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #FF6B35;box-shadow:0 4px 12px rgba(255,107,53,0.2);';
                avatarContainer.prepend(preview);
            };
            reader.readAsDataURL(file);
        }
    }
    </script>

    {{-- ── 2FA Section ── --}}
    <div class="dash-fade settings-card-l" style="animation-delay:80ms;margin-bottom:24px;">

        {{-- Card header --}}
        <div style="display:flex;align-items:center;gap:14px;padding:20px 24px;border-bottom:1px solid #EAE7E0;">
            <div class="option-icon-l" style="background:#EFF6FF;border:1px solid #93C5FD;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div>
                <h2 style="font-weight:900;font-size:0.95rem;color:#1A2744;margin:0 0 2px 0;">Double authentification (2FA)</h2>
                <p style="font-size:0.75rem;color:#8A8278;margin:0;font-weight:500;">Sécurisez davantage votre compte</p>
            </div>
        </div>

        <div style="padding:20px 24px;display:flex;flex-direction:column;gap:16px;">

            @if($twoFaEnabled)

                {{-- 2FA active status --}}
                <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-radius:14px;background:#F0FDF4;border:1.5px solid #86EFAC;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:36px;height:36px;border-radius:50%;background:#16A34A;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <p style="font-weight:800;font-size:0.875rem;color:#15803D;margin:0 0 2px 0;">2FA Activée</p>
                            <p style="font-size:0.75rem;color:#16A34A;margin:0;font-weight:500;">
                                @if($twoFaType === 'totp') Application TOTP (Google Authenticator, etc.)
                                @elseif($twoFaType === 'email') Vérification par email
                                @else Activée
                                @endif
                            </p>
                        </div>
                    </div>
                    <span style="font-size:0.7rem;font-weight:800;padding:5px 13px;border-radius:9999px;background:#16A34A;color:white;letter-spacing:0.03em;">Activée</span>
                </div>

                {{-- Disable 2FA --}}
                <div x-data="{ open: false }">
                    <button @click="open = !open"
                        style="width:100%;display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-radius:12px;font-size:0.875rem;font-weight:700;background:#FEF2F2;border:1.5px solid #FCA5A5;color:#991B1B;cursor:pointer;transition:background 0.15s;"
                        onmouseover="this.style.background='#FEE2E2'"
                        onmouseout="this.style.background='#FEF2F2'">
                        <span>Désactiver la double authentification</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="transition-transform" :class="open ? 'rotate-180' : ''"><path d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-transition style="margin-top:10px;padding:20px;border-radius:14px;background:#FFF8F8;border:1.5px solid #FCA5A5;">
                        <p style="font-size:0.875rem;color:#991B1B;margin:0 0 14px 0;">Confirmez votre mot de passe pour désactiver la 2FA.</p>
                        <form action="{{ route('client.settings.2fa.disable') }}" method="POST" style="display:flex;flex-direction:column;gap:12px;">
                            @csrf
                            <input type="password" name="password" placeholder="Votre mot de passe" class="settings-input-l" required>
                            <button type="submit"
                                style="padding:12px;border-radius:12px;font-size:0.875rem;font-weight:800;color:white;background:#EF4444;border:none;cursor:pointer;box-shadow:0 3px 12px rgba(239,68,68,0.28);transition:opacity 0.15s;"
                                onmouseover="this.style.opacity='0.88'"
                                onmouseout="this.style.opacity='1'">
                                Confirmer la désactivation
                            </button>
                        </form>
                    </div>
                </div>

            @else

                {{-- Warning badge --}}
                <div style="display:flex;align-items:flex-start;gap:12px;padding:14px 18px;border-radius:12px;background:#FFFBEB;border:1.5px solid #FCD34D;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="#B45309" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px;"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p style="font-size:0.875rem;color:#92400E;margin:0;font-weight:500;line-height:1.5;">La double authentification n'est pas activée. Renforcez la sécurité de votre compte.</p>
                </div>

                {{-- TOTP option --}}
                <div x-data="{ open: {{ $qrCode ? 'true' : 'false' }} }" style="border-radius:14px;border:1.5px solid #E5E1DA;overflow:hidden;">
                    <button @click="open = !open" class="option-row-l" style="border-bottom:0;">
                        <div class="option-icon-l" style="background:#EFF6FF;border:1px solid #93C5FD;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <div style="flex:1;text-align:left;">
                            <p style="font-weight:800;font-size:0.875rem;color:#1A2744;margin:0 0 2px 0;">Application Authenticator (TOTP)</p>
                            <p style="font-size:0.75rem;color:#8A8278;margin:0;font-weight:500;">Google Authenticator, Authy, etc.</p>
                        </div>
                        <span style="font-size:0.65rem;font-weight:800;padding:4px 10px;border-radius:9999px;background:#EFF6FF;color:#1D4ED8;border:1px solid #93C5FD;margin-right:8px;letter-spacing:0.03em;">Recommandé</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#B0A89E" stroke-width="2" class="transition-transform flex-shrink-0" :class="open ? 'rotate-180' : ''"><path d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-transition style="padding:20px;border-top:1px solid #EAE7E0;background:#FAFAF8;display:flex;flex-direction:column;gap:16px;">
                        @if($qrCode)
                            <p style="font-size:0.875rem;color:#8A8278;margin:0;font-weight:500;">Scannez ce QR code avec votre application authenticator :</p>
                            <div style="display:flex;justify-content:center;">
                                <div class="qr-wrap">{!! $qrCode !!}</div>
                            </div>
                            @if($secret)
                            <div style="padding:14px 18px;border-radius:12px;background:#F9F8F5;border:1.5px solid #E5E1DA;">
                                <p style="font-size:0.75rem;color:#8A8278;margin:0 0 6px 0;font-weight:600;">Clé secrète (si vous ne pouvez pas scanner) :</p>
                                <code style="font-size:0.875rem;font-family:monospace;font-weight:900;letter-spacing:0.12em;color:#1A2744;">{{ $secret }}</code>
                            </div>
                            @endif
                            <form action="{{ route('client.settings.2fa.enable.totp') }}" method="POST" style="display:flex;flex-direction:column;gap:12px;">
                                @csrf
                                <div>
                                    <label style="display:block;font-size:0.75rem;font-weight:800;color:#1A2744;margin-bottom:8px;letter-spacing:0.02em;">Code de vérification (6 chiffres)</label>
                                    <input type="text" name="code" placeholder="000000" maxlength="6" inputmode="numeric"
                                        class="settings-input-l" style="text-align:center;font-size:1.2rem;font-family:monospace;letter-spacing:0.2em;font-weight:900;" required>
                                </div>
                                <button type="submit"
                                    style="padding:14px;border-radius:12px;font-size:0.875rem;font-weight:900;color:white;background:linear-gradient(135deg,#1D4ED8,#2563EB);border:none;cursor:pointer;box-shadow:0 4px 14px rgba(37,99,235,0.30);transition:opacity 0.15s;"
                                    onmouseover="this.style.opacity='0.88'" onmouseout="this.style.opacity='1'">
                                    Confirmer et activer
                                </button>
                            </form>
                        @else
                            <p style="font-size:0.875rem;color:#8A8278;margin:0;font-weight:500;">Cliquez pour générer un QR code à scanner avec votre application.</p>
                            <form action="{{ route('client.settings.2fa.setup.totp') }}" method="POST">
                                @csrf
                                <button type="submit"
                                    style="width:100%;padding:14px;border-radius:12px;font-size:0.875rem;font-weight:900;color:white;background:linear-gradient(135deg,#1D4ED8,#2563EB);border:none;cursor:pointer;box-shadow:0 4px 14px rgba(37,99,235,0.28);transition:opacity 0.15s;"
                                    onmouseover="this.style.opacity='0.88'" onmouseout="this.style.opacity='1'">
                                    Configurer l'application Authenticator
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Email 2FA option --}}
                <div x-data="{ open: false }" style="border-radius:14px;border:1.5px solid #E5E1DA;overflow:hidden;">
                    <button @click="open = !open" class="option-row-l" style="border-bottom:0;">
                        <div class="option-icon-l" style="background:#F9F8F5;border:1px solid #E5E1DA;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#8A8278" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div style="flex:1;text-align:left;">
                            <p style="font-weight:800;font-size:0.875rem;color:#1A2744;margin:0 0 2px 0;">Vérification par Email</p>
                            <p style="font-size:0.75rem;color:#8A8278;margin:0;font-weight:500;">Recevez un code sur {{ $user->email }}</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#B0A89E" stroke-width="2" class="transition-transform flex-shrink-0" :class="open ? 'rotate-180' : ''"><path d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-transition style="padding:20px;border-top:1px solid #EAE7E0;background:#FAFAF8;display:flex;flex-direction:column;gap:16px;">
                        <p style="font-size:0.875rem;color:#8A8278;margin:0;font-weight:500;">Un code de vérification sera envoyé à <strong style="color:#1A2744;font-weight:800;">{{ $user->email }}</strong>.</p>

                        <form action="{{ route('client.settings.2fa.setup.email') }}" method="POST">
                            @csrf
                            <button type="submit"
                                style="width:100%;padding:12px;border-radius:12px;font-size:0.875rem;font-weight:700;color:#1A2744;background:#F9F8F5;border:1.5px solid #E5E1DA;cursor:pointer;transition:background 0.15s,border-color 0.15s;"
                                onmouseover="this.style.background='#EDECE8';this.style.borderColor='#C8C3BC'"
                                onmouseout="this.style.background='#F9F8F5';this.style.borderColor='#E5E1DA'">
                                Envoyer le code par email
                            </button>
                        </form>

                        <form action="{{ route('client.settings.2fa.enable.email') }}" method="POST" style="display:flex;flex-direction:column;gap:12px;">
                            @csrf
                            <div>
                                <label style="display:block;font-size:0.75rem;font-weight:800;color:#1A2744;margin-bottom:8px;letter-spacing:0.02em;">Code reçu par email (6 chiffres)</label>
                                <input type="text" name="code" placeholder="000000" maxlength="6" inputmode="numeric"
                                    class="settings-input-l" style="text-align:center;font-size:1.2rem;font-family:monospace;letter-spacing:0.2em;font-weight:900;" required>
                            </div>
                            <button type="submit"
                                style="padding:14px;border-radius:12px;font-size:0.875rem;font-weight:900;color:white;background:linear-gradient(135deg,#15803D,#16A34A);border:none;cursor:pointer;box-shadow:0 4px 14px rgba(22,163,74,0.26);transition:opacity 0.15s;"
                                onmouseover="this.style.opacity='0.88'" onmouseout="this.style.opacity='1'">
                                Confirmer et activer
                            </button>
                        </form>
                    </div>
                </div>

            @endif

        </div>
    </div>

    {{-- ── Account info ── --}}
    <div class="dash-fade settings-card-l" style="animation-delay:160ms;">
        <div style="padding:20px 24px;border-bottom:1px solid #EAE7E0;">
            <h3 style="font-weight:900;font-size:0.95rem;color:#1A2744;margin:0 0 2px 0;">Informations du compte</h3>
            <p style="font-size:0.75rem;color:#8A8278;margin:0;font-weight:500;">Données en lecture seule</p>
        </div>
        <div style="padding:4px 24px 12px;">
            @foreach([
                ['Email', $user->email],
                ...(($user->phone) ? [['Téléphone', $user->phone]] : []),
                ['Membre depuis', $user->created_at->format('d/m/Y')],
            ] as [$label, $value])
            <div class="info-row-l">
                <span style="color:#8A8278;font-weight:600;">{{ $label }}</span>
                <span style="font-weight:800;color:#1A2744;">{{ $value }}</span>
            </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
