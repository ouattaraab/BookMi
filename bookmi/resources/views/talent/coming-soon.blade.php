@extends('layouts.talent')

@section('title', $title . ' — BookMi Talent')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $title }}</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $description }}</p>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background:#fff3e0">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" stroke="#FF6B35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
            </svg>
        </div>
        <p class="text-gray-700 font-semibold">Fonctionnalité en cours de développement</p>
        <p class="text-gray-400 text-sm mt-1">Cette section sera disponible prochainement.</p>
        <a href="{{ route('talent.dashboard') }}"
           class="mt-5 inline-block px-5 py-2 rounded-xl text-sm font-semibold text-white"
           style="background:#FF6B35">
            Retour au tableau de bord
        </a>
    </div>
</div>
@endsection
