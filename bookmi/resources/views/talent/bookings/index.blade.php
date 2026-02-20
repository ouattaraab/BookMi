@extends('layouts.talent')

@section('title', 'Réservations — BookMi Talent')

@section('content')
<div class="space-y-6">

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
        <h1 class="text-2xl font-bold text-gray-900">Réservations</h1>
        <p class="text-sm text-gray-500 mt-1">Gérez les demandes de prestation de vos clients</p>
    </div>

    {{-- Tabs filtre statut --}}
    @php
        $tabs = [
            '' => 'Toutes',
            'pending' => 'En attente',
            'accepted' => 'Acceptées',
            'paid' => 'Payées',
            'confirmed' => 'Confirmées',
            'completed' => 'Terminées',
            'cancelled' => 'Annulées',
        ];
        $currentStatus = request('status', '');
    @endphp
    <div class="flex gap-2 flex-wrap">
        @foreach($tabs as $value => $label)
            <a href="{{ route('talent.bookings', $value ? ['status' => $value] : []) }}"
               class="px-4 py-2 rounded-full text-sm font-medium transition-all border
               {{ $currentStatus === $value
                    ? 'text-white border-transparent'
                    : 'text-gray-600 bg-white border-gray-200 hover:text-orange-600 hover:border-orange-300' }}"
               @if($currentStatus === $value) style="background:#FF6B35; border-color:#FF6B35" @endif>
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Liste des réservations --}}
    @if($bookings->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-center bg-white rounded-2xl border border-gray-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-gray-500 text-lg font-medium mb-2">Aucune réservation trouvée</p>
            <p class="text-gray-400 text-sm">{{ $currentStatus ? 'Aucune réservation avec ce statut.' : 'Vos réservations apparaîtront ici.' }}</p>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="divide-y divide-gray-50">
                @foreach($bookings as $booking)
                    @php
                        $sk = $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status;
                        $sc = ['pending'=>'#FF9800','accepted'=>'#2196F3','paid'=>'#00BCD4','confirmed'=>'#4CAF50','completed'=>'#9C27B0','cancelled'=>'#f44336','disputed'=>'#FF5722'][$sk] ?? '#6b7280';
                        $sl = ['pending'=>'En attente','accepted'=>'Acceptée','paid'=>'Payée','confirmed'=>'Confirmée','completed'=>'Terminée','cancelled'=>'Annulée','disputed'=>'En litige'][$sk] ?? $sk;
                    @endphp
                    <div class="p-4 md:p-5 flex flex-col sm:flex-row sm:items-center gap-4">
                        {{-- Avatar client --}}
                        <div class="flex-shrink-0 w-11 h-11 rounded-xl flex items-center justify-center text-white font-bold text-base" style="background:#FF6B35">
                            {{ strtoupper(substr($booking->client->first_name ?? 'C', 0, 1)) }}
                        </div>

                        {{-- Infos --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-semibold text-gray-900">{{ $booking->client->first_name ?? '—' }} {{ $booking->client->last_name ?? '' }}</span>
                                <span class="text-xs text-gray-400">·</span>
                                <span class="text-xs text-gray-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="inline h-3.5 w-3.5 mr-0.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ \Carbon\Carbon::parse($booking->event_date)->format('d/m/Y') }}
                                </span>
                                @if($booking->servicePackage)
                                    <span class="text-gray-300">|</span>
                                    <span class="text-xs text-gray-500 truncate">{{ $booking->servicePackage->name }}</span>
                                @endif
                            </div>
                            @if($booking->event_location)
                                <p class="text-xs text-gray-400 mt-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="inline h-3 w-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ $booking->event_location }}
                                </p>
                            @endif
                        </div>

                        {{-- Montant + badge --}}
                        <div class="flex flex-col items-start sm:items-end gap-1.5">
                            <span class="font-bold text-gray-900 text-sm">{{ number_format($booking->cachet_amount, 0, ',', ' ') }} FCFA</span>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background:{{ $sc }}20; color:{{ $sc }}">{{ $sl }}</span>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <a href="{{ route('talent.bookings.show', $booking->id) }}"
                               class="px-3 py-1.5 rounded-lg text-xs font-medium border border-gray-200 text-gray-600 hover:border-orange-300 hover:text-orange-600 transition-colors">
                                Détail
                            </a>
                            @if($sk === 'pending')
                                <form method="POST" action="{{ route('talent.bookings.accept', $booking->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium text-white transition-colors" style="background:#4CAF50"
                                            onclick="return confirm('Accepter cette réservation ?')">
                                        Accepter
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('talent.bookings.reject', $booking->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium text-white transition-colors" style="background:#f44336"
                                            onclick="return confirm('Refuser cette réservation ?')">
                                        Refuser
                                    </button>
                                </form>
                            @elseif($sk === 'accepted')
                                <form method="POST" action="{{ route('talent.bookings.reject', $booking->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium text-white transition-colors" style="background:#f44336"
                                            onclick="return confirm('Annuler cette réservation ?')">
                                        Annuler
                                    </button>
                                </form>
                            @elseif($sk === 'confirmed')
                                <form method="POST" action="{{ route('talent.bookings.complete', $booking->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium text-white transition-colors" style="background:#9C27B0"
                                            onclick="return confirm('Marquer comme terminée ?')">
                                        Terminer
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Pagination --}}
        @if($bookings->hasPages())
            <div class="mt-4">{{ $bookings->links() }}</div>
        @endif
    @endif

</div>
@endsection
