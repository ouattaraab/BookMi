@extends('layouts.client')

@section('title', 'Mes favoris — BookMi Client')

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
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Mes favoris</h1>
        <p class="text-sm text-gray-500 mt-1">Talents que vous avez enregistrés</p>
    </div>

    @if($favorites->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-center bg-white rounded-2xl border border-gray-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
            <p class="text-gray-500 text-lg font-medium mb-2">Aucun talent en favori</p>
            <p class="text-gray-400 text-sm mb-6">Explorez les talents et ajoutez-les à vos favoris !</p>
            <a href="{{ route('home') }}" class="px-6 py-2.5 rounded-xl text-sm font-semibold text-white" style="background:#2196F3">
                Découvrir les talents
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($favorites as $talent)
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden hover:border-blue-200 hover:shadow-md transition-all group">

                {{-- Avatar / Photo --}}
                @php
                    $tName = $talent->stage_name
                        ?? trim(($talent->user->first_name ?? '') . ' ' . ($talent->user->last_name ?? ''))
                        ?: '?';
                @endphp
                <div class="relative h-40 flex items-center justify-center" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%)">
                    @if($talent->user->profile_photo_url ?? false)
                        <img src="{{ $talent->user->profile_photo_url }}" alt="{{ $tName }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-white font-bold text-3xl" style="background:#2196F3">
                            {{ strtoupper(substr($tName, 0, 1)) }}
                        </div>
                    @endif
                    {{-- Bouton retirer favori --}}
                    <form action="{{ route('client.favorites.destroy', $talent->id) }}" method="POST"
                          class="absolute top-3 right-3"
                          onsubmit="return confirm('Retirer ce talent de vos favoris ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="w-9 h-9 rounded-full bg-white shadow-md flex items-center justify-center text-red-500 hover:bg-red-50 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                        </button>
                    </form>
                </div>

                {{-- Infos --}}
                <div class="p-4">
                    <h3 class="font-bold text-gray-900 group-hover:text-blue-600 transition-colors">{{ $tName }}</h3>
                    <p class="text-sm text-gray-500 mb-3">{{ $talent->category->name ?? '—' }}</p>
                    @if($talent->bio)
                        <p class="text-xs text-gray-400 line-clamp-2 mb-3">{{ $talent->bio }}</p>
                    @endif
                    <div class="flex gap-2">
                        <a href="{{ route('talent.show', $talent->id) }}"
                           class="flex-1 text-center px-3 py-2 rounded-xl text-xs font-semibold text-white transition-opacity hover:opacity-90"
                           style="background:#2196F3">
                            Voir le profil
                        </a>
                    </div>
                </div>

            </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
