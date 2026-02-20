@extends('layouts.public')

@section('title', 'BookMi — Connectez-vous aux Talents Ivoiriens')
@section('meta_description', 'La première plateforme pour réserver des talents vérifiés en Côte d\'Ivoire. Paiement sécurisé via Mobile Money.')

@section('head')
<style>
/* ─── HERO ─── */
.hero-section {
    position: relative;
    min-height: 88vh;
    display: flex;
    align-items: center;
    overflow: hidden;
    background: #0D1117;
}
.hero-bg {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 70% 60% at 50% 100%, rgba(255,107,53,0.12) 0%, transparent 70%),
        radial-gradient(ellipse 50% 40% at 20% 20%,  rgba(26,39,68,0.9) 0%, transparent 60%),
        radial-gradient(ellipse 60% 50% at 80% 30%,  rgba(30,58,138,0.08) 0%, transparent 60%),
        linear-gradient(170deg, #0D1117 0%, #111827 40%, #0D1117 100%);
}
/* subtle grid texture */
.hero-bg::after {
    content:'';
    position:absolute;
    inset:0;
    background-image:
        linear-gradient(rgba(255,255,255,0.012) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.012) 1px, transparent 1px);
    background-size: 60px 60px;
}

/* ─── SEARCH WRAPPER (conteneur glossy) ─── */
.search-wrapper {
    max-width: 900px;
    margin: 0 auto;
    background: rgba(10, 14, 26, 0.65);
    border: 1.5px solid rgba(255,255,255,0.20);
    border-radius: 26px;
    padding: 18px;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.16),
        inset 0 -1px 0 rgba(255,255,255,0.05),
        0 28px 70px rgba(0,0,0,0.6),
        0 0 0 1px rgba(255,255,255,0.07),
        0 0 40px rgba(255,107,53,0.07);
}
.search-bar {
    display: flex;
    align-items: stretch;
    gap: 12px;
}

