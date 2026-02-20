@extends('layouts.talent')

@section('title', 'Détail réservation — BookMi Talent')

@section('content')
<div class="space-y-6 max-w-2xl">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-800 text-sm font-medium">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-800 text-sm font-medium">{{ session('error') }}</div>
    @endif

    {{-- Back --}}
    <div>
        <a href="{{ route('talent.bookings') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-orange-600 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Retour aux réservations
        </a>
    </div>

    @php
        $sk = $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status;
        $sc = ['pending'=>'#FF9800','accepted'=>'#2196F3','paid'=>'#00BCD4','confirmed'=>'#4CAF50','completed'=>'#9C27B0','cancelled'=>'#f44336','disputed'=>'#FF5722'][$sk] ?? '#6b7280';
        $sl = ['pending'=>'En attente','accepted'=>'Acceptée','paid'=>'Payée','confirmed'=>'Confirmée','completed'=>'Terminée','cancelled'=>'Annulée','disputed'=>'En litige'][$sk] ?? $sk;
    @endphp

    {{-- Card principale --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        {{-- Header card --}}
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-gray-900">Réservation #{{ $booking->id }}</h1>
                <p class="text-xs text-gray-400 mt-0.5">Créée le {{ $booking->created_at->format('d/m/Y à H:i') }}</p>
            </div>
            <span class="text-sm font-semibold px-3 py-1.5 rounded-full" style="background:{{ $sc }}20; color:{{ $sc }}">{{ $sl }}</span>
        </div>

        <div class="p-6 space-y-5">
            {{-- Client --}}
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg flex-shrink-0" style="background:#FF6B35">
                    {{ strtoupper(substr($booking->client->first_name ?? 'C', 0, 1)) }}
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-0.5">Client</p>
                    <p class="font-semibold text-gray-900">{{ $booking->client->first_name ?? '—' }} {{ $booking->client->last_name ?? '' }}</p>
                    <p class="text-sm text-gray-500">{{ $booking->client->email ?? '' }}</p>
                    @if($booking->client->phone)
                        <p class="text-sm text-gray-500">{{ $booking->client->phone }}</p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Date événement --}}
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Date de l'événement</p>
                    <p class="font-semibold text-gray-900">{{ \Carbon\Carbon::parse($booking->event_date)->format('d/m/Y') }}</p>
                </div>

                {{-- Lieu --}}
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Lieu</p>
                    <p class="font-semibold text-gray-900">{{ $booking->event_location ?: '—' }}</p>
                </div>

                {{-- Package --}}
                @if($booking->servicePackage)
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Package</p>
                    <p class="font-semibold text-gray-900">{{ $booking->servicePackage->name }}</p>
                </div>
                @endif

                {{-- Cachet --}}
                <div class="rounded-xl p-4" style="background:#fff3e0">
                    <p class="text-xs uppercase tracking-wider mb-1" style="color:#C85A20">Cachet</p>
                    <p class="font-bold text-lg" style="color:#FF6B35">{{ number_format($booking->cachet_amount, 0, ',', ' ') }} FCFA</p>
                </div>

                {{-- Total --}}
                @if($booking->total_amount)
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Total client</p>
                    <p class="font-semibold text-gray-900">{{ number_format($booking->total_amount, 0, ',', ' ') }} FCFA</p>
                </div>
                @endif

                {{-- Commission --}}
                @if($booking->commission_amount)
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Commission BookMi</p>
                    <p class="font-semibold text-gray-900">{{ number_format($booking->commission_amount, 0, ',', ' ') }} FCFA</p>
                </div>
                @endif
            </div>

            {{-- Message --}}
            @if($booking->message)
            <div class="bg-gray-50 rounded-xl p-4">
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Message du client</p>
                <p class="text-gray-700 text-sm leading-relaxed">{{ $booking->message }}</p>
            </div>
            @endif
        </div>

        {{-- Actions --}}
        @if(in_array($sk, ['pending', 'accepted', 'confirmed']))
        <div class="px-6 py-4 border-t border-gray-100 flex flex-wrap gap-3">
            @if($sk === 'pending')
                <form method="POST" action="{{ route('talent.bookings.accept', $booking->id) }}">
                    @csrf
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
                            style="background:#4CAF50"
                            onclick="return confirm('Accepter cette réservation ?')">
                        Accepter la réservation
                    </button>
                </form>
                <form method="POST" action="{{ route('talent.bookings.reject', $booking->id) }}">
                    @csrf
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
                            style="background:#f44336"
                            onclick="return confirm('Refuser cette réservation ?')">
                        Refuser
                    </button>
                </form>
            @elseif($sk === 'accepted')
                <form method="POST" action="{{ route('talent.bookings.reject', $booking->id) }}">
                    @csrf
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
                            style="background:#f44336"
                            onclick="return confirm('Annuler cette réservation ?')">
                        Annuler
                    </button>
                </form>
            @elseif($sk === 'confirmed')
                <form method="POST" action="{{ route('talent.bookings.complete', $booking->id) }}">
                    @csrf
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
                            style="background:#9C27B0"
                            onclick="return confirm('Marquer comme terminée ?')">
                        Marquer comme terminée
                    </button>
                </form>
            @endif
        </div>
        @endif
    </div>

</div>
@endsection
