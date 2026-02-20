@extends('layouts.client')

@section('title', 'Paiement réservation #' . $booking->id . ' — BookMi Client')

@section('content')
<div class="space-y-6 max-w-xl">

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
    <div class="flex items-center gap-3">
        <a href="{{ route('client.bookings.show', $booking->id) }}" class="p-2 rounded-xl hover:bg-gray-100 transition-colors text-gray-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Paiement</h1>
            <p class="text-sm text-gray-500">Réservation #{{ $booking->id }}</p>
        </div>
    </div>

    {{-- Card récapitulatif --}}
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">

        {{-- Talent --}}
        @php
            $talentName = $booking->talentProfile->stage_name
                ?? trim(($booking->talentProfile->user->first_name ?? '') . ' ' . ($booking->talentProfile->user->last_name ?? ''))
                ?: '?';
        @endphp
        <div class="p-6 border-b border-gray-100 flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white font-bold text-xl flex-shrink-0" style="background:#2196F3">
                {{ strtoupper(substr($talentName, 0, 1)) }}
            </div>
            <div>
                <p class="font-bold text-gray-900 text-lg">{{ $talentName }}</p>
                @if($booking->servicePackage)
                    <p class="text-sm text-gray-500">{{ $booking->servicePackage->name }}</p>
                @endif
            </div>
        </div>

        {{-- Montants --}}
        <div class="p-6 space-y-4">
            <h3 class="font-semibold text-gray-900">Récapitulatif du paiement</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-500 text-sm">Cachet talent</span>
                    <span class="font-medium text-gray-900">{{ number_format($booking->cachet_amount, 0, ',', ' ') }} FCFA</span>
                </div>
                @if($booking->commission_amount)
                <div class="flex justify-between">
                    <span class="text-gray-500 text-sm">Commission BookMi</span>
                    <span class="font-medium text-gray-900">{{ number_format($booking->commission_amount, 0, ',', ' ') }} FCFA</span>
                </div>
                @endif
                <div class="flex justify-between pt-3 border-t border-gray-200">
                    <span class="font-bold text-gray-900">Total à payer</span>
                    <span class="font-bold text-2xl" style="color:#2196F3">{{ number_format($booking->total_amount ?? $booking->cachet_amount, 0, ',', ' ') }} FCFA</span>
                </div>
            </div>
        </div>

        {{-- Coming soon Paystack --}}
        <div class="px-6 pb-6 space-y-4">
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                <div class="flex items-center gap-2 text-amber-700 text-sm font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Paiement en cours d'intégration
                </div>
                <p class="text-amber-600 text-xs mt-1">L'intégration Paystack sera disponible prochainement. Votre réservation est bien enregistrée.</p>
            </div>
            <form action="{{ route('client.bookings.processPayment', $booking->id) }}" method="POST">
                @csrf
                <button type="submit"
                    class="w-full py-3.5 rounded-xl text-sm font-bold text-white flex items-center justify-center gap-2 opacity-75 cursor-not-allowed"
                    style="background:#2196F3" disabled>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    Payer via Paystack (bientôt disponible)
                </button>
            </form>
        </div>

    </div>

    {{-- Sécurité --}}
    <div class="flex items-center justify-center gap-2 text-xs text-gray-400">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
        Paiement sécurisé — Vos données sont protégées
    </div>

</div>
@endsection
