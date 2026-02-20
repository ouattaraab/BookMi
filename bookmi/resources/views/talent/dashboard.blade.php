@extends('layouts.talent')

@section('title', 'Dashboard — BookMi Talent')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-500 text-sm mt-1">Vue d'ensemble de votre activité</p>
    </div>

    @if(! $profile)
        <div class="bg-orange-50 border border-orange-200 rounded-2xl p-6 text-center">
            <p class="text-orange-800 font-semibold text-sm">Votre profil talent n'est pas encore configuré.</p>
            <a href="{{ route('talent.profile') }}" class="mt-3 inline-block px-5 py-2 rounded-xl text-sm font-semibold text-white" style="background:#FF6B35">
                Configurer mon profil
            </a>
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach([
            ['label' => 'Total réservations', 'value' => $stats['total'], 'color' => '#FF6B35'],
            ['label' => 'En attente',          'value' => $stats['pending'], 'color' => '#FF9800'],
            ['label' => 'Confirmées',          'value' => $stats['confirmed'], 'color' => '#4CAF50'],
            ['label' => 'Revenus (FCFA)',      'value' => number_format($stats['revenue'], 0, ',', ' '), 'color' => '#9C27B0'],
        ] as $stat)
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
            <p class="text-2xl font-extrabold" style="color:{{ $stat['color'] }}">{{ $stat['value'] }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $stat['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Dernières réservations --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-900">Dernières réservations</h2>
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
