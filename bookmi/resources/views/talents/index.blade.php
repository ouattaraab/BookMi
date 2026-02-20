@extends('layouts.public')

@section('title', 'Découvrir les talents — BookMi')

@section('content')
<div class="min-h-screen" style="background:#f8fafc">

    {{-- ═══════ Header ═══════ --}}
    <div class="relative overflow-hidden py-16"
         style="background: linear-gradient(135deg, #FF6B35 0%, #E55A2B 50%, #C84B1E 100%)">
        <div class="absolute inset-0 pointer-events-none"
             style="background-image:radial-gradient(circle at 80% 50%, rgba(255,255,255,0.06) 0%, transparent 50%)"></div>
        <div class="max-w-6xl mx-auto px-4 text-center relative">
            <p class="text-white/70 text-xs font-extrabold uppercase tracking-widest mb-3">Plateforme BookMi</p>
            <h1 class="text-4xl md:text-5xl font-black text-white mb-3" style="letter-spacing:-0.02em">
                Découvrez nos talents
            </h1>
            <p class="text-white/75 font-medium">
                {{ $talents->total() }} talent{{ $talents->total() > 1 ? 's' : '' }} disponible{{ $talents->total() > 1 ? 's' : '' }} en Côte d'Ivoire
            </p>

            {{-- Catégories pills --}}
            <div class="mt-6 flex flex-wrap justify-center gap-2">
                <a href="{{ route('talents.index') }}"
                   class="px-4 py-1.5 rounded-full text-sm font-semibold transition-all {{ !request('category') ? 'bg-white text-[#FF6B35]' : 'bg-white/20 text-white hover:bg-white/30' }}">
                    Tous
                </a>
                @foreach(['DJ', 'Musicien', 'Chanteur', 'Animateur', 'Danseur', 'Comédien', 'Photographe', 'Vidéaste'] as $cat)
                <a href="{{ route('talents.index', ['category' => $cat] + request()->except('category', 'page')) }}"
                   class="px-4 py-1.5 rounded-full text-sm font-semibold transition-all {{ request('category') === $cat ? 'bg-white text-[#FF6B35]' : 'bg-white/20 text-white hover:bg-white/30' }}">
                    {{ $cat }}
                </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ═══════ Filtres ═══════ --}}
    <div class="bg-white border-b border-gray-100 shadow-sm sticky top-0 z-10">
        <form method="GET" action="{{ route('talents.index') }}"
              class="max-w-6xl mx-auto px-4 py-3 flex flex-wrap gap-3 items-center">

            {{-- Search --}}
            <div class="relative flex-1 min-w-52">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2" xmlns="http://www.w3.org/2000/svg"
                     width="16" height="16" fill="none" stroke="#9ca3af" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Rechercher un artiste..."
                       class="w-full border border-gray-200 rounded-xl pl-9 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                       style="--tw-ring-color:#2196F3">
            </div>

            {{-- Catégorie --}}
            <select name="category"
                    class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:border-transparent bg-white"
                    style="--tw-ring-color:#2196F3">
                <option value="">Toutes catégories</option>
                @foreach(['DJ', 'Musicien', 'Chanteur', 'Comédien', 'Danseur', 'Animateur', 'Photographe', 'Vidéaste'] as $cat)
                <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>

            {{-- Ville --}}
            <select name="city"
                    class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:border-transparent bg-white"
                    style="--tw-ring-color:#2196F3">
                <option value="">Toutes villes</option>
                @foreach(['Abidjan', 'Bouaké', 'Daloa', 'Korhogo', 'Yamoussoukro', 'San Pedro'] as $city)
                <option value="{{ $city }}" {{ request('city') === $city ? 'selected' : '' }}>{{ $city }}</option>
                @endforeach
            </select>

            {{-- Tri --}}
            <select name="sort"
                    class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:border-transparent bg-white"
                    style="--tw-ring-color:#2196F3">
                <option value="popular"    {{ request('sort', 'popular') === 'popular'    ? 'selected' : '' }}>Plus populaires</option>
                <option value="recent"     {{ request('sort') === 'recent'     ? 'selected' : '' }}>Plus récents</option>
                <option value="price_asc"  {{ request('sort') === 'price_asc'  ? 'selected' : '' }}>Prix croissant</option>
                <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Prix décroissant</option>
            </select>

            <button type="submit"
                    class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white shadow-sm hover:opacity-90 transition-opacity"
                    style="background:#1A2744">
                Filtrer
            </button>

            @if(request()->hasAny(['search', 'category', 'city', 'sort']))
            <a href="{{ route('talents.index') }}" class="text-sm text-gray-400 hover:text-gray-600 transition-colors">
                Réinitialiser
            </a>
            @endif
        </form>
    </div>

    {{-- ═══════ Grille ═══════ --}}
    <div class="max-w-6xl mx-auto px-4 py-10">

        @if($talents->isEmpty())
        <div class="text-center py-24">
            <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-5" style="background:#f1f5f9">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none"
                     stroke="#cbd5e1" stroke-width="1.5" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
            </div>
            <p class="text-gray-500 text-lg font-medium">Aucun talent trouvé</p>
            <p class="text-gray-400 text-sm mt-1">Essayez d'autres critères de recherche.</p>
            <a href="{{ route('talents.index') }}"
               class="mt-5 inline-block text-sm font-semibold hover:underline"
               style="color:#FF6B35">
                Voir tous les talents
            </a>
        </div>

        @else
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
            @foreach($talents as $talent)
            <div class="group bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1 overflow-hidden flex flex-col">

                {{-- Cover --}}
                <a href="{{ route('talent.show', $talent->slug ?? $talent->id) }}" class="block">
                <div class="h-44 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center relative overflow-hidden">
                    @if($talent->cover_photo_url)
                        <img src="{{ $talent->cover_photo_url }}"
                             alt="{{ $talent->stage_name }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    @else
                        <div class="w-16 h-16 rounded-full flex items-center justify-center" style="background:#e2e8f0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none"
                                 stroke="#94a3b8" stroke-width="1.5" viewBox="0 0 24 24">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                    @endif

                    @if($talent->is_verified ?? false)
                    <div class="absolute top-2 right-2 bg-white/95 backdrop-blur rounded-full px-2 py-0.5 flex items-center gap-1 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="#4CAF50">
                            <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/>
                            <path fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" d="m9 12 2 2 4-4"/>
                        </svg>
                        <span class="text-xs font-semibold text-gray-600">Vérifié</span>
                    </div>
                    @endif
                </div>
                </a>

                {{-- Info --}}
                <div class="p-4 flex flex-col flex-1">
                    <a href="{{ route('talent.show', $talent->slug ?? $talent->id) }}" class="block">
                        <p class="font-extrabold text-gray-900 group-hover:text-[#FF6B35] transition-colors truncate text-sm">
                            {{ $talent->stage_name ?? ($talent->user->first_name ?? 'Artiste') }}
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5 font-semibold uppercase tracking-wide">{{ $talent->category?->name ?? 'Artiste' }}</p>
                    </a>

                    <div class="flex items-center justify-between mt-2 mb-3">
                        <div class="flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="#FF9800" viewBox="0 0 24 24">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                            <span class="text-xs font-bold text-gray-600">
                                {{ number_format($talent->average_rating ?? 0, 1) }}
                            </span>
                        </div>
                        @if($talent->cachet_amount)
                        <span class="text-xs font-extrabold" style="color:#1A2744">
                            Dès {{ number_format($talent->cachet_amount, 0, ',', ' ') }} F
                        </span>
                        @endif
                    </div>

                    <a href="{{ route('talent.show', $talent->slug ?? $talent->id) }}"
                       class="mt-auto block w-full text-center py-2 rounded-xl font-extrabold text-white text-xs transition-all hover:scale-[1.02]"
                       style="background:linear-gradient(135deg,#FF6B35,#E55A2B);box-shadow:0 3px 10px rgba(255,107,53,0.35)">
                        Réserver →
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($talents->hasPages())
        <div class="mt-10 flex justify-center">
            {{ $talents->appends(request()->query())->links() }}
        </div>
        @endif

        @endif
    </div>
</div>
@endsection
