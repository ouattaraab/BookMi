@extends('layouts.client')

@section('title', 'Mes favoris — BookMi Client')

@section('head')
<style>
main.page-content { background: #F2EFE9 !important; }

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(22px); }
    to   { opacity: 1; transform: translateY(0); }
}
.dash-fade { opacity: 0; animation: fadeUp 0.6s cubic-bezier(0.16,1,0.3,1) forwards; }

/* ── Favorite card ── */
.fav-card {
    border-radius: 18px;
    background: #FFFFFF;
    border: 1px solid #E5E1DA;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(26,39,68,0.06);
    transition: transform 0.3s cubic-bezier(0.16,1,0.3,1), box-shadow 0.3s, border-color 0.3s;
    position: relative;
}
.fav-card:hover {
    transform: translateY(-6px) scale(1.01);
    border-color: #FF6B35;
    box-shadow: 0 14px 40px rgba(255,107,53,0.14);
}

/* ── Photo area ── */
.fav-photo {
    position: relative;
    aspect-ratio: 4/3;
    overflow: hidden;
}
.fav-photo-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(26,39,68,0.75) 0%, rgba(26,39,68,0.10) 50%, transparent 100%);
}
.fav-photo img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s cubic-bezier(0.16,1,0.3,1); }
.fav-card:hover .fav-photo img { transform: scale(1.06); }

/* ── Monogram fallback ── */
.fav-mono {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #EFF6FF 0%, #F0FDF4 100%);
}
.fav-mono-circle {
    width: 5rem; height: 5rem;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; font-weight: 900; color: white;
    background: linear-gradient(135deg, #1A2744 0%, #FF6B35 100%);
    box-shadow: 0 0 30px rgba(255,107,53,0.25);
}

/* ── Remove fav button ── */
.fav-remove {
    position: absolute; top: 10px; right: 10px;
    width: 34px; height: 34px; border-radius: 50%;
    background: rgba(255,255,255,0.92); backdrop-filter: blur(8px);
    border: 1.5px solid #FCA5A5;
    display: flex; align-items: center; justify-content: center;
    color: #EF4444; cursor: pointer;
    transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.10);
}
.fav-remove:hover {
    background: #FEF2F2;
    transform: scale(1.12);
    box-shadow: 0 4px 12px rgba(239,68,68,0.20);
}

