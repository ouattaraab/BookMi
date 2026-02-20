@extends('layouts.client')

@section('title', 'Tableau de bord — BookMi Client')

@section('head')
<style>
/* ── Stat cards ── */
.stat-card {
    position: relative;
    overflow: hidden;
    padding: 1.25rem 1.5rem;
    border-radius: 1.125rem;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    transition: transform 0.25s cubic-bezier(0.16,1,0.3,1), box-shadow 0.25s;
}
.stat-card:hover { transform: translateY(-3px); }
.stat-card::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 2px;
    border-radius: 9999px 9999px 0 0;
}
.stat-card .bg-orb {
    position: absolute; top: -20px; right: -20px;
    width: 80px; height: 80px; border-radius: 50%;
    opacity: 0.08; pointer-events: none;
}
/* ── Booking row ── */
.booking-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.875rem 1.25rem;
    border-radius: 0.875rem;
    background: rgba(255,255,255,0.025);
    border: 1px solid rgba(255,255,255,0.06);
    text-decoration: none;
    transition: background 0.15s, border-color 0.15s, transform 0.2s;
}
.booking-row:hover {
    background: rgba(255,255,255,0.055);
    border-color: rgba(100,181,246,0.18);
    transform: translateX(3px);
}
/* ── Reveal animation ── */
.reveal-item {
    opacity: 0;
    transform: translateY(18px);
    transition: opacity 0.5s cubic-bezier(0.16,1,0.3,1), transform 0.5s cubic-bezier(0.16,1,0.3,1);
}
.reveal-item.visible { opacity: 1; transform: none; }
</style>
@endsection

@section('content')
<div class="space-y-7">

    {{-- ── Header ── --}}
    <div class="flex items-start justify-between">
        <div>
            <h1 class="section-title">Tableau de bord</h1>
            <p class="section-sub">Bonjour, <span style="color:var(--blue-light)">{{ auth()->user()->first_name }}</span> — voici votre activité</p>
        </div>
        <a href="{{ route('talents.index') }}"
           class="hidden md:inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold text-white transition-all hover:scale-105 active:scale-95"
           style="background:linear-gradient(135deg,var(--orange) 0%,#ff8c5a 100%);box-shadow:0 4px 16px rgba(255,107,53,0.35)">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            Découvrir
        </a>
    </div>

    {{-- ── Stats ── --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @php
        $statDefs = [
            ['label' => 'Total réservations', 'value' => $stats['total'],     'color' => '#2196F3', 'glow' => 'rgba(33,150,243,0.5)'],
            ['label' => 'En attente',          'value' => $stats['pending'],   'color' => '#FF9800', 'glow' => 'rgba(255,152,0,0.5)'],
            ['label' => 'Confirmées',          'value' => $stats['confirmed'], 'color' => '#4CAF50', 'glow' => 'rgba(76,175,80,0.5)'],
            ['label' => 'Terminées',           'value' => $stats['completed'], 'color' => '#9C27B0', 'glow' => 'rgba(156,39,176,0.5)'],
        ];
        @endphp
        @foreach($statDefs as $i => $stat)
        <div class="stat-card reveal-item" style="--delay:{{ $i * 0.07 }}s; transition-delay:{{ $i * 0.07 }}s; box-shadow:0 0 0 1px transparent">
            <div class="bg-orb" style="background:{{ $stat['color'] }}"></div>
            <div class="stat-card-bar" style="position:absolute;top:0;left:0;right:0;height:2px;background:{{ $stat['color'] }};border-radius:9999px 9999px 0 0;box-shadow:0 0 12px {{ $stat['glow'] }}"></div>
            <p class="text-3xl font-black relative z-10" style="color:{{ $stat['color'] }};text-shadow:0 0 20px {{ $stat['glow'] }}">{{ $stat['value'] }}</p>
            <p class="text-xs font-semibold mt-1 relative z-10" style="color:var(--text-muted)">{{ $stat['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- ── Dernières réservations ── --}}
    <div class="glass-card overflow-hidden reveal-item" style="transition-delay:0.28s">
        <div class="px-5 py-4 flex items-center justify-between" style="border-bottom:1px solid var(--glass-border)">
            <h2 class="font-black text-base" style="color:var(--text)">Dernières réservations</h2>
            <a href="{{ route('client.bookings') }}"
               class="text-xs font-bold px-3 py-1.5 rounded-lg transition-colors"
               style="color:var(--blue-light);background:rgba(100,181,246,0.10);border:1px solid rgba(100,181,246,0.18)">
               Voir tout →
            </a>
        </div>

        @if($bookings->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <div class="w-14 h-14 rounded-2xl mb-4 flex items-center justify-center" style="background:rgba(255,107,53,0.10);border:1px solid rgba(255,107,53,0.2)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#FF6B35" stroke-width="1.5"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <p class="font-semibold text-sm" style="color:var(--text-muted)">Aucune réservation pour l'instant</p>
                <a href="{{ route('talents.index') }}" class="mt-4 text-xs font-bold px-4 py-2 rounded-xl text-white" style="background:var(--orange)">Réserver un talent</a>
            </div>
        @else
            <div class="p-4 space-y-2">
                @foreach($bookings as $booking)
                @php
                    $sk = $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status;
                    $sc = ['pending'=>'#FF9800','accepted'=>'#2196F3','paid'=>'#00BCD4','confirmed'=>'#4CAF50','completed'=>'#9C27B0','cancelled'=>'#f44336','disputed'=>'#FF5722'][$sk] ?? '#6b7280';
                    $sl = ['pending'=>'En attente','accepted'=>'Acceptée','paid'=>'Payée','confirmed'=>'Confirmée','completed'=>'Terminée','cancelled'=>'Annulée','disputed'=>'En litige'][$sk] ?? $sk;
                    $talentName = $booking->talentProfile?->stage_name ?? trim(($booking->talentProfile?->user->first_name ?? '') . ' ' . ($booking->talentProfile?->user->last_name ?? '')) ?: '?';
                @endphp
                <a href="{{ route('client.bookings.show', $booking->id) }}" class="booking-row">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm flex-shrink-0 text-white"
                         style="background:linear-gradient(135deg,{{ $sc }}aa,{{ $sc }}66)">
                        {{ strtoupper(substr($talentName, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm truncate" style="color:var(--text)">{{ $talentName }}</p>
                        <p class="text-xs" style="color:var(--text-muted)">{{ $booking->event_date?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <span class="badge-status" style="background:{{ $sc }}20;color:{{ $sc }};border:1px solid {{ $sc }}40">{{ $sl }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--text-faint)"><path d="M9 5l7 7-7 7"/></svg>
                </a>
                @endforeach
            </div>
        @endif
    </div>

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
