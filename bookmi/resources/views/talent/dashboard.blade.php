@extends('layouts.talent')

@section('title', 'Dashboard — BookMi Talent')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-gray-900">Tableau de bord</h1>
        <p class="text-gray-400 text-sm mt-0.5 font-semibold">Vue d'ensemble de votre activité talent</p>
    </div>

    @if(! $profile)
        <div class="rounded-2xl p-5 flex items-center gap-4"
             style="background:linear-gradient(135deg,#FFF4EF,#FFE8D6);border:1.5px solid rgba(255,107,53,0.25)">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#FF6B35">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
            </div>
            <div class="flex-1">
                <p class="font-bold text-sm" style="color:#7C2D12">Profil incomplet</p>
                <p class="text-xs font-semibold mt-0.5" style="color:#9A3412">Configurez votre profil pour apparaître dans les résultats de recherche.</p>
            </div>
            <a href="{{ route('talent.profile') }}" class="flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold text-white"
               style="background:#FF6B35">Configurer →</a>
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach([
            ['label' => 'Total réservations', 'value' => $stats['total'], 'color' => '#FF6B35', 'bg' => '#FFF4EF'],
            ['label' => 'En attente',          'value' => $stats['pending'], 'color' => '#FF9800', 'bg' => '#FFF8EE'],
            ['label' => 'Confirmées',          'value' => $stats['confirmed'], 'color' => '#4CAF50', 'bg' => '#F0FDF4'],
            ['label' => 'Revenus (FCFA)',      'value' => number_format($stats['revenue'], 0, ',', ' '), 'color' => '#9C27B0', 'bg' => '#FDF4FF'],
        ] as $stat)
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm overflow-hidden relative"
             style="border-top: 3px solid {{ $stat['color'] }}">
            <div class="absolute top-0 right-0 w-20 h-20 rounded-full -translate-y-8 translate-x-8 opacity-60"
                 style="background: {{ $stat['bg'] }}"></div>
            <p class="text-2xl font-black relative" style="color:{{ $stat['color'] }}">{{ $stat['value'] }}</p>
            <p class="text-xs text-gray-500 mt-1 font-semibold relative">{{ $stat['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Dernières réservations --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-black text-gray-900">Dernières réservations</h2>
            <a href="{{ route('talent.bookings') }}" class="text-sm font-medium hover:underline" style="color:#FF6B35">Voir tout</a>
        </div>
        @if($bookings->isEmpty())
            <p class="text-center text-gray-400 text-sm py-12">Aucune réservation pour l'instant</p>
        @else
            <div class="divide-y divide-gray-50">
                @foreach($bookings as $booking)
                <div class="px-6 py-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">
                            {{ $booking->client->first_name }} {{ $booking->client->last_name }}
                        </p>
                        <p class="text-xs text-gray-500">{{ $booking->event_date?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    @php
                        $statusKey    = $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status;
                        $statusColors = ['pending' => '#FF9800', 'accepted' => '#2196F3', 'paid' => '#00BCD4', 'confirmed' => '#4CAF50', 'completed' => '#9C27B0', 'cancelled' => '#f44336', 'disputed' => '#FF5722'];
                        $statusLabels = ['pending' => 'En attente', 'accepted' => 'Acceptée', 'paid' => 'Payée', 'confirmed' => 'Confirmée', 'completed' => 'Terminée', 'cancelled' => 'Annulée', 'disputed' => 'En litige'];
                        $statusColor  = $statusColors[$statusKey] ?? '#6b7280';
                    @endphp
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full"
                          style="background:{{ $statusColor }}20; color:{{ $statusColor }}">
                        {{ $statusLabels[$statusKey] ?? $statusKey }}
                    </span>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
