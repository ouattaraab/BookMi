@extends('layouts.public')

@section('title', 'BookMi — Organisez l\'Événement de Vos Rêves')
@section('meta_description', 'Trouvez et réservez les meilleurs talents en Côte d\'Ivoire : DJ, musiciens, animateurs, danseurs et bien plus. Paiement sécurisé via Orange Money, MTN MoMo, Wave.')

@section('content')

{{-- ═══════════════════════════════ HERO ═══════════════════════════════ --}}
<section class="relative overflow-hidden flex items-center"
         style="background: linear-gradient(135deg, #FF6B35 0%, #E55A2B 45%, #C84B1E 100%); min-height: 88vh;">

    {{-- Cercles décoratifs --}}
    <div class="absolute top-0 right-0 w-[500px] h-[500px] rounded-full opacity-[0.08]"
         style="background:white; transform:translate(30%,-30%)"></div>
    <div class="absolute bottom-0 left-0 w-[400px] h-[400px] rounded-full opacity-[0.06]"
         style="background:white; transform:translate(-30%,30%)"></div>
    <div class="absolute top-1/2 right-1/4 w-40 h-40 rounded-full opacity-[0.05]"
         style="background:white; transform:translateY(-50%)"></div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-24 text-center relative z-10 w-full">

        {{-- Badge --}}
        <div class="inline-flex items-center gap-2 text-white/90 text-sm font-semibold px-5 py-2.5 rounded-full mb-8"
             style="background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.25); backdrop-filter:blur(12px)">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="white" viewBox="0 0 24 24">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
            La plateforme N°1 des artistes en Côte d'Ivoire
        </div>

        {{-- Titre --}}
        <h1 class="font-black text-white leading-tight mb-6"
            style="font-size:clamp(2.4rem,6vw,4.5rem); letter-spacing:-0.02em">
            Organisez l'Événement<br>
            de <span style="text-decoration:underline; text-decoration-color:rgba(255,255,255,0.4); text-underline-offset:8px">Vos Rêves</span>
        </h1>

        {{-- Sous-titre --}}
        <p class="text-white/80 max-w-2xl mx-auto mb-10 font-medium leading-relaxed"
           style="font-size:clamp(1rem,2vw,1.2rem)">
            Trouvez et réservez les meilleurs artistes, musiciens, DJ et animateurs
            pour vos événements en toute confiance.
        </p>

        {{-- CTAs --}}
        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16">
            <a href="{{ route('talents.index') }}"
               class="inline-block px-8 py-4 rounded-2xl font-extrabold text-lg transition-all hover:scale-105"
               style="background:white; color:#FF6B35; box-shadow:0 12px 40px rgba(0,0,0,0.2)">
                Découvrir les talents
            </a>
            <a href="{{ route('register') }}"
               class="inline-block px-8 py-4 rounded-2xl font-extrabold text-white text-lg transition-all hover:scale-105"
               style="border:2px solid rgba(255,255,255,0.5); background:rgba(255,255,255,0.12); backdrop-filter:blur(8px)">
                Devenir talent →
            </a>
        </div>

        {{-- Stats --}}
        <div class="flex flex-wrap justify-center gap-8 md:gap-16">
            @foreach([
                ['500+',   'Talents vérifiés'],
                ['2 000+', 'Événements réalisés'],
                ['98%',    'Clients satisfaits'],
            ] as [$val, $lab])
            <div class="text-center">
                <p class="font-black text-white leading-none" style="font-size:clamp(2rem,4vw,2.8rem)">{{ $val }}</p>
                <p class="text-white/65 text-sm mt-1.5 font-semibold">{{ $lab }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════ STEPS BAR ═══════════════════════════════ --}}
