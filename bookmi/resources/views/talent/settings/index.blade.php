@extends('layouts.talent')

@section('title', 'Paramètres — BookMi Talent')

@section('content')
<div class="space-y-6 max-w-2xl">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-800 text-sm font-medium">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-800 text-sm font-medium">{{ session('error') }}</div>
    @endif
    @if(session('info'))
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-blue-800 text-sm font-medium">{{ session('info') }}</div>
    @endif

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-black text-gray-900">Paramètres</h1>
        <p class="text-sm text-gray-400 mt-0.5 font-semibold">Sécurité et configuration de votre compte</p>
    </div>

    {{-- Statut 2FA --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-black text-gray-900">Double authentification (2FA)</h2>
                <p class="text-xs text-gray-400 mt-0.5">Renforcez la sécurité de votre compte</p>
            </div>
            @if($user->two_factor_enabled ?? false)
                <span class="text-xs font-semibold px-3 py-1 rounded-full" style="background:#f0fdf4; color:#4CAF50; border:1px solid #bbf7d0">
                    Activée
                </span>
            @else
                <span class="text-xs font-semibold px-3 py-1 rounded-full bg-gray-100 text-gray-500">
                    Désactivée
                </span>
            @endif
        </div>

        @if($user->two_factor_enabled ?? false)
        {{-- 2FA active --}}
        <div class="p-6 space-y-4">
            <div class="flex items-center gap-3 p-4 rounded-xl" style="background:#f0fdf4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="#4CAF50"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <div>
                    <p class="text-sm font-semibold text-green-900">2FA active via {{ $user->two_factor_method === 'totp' ? 'Authenticator App' : 'Email' }}</p>
                    <p class="text-xs text-green-700 mt-0.5">Votre compte est protégé par une double authentification.</p>
                </div>
            </div>

            {{-- Désactiver --}}
            <div x-data="{ open: false }">
                <button @click="open = !open" type="button"
                        class="px-4 py-2.5 rounded-xl text-sm font-medium border border-red-200 text-red-600 hover:bg-red-50 transition-colors">
                    Désactiver la 2FA
                </button>
                <div x-show="open" x-cloak x-transition class="mt-3">
                    <form method="POST" action="{{ route('talent.settings.2fa.disable') }}" class="flex items-center gap-3">
                        @csrf
                        <input type="password" name="password" required placeholder="Confirmer votre mot de passe"
                               class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                        <button type="submit"
                                class="px-4 py-2.5 rounded-xl text-sm font-semibold text-white"
                                style="background:#f44336"
                                onclick="return confirm('Désactiver la 2FA ?')">
                            Confirmer
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @else
        {{-- 2FA inactive - choix méthode --}}
        <div class="p-6 space-y-6" x-data="{ method: '' }">

            {{-- Choisir méthode --}}
            <div>
                <p class="text-sm font-semibold text-gray-700 mb-3">Choisir une méthode</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="border-2 rounded-xl p-4 cursor-pointer hover:border-orange-400 transition-colors"
                           :class="method === 'totp' ? 'border-orange-400 bg-orange-50' : 'border-gray-200'">
                        <input type="radio" value="totp" x-model="method" class="sr-only">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#fff3e0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="#FF6B35"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Authenticator App</p>
                                <p class="text-xs text-gray-400 mt-0.5">Google Authenticator, Authy...</p>
                            </div>
                        </div>
                    </label>
                    <label class="border-2 rounded-xl p-4 cursor-pointer hover:border-orange-400 transition-colors"
                           :class="method === 'email' ? 'border-orange-400 bg-orange-50' : 'border-gray-200'">
                        <input type="radio" value="email" x-model="method" class="sr-only">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#fff3e0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="#FF6B35"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Code par Email</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $user->email }}</p>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Setup TOTP --}}
            <div x-show="method === 'totp'" x-cloak class="space-y-4">
                @if($qrCode && $secret)
                {{-- QR Code affiché --}}
                <div class="p-4 rounded-xl border border-orange-200" style="background:#fff8f5">
                    <p class="text-sm font-semibold text-gray-900 mb-3">Scannez ce QR Code avec votre app</p>
                    <div class="flex justify-center mb-3">
                        {!! $qrCode !!}
                    </div>
                    <p class="text-xs text-gray-500 text-center mb-2">Ou entrez la clé manuellement :</p>
                    <div class="bg-gray-100 rounded-lg p-2 text-center">
                        <code class="text-xs font-mono text-gray-800 break-all">{{ $secret }}</code>
                    </div>
                </div>
                <form method="POST" action="{{ route('talent.settings.2fa.enable.totp') }}" class="flex items-center gap-3">
                    @csrf
                    <input type="text" name="code" required placeholder="Code à 6 chiffres" maxlength="6"
                           class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-center tracking-widest font-mono focus:outline-none focus:ring-2 focus:ring-orange-400">
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white hover:opacity-90"
                            style="background:#FF6B35">
                        Activer
                    </button>
                </form>
                @else
                <form method="POST" action="{{ route('talent.settings.2fa.setup.totp') }}">
                    @csrf
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white hover:opacity-90 transition-opacity"
                            style="background:#FF6B35">
                        Générer le QR Code
                    </button>
                </form>
                @endif
            </div>

            {{-- Setup Email --}}
            <div x-show="method === 'email'" x-cloak class="space-y-4">
                <form method="POST" action="{{ route('talent.settings.2fa.setup.email') }}" class="mb-3">
                    @csrf
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white hover:opacity-90 transition-opacity"
                            style="background:#FF6B35">
                        Envoyer le code par email
                    </button>
                </form>
                <form method="POST" action="{{ route('talent.settings.2fa.enable.email') }}" class="flex items-center gap-3">
                    @csrf
                    <input type="text" name="code" required placeholder="Code reçu par email" maxlength="10"
                           class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-center tracking-widest font-mono focus:outline-none focus:ring-2 focus:ring-orange-400">
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white hover:opacity-90"
                            style="background:#FF6B35">
                        Activer
                    </button>
                </form>
            </div>

        </div>
        @endif
    </div>

    {{-- Infos compte --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-black text-gray-900">Informations du compte</h2>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Nom complet</p>
                    <p class="font-medium text-gray-900">{{ $user->first_name }} {{ $user->last_name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Email</p>
                    <p class="font-medium text-gray-900">{{ $user->email }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Téléphone</p>
                    <p class="font-medium text-gray-900">{{ $user->phone ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Membre depuis</p>
                    <p class="font-medium text-gray-900">{{ $user->created_at->format('d/m/Y') }}</p>
                </div>
            </div>
            <div class="pt-2">
                <a href="{{ route('talent.profile') }}"
                   class="text-sm font-medium hover:underline" style="color:#FF6B35">
                    Modifier le profil
                </a>
            </div>
        </div>
    </div>


    {{-- Notification preferences --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#f0fdf4;border:1px solid #bbf7d0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="#16a34a"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>
            </div>
            <div>
                <h2 class="text-base font-black text-gray-900">Préférences de notifications</h2>
                <p class="text-xs text-gray-400 mt-0.5">Choisissez les notifications push à recevoir</p>
            </div>
        </div>

        <form method="POST" action="{{ route('talent.settings.notifications.update') }}">
            @csrf
            <div class="divide-y divide-gray-100">
                @php
                $notifLabels = [
                    'new_message'     => ['label' => 'Nouveaux messages',             'desc' => 'Quand un client vous envoie un message'],
                    'booking_updates' => ['label' => 'Mises à jour des réservations', 'desc' => 'Nouvelles demandes, paiements et confirmations'],
                    'new_review'      => ['label' => 'Nouveaux avis',                 'desc' => 'Quand un client laisse un avis sur votre prestation'],
                    'follow_update'   => ['label' => 'Nouveaux abonnés',              'desc' => 'Quand quelqu\'un commence à vous suivre'],
                    'admin_broadcast' => ['label' => 'Annonces BookMi',               'desc' => 'Actualités et informations importantes de la plateforme'],
                ];
                @endphp
                @foreach($notifLabels as $key => $info)
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex-1 min-w-0 pr-4">
                        <p class="text-sm font-semibold text-gray-900">{{ $info['label'] }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $info['desc'] }}</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                        <input type="checkbox" name="{{ $key }}" value="1"
                               {{ ($notifPrefs[$key] ?? true) ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="talent-toggle-track w-11 h-6 rounded-full transition-all cursor-pointer relative"
                             data-key="{{ $key }}"
                             style="{{ ($notifPrefs[$key] ?? true) ? 'background:#FF6B35;' : 'background:#D1D5DB;' }}"
                             onclick="toggleTalentNotif(this)">
                            <div class="absolute top-[3px] left-[3px] w-[18px] h-[18px] bg-white rounded-full shadow-sm transition-transform"
                                 style="{{ ($notifPrefs[$key] ?? true) ? 'transform:translateX(20px);' : '' }}"></div>
                        </div>
                    </label>
                </div>
                @endforeach
            </div>
            <div class="px-6 py-4 border-t border-gray-100">
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white hover:opacity-90 transition-opacity"
                        style="background:#FF6B35">
                    Enregistrer les préférences
                </button>
            </div>
        </form>
    </div>

    <script>
    function toggleTalentNotif(track) {
        const isOn = track.style.background.includes('FF6B35') || track.style.background.includes('ff6b35');
        const checkbox = track.closest('label').querySelector('input[type="checkbox"]');
        const thumb = track.querySelector('div');
        if (isOn) {
            track.style.background = '#D1D5DB';
            thumb.style.transform = 'translateX(0px)';
            checkbox.checked = false;
        } else {
            track.style.background = '#FF6B35';
            thumb.style.transform = 'translateX(20px)';
            checkbox.checked = true;
        }
    }
    </script>

</div>
@endsection
