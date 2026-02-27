@extends('layouts.public')

@section('title', $profile->stage_name . ' — BookMi')
@section('meta_description'){{ Str::limit($profile->bio ?? 'Talent sur BookMi', 160) }}@endsection

@section('head')
{{-- Open Graph --}}
<meta property="og:title" content="{{ $profile->stage_name }} — BookMi">
<meta property="og:description" content="{{ Str::limit($profile->bio ?? 'Talent sur BookMi', 160) }}">
<meta property="og:url" content="{{ route('talent.show', $profile->slug) }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="BookMi">
{{-- Schema.org JSON-LD --}}
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Person',
    'name' => $profile->stage_name,
    'url' => route('talent.show', $profile->slug),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<style>
/* ── Hero ── */
.tp-hero {
    background: #070B14;
    background-image:
        radial-gradient(ellipse 120% 50% at 50% 120%, rgba(255,107,53,0.18) 0%, transparent 65%),
        radial-gradient(ellipse 100% 50% at 50% -10%, rgba(20,35,70,0.95) 0%, transparent 60%),
        radial-gradient(ellipse 60% 40% at 95% 5%,   rgba(33,150,243,0.07) 0%, transparent 55%);
    padding: 3.5rem 1.5rem 0;
}
.tp-avatar {
    width: 96px; height: 96px;
    border-radius: 24px;
    background: linear-gradient(135deg, #FF6B35, #E8520A);
    display: flex; align-items: center; justify-content: center;
    font-size: 2.5rem; font-weight: 900; color: white;
    box-shadow: 0 8px 32px rgba(255,107,53,0.40);
    flex-shrink: 0;
}
.tp-verified {
    display: inline-flex; align-items: center; gap: 4px;
    background: rgba(76,175,80,0.15);
    border: 1px solid rgba(76,175,80,0.35);
    color: #4CAF50;
    font-size: 0.72rem; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase;
    padding: 4px 10px; border-radius: 100px;
}
.tp-rating-star { color: #FF9800; }

/* ── Tabs ── */
.tp-tab-bar {
    display: flex; gap: 0; border-bottom: 1px solid rgba(255,255,255,0.08);
    padding: 0 1.5rem; max-width: 1200px; margin: 0 auto;
}
.tp-tab {
    padding: 1rem 1.25rem;
    font-size: 0.85rem; font-weight: 700;
    color: rgba(255,255,255,0.45);
    border-bottom: 2px solid transparent;
    cursor: pointer; text-decoration: none;
    transition: color 0.15s, border-color 0.15s;
}
.tp-tab.active, .tp-tab:hover { color: white; }
.tp-tab.active { border-bottom-color: #FF6B35; }

/* ── Body layout ── */
.tp-body {
    max-width: 1200px; margin: 0 auto;
    padding: 2.5rem 1.5rem;
    display: grid; grid-template-columns: 1fr 340px; gap: 2rem;
    align-items: start;
}
@media (max-width: 900px) {
    .tp-body { grid-template-columns: 1fr; }
    .tp-sidebar { order: -1; }
}

/* ── Sections ── */
.tp-section {
    background: #111827;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 20px;
    padding: 1.75rem;
    margin-bottom: 1.25rem;
}
.tp-section-title {
    font-size: 1rem; font-weight: 900; color: white;
    margin: 0 0 1.25rem;
    display: flex; align-items: center; gap: 8px;
}
.tp-section-title::before {
    content: ''; display: block;
    width: 3px; height: 18px; border-radius: 2px;
    background: #FF6B35;
}

/* ── Package cards ── */
.pkg-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 14px;
    padding: 1.25rem;
    transition: border-color 0.2s, background 0.2s;
    cursor: pointer;
}
.pkg-card:hover {
    border-color: rgba(255,107,53,0.4);
    background: rgba(255,107,53,0.04);
}
.pkg-card.selected {
    border-color: #FF6B35;
    background: rgba(255,107,53,0.07);
}

/* ── Sidebar ── */
.tp-sidebar-card {
    background: #111827;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 20px;
    padding: 1.75rem;
    position: sticky; top: 80px;
}
.tp-cta-btn {
    display: block; width: 100%;
    padding: 14px 20px;
    background: linear-gradient(135deg, #FF6B35, #E8520A);
    color: white; font-weight: 900; font-size: 0.95rem;
    border: none; border-radius: 12px; cursor: pointer;
    text-align: center; text-decoration: none;
    transition: transform 0.15s, box-shadow 0.15s;
    position: relative; overflow: hidden;
}
.tp-cta-btn::before {
    content: '';
    position: absolute; inset: 0 0 50% 0;
    background: linear-gradient(rgba(255,255,255,0.12), rgba(255,255,255,0));
    border-radius: 12px 12px 0 0;
}
.tp-cta-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 28px rgba(255,107,53,0.45); }
.tp-login-btn {
    display: block; width: 100%;
    padding: 12px 20px; margin-top: 0.75rem;
    background: transparent;
    color: rgba(255,255,255,0.6); font-weight: 700; font-size: 0.85rem;
    border: 1px solid rgba(255,255,255,0.15); border-radius: 12px; cursor: pointer;
    text-align: center; text-decoration: none;
    transition: border-color 0.15s, color 0.15s;
}
.tp-login-btn:hover { border-color: rgba(255,255,255,0.4); color: white; }

/* ── Similar talents ── */
.similar-card {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 12px;
    text-decoration: none;
    transition: border-color 0.15s, background 0.15s;
}
.similar-card:hover {
    border-color: rgba(255,107,53,0.35);
    background: rgba(255,107,53,0.05);
}

/* ── Lightbox ── */
[x-cloak] { display: none !important; }
.scale-95 { transform: scale(0.95); }
.scale-100 { transform: scale(1); }
.opacity-0 { opacity: 0; }
.opacity-100 { opacity: 1; }

/* ── Page entry animation ── */
/* IMPORTANT: 'to' uses transform:none (not translateY(0)) to avoid creating
   a stacking context that would trap position:fixed descendants (lightbox). */
@keyframes tpFadeUp {
    from { opacity: 0; transform: translateY(24px); }
    to   { opacity: 1; transform: none; }
}
.tp-hero-inner { animation: tpFadeUp 0.75s cubic-bezier(0.16,1,0.3,1) both; }
.tp-body       { animation: tpFadeUp 0.75s cubic-bezier(0.16,1,0.3,1) 0.12s both; }
</style>
@endsection

@section('content')
@php
    $initial = strtoupper(substr($profile->stage_name, 0, 1));
    $minPrice = $profile->servicePackages->min('cachet_amount');
    $ratingVal = (float) $profile->average_rating;
    $fullStars = floor($ratingVal);
@endphp

{{-- ══════════════════════ HERO ══════════════════════ --}}
<section class="tp-hero">
    <div style="max-width:1200px; margin:0 auto;">
        <div class="tp-hero-inner" style="display:flex; align-items:flex-end; gap:1.5rem; padding-bottom:2rem; flex-wrap:wrap;">

            {{-- Avatar / Photo de profil --}}
            @if($profile->cover_photo_url)
                <img src="{{ $profile->cover_photo_url }}"
                     alt="{{ $profile->stage_name }}"
                     class="tp-avatar"
                     style="object-fit:cover;font-size:0">
            @else
                <div class="tp-avatar">{{ $initial }}</div>
            @endif

            {{-- Infos --}}
            <div style="flex:1; min-width:200px;">
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:0.5rem;">
                    <h1 style="font-size:2.2rem; font-weight:900; color:white; margin:0; letter-spacing:-0.02em; line-height:1.1;">
                        {{ $profile->stage_name }}
                    </h1>
                    @if($profile->is_verified)
                        <span class="tp-verified">
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            Vérifié
                        </span>
                    @endif
                </div>

                <div style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
                    {{-- Catégorie --}}
                    <span style="display:inline-flex; align-items:center; gap:5px; color:rgba(255,255,255,0.55); font-size:0.875rem; font-weight:600;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="#FF6B35" stroke-width="2" viewBox="0 0 24 24"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>
                        {{ $profile->category->name ?? '—' }}
                    </span>
                    {{-- Ville --}}
                    <span style="display:inline-flex; align-items:center; gap:5px; color:rgba(255,255,255,0.55); font-size:0.875rem; font-weight:600;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        {{ $profile->city ?? 'Côte d\'Ivoire' }}
                    </span>
                    {{-- Note --}}
                    <span style="display:inline-flex; align-items:center; gap:4px; font-size:0.875rem; font-weight:700; color:rgba(255,255,255,0.75);">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= $fullStars)
                                <span class="tp-rating-star">★</span>
                            @else
                                <span style="color:rgba(255,255,255,0.2);">★</span>
                            @endif
                        @endfor
                        <span style="color:rgba(255,255,255,0.5); font-size:0.8rem; margin-left:2px;">{{ number_format($ratingVal, 1) }}/5</span>
                    </span>
                    {{-- Prix min --}}
                    @if($minPrice)
                        <span style="color:#FF6B35; font-size:0.875rem; font-weight:800;">
                            Dès {{ number_format($minPrice, 0, ',', ' ') }} FCFA
                        </span>
                    @endif
                </div>
            </div>

            {{-- CTA desktop --}}
            <div class="hidden md:block" style="flex-shrink:0;">
                @auth
                    @if(auth()->user()->hasRole('client'))
                        <a href="{{ route('client.bookings.create', ['talent' => $profile->slug ?? $profile->id]) }}"
                           class="tp-cta-btn" style="display:inline-flex; align-items:center; gap:8px; width:auto; padding:12px 28px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            Réserver ce talent
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="tp-cta-btn" style="display:inline-flex; align-items:center; gap:8px; width:auto; padding:12px 28px;">
                        Réserver ce talent
                    </a>
                @endauth
            </div>
        </div>

        {{-- Tabs --}}
        <div class="tp-tab-bar">
            <a href="#bio"      class="tp-tab active">À propos</a>
            <a href="#packages" class="tp-tab">Tarifs</a>
            @if($profile->portfolioItems->isNotEmpty())
                <a href="#portfolio" class="tp-tab">Portfolio</a>
            @endif
            @if($profile->receivedReviews->isNotEmpty())
                <a href="#avis" class="tp-tab">Avis ({{ $profile->receivedReviews->count() }})</a>
            @endif
        </div>
    </div>
</section>

{{-- ══════════════════════ BODY ══════════════════════ --}}
<div style="background:#0D1117; min-height:60vh;">
    <div class="tp-body">

        {{-- ── COLONNE PRINCIPALE ── --}}
        <div>

            {{-- Bio --}}
            <div class="tp-section" id="bio">
                <h2 class="tp-section-title">À propos</h2>
                @if($profile->bio)
                    <p style="color:rgba(255,255,255,0.65); font-size:0.95rem; line-height:1.75; margin:0;">
                        {{ $profile->bio }}
                    </p>
                @else
                    <p style="color:rgba(255,255,255,0.3); font-size:0.9rem; font-style:italic;">Aucune biographie renseignée.</p>
                @endif

                @if($profile->city || ($profile->category && $profile->category->name))
                    <div style="display:flex; flex-wrap:wrap; gap:0.75rem; margin-top:1.5rem;">
                        @if($profile->city)
                            <div style="display:flex; align-items:center; gap:6px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.07); border-radius:10px; padding:8px 14px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="#FF6B35" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                <span style="color:rgba(255,255,255,0.6); font-size:0.82rem; font-weight:600;">{{ $profile->city }}</span>
                            </div>
                        @endif
                        @if($profile->category)
                            <div style="display:flex; align-items:center; gap:6px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.07); border-radius:10px; padding:8px 14px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="#2196F3" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="16"/><line x1="8" x2="16" y1="12" y2="12"/></svg>
                                <span style="color:rgba(255,255,255,0.6); font-size:0.82rem; font-weight:600;">{{ $profile->category->name }}</span>
                            </div>
                        @endif
                        @if($profile->is_verified)
                            <div style="display:flex; align-items:center; gap:6px; background:rgba(76,175,80,0.08); border:1px solid rgba(76,175,80,0.2); border-radius:10px; padding:8px 14px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="#4CAF50" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                                <span style="color:#4CAF50; font-size:0.82rem; font-weight:700;">Profil vérifié BookMi</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Packages --}}
            @if($profile->servicePackages->isNotEmpty())
                <div class="tp-section" id="packages">
                    <h2 class="tp-section-title">Tarifs & Packages</h2>
                    <div style="display:flex; flex-direction:column; gap:0.875rem;">
                        @foreach($profile->servicePackages as $pkg)
                        <div class="pkg-card {{ $loop->first ? 'selected' : '' }}">
                            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                                <div style="flex:1;">
                                    <p style="font-weight:800; font-size:0.95rem; color:white; margin:0 0 0.3rem;">{{ $pkg->name }}</p>
                                    @if($pkg->description)
                                        <p style="color:rgba(255,255,255,0.45); font-size:0.82rem; line-height:1.55; margin:0;">{{ $pkg->description }}</p>
                                    @endif
                                    @if($pkg->duration_minutes)
                                        <p style="color:rgba(255,255,255,0.35); font-size:0.75rem; margin:0.4rem 0 0; display:flex; align-items:center; gap:4px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                            {{ $pkg->duration_minutes >= 60 ? floor($pkg->duration_minutes / 60).'h' : $pkg->duration_minutes.'min' }} de prestation
                                        </p>
                                    @endif
                                </div>
                                <div style="text-align:right; flex-shrink:0;">
                                    <p style="font-size:1.3rem; font-weight:900; color:#FF6B35; margin:0; line-height:1;">
                                        {{ number_format($pkg->cachet_amount, 0, ',', ' ') }}
                                    </p>
                                    <p style="color:rgba(255,255,255,0.3); font-size:0.72rem; font-weight:600; margin:2px 0 0;">FCFA</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Portfolio --}}
            @if($profile->portfolioItems->isNotEmpty())
                <div class="tp-section" id="portfolio" x-data="portfolioLightbox()" @keydown.escape.window="close()">
                    <h2 class="tp-section-title">Portfolio</h2>
                    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:1rem;">
                        @foreach($profile->portfolioItems as $item)
                            @if($item->media_type === 'image')
                                {{-- Image cliquable → lightbox --}}
                                <div @click="openImage('{{ $item->publicUrl() }}', '{{ addslashes($item->caption ?? 'Portfolio') }}')"
                                     style="border-radius:14px; overflow:hidden; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.07); aspect-ratio:4/3; cursor:pointer; position:relative; transition:transform 0.2s, box-shadow 0.2s;"
                                     onmouseover="this.style.transform='scale(1.02)';this.style.boxShadow='0 8px 32px rgba(255,107,53,0.3)'"
                                     onmouseout="this.style.transform='';this.style.boxShadow=''">
                                    <img src="{{ $item->publicUrl() }}"
                                         alt="{{ $item->caption ?? 'Portfolio' }}"
                                         style="width:100%; height:100%; object-fit:cover; display:block;">
                                    {{-- Icône loupe au survol --}}
                                    <div style="position:absolute;inset:0;background:rgba(0,0,0,0);display:flex;align-items:center;justify-content:center;transition:background 0.2s;"
                                         onmouseover="this.style.background='rgba(0,0,0,0.35)'"
                                         onmouseout="this.style.background='rgba(0,0,0,0)'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24" style="opacity:0;transition:opacity 0.2s"
                                             onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                            <line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/>
                                        </svg>
                                    </div>
                                </div>

                            @elseif($item->media_type === 'video')
                                {{-- Vidéo native cliquable → lightbox --}}
                                <div @click="openVideo('{{ $item->publicUrl() }}', '{{ addslashes($item->caption ?? 'Vidéo') }}')"
                                     style="border-radius:14px; overflow:hidden; background:#000; border:1px solid rgba(255,255,255,0.07); aspect-ratio:16/9; cursor:pointer; position:relative; transition:transform 0.2s, box-shadow 0.2s;"
                                     onmouseover="this.style.transform='scale(1.02)';this.style.boxShadow='0 8px 32px rgba(255,107,53,0.3)'"
                                     onmouseout="this.style.transform='';this.style.boxShadow=''">
                                    <video src="{{ $item->publicUrl() }}"
                                           preload="metadata"
                                           style="width:100%; height:100%; object-fit:cover; display:block; pointer-events:none;">
                                    </video>
                                    <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.3);">
                                        <div style="width:52px;height:52px;background:rgba(255,107,53,0.9);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="white" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                        </div>
                                    </div>
                                </div>

                            @elseif($item->media_type === 'link')
                                @php
                                    $embedUrl = $item->youtubeEmbedUrl();
                                    $videoId  = null;
                                    if ($embedUrl) {
                                        preg_match('/embed\/([a-zA-Z0-9_-]{11})/', $embedUrl, $vm);
                                        $videoId = $vm[1] ?? null;
                                    }
                                @endphp
                                @if($embedUrl && $videoId)
                                    {{-- YouTube : miniature cliquable → lightbox --}}
                                    <div @click="openYoutube('{{ $embedUrl }}')"
                                         style="border-radius:14px; overflow:hidden; border:1px solid rgba(255,107,53,0.2); aspect-ratio:16/9; background:#000; cursor:pointer; position:relative; transition:transform 0.2s, box-shadow 0.2s;"
                                         onmouseover="this.style.transform='scale(1.02)';this.style.boxShadow='0 8px 32px rgba(255,107,53,0.35)'"
                                         onmouseout="this.style.transform='';this.style.boxShadow=''">
                                        <img src="https://img.youtube.com/vi/{{ $videoId }}/hqdefault.jpg"
                                             alt="{{ $item->caption ?? 'Vidéo YouTube' }}"
                                             style="width:100%; height:100%; object-fit:cover; display:block;">
                                        {{-- Bouton play YouTube --}}
                                        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.25);">
                                            <div style="width:60px;height:60px;background:rgba(255,0,0,0.9);border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 20px rgba(0,0,0,0.5);">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="white" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    {{-- Autre lien --}}
                                    <a href="{{ $item->link_url }}" target="_blank" rel="noopener"
                                       style="border-radius:14px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.07); aspect-ratio:4/3; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; text-decoration:none; transition:border-color 0.15s;"
                                       onmouseover="this.style.borderColor='rgba(255,107,53,0.4)'"
                                       onmouseout="this.style.borderColor='rgba(255,255,255,0.07)'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#FF6B35" stroke-width="2" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                        <span style="color:rgba(255,255,255,0.5); font-size:0.75rem; font-weight:600; text-align:center; padding:0 8px;">
                                            {{ $item->caption ?? Str::limit($item->link_url, 30) }}
                                        </span>
                                    </a>
                                @endif
                            @endif
                            {{-- Caption --}}
                            @if($item->caption && $item->media_type !== 'link')
                                <p style="color:rgba(255,255,255,0.35); font-size:0.75rem; margin:0.4rem 0 0; text-align:center;">{{ $item->caption }}</p>
                            @endif
                        @endforeach
                    </div>

                    {{-- ── LIGHTBOX MODAL ──
                         Téléporté à <body> pour échapper aux stacking contexts (animations).
                         Architecture deux niveaux :
                         - Niveau 1 (x-show shell) : position:fixed couvrant le viewport, togglé par Alpine
                         - Niveau 2 (flex inner) : jamais touché par Alpine → display:flex garanti
                    --}}
                    <template x-teleport="body">
                        {{-- Shell : Alpine ne fait que toggle display:none ↔ display:block ici --}}
                        <div x-show="open" x-cloak
                             @keydown.escape.window="close()"
                             style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:99999;">

                            {{-- Flex centering : jamais touché par Alpine, display:flex garanti --}}
                            <div @click.self="close()"
                                 style="position:absolute;top:0;left:0;width:100%;height:100%;
                                        background:rgba(0,0,0,0.92);
                                        display:flex;align-items:center;justify-content:center;
                                        padding:1.5rem;box-sizing:border-box;">

                                {{-- Bouton ✕ Fermer --}}
                                <button @click="close()"
                                        style="position:absolute;top:1rem;right:1rem;
                                               width:48px;height:48px;border-radius:50%;
                                               background:rgba(255,255,255,0.15);
                                               border:2px solid rgba(255,255,255,0.3);
                                               color:#fff;font-size:1.4rem;font-weight:700;line-height:1;
                                               cursor:pointer;display:flex;align-items:center;justify-content:center;"
                                        onmouseover="this.style.background='rgba(255,107,53,0.85)';this.style.borderColor='#FF6B35'"
                                        onmouseout="this.style.background='rgba(255,255,255,0.15)';this.style.borderColor='rgba(255,255,255,0.3)'">
                                    ✕
                                </button>

                                {{-- Image --}}
                                <div x-show="type === 'image'"
                                     style="width:100%;max-width:920px;text-align:center;flex-shrink:0;">
                                    <img :src="src" :alt="alt"
                                         style="max-width:100%;max-height:80vh;object-fit:contain;
                                                border-radius:16px;box-shadow:0 24px 80px rgba(0,0,0,0.8);
                                                display:block;margin:0 auto;">
                                    <p x-show="alt" x-text="alt"
                                       style="color:rgba(255,255,255,0.6);font-size:0.9rem;font-weight:600;margin:0.75rem 0 0;"></p>
                                </div>

                                {{-- Vidéo native --}}
                                <div x-show="type === 'video'"
                                     style="width:100%;max-width:920px;flex-shrink:0;">
                                    <video :src="src" controls autoplay
                                           style="width:100%;max-height:80vh;border-radius:16px;
                                                  box-shadow:0 24px 80px rgba(0,0,0,0.8);background:#000;display:block;">
                                    </video>
                                </div>

                                {{-- YouTube (ratio 16/9 garanti par padding-top) --}}
                                <div x-show="type === 'youtube'"
                                     style="width:100%;max-width:920px;flex-shrink:0;">
                                    <div style="position:relative;width:100%;padding-top:56.25%;">
                                        <iframe :src="src"
                                                style="position:absolute;top:0;left:0;width:100%;height:100%;
                                                       border:none;border-radius:16px;
                                                       box-shadow:0 24px 80px rgba(0,0,0,0.8);"
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                allowfullscreen>
                                        </iframe>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </template>
                </div>
            @endif

            {{-- Avis clients --}}
            @if($profile->receivedReviews->isNotEmpty())
            <div class="tp-section" id="avis">
                <h2 class="tp-section-title">Avis clients</h2>
                <div style="display:flex; flex-direction:column; gap:1rem;">
                    @foreach($profile->receivedReviews as $review)
                    @php
                        $rInit = strtoupper(substr($review->reviewer->first_name ?? 'C', 0, 1));
                        $rName = ($review->reviewer->first_name ?? 'Client')
                            . ($review->reviewer->last_name ? ' ' . strtoupper(substr($review->reviewer->last_name, 0, 1)) . '.' : '');
                    @endphp
                    <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:14px; padding:1.25rem;">

                        {{-- En-tête : avatar + nom + date + étoiles --}}
                        <div style="display:flex; align-items:flex-start; gap:12px; margin-bottom:0.875rem;">
                            <div style="width:38px; height:38px; border-radius:10px; background:linear-gradient(135deg,#1A2744,#2D4A8A); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.88rem; color:white; flex-shrink:0;">
                                {{ $rInit }}
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:6px;">
                                    <p style="font-weight:700; color:white; font-size:0.88rem; margin:0;">{{ $rName }}</p>
                                    <p style="color:rgba(255,255,255,0.3); font-size:0.75rem; margin:0;">{{ $review->created_at->format('d/m/Y') }}</p>
                                </div>
                                <div style="display:flex; gap:2px; margin-top:4px;">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span style="color:{{ $i <= $review->rating ? '#FF9800' : 'rgba(255,255,255,0.15)' }}; font-size:0.9rem;">★</span>
                                    @endfor
                                    <span style="color:rgba(255,255,255,0.35); font-size:0.78rem; margin-left:4px; align-self:center;">{{ $review->rating }}/5</span>
                                </div>
                            </div>
                        </div>

                        {{-- Commentaire --}}
                        @if($review->comment)
                        <p style="color:rgba(255,255,255,0.6); font-size:0.875rem; line-height:1.7; margin:0 0 0.75rem;">{{ $review->comment }}</p>
                        @endif

                        {{-- Réponse du talent --}}
                        @if($review->reply)
                        <div style="background:rgba(255,107,53,0.07); border:1px solid rgba(255,107,53,0.2); border-radius:10px; padding:0.875rem;">
                            <div style="display:flex; align-items:center; gap:6px; margin-bottom:0.4rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" stroke="#FF6B35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>
                                <span style="color:#FF6B35; font-size:0.72rem; font-weight:800; text-transform:uppercase; letter-spacing:0.05em;">Réponse du talent</span>
                            </div>
                            <p style="color:rgba(255,255,255,0.55); font-size:0.85rem; line-height:1.65; margin:0;">{{ $review->reply }}</p>
                        </div>
                        @endif

                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Liens sociaux --}}
            @if($profile->social_links && count($profile->social_links) > 0)
                <div class="tp-section">
                    <h2 class="tp-section-title">Réseaux sociaux</h2>
                    <div style="display:flex; flex-wrap:wrap; gap:0.75rem;">
                        @foreach($profile->social_links as $platform => $url)
                            @if($url)
                            @php
                                $icons = [
                                    'instagram' => ['color'=>'#E1306C','label'=>'Instagram','svg'=>'<path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>'],
                                    'facebook'  => ['color'=>'#1877F2','label'=>'Facebook', 'svg'=>'<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>'],
                                    'youtube'   => ['color'=>'#FF0000','label'=>'YouTube',  'svg'=>'<path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.54C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02"/>'],
                                    'tiktok'    => ['color'=>'#fff',   'label'=>'TikTok',  'svg'=>'<path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/>'],
                                ];
                                $info = $icons[$platform] ?? ['color'=>'#aaa','label'=>ucfirst($platform),'svg'=>'<circle cx="12" cy="12" r="10"/>'];
                            @endphp
                            <a href="{{ $url }}" target="_blank" rel="noopener"
                               style="display:inline-flex; align-items:center; gap:8px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.09); border-radius:10px; padding:8px 16px; text-decoration:none; transition:all 0.15s;"
                               onmouseover="this.style.borderColor='{{ $info['color'] }}40';this.style.background='rgba(255,255,255,0.08)'"
                               onmouseout="this.style.borderColor='rgba(255,255,255,0.09)';this.style.background='rgba(255,255,255,0.05)'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="{{ $info['color'] }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">{!! $info['svg'] !!}</svg>
                                <span style="color:rgba(255,255,255,0.75); font-size:0.82rem; font-weight:700;">{{ $info['label'] }}</span>
                            </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

        </div>

        {{-- ── SIDEBAR ── --}}
        <div class="tp-sidebar">
            <div class="tp-sidebar-card">
                <h3 style="font-size:0.95rem; font-weight:900; color:white; margin:0 0 0.25rem;">Réserver {{ $profile->stage_name }}</h3>
                <p style="color:rgba(255,255,255,0.35); font-size:0.8rem; font-weight:600; margin:0 0 1.5rem;">
                    Confirmez votre prestation en quelques clics
                </p>

                @if($minPrice)
                    <div style="background:rgba(255,107,53,0.08); border:1px solid rgba(255,107,53,0.2); border-radius:12px; padding:12px 16px; margin-bottom:1.25rem;">
                        <p style="color:rgba(255,255,255,0.4); font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; margin:0 0 2px;">À partir de</p>
                        <p style="font-size:1.7rem; font-weight:900; color:#FF6B35; margin:0; line-height:1.1;">
                            {{ number_format($minPrice, 0, ',', ' ') }}
                            <span style="font-size:0.85rem; font-weight:700; color:rgba(255,107,53,0.7);">FCFA</span>
                        </p>
                    </div>
                @endif

                @auth
                    @if(auth()->user()->hasRole('client'))
                        <a href="{{ route('client.bookings.create', ['talent' => $profile->slug ?? $profile->id]) }}" class="tp-cta-btn">
                            Demander une prestation →
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="tp-cta-btn">
                            Créer un compte client →
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="tp-cta-btn">
                        Connexion pour réserver →
                    </a>
                    <a href="{{ route('register') }}" class="tp-login-btn">
                        Créer un compte gratuitement
                    </a>
                @endauth

                <div style="margin-top:1.5rem; padding-top:1.25rem; border-top:1px solid rgba(255,255,255,0.06);">
                    <div style="display:flex; flex-direction:column; gap:0.6rem;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="#4CAF50" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                            <span style="color:rgba(255,255,255,0.45); font-size:0.8rem; font-weight:600;">Paiement sécurisé Mobile Money</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="#4CAF50" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                            <span style="color:rgba(255,255,255,0.45); font-size:0.8rem; font-weight:600;">Argent bloqué jusqu'à la prestation</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="#4CAF50" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                            <span style="color:rgba(255,255,255,0.45); font-size:0.8rem; font-weight:600;">Support BookMi 7j/7</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ══ Talents similaires ══ --}}
    @if($similarTalents->isNotEmpty())
        <div style="max-width:1200px; margin:0 auto; padding:0 1.5rem 3rem;">
            <h2 style="font-size:1.1rem; font-weight:900; color:white; margin:0 0 1.25rem; display:flex; align-items:center; gap:8px;">
                <span style="display:block; width:3px; height:18px; border-radius:2px; background:#FF6B35;"></span>
                Talents similaires
            </h2>
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:0.75rem;">
                @foreach($similarTalents as $similar)
                    @php $sInit = strtoupper(substr($similar->stage_name, 0, 1)); @endphp
                    <a href="{{ route('talent.show', $similar->slug) }}" class="similar-card">
                        <div style="width:44px; height:44px; border-radius:12px; background:linear-gradient(135deg,#FF6B35,#E8520A); display:flex; align-items:center; justify-content:center; font-weight:900; font-size:1.1rem; color:white; flex-shrink:0;">
                            {{ $sInit }}
                        </div>
                        <div style="flex:1; min-width:0;">
                            <p style="font-weight:800; font-size:0.9rem; color:white; margin:0 0 2px; truncate;">{{ $similar->stage_name }}</p>
                            <p style="color:rgba(255,255,255,0.35); font-size:0.78rem; font-weight:600; margin:0;">{{ $similar->category->name ?? '' }} · {{ $similar->city ?? '' }}</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="rgba(255,255,255,0.25)" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
function portfolioLightbox() {
    return {
        open: false,
        type: '',   // 'image' | 'video' | 'youtube'
        src:  '',
        alt:  '',

        openImage(src, alt) {
            this.type = 'image';
            this.src  = src;
            this.alt  = alt;
            this.open = true;
            document.body.style.overflow = 'hidden';
        },

        openVideo(src, alt) {
            this.type = 'video';
            this.src  = src;
            this.alt  = alt;
            this.open = true;
            document.body.style.overflow = 'hidden';
        },

        openYoutube(embedUrl) {
            this.type = 'youtube';
            // Ajouter autoplay=1 pour lancer la vidéo immédiatement
            this.src  = embedUrl.replace('?rel=0', '?rel=0&autoplay=1');
            this.open = true;
            document.body.style.overflow = 'hidden';
        },

        close() {
            this.open = false;
            this.src  = '';
            this.alt  = '';
            document.body.style.overflow = '';
        }
    }
}
</script>
@endsection