<section class="py-10 bg-white border-b border-gray-100">
    <div class="max-w-5xl mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            @foreach([
                ['01', 'Rechercher des Talents',  'Parcourez et filtrez notre catalogue de talents vérifiés par catégorie, ville ou budget.', '#FF6B35'],
                ['02', 'Types d\'Événements',      'Mariage, concert, anniversaire, conférence, soirée privée... nous couvrons tout.',        '#2196F3'],
                ['03', 'Comment ça marche',        'Demandez → Confirmez → Payez en sécurité → Profitez de votre événement.',                 '#1A2744'],
            ] as [$num, $title, $desc, $color])
            <div class="flex items-start gap-4 p-5 rounded-2xl" style="background:#f8fafc">
                <div class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center text-white font-extrabold text-sm"
                     style="background:{{ $color }}">{{ $num }}</div>
                <div>
                    <p class="font-bold text-gray-900 text-sm leading-snug">{{ $title }}</p>
                    <p class="text-gray-500 text-xs mt-1 leading-relaxed">{{ $desc }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════ TALENTS POPULAIRES ═══════════════════════════════ --}}
<section id="talents" class="py-24 bg-gray-50">
    <div class="max-w-6xl mx-auto px-4">

        <div class="flex items-end justify-between mb-14">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-widest mb-2" style="color:#FF6B35">Nos artistes</p>
                <h2 class="font-black text-gray-900" style="font-size:clamp(1.8rem,4vw,2.5rem)">Talents Populaires</h2>
                <p class="text-gray-500 mt-2 font-medium">Nos artistes les mieux notés sur la plateforme</p>
            </div>
            <a href="{{ route('talents.index') }}"
               class="hidden md:inline-flex items-center gap-1.5 text-sm font-bold hover:gap-3 transition-all"
               style="color:#FF6B35">
                Voir tous les talents
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                     stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path d="m9 18 6-6-6-6"/>
                </svg>
            </a>
        </div>

        @if($featuredTalents->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            @foreach($featuredTalents as $talent)
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl
                        transition-all duration-300 hover:-translate-y-1.5 overflow-hidden group">

                {{-- Photo / Cover --}}
                <div class="h-56 relative overflow-hidden" style="background:linear-gradient(135deg,#f1f5f9,#e2e8f0)">
                    @if($talent->cover_photo_url)
                        <img src="{{ $talent->cover_photo_url }}"
                             alt="{{ $talent->stage_name }}"
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <div class="w-20 h-20 rounded-full flex items-center justify-center" style="background:#dde4ee">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none"
                                     stroke="#94a3b8" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </div>
                        </div>
                    @endif

                    {{-- Badge vérifié --}}
                    @if($talent->is_verified ?? false)
                    <div class="absolute top-3 right-3 bg-white/95 backdrop-blur rounded-full px-2.5 py-1
                                flex items-center gap-1 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="#4CAF50">
                            <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/>
                            <path fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" d="m9 12 2 2 4-4"/>
                        </svg>
                        <span class="text-xs font-bold text-gray-700">Vérifié</span>
                    </div>
                    @endif
                </div>

                {{-- Infos --}}
                <div class="p-5">
                    <p class="font-extrabold text-gray-900 text-base leading-snug">
                        {{ $talent->stage_name ?? ($talent->user->first_name ?? 'Artiste') }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5 font-semibold uppercase tracking-wide">
                        {{ $talent->category?->name ?? 'Artiste' }}
                    </p>

                    <div class="flex items-center justify-between mt-3 mb-4">
                        <div class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="#FF9800" viewBox="0 0 24 24">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                            <span class="text-sm font-bold text-gray-700">
                                {{ number_format($talent->average_rating ?? 0, 1) }}
                            </span>
                        </div>
                        @if($talent->cachet_amount)
                        <span class="text-sm font-extrabold" style="color:#1A2744">
                            Dès {{ number_format($talent->cachet_amount, 0, ',', ' ') }} FCFA
                        </span>
                        @endif
                    </div>

                    <a href="{{ route('talent.show', $talent->slug ?? $talent->id) }}"
                       class="block w-full text-center py-3 rounded-2xl font-extrabold text-white text-sm
                              transition-all hover:scale-[1.02] hover:shadow-lg"
                       style="background: linear-gradient(135deg, #FF6B35 0%, #E55A2B 100%);
                              box-shadow: 0 4px 16px rgba(255,107,53,0.35);">
                        Réserver Maintenant
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-10 text-center md:hidden">
            <a href="{{ route('talents.index') }}"
               class="inline-block px-8 py-3.5 rounded-2xl font-extrabold text-white text-sm"
               style="background: linear-gradient(135deg, #FF6B35, #E55A2B); box-shadow:0 4px 16px rgba(255,107,53,0.35)">
                Voir tous les talents
            </a>
        </div>

        @else
        <div class="text-center py-20 text-gray-400">
            <p class="text-lg font-medium">Aucun talent disponible pour le moment.</p>
        </div>
        @endif
    </div>
</section>

{{-- ═══════════════════════════════ POURQUOI BOOKMI ═══════════════════════════════ --}}
<section id="pourquoi-bookmi" class="py-24 bg-white">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-16">
            <p class="text-xs font-extrabold uppercase tracking-widest mb-2" style="color:#2196F3">Nos avantages</p>
            <h2 class="font-black text-gray-900" style="font-size:clamp(1.8rem,4vw,2.5rem)">Pourquoi choisir BookMi ?</h2>
            <p class="text-gray-500 mt-3 text-lg max-w-2xl mx-auto font-medium">
                Une plateforme conçue pour simplifier la réservation d'artistes en Côte d'Ivoire
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            @foreach([
                [
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>',
                    'title' => 'Talents vérifiés',
                    'desc'  => 'Chaque artiste est validé par notre équipe. Identité, compétences et portfolio confirmés avant publication.',
                    'color' => '#2196F3',
                    'bg'    => '#dbeafe',
                ],
                [
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>',
                    'title' => 'Paiement sécurisé',
                    'desc'  => 'Votre paiement est placé en séquestre et libéré uniquement après la prestation réalisée. Zéro risque.',
                    'color' => '#FF6B35',
                    'bg'    => '#fff3e0',
                ],
                [
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>',
                    'title' => 'Contrat en ligne',
                    'desc'  => 'Signez votre contrat digitalement en quelques secondes. Tout est traçable et légalement valide.',
                    'color' => '#4CAF50',
                    'bg'    => '#dcfce7',
                ],
                [
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/>',
                    'title' => 'Messagerie intégrée',
                    'desc'  => 'Échangez directement avec l\'artiste pour affiner vos besoins avant de confirmer votre réservation.',
                    'color' => '#9C27B0',
                    'bg'    => '#f3e8ff',
                ],
                [
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>',
                    'title' => 'Mobile Money',
                    'desc'  => 'Payez via Orange Money, MTN MoMo ou Wave. Aucune carte bancaire requise pour réserver.',
                    'color' => '#FF9800',
                    'bg'    => '#fef3c7',
                ],
                [
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>',
                    'title' => 'Avis certifiés',
                    'desc'  => 'Les évaluations proviennent uniquement de clients ayant réellement réservé l\'artiste. Zéro faux avis.',
                    'color' => '#F44336',
                    'bg'    => '#fee2e2',
                ],
            ] as $feature)
            <div class="p-7 rounded-3xl border border-gray-100 hover:border-gray-200 hover:shadow-lg transition-all duration-300 group">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-5 transition-transform group-hover:scale-110"
                     style="background:{{ $feature['bg'] }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                         stroke="{{ $feature['color'] }}" stroke-width="1.8" viewBox="0 0 24 24">
                        {!! $feature['icon'] !!}
                    </svg>
                </div>
                <h3 class="font-extrabold text-gray-900 text-base mb-2">{{ $feature['title'] }}</h3>
                <p class="text-gray-500 text-sm leading-relaxed font-medium">{{ $feature['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════ TÉMOIGNAGES ═══════════════════════════════ --}}
<section id="clients" class="py-24 bg-gray-50">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-16">
            <p class="text-xs font-extrabold uppercase tracking-widest mb-2" style="color:#FF6B35">Témoignages</p>
            <h2 class="font-black text-gray-900" style="font-size:clamp(1.8rem,4vw,2.5rem)">Ce que disent nos clients</h2>
            <p class="text-gray-500 mt-3 text-lg font-medium">Des milliers d'événements réussis grâce à BookMi</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach([
                [
                    'name'   => 'Awa Coulibaly',
                    'role'   => 'Mariage — Abidjan',
                    'text'   => 'J\'ai trouvé le DJ parfait pour mon mariage en moins de 2 heures. Le paiement sécurisé m\'a donné une totale confiance. Je recommande vivement BookMi !',
                    'rating' => 5,
                    'avatar' => 'AC',
                    'color'  => '#E91E63',
                ],
                [
                    'name'   => 'Kouamé Assi',
                    'role'   => 'Anniversaire — Yamoussoukro',
                    'text'   => 'Excellent service ! L\'animateur était professionnel et ponctuel. La messagerie permet de tout organiser facilement. Je suis pleinement satisfait.',
                    'rating' => 5,
                    'avatar' => 'KA',
                    'color'  => '#2196F3',
                ],
                [
                    'name'   => 'Fatou Traoré',
                    'role'   => 'Conférence — Bouaké',
                    'text'   => 'Le processus de réservation est simple et transparent. J\'ai adoré pouvoir lire les avis certifiés avant de choisir mon artiste. Une plateforme de confiance.',
                    'rating' => 5,
                    'avatar' => 'FT',
                    'color'  => '#FF6B35',
                ],
            ] as $t)
            <div class="bg-white rounded-3xl p-7 shadow-sm border border-gray-100 hover:shadow-xl transition-all duration-300 flex flex-col">

                {{-- Étoiles --}}
                <div class="flex gap-1 mb-5">
                    @for($i = 0; $i < $t['rating']; $i++)
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#FF9800" viewBox="0 0 24 24">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                    @endfor
                </div>

                {{-- Témoignage --}}
                <p class="text-gray-600 text-sm leading-relaxed font-medium flex-1 mb-6">
                    "{{ $t['text'] }}"
                </p>

                {{-- Auteur --}}
                <div class="flex items-center gap-3 pt-5 border-t border-gray-100">
                    <div class="w-11 h-11 rounded-full flex items-center justify-center text-white font-extrabold text-sm flex-shrink-0"
                         style="background:{{ $t['color'] }}">
                        {{ $t['avatar'] }}
                    </div>
                    <div>
                        <p class="font-extrabold text-gray-900 text-sm">{{ $t['name'] }}</p>
                        <p class="text-gray-400 text-xs font-medium">{{ $t['role'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════ CTA FINAL ═══════════════════════════════ --}}
<section class="py-24 relative overflow-hidden"
         style="background: linear-gradient(135deg, #FF6B35 0%, #E55A2B 50%, #C84B1E 100%)">

    {{-- Déco --}}
    <div class="absolute inset-0 pointer-events-none"
         style="background-image:
             radial-gradient(circle at 15% 50%, rgba(255,255,255,0.06) 0%, transparent 50%),
             radial-gradient(circle at 85% 20%, rgba(255,255,255,0.08) 0%, transparent 45%)">
    </div>

    <div class="max-w-3xl mx-auto px-4 text-center relative">
        <h2 class="font-black text-white leading-tight mb-4"
            style="font-size:clamp(2rem,5vw,3rem)">
            Prêt à organiser<br>votre événement ?
        </h2>
        <p class="text-white/80 text-lg mb-10 max-w-xl mx-auto font-medium">
            Rejoignez des milliers de clients qui font confiance à BookMi pour leurs événements en Côte d'Ivoire.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('talents.index') }}"
               class="inline-block px-8 py-4 rounded-2xl font-extrabold text-lg transition-all hover:scale-105"
               style="background:white; color:#FF6B35; box-shadow:0 12px 40px rgba(0,0,0,0.15)">
                Trouver un talent maintenant
            </a>
            <a href="{{ route('register') }}"
               class="inline-block px-8 py-4 rounded-2xl font-extrabold text-white text-lg transition-all hover:scale-105"
               style="border:2px solid rgba(255,255,255,0.5); background:rgba(255,255,255,0.12)">
                Créer un compte gratuit
            </a>
        </div>
    </div>
</section>

@endsection
