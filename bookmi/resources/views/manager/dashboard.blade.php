@extends('layouts.manager')

@section('title', 'Dashboard Manager — BookMi')

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
.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 32px rgba(26,39,68,0.12);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
@media (max-width: 900px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}

.shortcut-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}
@media (max-width: 700px) {
    .shortcut-grid { grid-template-columns: 1fr; }
}

.shortcut-card {
    background: #FFFFFF;
    border-radius: 20px;
    border: 1px solid #E5E1DA;
    box-shadow: 0 2px 12px rgba(26,39,68,0.06);
    padding: 22px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    text-decoration: none;
    transition: transform 0.22s ease, box-shadow 0.22s ease;
}
.shortcut-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 28px rgba(26,39,68,0.10);
}

.icon-badge {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
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
            Vue d'ensemble de vos talents gérés
        </p>
    </div>

    {{-- ── Stats (4 colonnes) ── --}}
    <div class="dash-fade stats-grid" style="animation-delay:60ms;">

        {{-- Talents gérés --}}
        <div class="stat-card">
            <div class="icon-badge" style="background:#EFF6FF;border-color:rgba(29,78,216,0.20);margin-bottom:16px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="#1D4ED8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div x-data="{ count: 0, target: {{ (int)($stats['talents'] ?? 0) }} }"
                 x-init="let s=0;const f=()=>{s+=Math.ceil(target/20);count=Math.min(s,target);if(count<target)requestAnimationFrame(f)};requestAnimationFrame(f)">
                <p style="font-size:2rem;font-weight:900;color:#1D4ED8;margin:0 0 5px;letter-spacing:-0.04em;line-height:1;">
                    <span x-text="count">{{ $stats['talents'] ?? 0 }}</span>
                </p>
            </div>
            <p style="font-size:0.72rem;font-weight:700;color:#8A8278;margin:0;text-transform:uppercase;letter-spacing:0.05em;">Talents gérés</p>
        </div>

        {{-- Total réservations --}}
        <div class="stat-card">
            <div class="icon-badge" style="background:#FFF4EF;border-color:rgba(255,107,53,0.25);margin-bottom:16px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="#FF6B35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div x-data="{ count: 0, target: {{ (int)($stats['bookings'] ?? 0) }} }"
                 x-init="let s=0;const f=()=>{s+=Math.ceil(target/20);count=Math.min(s,target);if(count<target)requestAnimationFrame(f)};requestAnimationFrame(f)">
                <p style="font-size:2rem;font-weight:900;color:#FF6B35;margin:0 0 5px;letter-spacing:-0.04em;line-height:1;">
                    <span x-text="count">{{ $stats['bookings'] ?? 0 }}</span>
                </p>
            </div>
            <p style="font-size:0.72rem;font-weight:700;color:#8A8278;margin:0;text-transform:uppercase;letter-spacing:0.05em;">Total réservations</p>
        </div>

        {{-- En attente --}}
        <div class="stat-card">
            <div class="icon-badge" style="background:#FFF3E0;border-color:rgba(180,83,9,0.20);margin-bottom:16px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="#B45309" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
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

        {{-- Terminées --}}
        <div class="stat-card">
            <div class="icon-badge" style="background:#F0FDF4;border-color:rgba(21,128,61,0.22);margin-bottom:16px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="#15803D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <div x-data="{ count: 0, target: {{ (int)($stats['completed'] ?? 0) }} }"
                 x-init="let s=0;const f=()=>{s+=Math.ceil(target/20);count=Math.min(s,target);if(count<target)requestAnimationFrame(f)};requestAnimationFrame(f)">
                <p style="font-size:2rem;font-weight:900;color:#15803D;margin:0 0 5px;letter-spacing:-0.04em;line-height:1;">
                    <span x-text="count">{{ $stats['completed'] ?? 0 }}</span>
                </p>
            </div>
            <p style="font-size:0.72rem;font-weight:700;color:#8A8278;margin:0;text-transform:uppercase;letter-spacing:0.05em;">Terminées</p>
        </div>

    </div>

    {{-- ── Raccourcis ── --}}
    <div class="dash-fade shortcut-grid" style="animation-delay:180ms;">

        <a href="{{ route('manager.talents') }}" class="shortcut-card">
            <div class="icon-badge" style="background:#EFF6FF;border-color:rgba(29,78,216,0.18);">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="#1D4ED8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div>
                <p style="font-size:0.9rem;font-weight:800;color:#1A2744;margin:0 0 3px;">Mes talents</p>
                <p style="font-size:0.75rem;color:#8A8278;font-weight:500;margin:0;">{{ $stats['talents'] ?? 0 }} talent(s)</p>
            </div>
            <svg style="margin-left:auto;flex-shrink:0;" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="#B0A89E" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
        </a>

        <a href="{{ route('manager.bookings') }}" class="shortcut-card">
            <div class="icon-badge" style="background:#FFF4EF;border-color:rgba(255,107,53,0.22);">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="#FF6B35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                </svg>
            </div>
            <div>
                <p style="font-size:0.9rem;font-weight:800;color:#1A2744;margin:0 0 3px;">Réservations</p>
                <p style="font-size:0.75rem;color:#8A8278;font-weight:500;margin:0;">{{ $stats['bookings'] ?? 0 }} total</p>
            </div>
            <svg style="margin-left:auto;flex-shrink:0;" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="#B0A89E" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
        </a>

        <a href="{{ route('manager.messages') }}" class="shortcut-card">
            <div class="icon-badge" style="background:#F0FDF4;border-color:rgba(21,128,61,0.20);">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="#15803D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </div>
            <div>
                <p style="font-size:0.9rem;font-weight:800;color:#1A2744;margin:0 0 3px;">Messages</p>
                <p style="font-size:0.75rem;color:#8A8278;font-weight:500;margin:0;">Messagerie</p>
            </div>
            <svg style="margin-left:auto;flex-shrink:0;" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="#B0A89E" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
        </a>

    </div>

</div>
@endsection
