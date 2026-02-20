@extends('layouts.client')

@section('title', 'Paiement — BookMi Client')

@section('content')
@php
    $talentName = $booking->talentProfile->stage_name
        ?? trim(($booking->talentProfile->user->first_name ?? '') . ' ' . ($booking->talentProfile->user->last_name ?? ''))
        ?: '?';
    $isPending = session('payment_pending');
    $methodLabel = session('payment_method_label');
@endphp

<div class="max-w-xl space-y-6">

    {{-- Validation errors (flash handled by layout) --}}
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-800 text-sm">
        <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Retour --}}
    <a href="{{ route('client.bookings.show', $booking->id) }}"
       class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-gray-800 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Retour à la réservation
    </a>

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Paiement sécurisé</h1>
        <p class="text-sm text-gray-500 mt-1">Complétez votre paiement pour confirmer la réservation</p>
    </div>

    {{-- Récapitulatif réservation --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-50 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg flex-shrink-0" style="background:#2196F3">
                {{ strtoupper(substr($talentName, 0, 1)) }}
            </div>
            <div class="flex-1">
                <p class="font-bold text-gray-900">{{ $talentName }}</p>
                @if($booking->servicePackage)
                    <p class="text-sm text-gray-500">{{ $booking->servicePackage->name }}</p>
                @endif
            </div>
        </div>

        {{-- Montants --}}
        <div class="px-6 py-4 space-y-3">
            @if($booking->cachet_amount)
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Cachet artiste</span>
                <span class="font-medium text-gray-900">{{ number_format($booking->cachet_amount, 0, ',', ' ') }} XOF</span>
            </div>
            @endif
            @if($booking->commission_amount)
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Commission plateforme</span>
                <span class="font-medium text-gray-900">{{ number_format($booking->commission_amount, 0, ',', ' ') }} XOF</span>
            </div>
            @endif
            <div class="border-t border-gray-100 pt-3 flex justify-between">
                <span class="font-bold text-gray-900">Total</span>
                <span class="text-xl font-extrabold" style="color:#2196F3">
                    {{ number_format($booking->total_amount, 0, ',', ' ') }} XOF
                </span>
            </div>
        </div>
    </div>

    {{-- ── OTP FORM (affiché après initiation Mobile Money) ── --}}
    @if($isPending)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#fff3e0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" style="color:#FF9800" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h2 class="font-bold text-gray-900">Entrez votre code OTP</h2>
                    <p class="text-xs text-gray-500">Un code a été envoyé sur votre téléphone via {{ $methodLabel }}</p>
                </div>
            </div>
        </div>
        <div class="p-6">
            <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800">
                <p>Vérifiez votre téléphone et entrez le code reçu de <strong>{{ $methodLabel }}</strong> pour valider le paiement de <strong>{{ number_format($booking->total_amount, 0, ',', ' ') }} XOF</strong>.</p>
            </div>
            <form action="{{ route('client.bookings.pay.otp', $booking->id) }}" method="POST"
                  x-data="{ otp: '', submitting: false }" @submit="submitting = true">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Code OTP</label>
                    <input type="text" name="otp" x-model="otp"
                           inputmode="numeric" maxlength="8" autocomplete="one-time-code"
                           placeholder="000000"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 text-center text-2xl font-mono tracking-widest focus:outline-none focus:ring-2"
                           style="--tw-ring-color:#2196F3"
                           required autofocus>
                </div>
                <button type="submit"
                        :disabled="otp.length < 4 || submitting"
                        class="w-full py-3 rounded-xl text-sm font-bold text-white transition-opacity disabled:opacity-40 disabled:cursor-not-allowed"
                        style="background:#2196F3">
                    <span x-text="submitting ? 'Validation en cours...' : 'Valider le paiement'"></span>
                </button>
            </form>

            <p class="text-xs text-gray-400 text-center mt-3">
                Code non reçu ?
                <a href="{{ route('client.bookings.pay', $booking->id) }}" class="underline hover:text-gray-600">
                    Recommencer le paiement
                </a>
            </p>
        </div>
    </div>

    {{-- ── FORMULAIRE DE SÉLECTION DE MÉTHODE DE PAIEMENT ── --}}
    @else
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
         x-data="{
            method: '',
            mobileMethods: ['orange_money', 'wave', 'mtn_momo', 'moov_money'],
            get isMobile() { return this.mobileMethods.includes(this.method) },
            get isCard()   { return this.method === 'card' },
            get isBank()   { return this.method === 'bank_transfer' },
            submitting: false
         }">

        <div class="px-6 py-4 border-b border-gray-50">
            <h2 class="font-bold text-gray-900">Choisissez votre méthode de paiement</h2>
            <p class="text-xs text-gray-400 mt-0.5">Paiement sécurisé via Paystack</p>
        </div>

        <form action="{{ route('client.bookings.pay.process', $booking->id) }}" method="POST"
              class="p-6 space-y-5" @submit="submitting = true">
            @csrf

            {{-- Méthodes Mobile Money --}}
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Mobile Money</p>
                <div class="grid grid-cols-2 gap-3">
                    @foreach([
                        ['value' => 'orange_money', 'label' => 'Orange Money', 'color' => '#FF6600', 'bg' => '#fff3e0', 'icon' => '📱'],
                        ['value' => 'wave',         'label' => 'Wave',         'color' => '#009DFF', 'bg' => '#e3f2fd', 'icon' => '🌊'],
                        ['value' => 'mtn_momo',     'label' => 'MTN MoMo',    'color' => '#FFCB05', 'bg' => '#fffde7', 'icon' => '📲'],
                        ['value' => 'moov_money',   'label' => 'Moov Money',  'color' => '#00ADEF', 'bg' => '#e1f5fe', 'icon' => '💳'],
                    ] as $m)
                    <label class="relative border-2 rounded-xl p-3 cursor-pointer transition-all select-none"
                           :class="method === '{{ $m['value'] }}' ? 'border-blue-400 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                        <input type="radio" name="payment_method" value="{{ $m['value'] }}"
                               x-model="method" class="sr-only">
                        <div class="flex items-center gap-2">
                            <span class="text-xl leading-none">{{ $m['icon'] }}</span>
                            <span class="text-sm font-semibold text-gray-800">{{ $m['label'] }}</span>
                        </div>
                        <div x-show="method === '{{ $m['value'] }}'"
                             class="absolute top-2 right-2 w-4 h-4 rounded-full flex items-center justify-center"
                             style="background:#2196F3">
                            <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </div>
                    </label>
                    @endforeach
                </div>

                {{-- Champ numéro de téléphone (Mobile Money) --}}
                <div x-show="isMobile" x-transition class="mt-3">
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Numéro de téléphone Mobile Money *</label>
                    <input type="tel" name="phone_number"
                           placeholder="+225 07 XX XX XX XX"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2"
                           style="--tw-ring-color:#2196F3"
                           :required="isMobile">
                    <p class="text-xs text-gray-400 mt-1">Format : +225XXXXXXXXXX (avec indicatif pays)</p>
                </div>
            </div>

            {{-- Méthodes carte / virement --}}
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Carte & Virement</p>
                <div class="grid grid-cols-2 gap-3">
                    @foreach([
                        ['value' => 'card',          'label' => 'Carte bancaire', 'icon' => '💳'],
                        ['value' => 'bank_transfer',  'label' => 'Virement',       'icon' => '🏦'],
                    ] as $m)
                    <label class="relative border-2 rounded-xl p-3 cursor-pointer transition-all select-none"
                           :class="method === '{{ $m['value'] }}' ? 'border-blue-400 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                        <input type="radio" name="payment_method" value="{{ $m['value'] }}"
                               x-model="method" class="sr-only">
                        <div class="flex items-center gap-2">
                            <span class="text-xl leading-none">{{ $m['icon'] }}</span>
                            <span class="text-sm font-semibold text-gray-800">{{ $m['label'] }}</span>
                        </div>
                        <div x-show="method === '{{ $m['value'] }}'"
                             class="absolute top-2 right-2 w-4 h-4 rounded-full flex items-center justify-center"
                             style="background:#2196F3">
                            <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </div>
                    </label>
                    @endforeach
                </div>

                {{-- Note carte --}}
                <div x-show="isCard || isBank" x-transition class="mt-3 p-3 bg-blue-50 border border-blue-100 rounded-xl text-xs text-blue-700">
                    <p x-show="isCard">Vous serez redirigé vers la page de paiement sécurisée Paystack pour entrer vos informations de carte.</p>
                    <p x-show="isBank">Vous serez redirigé vers Paystack pour obtenir les coordonnées bancaires et effectuer votre virement.</p>
                </div>
            </div>

            {{-- Bouton payer --}}
            <div class="pt-2">
                <button type="submit"
                        :disabled="!method || submitting"
                        class="w-full py-3.5 rounded-xl text-base font-bold text-white transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                        style="background:linear-gradient(135deg,#1565C0,#2196F3)"
                        x-text="submitting
                            ? 'Traitement en cours...'
                            : (method
                                ? 'Payer ' + {{ $booking->total_amount ?? 0 }} .toLocaleString('fr-FR') + ' XOF'
                                : 'Sélectionnez une méthode')">
                </button>

                {{-- Sécurité --}}
                <div class="flex items-center justify-center gap-2 mt-3 text-xs text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span>Paiement sécurisé via <strong>Paystack</strong> — chiffrement SSL 256 bits</span>
                </div>
            </div>
        </form>
    </div>
    @endif

    {{-- Escrow info --}}
    <div class="p-4 rounded-xl text-xs text-gray-600 flex items-start gap-3" style="background:#f8fafc; border:1px solid #e2e8f0">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
            <p class="font-semibold text-gray-700 mb-0.5">Protection séquestre (escrow)</p>
            <p>Votre paiement est sécurisé par un compte séquestre. Les fonds ne sont versés au talent qu'après confirmation de la prestation. En cas de litige, le montant peut être remboursé.</p>
        </div>
    </div>

</div>
@endsection