/* Champs blancs internes */
.search-field {
    flex: 1;
    min-width: 0;
    background: white;
    border-radius: 14px;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.search-field-icon {
    flex-shrink: 0;
    display: flex;
    align-items: center;
}
.search-field-text { flex: 1; min-width: 0; }
.search-field label {
    display: block;
    font-size: 11px;
    font-weight: 800;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.09em;
    margin-bottom: 4px;
    font-family: 'Nunito', sans-serif;
    line-height: 1;
}
.search-field input {
    border: none;
    outline: none;
    font-size: 1rem;
    color: #1f2937;
    width: 100%;
    background: transparent;
    font-weight: 600;
    font-family: 'Nunito', sans-serif;
    padding: 0;
    line-height: 1.3;
}
.search-field input::placeholder { color: #9ca3af; font-weight: 500; }

/* Bouton Rechercher iOS-glossy */
.search-btn {
    flex-shrink: 0;
    background: linear-gradient(160deg, #FF7A45 0%, #FF6B35 40%, #E55A2B 100%);
    color: white;
    border: none;
    border-radius: 14px;
    padding: 0 30px;
    font-weight: 800;
    font-size: 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 9px;
    font-family: 'Nunito', sans-serif;
    transition: transform 0.15s, box-shadow 0.15s;
    box-shadow: 0 4px 18px rgba(255,107,53,0.5), inset 0 1px 0 rgba(255,255,255,0.18);
    position: relative;
    overflow: hidden;
    min-height: 62px;
}
/* Effet gloss iOS : reflet blanc en haut */
.search-btn::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 50%;
    background: linear-gradient(180deg, rgba(255,255,255,0.22) 0%, rgba(255,255,255,0) 100%);
    border-radius: 14px 14px 50% 50%;
    pointer-events: none;
}
.search-btn:hover { transform: scale(1.03); box-shadow: 0 8px 28px rgba(255,107,53,0.6), inset 0 1px 0 rgba(255,255,255,0.18); }

/* ─── CATEGORY CARDS ─── */
.cat-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    max-width: 1100px;
    margin: 0 auto;
}
.cat-card {
    border-radius: 20px;
    padding: 2.5rem 2rem 2.5rem;
    text-align: center;
    text-decoration: none;
    display: block;
    transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.3s;
    position: relative;
    overflow: hidden;
}
.cat-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.08) 0%, transparent 60%);
}
.cat-card:hover { transform: translateY(-8px); }
.cat-card-blue  { background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 50%, #1e3a8a 100%); box-shadow: 0 8px 30px rgba(59,130,246,0.3); }
.cat-card-blue:hover  { box-shadow: 0 20px 50px rgba(59,130,246,0.5); }
.cat-card-purple{ background: linear-gradient(135deg, #8B5CF6 0%, #6D28D9 50%, #4c1d95 100%); box-shadow: 0 8px 30px rgba(139,92,246,0.3); }
.cat-card-purple:hover{ box-shadow: 0 20px 50px rgba(139,92,246,0.5); }
.cat-card-pink  { background: linear-gradient(135deg, #EC4899 0%, #DB2777 50%, #9d174d 100%); box-shadow: 0 8px 30px rgba(236,72,153,0.3); }
.cat-card-pink:hover  { box-shadow: 0 20px 50px rgba(236,72,153,0.5); }
.cat-icon-wrap {
    width: 64px; height: 64px;
    background: rgba(255,255,255,0.15);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1.25rem;
}
.cat-badge {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    color: rgba(255,255,255,0.9);
    border-radius: 100px;
    padding: 4px 14px;
    font-size: 0.78rem;
    font-weight: 700;
    font-family: 'Nunito', sans-serif;
    margin-top: 0.75rem;
}

/* ─── STATS ─── */
.stats-section {
    background: linear-gradient(135deg, #0D1117 0%, #111827 50%, #0D1117 100%);
    padding: 5rem 1.5rem;
}
.stats-grid {
    max-width: 900px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    text-align: center;
}
.stat-icon {
    width: 52px; height: 52px;
    background: rgba(255,107,53,0.12);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1rem;
}

/* ─── WHY CARDS ─── */
.why-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 2rem;
    transition: transform 0.2s, box-shadow 0.2s;
}
.why-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.08); }
.why-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 1.25rem;
}
.why-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.75rem;
    font-weight: 700;
    color: #FF6B35;
    margin-top: 1.25rem;
}

/* ─── CTA DUAL ─── */
.cta-section {
    background: linear-gradient(135deg, #060912 0%, #0D1117 50%, #060912 100%);
    padding: 5rem 1.5rem;
}
.cta-inner {
    max-width: 1100px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: center;
}
.path-card {
    border-radius: 16px;
    padding: 1.75rem;
    margin-bottom: 1rem;
    position: relative;
}
.path-card:last-child { margin-bottom: 0; }
.path-card-dark {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
}
.path-card-orange {
    background: linear-gradient(135deg, #FF6B35 0%, #E55A2B 100%);
    border: none;
    box-shadow: 0 8px 30px rgba(255,107,53,0.35);
}
.path-label {
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 0.75rem;
}
.path-btn {
    display: block;
    width: 100%;
    padding: 11px;
    border-radius: 10px;
    font-weight: 800;
    font-size: 0.875rem;
    font-family: 'Nunito', sans-serif;
    cursor: pointer;
    border: none;
    text-align: center;
    text-decoration: none;
    transition: transform 0.15s, box-shadow 0.15s;
    margin-top: 1.25rem;
}
.path-btn:hover { transform: scale(1.02); }
.path-btn-white { background: white; color: #0D1117; box-shadow: 0 2px 12px rgba(255,255,255,0.1); }
.path-btn-dark  { background: #0D1117; color: white; }

/* ══════════════════════════════════════════════════
   ANIMATION SYSTEM — Apple-style cinematic reveals
══════════════════════════════════════════════════ */

/* Hero — keyframes jouées au chargement */
@keyframes heroSlideUp {
    from { opacity: 0; transform: translateY(64px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes heroScale {
    from { opacity: 0; transform: scale(0.95) translateY(22px); }
    to   { opacity: 1; transform: scale(1)    translateY(0); }
}

.hero-title-1 { animation: heroSlideUp 0.95s cubic-bezier(0.16,1,0.3,1) 0.10s both; }
.hero-title-2 { animation: heroSlideUp 0.95s cubic-bezier(0.16,1,0.3,1) 0.28s both; }
.hero-sub     { animation: heroSlideUp 0.85s cubic-bezier(0.16,1,0.3,1) 0.48s both; }
.hero-search  { animation: heroScale   0.90s cubic-bezier(0.16,1,0.3,1) 0.65s both; }
.hero-tags    { animation: heroSlideUp 0.75s cubic-bezier(0.16,1,0.3,1) 0.82s both; }

/* Scroll reveal — états initiaux */
.reveal, .reveal-left, .reveal-right {
    will-change: transform, opacity;
}
.reveal {
    opacity: 0;
    transform: translateY(54px);
    transition: opacity .85s cubic-bezier(0.16,1,0.3,1),
                transform .85s cubic-bezier(0.16,1,0.3,1);
}
.reveal-left {
    opacity: 0;
    transform: translateX(-72px);
    transition: opacity .9s cubic-bezier(0.16,1,0.3,1),
                transform .9s cubic-bezier(0.16,1,0.3,1);
}
.reveal-right {
    opacity: 0;
    transform: translateX(72px);
    transition: opacity .9s cubic-bezier(0.16,1,0.3,1),
                transform .9s cubic-bezier(0.16,1,0.3,1);
}
/* États visibles */
.reveal.is-visible       { opacity: 1; transform: translateY(0); }
.reveal-left.is-visible  { opacity: 1; transform: translateX(0); }
.reveal-right.is-visible { opacity: 1; transform: translateX(0); }

/* Délais de stagger */
.d-1 { transition-delay: .12s; }
.d-2 { transition-delay: .24s; }
.d-3 { transition-delay: .36s; }
.d-4 { transition-delay: .48s; }

/* ─── RESPONSIVE ─── */
@media (max-width: 768px) {
    .cat-grid { grid-template-columns: 1fr !important; }
    .stats-grid { grid-template-columns: 1fr !important; gap: 2.5rem; }
    .cta-inner { grid-template-columns: 1fr !important; }
    .why-grid { grid-template-columns: 1fr !important; }
    .search-bar { flex-direction: column; border-radius: 20px; padding: 16px; }
    .search-divider { width: 100%; height: 1px; margin: 12px 0; }
    .search-field { width: 100%; }
    .search-btn { width: 100%; justify-content: center; }
}
</style>
@endsection

@section('content')

{{-- ════════════════════════════════════════════════
     1. HERO
════════════════════════════════════════════════ --}}
<section class="hero-section">
    <div class="hero-bg"></div>

    {{-- Orbe lumineux décoratif --}}
    <div style="position:absolute; top:-10%; right:-5%; width:500px; height:500px; border-radius:50%; background:radial-gradient(circle, rgba(255,107,53,0.06) 0%, transparent 70%); pointer-events:none;"></div>
    <div style="position:absolute; bottom:-15%; left:10%; width:400px; height:400px; border-radius:50%; background:radial-gradient(circle, rgba(30,58,138,0.08) 0%, transparent 70%); pointer-events:none;"></div>

    <div style="max-width:960px; margin:0 auto; padding:5rem 1.5rem; text-align:center; position:relative; z-index:10; width:100%;">

        {{-- Titre --}}
        <h1 class="hero-title-1" style="font-weight:900; color:white; line-height:1.08; margin:0 0 0.1em; font-size:clamp(2.4rem,6vw,4.2rem); letter-spacing:-0.02em;">
            Connectez-vous aux
        </h1>
        <h1 class="hero-title-2" style="font-weight:900; color:#FF6B35; line-height:1.08; margin:0 0 1.4rem; font-size:clamp(2.4rem,6vw,4.2rem); letter-spacing:-0.02em;">
            Talents Ivoiriens
        </h1>

        {{-- Sous-titre --}}
        <p class="hero-sub" style="color:rgba(255,255,255,0.65); font-size:1.05rem; line-height:1.65; margin:0 auto 2.5rem; max-width:460px; font-weight:500;">
            La première plateforme pour réserver des talents vérifiés en toute sécurité.<br>
            Paiement via Mobile Money.
        </p>

        {{-- Barre de recherche (contour glossy) --}}
        <div class="search-wrapper hero-search">
            <form action="{{ route('talents.index') }}" method="GET" class="search-bar">
                {{-- Champ talent --}}
                <div class="search-field">
                    <div class="search-field-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="#FF6B35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                    </div>
                    <div class="search-field-text">
                        <label>Quel talent ?</label>
                        <input type="text" name="search" placeholder="DJ, Pianiste, Groupe..." value="{{ request('search') }}">
                    </div>
                </div>

                {{-- Champ date --}}
                <div class="search-field">
                    <div class="search-field-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="#FF6B35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <div class="search-field-text">
                        <label>Pour Quand ?</label>
                        <input type="date" name="date" style="color:#6b7280;">
                    </div>
                </div>

                <button type="submit" class="search-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    Rechercher
                </button>
            </form>
        </div>

        {{-- Tags populaires --}}
        <div class="hero-tags" style="margin-top:1.25rem; display:flex; align-items:center; justify-content:center; flex-wrap:wrap; gap:8px; font-size:0.8rem;">
            <span style="color:rgba(255,255,255,0.35); font-weight:700; text-transform:uppercase; letter-spacing:0.08em; font-size:0.7rem;">Populaire&nbsp;:</span>
            @foreach(['Orchestres Zouglou'=>'Musicien', 'DJ Mariage'=>'DJ', 'Maître de Cérémonie'=>'Animateur'] as $label => $cat)
            <a href="{{ route('talents.index', ['category'=>$cat]) }}"
               style="color:#FF6B35; font-weight:600; text-decoration:underline; text-underline-offset:3px; text-decoration-color:rgba(255,107,53,0.35); transition:color 0.15s, text-decoration-color 0.15s;"
               onmouseover="this.style.color='white'; this.style.textDecorationColor='rgba(255,255,255,0.35)'"
               onmouseout="this.style.color='#FF6B35'; this.style.textDecorationColor='rgba(255,107,53,0.35)'">{{ $label }}</a>
            @if(!$loop->last)<span style="color:rgba(255,255,255,0.18); font-size:0.75rem;">·</span>@endif
            @endforeach
        </div>
    </div>
</section>


{{-- ════════════════════════════════════════════════
     2. CATÉGORIES DE TALENTS
════════════════════════════════════════════════ --}}
<section style="background:white; padding:5rem 1.5rem;">
    <div class="reveal" style="text-align:center; margin-bottom:3rem;">
        <h2 style="font-size:clamp(1.8rem,4vw,2.5rem); font-weight:900; color:#111; margin:0 0 0.5rem;">Catégories de Talents</h2>
        <p style="color:#6b7280; font-size:0.95rem; font-weight:500; margin:0 0 0.75rem;">Explorez la richesse artistique ivoirienne.</p>
        <div style="width:36px; height:3px; background:#FF6B35; border-radius:2px; margin:0 auto;"></div>
    </div>

    <div class="cat-grid">
        {{-- DJs --}}
        <a href="{{ route('talents.index', ['category'=>'DJ']) }}" class="cat-card cat-card-blue reveal">
            <div class="cat-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" stroke="white" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
            </div>
            <h3 style="font-size:1.6rem; font-weight:900; color:white; margin:0;">DJs</h3>
            <span class="cat-badge">{{ $categoryCount['DJ'] ?? 0 }} talent{{ ($categoryCount['DJ'] ?? 0) > 1 ? 's' : '' }}</span>
        </a>

        {{-- Musiciens --}}
        <a href="{{ route('talents.index', ['category'=>'Musicien']) }}" class="cat-card cat-card-purple reveal d-1">
            <div class="cat-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" stroke="white" stroke-width="1.8" viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
            </div>
            <h3 style="font-size:1.6rem; font-weight:900; color:white; margin:0;">Musiciens</h3>
            <span class="cat-badge">{{ $categoryCount['Musicien'] ?? 0 }} talent{{ ($categoryCount['Musicien'] ?? 0) > 1 ? 's' : '' }}</span>
        </a>

        {{-- Danseurs --}}
        <a href="{{ route('talents.index', ['category'=>'Danseur']) }}" class="cat-card cat-card-pink reveal d-2">
            <div class="cat-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" stroke="white" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="4" r="2"/><path d="M15.09 8.26L12 10l-3.09-1.74A2 2 0 0 0 7 10v5h2v7h2v-5h2v5h2v-7h2v-5a2 2 0 0 0-1.91-1.74z"/></svg>
            </div>
            <h3 style="font-size:1.6rem; font-weight:900; color:white; margin:0;">Danseurs</h3>
            <span class="cat-badge">{{ $categoryCount['Danseur'] ?? 0 }} talent{{ ($categoryCount['Danseur'] ?? 0) > 1 ? 's' : '' }}</span>
        </a>
    </div>
</section>


{{-- ════════════════════════════════════════════════
     3. STATS
════════════════════════════════════════════════ --}}
<section class="stats-section">
    <div class="stats-grid">
        @foreach([
            [
                'value'  => '9+',
                'count'  => 9,
                'suffix' => '+',
                'label'  => 'Talents Vérifiés',
                'svg'    => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            ],
            [
                'value'  => '16+',
                'count'  => 16,
                'suffix' => '+',
                'label'  => 'Événements Réussis',
                'svg'    => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
            ],
            [
                'value'  => '100%',
                'count'  => 100,
                'suffix' => '%',
                'label'  => 'Satisfaction Client',
                'svg'    => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
            ],
        ] as $stat)
        <div class="reveal" style="transition-delay: {{ $loop->index * 0.14 }}s;">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="#FF6B35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    {!! $stat['svg'] !!}
                </svg>
            </div>
            <div class="stat-count" data-count="{{ $stat['count'] }}" data-suffix="{{ $stat['suffix'] }}"
                 style="font-size:clamp(2.5rem,5vw,3.5rem); font-weight:900; color:white; line-height:1; margin-bottom:0.5rem; letter-spacing:-0.02em;">
                {{ $stat['value'] }}
            </div>
            <div style="font-size:0.72rem; font-weight:800; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:0.12em;">
                {{ $stat['label'] }}
            </div>
        </div>
        @endforeach
    </div>
</section>


{{-- ════════════════════════════════════════════════
     4. POURQUOI BOOKMI
════════════════════════════════════════════════ --}}
<section style="background:white; padding:5rem 1.5rem;">
    <div class="reveal" style="text-align:center; margin-bottom:3rem;">
        <h2 style="font-size:clamp(1.8rem,4vw,2.5rem); font-weight:900; color:#111; margin:0 0 0.5rem;">Pourquoi choisir BookMi&nbsp;?</h2>
        <div style="width:36px; height:3px; background:#FF6B35; border-radius:2px; margin:0 auto 0.75rem;"></div>
        <p style="color:#6b7280; font-size:0.95rem; font-weight:500; margin:0; max-width:480px; margin-left:auto; margin-right:auto;">
            Une plateforme sécurisée et intuitive, conçue pour Babi.
        </p>
    </div>

    <div class="why-grid" style="max-width:1100px; margin:0 auto; display:grid; grid-template-columns:repeat(3,1fr); gap:1.5rem;">
        @foreach([
            [
                'title'  => 'Paiement Sécurisé',
                'desc'   => "Système d'escrow avec Mobile Money (MTN, Orange, Wave). L'argent est bloqué jusqu'à la fin de la prestation.",
                'icon'   => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
                'icon_bg'=> 'rgba(255,107,53,0.1)',
                'icon_color'=> '#FF6B35',
            ],
            [
                'title'  => 'Recherche Avancée',
                'desc'   => 'Trouvez la perle rare par budget, disponibilité, genre musical et localisation précise à Abidjan.',
                'icon'   => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>',
                'icon_bg'=> 'rgba(33,150,243,0.1)',
                'icon_color'=> '#2196F3',
            ],
            [
                'title'  => 'Support Local',
                'desc'   => "Une équipe dédiée basée à Cocody, disponible 7j/7 pour vous accompagner en cas de besoin.",
                'icon'   => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
                'icon_bg'=> 'rgba(34,197,94,0.1)',
                'icon_color'=> '#16a34a',
            ],
        ] as $why)
        <div class="why-card reveal" style="transition-delay: {{ $loop->index * 0.13 }}s;">
            <div class="why-icon" style="background:{{ $why['icon_bg'] }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="{{ $why['icon_color'] }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    {!! $why['icon'] !!}
                </svg>
            </div>
            <h3 style="font-size:1.05rem; font-weight:800; color:#111; margin:0 0 0.6rem;">{{ $why['title'] }}</h3>
            <p style="color:#6b7280; font-size:0.875rem; line-height:1.65; font-weight:500; margin:0;">{{ $why['desc'] }}</p>
            <div class="why-tag">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" stroke="#FF6B35" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Expérience premium BookMi
            </div>
        </div>
        @endforeach
    </div>
</section>


{{-- ════════════════════════════════════════════════
     5. CTA DUAL PATH
════════════════════════════════════════════════ --}}
<section class="cta-section">
    <div class="cta-inner">

        {{-- Gauche --}}
        <div class="reveal-left">
            <div style="display:inline-flex; align-items:center; gap:6px; background:rgba(255,107,53,0.12); border:1px solid rgba(255,107,53,0.25); border-radius:100px; padding:5px 14px; margin-bottom:1.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="#FF6B35" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/></svg>
                <span style="font-size:0.7rem; font-weight:800; color:#FF6B35; text-transform:uppercase; letter-spacing:0.1em;">Réservation Sécurisée · Mobile Money</span>
            </div>

            <h2 style="font-size:clamp(2rem,4vw,2.8rem); font-weight:900; color:white; line-height:1.1; margin:0 0 1rem; letter-spacing:-0.02em;">
                Prêt à commencer&nbsp;?
            </h2>
            <p style="color:rgba(255,255,255,0.55); font-size:0.95rem; font-weight:500; line-height:1.65; margin:0 0 1.75rem; max-width:380px;">
                Rejoignez BookMi. Talents et organisateurs réservent en toute sérénité, avec paiement sécurisé et support local.
            </p>

            <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.8rem;">
                @foreach(['Paiement escrow Mobile Money', 'Profils vérifiés et accompagnés', 'Dispo et confirmations rapides'] as $bullet)
                <li style="display:flex; align-items:center; gap:10px; color:rgba(255,255,255,0.75); font-size:0.875rem; font-weight:600;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="#FF6B35" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    {{ $bullet }}
                </li>
                @endforeach
            </ul>
        </div>

        {{-- Droite : 2 cartes --}}
        <div class="reveal-right">
            {{-- Carte Client --}}
            <div class="path-card path-card-dark" style="position:relative;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.75rem;">
                    <span class="path-label" style="color:rgba(255,255,255,0.45);">Parcours Client</span>
                    <span style="background:rgba(255,107,53,0.15); color:#FF6B35; font-size:0.68rem; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; padding:3px 10px; border-radius:100px; border:1px solid rgba(255,107,53,0.25);">Recommandé</span>
                </div>
                <h3 style="font-size:1.15rem; font-weight:900; color:white; margin:0 0 0.4rem;">Je cherche un Talent</h3>
                <p style="color:rgba(255,255,255,0.45); font-size:0.85rem; font-weight:500; margin:0; line-height:1.5;">Pour mariage, anniversaire ou événement corporate.</p>
                <a href="{{ route('talents.index') }}" class="path-btn path-btn-white">
                    Trouver un talent →
                </a>
            </div>

            {{-- Carte Talent --}}
            <div class="path-card path-card-orange">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.75rem;">
                    <span class="path-label" style="color:rgba(255,255,255,0.7);">Parcours Talent</span>
                    <span style="background:rgba(0,0,0,0.2); color:rgba(255,255,255,0.9); font-size:0.68rem; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; padding:3px 10px; border-radius:100px;">Boost carrière</span>
                </div>
                <h3 style="font-size:1.15rem; font-weight:900; color:white; margin:0 0 0.4rem;">Je suis un Talent</h3>
                <p style="color:rgba(255,255,255,0.75); font-size:0.85rem; font-weight:500; margin:0; line-height:1.5;">Gérez vos bookings, paiements et boostez votre visibilité.</p>
                <a href="{{ route('register') }}" class="path-btn path-btn-dark">
                    Inscription Talent →
                </a>
            </div>
        </div>
    </div>
</section>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ── Scroll reveal (fade-up / slide-left / slide-right) ── */
    var revealObs = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                revealObs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    document.querySelectorAll('.reveal, .reveal-left, .reveal-right')
        .forEach(function (el) { revealObs.observe(el); });

    /* ── Count-up pour les stats ── */
    var countObs = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            var el     = entry.target;
            var end    = parseInt(el.dataset.count, 10);
            var suffix = el.dataset.suffix || '';
            var dur    = 1700;
            var t0     = performance.now();

            (function tick(now) {
                var progress = Math.min((now - t0) / dur, 1);
                var eased    = 1 - Math.pow(1 - progress, 3); /* ease-out cubic */
                el.textContent = Math.round(eased * end) + suffix;
                if (progress < 1) requestAnimationFrame(tick);
            })(t0);

            countObs.unobserve(el);
        });
    }, { threshold: 0.5 });

    document.querySelectorAll('[data-count]')
        .forEach(function (el) { countObs.observe(el); });

});
</script>
@endsection
