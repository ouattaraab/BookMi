@extends('layouts.client')

@section('title', 'Paramètres — BookMi Client')

@section('content')
<div class="space-y-6 max-w-2xl">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 rounded-xl p-4 text-green-800 text-sm font-medium">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 rounded-xl p-4 text-red-800 text-sm font-medium">{{ session('error') }}</div>
    @endif
    @if(session('info'))
    <div class="mb-4 bg-blue-50 border border-blue-200 rounded-xl p-4 text-blue-800 text-sm font-medium">{{ session('info') }}</div>
    @endif

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Paramètres</h1>
        <p class="text-sm text-gray-500 mt-1">Gérez la sécurité de votre compte</p>
    </div>

    {{-- Section sécurité --}}
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">

        {{-- Title section --}}
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <h2 class="font-bold text-gray-900">Double authentification (2FA)</h2>
                <p class="text-xs text-gray-500">Sécurisez davantage votre compte</p>
            </div>
        </div>

        <div class="p-6 space-y-6">

            {{-- Statut 2FA --}}
            @php
                $twoFaEnabled = $user->two_factor_confirmed_at !== null || $user->two_factor_type !== null;
                $twoFaType = $user->two_factor_type ?? null;
            @endphp

            @if($twoFaEnabled)
                {{-- 2FA activée --}}
                <div class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-green-800 text-sm">2FA Activée</p>
                            <p class="text-xs text-green-600">
                                Type :
                                @if($twoFaType === 'totp') Application TOTP (Google Authenticator, etc.)
                                @elseif($twoFaType === 'email') Vérification par email
                                @else Activée
                                @endif
                            </p>
                        </div>
                    </div>
                    <span class="text-xs font-bold px-3 py-1 rounded-full bg-green-500 text-white">Activée</span>
                </div>

                {{-- Désactiver 2FA --}}
                <div x-data="{ open: false }">
                    <button @click="open = !open"
                        class="w-full flex items-center justify-between px-4 py-3 rounded-xl border border-red-200 text-red-600 hover:bg-red-50 transition-colors text-sm font-medium">
                        <span>Désactiver la double authentification</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-transition class="mt-3 p-4 bg-red-50 border border-red-100 rounded-xl">
                        <p class="text-sm text-red-700 mb-3">Confirmez votre mot de passe pour désactiver la 2FA.</p>
                        <form action="{{ route('client.settings.2fa.disable') }}" method="POST" class="space-y-3">
                            @csrf
                            <input type="password" name="password" placeholder="Votre mot de passe"
                                class="w-full px-4 py-2.5 rounded-xl border border-red-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-300"
                                required>
                            <button type="submit"
                                class="w-full py-2.5 rounded-xl text-sm font-semibold text-white bg-red-500 hover:bg-red-600 transition-colors">
                                Confirmer la désactivation
                            </button>
                        </form>
                    </div>
                </div>

            @else
                {{-- 2FA non activée --}}
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-sm text-amber-700">La double authentification n'est pas activée. Renforcez la sécurité de votre compte.</p>
                </div>

                {{-- Option TOTP --}}
                <div x-data="{ open: {{ $qrCode ? 'true' : 'false' }} }" class="border border-gray-200 rounded-xl overflow-hidden">
                    <button @click="open = !open"
                        class="w-full flex items-center gap-3 p-4 hover:bg-gray-50 transition-colors text-left">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#2196F320">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" style="color:#2196F3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900 text-sm">Application Authenticator (TOTP)</p>
                            <p class="text-xs text-gray-500">Google Authenticator, Authy, etc.</p>
                        </div>
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-blue-100 text-blue-700">Recommandé</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open" x-transition class="border-t border-gray-100 p-4 space-y-4">
                        @if($qrCode)
                            {{-- QR Code affiché --}}
                            <div class="text-center space-y-3">
                                <p class="text-sm text-gray-600">Scannez ce QR code avec votre application authenticator :</p>
                                <div class="inline-block p-3 bg-white border border-gray-200 rounded-xl shadow-sm">
                                    {!! $qrCode !!}
                                </div>
                                @if($secret)
                                <div class="bg-gray-50 rounded-xl p-3">
                                    <p class="text-xs text-gray-500 mb-1">Clé secrète (si vous ne pouvez pas scanner) :</p>
                                    <code class="text-sm font-mono font-bold text-gray-800 tracking-widest">{{ $secret }}</code>
                                </div>
                                @endif
                                <form action="{{ route('client.settings.2fa.enable.totp') }}" method="POST" class="space-y-3">
                                    @csrf
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Code de vérification (6 chiffres)</label>
                                        <input type="text" name="code" placeholder="000000" maxlength="6" inputmode="numeric"
                                            class="w-full px-4 py-3 rounded-xl border border-gray-200 text-center text-lg font-mono tracking-widest focus:outline-none focus:ring-2"
                                            style="--tw-ring-color:#2196F3"
                                            required>
                                    </div>
                                    <button type="submit"
                                        class="w-full py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
                                        style="background:#2196F3">
                                        Confirmer et activer
                                    </button>
                                </form>
                            </div>
                        @else
                            {{-- Bouton configurer TOTP --}}
                            <p class="text-sm text-gray-600">Cliquez pour générer un QR code à scanner avec votre application.</p>
                            <form action="{{ route('client.settings.2fa.setup.totp') }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="w-full py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
                                    style="background:#2196F3">
                                    Configurer l'application Authenticator
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Option Email --}}
                <div x-data="{ open: false }" class="border border-gray-200 rounded-xl overflow-hidden">
                    <button @click="open = !open"
                        class="w-full flex items-center gap-3 p-4 hover:bg-gray-50 transition-colors text-left">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 bg-gray-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900 text-sm">Vérification par Email</p>
                            <p class="text-xs text-gray-500">Recevez un code sur {{ $user->email }}</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open" x-transition class="border-t border-gray-100 p-4 space-y-4">
                        <p class="text-sm text-gray-600">Un code de vérification sera envoyé à <strong>{{ $user->email }}</strong>.</p>

                        {{-- Envoyer code --}}
                        <form action="{{ route('client.settings.2fa.setup.email') }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="w-full py-2.5 rounded-xl text-sm font-semibold border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors">
                                Envoyer le code par email
                            </button>
                        </form>

                        {{-- Confirmer code --}}
                        <form action="{{ route('client.settings.2fa.enable.email') }}" method="POST" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Code reçu par email (6 chiffres)</label>
                                <input type="text" name="code" placeholder="000000" maxlength="6" inputmode="numeric"
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 text-center text-lg font-mono tracking-widest focus:outline-none focus:ring-2"
                                    style="--tw-ring-color:#2196F3"
                                    required>
                            </div>
                            <button type="submit"
                                class="w-full py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
                                style="background:#4CAF50">
                                Confirmer et activer
                            </button>
                        </form>
                    </div>
                </div>

            @endif

        </div>
    </div>

    {{-- Info compte --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="font-bold text-gray-900 mb-4">Informations du compte</h3>
        <div class="space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Nom</span>
                <span class="font-medium text-gray-900">{{ trim($user->first_name . ' ' . $user->last_name) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Email</span>
                <span class="font-medium text-gray-900">{{ $user->email }}</span>
            </div>
            @if($user->phone)
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Téléphone</span>
                <span class="font-medium text-gray-900">{{ $user->phone }}</span>
            </div>
            @endif
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Membre depuis</span>
                <span class="font-medium text-gray-900">{{ $user->created_at->format('d/m/Y') }}</span>
            </div>
        </div>
    </div>

</div>
@endsection
