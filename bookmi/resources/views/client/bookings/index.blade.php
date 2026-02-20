@extends('layouts.client')

@section('title', 'Mes réservations — BookMi Client')

@section('content')
<div class="space-y-6">

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
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-gray-900">Mes réservations</h1>
            <p class="text-sm text-gray-400 mt-0.5 font-semibold">Gérez toutes vos demandes de prestation</p>
        </div>
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white" style="background:#2196F3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Réserver un talent
        </a>
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
            <a href="{{ route('client.bookings', $value ? ['status' => $value] : []) }}"
               class="px-4 py-2 rounded-full text-sm font-medium transition-all border
               {{ $currentStatus === $value
                    ? 'text-white border-transparent'
                    : 'text-gray-600 bg-white border-gray-200 hover:border-blue-300 hover:text-blue-600' }}"
               @if($currentStatus === $value) style="background:#2196F3; border-color:#2196F3" @endif>
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
            <p class="text-gray-400 text-sm mb-6">{{ $currentStatus ? 'Aucune réservation avec ce statut.' : 'Commencez par réserver un talent !' }}</p>
            <a href="{{ route('home') }}" class="px-6 py-2.5 rounded-xl text-sm font-semibold text-white" style="background:#2196F3">
                Découvrir les talents
            </a>
        </div>
    @else
        <div class="space-y-3">
            @foreach($bookings as $booking)
                @php
                    $sk = $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status;
                    $sc = ['pending'=>'#FF9800','accepted'=>'#2196F3','paid'=>'#00BCD4','confirmed'=>'#4CAF50','completed'=>'#9C27B0','cancelled'=>'#f44336','disputed'=>'#FF5722'][$sk] ?? '#6b7280';
                    $sl = ['pending'=>'En attente','accepted'=>'Acceptée','paid'=>'Payée','confirmed'=>'Confirmée','completed'=>'Terminée','cancelled'=>'Annulée','disputed'=>'En litige'][$sk] ?? $sk;
                @endphp
                <a href="{{ route('client.bookings.show', $booking->id) }}"
                   class="flex items-center gap-4 bg-white rounded-2xl border border-gray-100 p-4 hover:border-blue-200 hover:shadow-sm transition-all group">
                    {{-- Avatar talent --}}
                    @php
                        $talentName = $booking->talentProfile->stage_name
                            ?? trim(($booking->talentProfile->user->first_name ?? '') . ' ' . ($booking->talentProfile->user->last_name ?? ''))
                            ?: '?';
                    @endphp
                    <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg" style="background:#2196F3">
                        {{ strtoupper(substr($talentName, 0, 1)) }}
                    </div>
                    {{-- Infos --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-semibold text-gray-900 truncate">{{ $talentName }}</span>
                            <span class="text-xs text-gray-400">·</span>
                            <span class="text-xs text-gray-500">{{ $booking->talentProfile->category->name ?? '—' }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-500">
                            <span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="inline h-3.5 w-3.5 mr-1 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ \Carbon\Carbon::parse($booking->event_date)->format('d/m/Y') }}
                            </span>
                            @if($booking->servicePackage)
                                <span class="text-gray-300">|</span>
                                <span class="truncate">{{ $booking->servicePackage->name }}</span>
                            @endif
                        </div>
                    </div>
                    {{-- Montant + badge --}}
                    <div class="flex flex-col items-end gap-2">
                        <span class="font-bold text-gray-900">{{ number_format($booking->cachet_amount, 0, ',', ' ') }} FCFA</span>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background:{{ $sc }}20; color:{{ $sc }}">{{ $sl }}</span>
                    </div>
                    {{-- Flèche --}}
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-300 group-hover:text-blue-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($bookings->hasPages())
            <div class="mt-6">
                {{ $bookings->links() }}
            </div>
        @endif
    @endif

</div>
@endsection