/* ── CTA Button ── */
.btn-cta {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 22px;
    background: linear-gradient(135deg, #FF6B35 0%, #f0520f 100%);
    color: #fff; font-weight: 800; font-size: 0.85rem;
    border-radius: 12px; text-decoration: none;
    box-shadow: 0 4px 18px rgba(255,107,53,0.32);
    transition: transform 0.2s, box-shadow 0.2s;
}
.btn-cta:hover { transform: translateY(-2px); box-shadow: 0 8px 26px rgba(255,107,53,0.42); }
</style>
@endsection

@section('content')
<div style="font-family:'Nunito',sans-serif;color:#1A2744;max-width:1100px;">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="dash-fade" style="animation-delay:0ms;margin-bottom:16px;padding:14px 18px;border-radius:12px;font-size:0.875rem;font-weight:600;background:#F0FDF4;border:1px solid #86EFAC;color:#15803D;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="dash-fade" style="animation-delay:0ms;margin-bottom:16px;padding:14px 18px;border-radius:12px;font-size:0.875rem;font-weight:600;background:#FEF2F2;border:1px solid #FCA5A5;color:#991B1B;">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="dash-fade" style="animation-delay:0ms;margin-bottom:28px;">
        <h1 style="font-size:1.8rem;font-weight:900;color:#1A2744;letter-spacing:-0.025em;margin:0 0 5px 0;line-height:1.15;">Mes favoris</h1>
        <p style="font-size:0.875rem;color:#8A8278;font-weight:500;margin:0;">
            {{ $favorites->count() }} talent{{ $favorites->count() > 1 ? 's' : '' }} enregistré{{ $favorites->count() > 1 ? 's' : '' }}
        </p>
    </div>

    @if($favorites->isEmpty())
        {{-- Empty state --}}
        <div class="dash-fade" style="animation-delay:80ms;background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:72px 24px;text-align:center;">
            <div style="width:80px;height:80px;border-radius:24px;background:#FFF0E8;border:2px solid #FFCAAD;display:flex;align-items:center;justify-content:center;margin-bottom:22px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" fill="#FF6B35" viewBox="0 0 24 24">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" opacity="0.4"/>
                </svg>
            </div>
            <p style="font-size:1.05rem;font-weight:800;color:#1A2744;margin:0 0 8px 0;">Aucun talent en favori</p>
            <p style="font-size:0.875rem;color:#8A8278;margin:0 0 28px 0;max-width:280px;line-height:1.6;">Explorez les talents et cliquez sur ❤ pour les sauvegarder ici.</p>
            <a href="{{ route('talents.index') }}" class="btn-cta">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                Découvrir les talents
            </a>
        </div>
    @else
        <div class="dash-fade" style="animation-delay:80ms;display:grid;grid-template-columns:repeat(1,1fr);gap:20px;">
            <style>
                @media(min-width:640px){ .fav-grid{ grid-template-columns:repeat(2,1fr)!important; } }
                @media(min-width:1024px){ .fav-grid{ grid-template-columns:repeat(3,1fr)!important; } }
            </style>
            <div class="fav-grid" style="display:grid;grid-template-columns:repeat(1,1fr);gap:20px;grid-column:1/-1;">
                @foreach($favorites as $i => $talent)
                @php
                    $tName = $talent->stage_name ?? trim(($talent->user->first_name ?? '') . ' ' . ($talent->user->last_name ?? '')) ?: '?';
                    $catColors = ['DJ' => '#5865F2','Musicien' => '#9B59B6','Danseur' => '#E91E63','Photographe' => '#00BCD4','Vidéaste' => '#FF5722','Animateur' => '#FF6B35','Chanteur' => '#16A34A'];
                    $catName  = $talent->category->name ?? '';
                    $catColor = $catColors[$catName] ?? '#FF6B35';
                @endphp
                <div class="fav-card" style="animation-delay:{{ min($i * 80, 400) }}ms;">

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

                        {{-- Category badge --}}
                        @if($catName)
                        <span style="position:absolute;bottom:10px;left:12px;font-size:0.68rem;font-weight:800;padding:4px 10px;border-radius:9999px;background:rgba(255,255,255,0.18);color:#fff;border:1px solid rgba(255,255,255,0.35);backdrop-filter:blur(6px);letter-spacing:0.03em;">
                            {{ $catName }}
                        </span>
                        @endif

                        {{-- Remove button --}}
                        <form action="{{ route('client.favorites.destroy', $talent->id) }}" method="POST"
                              onsubmit="return confirm('Retirer ce talent de vos favoris ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="fav-remove">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                </svg>
                            </button>
                        </form>
                    </div>

                    {{-- Info --}}
                    <div style="padding:18px 20px 20px;">
                        <h3 style="font-weight:900;font-size:0.95rem;color:#1A2744;margin:0 0 6px 0;">{{ $tName }}</h3>
                        @if($talent->bio)
                            <p style="font-size:0.8rem;line-height:1.55;color:#8A8278;margin:0 0 16px 0;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $talent->bio }}</p>
                        @else
                            <div style="margin-bottom:16px;"></div>
                        @endif
                        <a href="{{ route('talent.show', $talent->slug ?? $talent->id) }}"
                           style="display:block;width:100%;text-align:center;padding:11px 16px;border-radius:12px;font-size:0.82rem;font-weight:800;color:#fff;text-decoration:none;background:linear-gradient(135deg,#1A2744,#2563EB);box-shadow:0 3px 12px rgba(26,39,68,0.22);transition:transform 0.2s,box-shadow 0.2s;"
                           onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 18px rgba(26,39,68,0.30)'"
                           onmouseout="this.style.transform='';this.style.boxShadow='0 3px 12px rgba(26,39,68,0.22)'">
                            Voir le profil →
                        </a>
                    </div>

                </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
