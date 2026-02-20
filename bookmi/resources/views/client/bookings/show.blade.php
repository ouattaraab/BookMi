@extends('layouts.client')

@section('title', 'Réservation #' . $booking->id . ' — BookMi Client')

@section('content')
<div class="space-y-6 max-w-3xl">

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
        <a href="{{ route('client.bookings') }}" class="p-2 rounded-xl hover:bg-gray-100 transition-colors text-gray-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Réservation #{{ $booking->id }}</h1>
            <p class="text-sm text-gray-500">Détails de votre demande de prestation</p>
        </div>
    </div>

    @php
        $sk = $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status;
        $sc = ['pending'=>'#FF9800','accepted'=>'#2196F3','paid'=>'#00BCD4','confirmed'=>'#4CAF50','completed'=>'#9C27B0','cancelled'=>'#f44336','disputed'=>'#FF5722'][$sk] ?? '#6b7280';
        $sl = ['pending'=>'En attente','accepted'=>'Acceptée','paid'=>'Payée','confirmed'=>'Confirmée','completed'=>'Terminée','cancelled'=>'Annulée','disputed'=>'En litige'][$sk] ?? $sk;
    @endphp

    {{-- Card principale --}}
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">

        {{-- Talent header --}}
        @php
            $talentName = $booking->talentProfile->stage_name
                ?? trim(($booking->talentProfile->user->first_name ?? '') . ' ' . ($booking->talentProfile->user->last_name ?? ''))
                ?: '?';
        @endphp
        <div class="p-6 border-b border-gray-100 flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white font-bold text-2xl flex-shrink-0" style="background:#2196F3">
                {{ strtoupper(substr($talentName, 0, 1)) }}
            </div>
            <div class="flex-1">
                <h2 class="text-xl font-bold text-gray-900">{{ $talentName }}</h2>
                <p class="text-sm text-gray-500">{{ $booking->talentProfile->category->name ?? '—' }}</p>
            </div>
            <span class="text-sm font-semibold px-3 py-1.5 rounded-full" style="background:{{ $sc }}20; color:{{ $sc }}">{{ $sl }}</span>
        </div>

        {{-- Détails --}}
        <div class="p-6 space-y-5">

            {{-- Date événement --}}
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Date de l'événement</p>
                    <p class="font-semibold text-gray-900">{{ \Carbon\Carbon::parse($booking->event_date)->translatedFormat('l d F Y') }}</p>
                </div>
            </div>

            {{-- Lieu --}}
            @if($booking->event_location)
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Lieu</p>
                    <p class="font-semibold text-gray-900">{{ $booking->event_location }}</p>
                </div>
            </div>
            @endif

            {{-- Package --}}
            @if($booking->servicePackage)
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Package</p>
                    <p class="font-semibold text-gray-900">{{ $booking->servicePackage->name }}</p>
                    @if($booking->servicePackage->duration_minutes)
                        <p class="text-sm text-gray-500">{{ $booking->servicePackage->duration_minutes }} min</p>
                    @endif
                </div>
            </div>
            @endif

            {{-- Message --}}
            @if($booking->message)
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Message</p>
                    <p class="text-gray-700 text-sm leading-relaxed">{{ $booking->message }}</p>
                </div>
            </div>
            @endif

            {{-- Montants --}}
            <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                <h3 class="font-semibold text-gray-900 text-sm">Détail financier</h3>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Cachet talent</span>
                        <span class="font-medium text-gray-900">{{ number_format($booking->cachet_amount, 0, ',', ' ') }} FCFA</span>
                    </div>
                    @if($booking->commission_amount)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Commission BookMi</span>
                        <span class="font-medium text-gray-900">{{ number_format($booking->commission_amount, 0, ',', ' ') }} FCFA</span>
                    </div>
                    @endif
                    <div class="flex justify-between text-sm pt-2 border-t border-gray-200">
                        <span class="font-semibold text-gray-900">Total</span>
                        <span class="font-bold text-gray-900 text-base">{{ number_format($booking->total_amount ?? $booking->cachet_amount, 0, ',', ' ') }} FCFA</span>
                    </div>
                </div>
            </div>

        </div>

        {{-- Actions --}}
        @if(in_array($sk, ['pending', 'accepted']))
        <div class="px-6 pb-6 flex gap-3 flex-wrap">
            @if($sk === 'accepted')
            <a href="{{ route('client.bookings.pay', $booking->id) }}"
               class="flex-1 text-center px-6 py-3 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
               style="background:#4CAF50">
                Payer maintenant
            </a>
            @endif
            <form action="{{ route('client.bookings.cancel', $booking->id) }}" method="POST" class="flex-1"
                  x-data onsubmit="return confirm('Confirmer l\'annulation de cette réservation ?')">
                @csrf
                <button type="submit" class="w-full px-6 py-3 rounded-xl text-sm font-semibold border border-red-200 text-red-600 hover:bg-red-50 transition-colors">
                    Annuler la réservation
                </button>
            </form>
        </div>
        @endif

    </div>

    {{-- Meta --}}
    <p class="text-xs text-gray-400 text-center">
        Réservation créée le {{ $booking->created_at->format('d/m/Y à H:i') }}
    </p>

</div>
@endsection
