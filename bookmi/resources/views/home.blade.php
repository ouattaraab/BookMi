@extends('layouts.public')

@section('title', 'BookMi — Réservez les meilleurs talents en Côte d\'Ivoire')

@section('content')

{{-- ═══════════════════════════════ HERO ═══════════════════════════════ --}}
<section style="background: linear-gradient(135deg, #1A2744 0%, #0F1E3A 50%, #2196F3 100%); min-height: 82vh;"
         class="flex items-center">
    <div class="max-w-6xl mx-auto px-4 py-24 text-center">

        <div class="inline-flex items-center gap-2 text-white/80 text-sm font-medium px-4 py-2 rounded-full mb-6"
             style="background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.15)">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#FF6B35" viewBox="0 0 24 24">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
            La plateforme N°1 des artistes en Côte d'Ivoire
        </div>

        <h1 class="text-5xl md:text-7xl font-black text-white leading-tight mb-6" style="letter-spacing:-0.02em">
            Réservez les meilleurs<br>
            <span style="color:#FF6B35">talents</span> en Côte d'Ivoire
        </h1>

        <p class="text-xl text-white/70 max-w-2xl mx-auto mb-10">
            Trouvez et réservez des artistes, musiciens, DJ, humoristes et bien plus
            pour vos événements en quelques clics.
        </p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('talents.index') }}"
               class="px-8 py-4 rounded-2xl font-bold text-white text-lg transition-transform hover:scale-105 shadow-lg"
               style="background:#FF6B35; box-shadow:0 8px 30px rgba(255,107,53,0.4)">
                Découvrir les talents
            </a>
            <a href="{{ route('register') }}"
               class="px-8 py-4 rounded-2xl font-bold text-white text-lg transition-colors"
               style="border:2px solid rgba(255,255,255,0.3); hover:border-white/60">
                Devenir talent
            </a>
        </div>

        {{-- Stats --}}
        <div class="mt-16 flex justify-center gap-10 text-white/60 text-sm">
            @foreach([['500+','Talents'], ['2 000+','Événements'], ['98%','Satisfaction']] as [$val, $lab])
            <div class="text-center">
                <p class="text-3xl font-black text-white">{{ $val }}</p>
                <p>{{ $lab }}</p>
            </div>
            @if(!$loop->last)
            <div class="w-px bg-white/15 self-stretch"></div>
            @endif
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════ COMMENT ÇA MARCHE ═══════════════════════════════ --}}
<section class="py-24 bg-gray-50">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-14">
            <h2 class="text-4xl font-black text-gray-900">Comment ça marche ?</h2>
            <p class="text-gray-500 mt-3 text-lg">Réservez un talent en 3 étapes simples</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach([
                [
                    'step'  => '1',
                    'title' => 'Cherchez un talent',
                    'desc'  => 'Parcourez notre catalogue de talents vérifiés et filtrez par catégorie, ville ou budget.',
                    'color' => '#2196F3',
                    'bg'    => '#dbeafe',
                    'icon'  => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>',
                ],
                [
                    'step'  => '2',
                    'title' => 'Envoyez une demande',
                    'desc'  => "Choisissez la date, le type d'événement et vos besoins. La demande est gratuite.",
                    'color' => '#FF6B35',
                    'bg'    => '#fff3e0',
                    'icon'  => '<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/>',
                ],
                [
                    'step'  => '3',
                    'title' => 'Confirmez et payez',
                    'desc'  => 'Signez le contrat en ligne et effectuez le paiement sécurisé via notre plateforme.',
                    'color' => '#4CAF50',
                    'bg'    => '#dcfce7',
                    'icon'  => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
                ],
            ] as $item)
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 text-center relative">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-black"
                     style="background:{{ $item['color'] }}">
                    {{ $item['step'] }}
                </div>
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-5 mt-3"
                     style="background:{{ $item['bg'] }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none"
                         stroke="{{ $item['color'] }}" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round" viewBox="0 0 24 24">
                        {!! $item['icon'] !!}
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $item['title'] }}</h3>
                <p class="text-gray-500 text-sm leading-relaxed">{{ $item['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════ CATÉGORIES ═══════════════════════════════ --}}
<section class="py-24 bg-white">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-14">
            <h2 class="text-4xl font-black text-gray-900">Explorez par catégorie</h2>
            <p class="text-gray-500 mt-3">Trouvez le talent parfait pour votre événement</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach([
                ['label' => 'DJ',          'color' => '#2196F3', 'bg' => '#dbeafe', 'emoji' => '🎧'],
                ['label' => 'Musicien',    'color' => '#9C27B0', 'bg' => '#f3e8ff', 'emoji' => '🎸'],
                ['label' => 'Chanteur',    'color' => '#FF6B35', 'bg' => '#fff3e0', 'emoji' => '🎤'],
                ['label' => 'Comédien',    'color' => '#4CAF50', 'bg' => '#dcfce7', 'emoji' => '🎭'],
                ['label' => 'Danseur',     'color' => '#E91E63', 'bg' => '#fce7f3', 'emoji' => '💃'],
                ['label' => 'Animateur',   'color' => '#FF9800', 'bg' => '#fef3c7', 'emoji' => '🎉'],
                ['label' => 'Photographe', 'color' => '#607D8B', 'bg' => '#f1f5f9', 'emoji' => '📸'],
                ['label' => 'Vidéaste',    'color' => '#F44336', 'bg' => '#fee2e2', 'emoji' => '🎬'],
            ] as $cat)
            <a href="{{ route('talents.index', ['category' => $cat['label']]) }}"
               class="group bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-all hover:-translate-y-0.5 text-center">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center mx-auto mb-3 text-2xl transition-transform group-hover:scale-110"
                     style="background:{{ $cat['bg'] }}">
                    {{ $cat['emoji'] }}
                </div>
                <p class="font-semibold text-gray-800 text-sm">{{ $cat['label'] }}</p>
            </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════ TALENTS EN VEDETTE ═══════════════════════════════ --}}
@if($featuredTalents->isNotEmpty())
<section class="py-24 bg-gray-50">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex items-end justify-between mb-14">
            <div>
                <h2 class="text-4xl font-black text-gray-900">Talents en vedette</h2>
                <p class="text-gray-500 mt-2">Nos artistes les mieux notés de la plateforme</p>
            </div>
            <a href="{{ route('talents.index') }}"
               class="text-sm font-semibold hover:underline hidden md:block"
               style="color:#FF6B35">
                Voir tous les talents →
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            @foreach($featuredTalents as $talent)
            <a href="{{ route('talent.show', $talent->slug ?? $talent->id) }}"
               class="group bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-lg transition-all hover:-translate-y-1 overflow-hidden">

                {{-- Cover --}}
                <div class="h-52 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center relative overflow-hidden">
                    @if($talent->cover_photo_url)
                        <img src="{{ $talent->cover_photo_url }}"
                             alt="{{ $talent->stage_name }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    @else
                        <div class="w-24 h-24 rounded-full flex items-center justify-center" style="background:#e2e8f0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="none"
                                 stroke="#94a3b8" stroke-width="1.5" viewBox="0 0 24 24">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                    @endif

                    @if($talent->is_verified ?? false)
                    <div class="absolute top-3 right-3 bg-white/95 backdrop-blur rounded-full px-2.5 py-1 flex items-center gap-1 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="#4CAF50">
                            <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/>
                            <path fill="none" stroke="white" stroke-width="2" stroke-linecap="round" d="m9 12 2 2 4-4"/>
                        </svg>
                        <span class="text-xs font-semibold text-gray-700">Vérifié</span>
                    </div>
                    @endif
                </div>

                {{-- Info --}}
                <div class="p-5">
                    <p class="font-bold text-gray-900 group-hover:text-[#FF6B35] transition-colors">
                        {{ $talent->stage_name ?? ($talent->user->first_name ?? 'Artiste') }}
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $talent->category?->name ?? 'Artiste' }}</p>

                    <div class="flex items-center justify-between mt-4">
                        <div class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#FF9800" viewBox="0 0 24 24">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                            <span class="text-sm font-semibold text-gray-700">
                                {{ number_format($talent->average_rating ?? 0, 1) }}
                            </span>
                        </div>
                        @if($talent->cachet_amount)
                        <span class="text-sm font-bold" style="color:#FF6B35">
                            À partir de {{ number_format($talent->cachet_amount, 0, ',', ' ') }} FCFA
                        </span>
                        @endif
                    </div>
                </div>
            </a>
            @endforeach
        </div>

        <div class="mt-8 text-center md:hidden">
            <a href="{{ route('talents.index') }}"
               class="inline-block px-6 py-3 rounded-xl font-semibold text-white text-sm"
               style="background:#FF6B35">
                Voir tous les talents
            </a>
        </div>
    </div>
