@extends('layouts.manager')

@section('title', 'Dashboard Manager — BookMi')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-gray-900">Tableau de bord</h1>
        <p class="text-gray-400 text-sm mt-0.5 font-semibold">Vue d'ensemble de vos talents gérés</p>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach([
            ['label' => 'Talents gérés',      'value' => $stats['talents'], 'color' => '#2196F3', 'bg' => '#EFF8FF'],
            ['label' => 'Total réservations', 'value' => $stats['bookings'], 'color' => '#FF9800', 'bg' => '#FFF8EE'],
            ['label' => 'En attente',          'value' => $stats['pending'], 'color' => '#FF6B35', 'bg' => '#FFF4EF'],
            ['label' => 'Terminées',           'value' => $stats['completed'], 'color' => '#4CAF50', 'bg' => '#F0FDF4'],
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

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('manager.talents') }}" class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background:#dbeafe">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2196F3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <p class="font-semibold text-gray-900">Mes talents</p>
                <p class="text-xs text-gray-500">{{ $stats['talents'] }} talent(s)</p>
            </div>
        </a>
        <a href="{{ route('manager.bookings') }}" class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background:#fff3e0">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#FF6B35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            </div>
            <div>
                <p class="font-semibold text-gray-900">Réservations</p>
                <p class="text-xs text-gray-500">{{ $stats['bookings'] }} total</p>
            </div>
        </a>
        <a href="{{ route('manager.messages') }}" class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background:#d1fae5">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#4CAF50" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div>
                <p class="font-semibold text-gray-900">Messages</p>
                <p class="text-xs text-gray-500">Messagerie</p>
            </div>
        </a>
    </div>
</div>
@endsection
