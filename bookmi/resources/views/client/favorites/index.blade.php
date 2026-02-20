@extends('layouts.client')

@section('title', 'Mes favoris — BookMi Client')

@section('head')
<style>
/* ── Favorite card ── */
.fav-card {
    border-radius: 1.125rem;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    overflow: hidden;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    transition: transform 0.3s cubic-bezier(0.16,1,0.3,1), box-shadow 0.3s, border-color 0.3s;
    position: relative;
}
.fav-card:hover {
    transform: translateY(-6px) scale(1.01);
    border-color: rgba(255,107,53,0.25);
    box-shadow: 0 12px 40px rgba(0,0,0,0.45), 0 0 0 1px rgba(255,107,53,0.12);
}
/* ── Photo area ── */
.fav-photo {
    position: relative;
    aspect-ratio: 4/3;
    overflow: hidden;
}
.fav-photo-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(13,17,23,0.85) 0%, rgba(13,17,23,0.10) 50%, transparent 100%);
}
.fav-photo img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s cubic-bezier(0.16,1,0.3,1); }
.fav-card:hover .fav-photo img { transform: scale(1.07); }
/* ── Monogram fallback ── */
.fav-mono {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #1A2744 0%, #0F1E3A 100%);
}
.fav-mono-circle {
    width: 4.5rem; height: 4.5rem;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.75rem; font-weight: 900; color: white;
    background: linear-gradient(135deg, var(--blue) 0%, var(--orange) 100%);
    box-shadow: 0 0 30px rgba(255,107,53,0.35);
}
/* ── Remove fav button ── */
.fav-remove {
    position: absolute; top: 0.625rem; right: 0.625rem;
    width: 2rem; height: 2rem; border-radius: 50%;
    background: rgba(13,17,23,0.70); backdrop-filter: blur(8px);
    border: 1px solid rgba(244,67,54,0.30);
    display: flex; align-items: center; justify-content: center;
    color: rgba(252,165,165,0.85);
    cursor: pointer;
    transition: background 0.2s, transform 0.2s;
}
.fav-remove:hover { background: rgba(244,67,54,0.25); transform: scale(1.1); }
/* ── Reveal ── */
.reveal-item { opacity:0; transform:translateY(20px); transition:opacity 0.45s cubic-bezier(0.16,1,0.3,1), transform 0.45s cubic-bezier(0.16,1,0.3,1); }
.reveal-item.visible { opacity:1; transform:none; }
</style>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="p-3 rounded-xl text-sm font-medium" style="background:rgba(76,175,80,0.12);border:1px solid rgba(76,175,80,0.25);color:rgba(134,239,172,0.95)">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="p-3 rounded-xl text-sm font-medium" style="background:rgba(244,67,54,0.12);border:1px solid rgba(244,67,54,0.25);color:rgba(252,165,165,0.95)">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="reveal-item">
        <h1 class="section-title">Mes favoris</h1>
        <p class="section-sub">{{ $favorites->count() }} talent{{ $favorites->count() > 1 ? 's' : '' }} enregistré{{ $favorites->count() > 1 ? 's' : '' }}</p>
    </div>

    @if($favorites->isEmpty())
        {{-- Empty state --}}
        <div class="glass-card flex flex-col items-center justify-center py-20 text-center reveal-item" style="transition-delay:0.06s">
            <div class="relative mb-6">
                <div class="w-20 h-20 rounded-3xl flex items-center justify-center"
                     style="background:rgba(255,107,53,0.08);border:1px solid rgba(255,107,53,0.15);box-shadow:0 0 40px rgba(255,107,53,0.10)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#FF6B35" viewBox="0 0 24 24">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" opacity="0.3"/>
                    </svg>
                </div>
            </div>
            <p class="font-black text-base mb-2" style="color:var(--text)">Aucun talent en favori</p>
            <p class="text-sm mb-7 max-w-xs" style="color:var(--text-muted)">Explorez les talents et cliquez sur ❤ pour les sauvegarder ici.</p>
            <a href="{{ route('talents.index') }}"
               class="px-7 py-3 rounded-xl text-sm font-bold text-white"
               style="background:linear-gradient(135deg,var(--orange),#ff8c5a);box-shadow:0 4px 16px rgba(255,107,53,0.35)">
                Découvrir les talents
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($favorites as $i => $talent)
            @php
                $tName = $talent->stage_name ?? trim(($talent->user->first_name ?? '') . ' ' . ($talent->user->last_name ?? '')) ?: '?';
                $catColors = ['DJ' => '#5865F2', 'Musicien' => '#9B59B6', 'Danseur' => '#E91E63', 'Photographe' => '#00BCD4', 'Vidéaste' => '#FF5722', 'Animateur' => '#FF6B35', 'Chanteur' => '#4CAF50'];
                $catName = $talent->category->name ?? '';
                $catColor = $catColors[$catName] ?? '#FF6B35';
            @endphp
            <div class="fav-card reveal-item" style="transition-delay:{{ min($i * 0.08, 0.4) }}s">

                {{-- Photo --}}
                <div class="fav-photo">
                    @if($talent->user->profile_photo_url ?? false)
                        <img src="{{ $talent->user->profile_photo_url }}" alt="{{ $tName }}" loading="lazy">
                    @else
                        <div class="fav-mono">
                            <div class="fav-mono-circle">{{ strtoupper(substr($tName, 0, 1)) }}</div>
                        </div>
                    @endif
                    <div class="fav-photo-overlay"></div>

                    {{-- Category badge (bottom-left over photo) --}}
                    @if($catName)
                    <span class="absolute bottom-2.5 left-3 badge-status"
                          style="background:{{ $catColor }}30;color:{{ $catColor }};border:1px solid {{ $catColor }}50;backdrop-filter:blur(6px);font-size:0.65rem">
                        {{ $catName }}
                    </span>
                    @endif

                    {{-- Remove button --}}
                    <form action="{{ route('client.favorites.destroy', $talent->id) }}" method="POST"
                          onsubmit="return confirm('Retirer ce talent de vos favoris ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="fav-remove">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                        </button>
                    </form>
                </div>

                {{-- Info --}}
                <div class="p-4">
                    <h3 class="font-black text-sm mb-0.5" style="color:var(--text)">{{ $tName }}</h3>
                    @if($talent->bio)
                        <p class="text-xs leading-relaxed mb-3 line-clamp-2" style="color:var(--text-muted)">{{ $talent->bio }}</p>
                    @else
                        <p class="mb-3"></p>
                    @endif
                    <a href="{{ route('talent.show', $talent->slug ?? $talent->id) }}"
                       class="block w-full text-center px-3 py-2.5 rounded-xl text-xs font-bold text-white transition-all hover:scale-105"
                       style="background:linear-gradient(135deg,var(--navy),var(--blue));box-shadow:0 3px 10px rgba(33,150,243,0.25)">
                        Voir le profil →
                    </a>
                </div>

            </div>
            @endforeach
        </div>
    @endif

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const io = new IntersectionObserver(entries => {
        entries.forEach(e => { if(e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); } });
    }, { threshold: 0.05 });
    document.querySelectorAll('.reveal-item').forEach(el => io.observe(el));
});
</script>
@endsection