</section>
@endif

{{-- ═══════════════════════════════ CTA TALENT ═══════════════════════════════ --}}
<section class="py-24 relative overflow-hidden" style="background: linear-gradient(135deg, #FF6B35 0%, #C85A20 100%)">
    <div class="absolute inset-0 opacity-10"
         style="background-image: radial-gradient(circle at 20% 50%, white 0%, transparent 50%), radial-gradient(circle at 80% 20%, white 0%, transparent 40%)">
    </div>
    <div class="max-w-4xl mx-auto px-4 text-center relative">
        <h2 class="text-4xl font-black text-white mb-4">Vous êtes artiste ou manager ?</h2>
        <p class="text-white/80 text-lg mb-10 max-w-xl mx-auto">
            Rejoignez BookMi et développez votre carrière en Côte d'Ivoire. Inscription gratuite.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('register') }}"
               class="inline-block px-8 py-4 rounded-2xl font-bold text-[#FF6B35] bg-white text-lg hover:bg-orange-50 transition-colors shadow-lg">
                Créer mon profil gratuitement
            </a>
            <a href="{{ route('login') }}"
               class="inline-block px-8 py-4 rounded-2xl font-bold text-white text-lg transition-colors"
               style="border:2px solid rgba(255,255,255,0.4)">
                Se connecter
            </a>
        </div>
    </div>
</section>

@endsection
