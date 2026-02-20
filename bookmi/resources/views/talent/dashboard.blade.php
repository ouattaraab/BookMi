@extends('layouts.talent')

@section('title', 'Dashboard — BookMi Talent')

@section('head')
<style>
main.page-content { background: #F2EFE9 !important; }

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.dash-fade {
    opacity: 0;
    animation: fadeUp 0.55s cubic-bezier(0.16,1,0.3,1) forwards;
}

/* ── Stat cards ── */
.stat-card {
    background: #FFFFFF;
    border-radius: 20px;
    border: 1px solid #E5E1DA;
    box-shadow: 0 2px 12px rgba(26,39,68,0.06);
    padding: 24px;
    transition: transform 0.22s ease, box-shadow 0.22s ease;
    position: relative;
    overflow: hidden;
}
.stat-card::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 20px;
    opacity: 0;
    transition: opacity 0.22s;
    pointer-events: none;
}
.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 32px rgba(26,39,68,0.12);
}

/* ── Stats grid : 4 cols desktop, 2 cols mobile ── */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
@media (max-width: 900px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 480px) {
    .stats-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
}

/* ── Booking rows ── */
.booking-row {
    padding: 14px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #F5F3EF;
    transition: background 0.15s;
    gap: 12px;
}
.booking-row:last-child { border-bottom: none; }
.booking-row:hover { background: #FAFAF8; }

/* ── Icon badge ── */
.icon-badge {
    width: 44px;
    height: 44px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-bottom: 16px;
    border: 1.5px solid transparent;
}
</style>
@endsection

@section('content')
<div style="font-family:'Nunito',sans-serif;color:#1A2744;">

    {{-- ── Header ── --}}
    <div class="dash-fade" style="animation-delay:0ms;margin-bottom:28px;">
        <h1 style="font-size:1.85rem;font-weight:900;color:#1A2744;letter-spacing:-0.03em;margin:0 0 5px;line-height:1.15;">
            Tableau de bord
        </h1>
        <p style="font-size:0.875rem;color:#8A8278;font-weight:500;margin:0;">
            Vue d'ensemble de votre activité talent
        </p>
    </div>

    {{-- ── Bannière profil incomplet ── --}}
    @if(! $profile)
    <div class="dash-fade" style="animation-delay:40ms;display:flex;align-items:center;gap:16px;padding:18px 22px;border-radius:16px;background:#FFF4EF;border:1.5px solid rgba(255,107,53,0.35);margin-bottom:24px;box-shadow:0 2px 12px rgba(255,107,53,0.08);">
        <div style="width:44px;height:44px;border-radius:13px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:#FF6B35;box-shadow:0 4px 12px rgba(255,107,53,0.35);">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>
            </svg>
        </div>
        <div style="flex:1;min-width:0;">
            <p style="font-weight:800;font-size:0.9rem;color:#7C2D12;margin:0 0 3px;">Profil incomplet</p>
            <p style="font-size:0.8rem;font-weight:500;color:#9A3412;margin:0;line-height:1.4;">
                Configurez votre profil pour apparaître dans les résultats de recherche.
            </p>
        </div>
        <a href="{{ route('talent.profile') }}"
           style="flex-shrink:0;padding:10px 20px;border-radius:12px;font-size:0.82rem;font-weight:800;color:#fff;background:#FF6B35;text-decoration:none;transition:opacity 0.2s, transform 0.2s;white-space:nowrap;box-shadow:0 4px 12px rgba(255,107,53,0.30);"
           onmouseover="this.style.opacity='0.88';this.style.transform='translateY(-1px)'"
           onmouseout="this.style.opacity='1';this.style.transform='translateY(0)'">
            Configurer →
        </a>
    </div>
    @endif

    {{-- ── Stats (4 colonnes) ── --}}
    <div class="dash-fade stats-grid" style="animation-delay:80ms;">

        {{-- Total réservations --}}
        <div class="stat-card">
            <div class="icon-badge" style="background:#FFF4EF;border-color:rgba(255,107,53,0.25);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="#FF6B35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div x-data="{ count: 0, target: {{ (int)($stats['total'] ?? 0) }} }"
                 x-init="let s=0;const f=()=>{s+=Math.ceil(target/20);count=Math.min(s,target);if(count<target)requestAnimationFrame(f)};requestAnimationFrame(f)">
                <p style="font-size:2rem;font-weight:900;color:#FF6B35;margin:0 0 5px;letter-spacing:-0.04em;line-height:1;">
                    <span x-text="count">{{ $stats['total'] ?? 0 }}</span>
                </p>
            </div>
            <p style="font-size:0.72rem;font-weight:700;color:#8A8278;margin:0;text-transform:uppercase;letter-spacing:0.05em;">Total réservations</p>
        </div>

        {{-- En attente --}}
        <div class="stat-card">
            <div class="icon-badge" style="background:#FFF3E0;border-color:rgba(180,83,9,0.20);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="#B45309" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div x-data="{ count: 0, target: {{ (int)($stats['pending'] ?? 0) }} }"
                 x-init="let s=0;const f=()=>{s+=Math.ceil(target/20);count=Math.min(s,target);if(count<target)requestAnimationFrame(f)};requestAnimationFrame(f)">
                <p style="font-size:2rem;font-weight:900;color:#B45309;margin:0 0 5px;letter-spacing:-0.04em;line-height:1;">
                    <span x-text="count">{{ $stats['pending'] ?? 0 }}</span>
                </p>
            </div>
            <p style="font-size:0.72rem;font-weight:700;color:#8A8278;margin:0;text-transform:uppercase;letter-spacing:0.05em;">En attente</p>
        </div>

        {{-- Confirmées --}}
        <div class="stat-card">
            <div class="icon-badge" style="background:#F0FDF4;border-color:rgba(21,128,61,0.22);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="#15803D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <div x-data="{ count: 0, target: {{ (int)($stats['confirmed'] ?? 0) }} }"
                 x-init="let s=0;const f=()=>{s+=Math.ceil(target/20);count=Math.min(s,target);if(count<target)requestAnimationFrame(f)};requestAnimationFrame(f)">
                <p style="font-size:2rem;font-weight:900;color:#15803D;margin:0 0 5px;letter-spacing:-0.04em;line-height:1;">
                    <span x-text="count">{{ $stats['confirmed'] ?? 0 }}</span>
                </p>
            </div>
            <p style="font-size:0.72rem;font-weight:700;color:#8A8278;margin:0;text-transform:uppercase;letter-spacing:0.05em;">Confirmées</p>
        </div>

        {{-- Revenus --}}
        <div class="stat-card">
            <div class="icon-badge" style="background:#EFF6FF;border-color:rgba(29,78,216,0.20);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="#1D4ED8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                </svg>
            </div>
            <p style="font-size:2rem;font-weight:900;color:#1D4ED8;margin:0 0 5px;letter-spacing:-0.04em;line-height:1;">
                {{ number_format($stats['revenue'] ?? 0, 0, ',', ' ') }}
            </p>
            <p style="font-size:0.72rem;font-weight:700;color:#8A8278;margin:0;text-transform:uppercase;letter-spacing:0.05em;">Revenus (FCFA)</p>
        </div>

    </div>

    {{-- ── Dernières réservations ── --}}
    <div class="dash-fade" style="animation-delay:220ms;background:#FFFFFF;border-radius:20px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);overflow:hidden;">

        <div style="padding:18px 24px;border-bottom:1px solid #EAE7E0;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:8px;height:8px;border-radius:50%;background:#FF6B35;"></div>
                <h2 style="font-size:1rem;font-weight:900;color:#1A2744;margin:0;letter-spacing:-0.01em;">
                    Dernières réservations
                </h2>
            </div>
            <a href="{{ route('talent.bookings') }}"
               style="font-size:0.8rem;font-weight:700;color:#FF6B35;text-decoration:none;transition:opacity 0.2s;display:flex;align-items:center;gap:4px;"
               onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">
                Voir tout
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
            </a>
        </div>

        @if($bookings->isEmpty())
        <div style="padding:52px 24px;text-align:center;">
            <div style="width:56px;height:56px;border-radius:16px;background:#FFF4EF;border:1.5px solid rgba(255,107,53,0.18);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#FF6B35" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <p style="font-size:0.9rem;font-weight:700;color:#8A8278;margin:0 0 4px;">Aucune réservation pour l'instant</p>
            <p style="font-size:0.78rem;font-weight:500;color:#B0A89E;margin:0;">Les demandes de vos clients apparaîtront ici.</p>
        </div>
        @else
        <div>
            @foreach($bookings as $booking)
            @php
                $sk = $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status;
                $statusMap = [
                    'pending'   => ['bg'=>'#FFF3E0','text'=>'#B45309','border'=>'#FCD34D','label'=>'En attente'],
                    'accepted'  => ['bg'=>'#EFF6FF','text'=>'#1D4ED8','border'=>'#93C5FD','label'=>'Acceptée'],
                    'paid'      => ['bg'=>'#ECFDF5','text'=>'#065F46','border'=>'#6EE7B7','label'=>'Payée'],
                    'confirmed' => ['bg'=>'#F0FDF4','text'=>'#15803D','border'=>'#86EFAC','label'=>'Confirmée'],
                    'completed' => ['bg'=>'#F5F3FF','text'=>'#5B21B6','border'=>'#C4B5FD','label'=>'Terminée'],
                    'cancelled' => ['bg'=>'#F9FAFB','text'=>'#4B5563','border'=>'#D1D5DB','label'=>'Annulée'],
                    'disputed'  => ['bg'=>'#FEF2F2','text'=>'#991B1B','border'=>'#FCA5A5','label'=>'En litige'],
                ];
                $ss = $statusMap[$sk] ?? ['bg'=>'#F3F4F6','text'=>'#6B7280','border'=>'#E5E7EB','label'=>$sk];
            @endphp
            <div class="booking-row">
                <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                    <div style="width:38px;height:38px;border-radius:11px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:0.88rem;color:#fff;background:linear-gradient(135deg,#1A2744 0%,#2D4A8A 100%);letter-spacing:0.01em;">
                        {{ strtoupper(substr($booking->client->first_name ?? '?', 0, 1)) }}
                    </div>
                    <div style="min-width:0;">
                        <p style="font-size:0.875rem;font-weight:800;color:#1A2744;margin:0 0 2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $booking->client->first_name }} {{ $booking->client->last_name }}
                        </p>
                        <p style="font-size:0.74rem;color:#8A8278;font-weight:500;margin:0;">
                            {{ $booking->event_date?->translatedFormat('d M Y') ?? '—' }}
                        </p>
                    </div>
                </div>
                <span style="font-size:0.7rem;font-weight:800;padding:4px 13px;border-radius:9999px;background:{{ $ss['bg'] }};color:{{ $ss['text'] }};border:1.5px solid {{ $ss['border'] }};letter-spacing:0.03em;flex-shrink:0;white-space:nowrap;">
                    {{ $ss['label'] }}
                </span>
            </div>
            @endforeach
        </div>
        @endif
    </div>

</div>
@endsection
