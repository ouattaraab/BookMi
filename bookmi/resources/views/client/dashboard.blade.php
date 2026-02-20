@extends('layouts.client')

@section('title', 'Tableau de bord — BookMi Client')

@section('head')
<style>
/* ── Override background: ivoire chaud au lieu du noir ── */
main.page-content {
    background: #F2EFE9 !important;
}

/* ── Animations page ── */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(22px); }
    to   { opacity: 1; transform: translateY(0); }
}
.dash-fade {
    opacity: 0;
    animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

/* ── Stat cards ── */
.stat-card-l {
    background: #FFFFFF;
    border-radius: 18px;
    padding: 24px 20px 20px;
    border: 1px solid #E5E1DA;
    box-shadow: 0 2px 12px rgba(26,39,68,0.06), 0 1px 3px rgba(26,39,68,0.04);
    position: relative;
    overflow: hidden;
    transition: transform 0.25s cubic-bezier(0.16,1,0.3,1), box-shadow 0.25s;
    cursor: default;
}
.stat-card-l:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 32px rgba(26,39,68,0.12);
}

/* ── Section card ── */
.section-card-l {
    background: #FFFFFF;
    border-radius: 18px;
    border: 1px solid #E5E1DA;
    box-shadow: 0 2px 12px rgba(26,39,68,0.06);
    overflow: hidden;
}

/* ── Booking rows ── */
.booking-row-l {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 18px;
    border-radius: 12px;
    background: #FAF9F6;
    border: 1px solid #EAE7E0;
    text-decoration: none;
    transition: background 0.18s, border-color 0.18s, transform 0.22s, box-shadow 0.22s;
}
.booking-row-l:hover {
    background: #FFFFFF;
    border-color: #FF6B35;
    transform: translateX(5px);
    box-shadow: 0 4px 18px rgba(255,107,53,0.10);
}

/* ── CTA Button ── */
.btn-cta {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 22px;
    background: linear-gradient(135deg, #FF6B35 0%, #f0520f 100%);
    color: #fff;
    font-weight: 800;
    font-size: 0.85rem;
    border-radius: 12px;
    text-decoration: none;
    box-shadow: 0 4px 18px rgba(255,107,53,0.32);
    transition: transform 0.2s, box-shadow 0.2s;
    letter-spacing: 0.01em;
}
.btn-cta:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 26px rgba(255,107,53,0.42);
}
.btn-cta:active { transform: translateY(0); }

/* ── "Voir tout" pill ── */
.btn-ghost-orange {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    font-weight: 700;
    color: #FF6B35;
    background: #FFF0E8;
    border: 1px solid #FFCAAD;
    padding: 7px 16px;
    border-radius: 9px;
    text-decoration: none;
    transition: background 0.15s, border-color 0.15s;
}
.btn-ghost-orange:hover {
    background: #FFE4D2;
    border-color: #FF6B35;
}

/* ── Découvrir button dans le header ── */
.btn-discover {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 10px 20px;
    background: #1A2744;
    color: #fff;
    font-weight: 700;
    font-size: 0.83rem;
    border-radius: 12px;
    text-decoration: none;
    box-shadow: 0 3px 12px rgba(26,39,68,0.20);
    transition: transform 0.2s, box-shadow 0.2s, background 0.15s;
    letter-spacing: 0.01em;
}
.btn-discover:hover {
    background: #243559;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(26,39,68,0.28);
}
</style>
@endsection

@section('content')
<div style="font-family:'Nunito',sans-serif;color:#1A2744;max-width:1100px;">

    {{-- ── Header ── --}}
    <div class="dash-fade" style="animation-delay:0ms;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:32px;">
        <div>
            <h1 style="font-size:1.8rem;font-weight:900;color:#1A2744;letter-spacing:-0.025em;margin:0 0 5px 0;line-height:1.15;">Tableau de bord</h1>
            <p style="font-size:0.875rem;color:#8A8278;font-weight:500;margin:0;">
                Bonjour,&nbsp;<span style="color:#FF6B35;font-weight:800;">{{ auth()->user()->first_name }}</span>&nbsp;— voici votre activité
            </p>
        </div>
        <a href="{{ route('talents.index') }}" class="btn-discover hidden md:inline-flex">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            Découvrir les talents
        </a>
    </div>

    {{-- ── Stats grid ── --}}
    @php
    $statDefs = [
        [
            'label'  => 'Total réservations',
            'value'  => $stats['total'],
            'color'  => '#2563EB',
            'bg'     => '#EFF6FF',
            'path'   => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        ],
        [
            'label'  => 'En attente',
            'value'  => $stats['pending'],
            'color'  => '#D97706',
            'bg'     => '#FFFBEB',
            'path'   => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        [
            'label'  => 'Confirmées',
            'value'  => $stats['confirmed'],
            'color'  => '#16A34A',
            'bg'     => '#F0FDF4',
            'path'   => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        [
            'label'  => 'Terminées',
            'value'  => $stats['completed'],
            'color'  => '#7C3AED',
            'bg'     => '#F5F3FF',
            'path'   => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
        ],
    ];
    @endphp

    <div class="dash-fade" style="animation-delay:90ms;display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:28px;">
        <style>@media(min-width:768px){.stats-four{grid-template-columns:repeat(4,1fr)!important;}}</style>
        <div class="stats-four" style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px;grid-column:1/-1;">
            @foreach($statDefs as $i => $stat)
            <div class="stat-card-l" style="animation-delay:{{ $i * 70 }}ms;">
                {{-- Top accent bar --}}
                <div style="position:absolute;top:0;left:0;right:0;height:3px;background:{{ $stat['color'] }};border-radius:18px 18px 0 0;"></div>

                {{-- Icon --}}
                <div style="width:42px;height:42px;border-radius:12px;background:{{ $stat['bg'] }};display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="{{ $stat['color'] }}" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="{{ $stat['path'] }}"/>
                    </svg>
                </div>

                {{-- Animated counter --}}
                <div
                    x-data="{ count: 0, target: {{ (int) $stat['value'] }} }"
                    x-init="
                        $nextTick(() => {
                            if (target === 0) { count = 0; return; }
                            let v = 0;
                            const step = () => {
                                v += Math.ceil(target / 18);
                                count = Math.min(v, target);
                                if (count < target) requestAnimationFrame(step);
                            };
                            requestAnimationFrame(step);
                        })
                    "
                >
                    <p style="font-size:2.2rem;font-weight:900;color:{{ $stat['color'] }};margin:0 0 6px 0;line-height:1;" x-text="count">{{ $stat['value'] }}</p>
                </div>
                <p style="font-size:0.75rem;font-weight:700;color:#8A8278;margin:0;letter-spacing:0.02em;text-transform:uppercase;">{{ $stat['label'] }}</p>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── Dernières réservations ── --}}
    <div class="dash-fade section-card-l" style="animation-delay:200ms;">

        {{-- Card header --}}
        <div style="padding:20px 24px 18px;border-bottom:1px solid #EAE7E0;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div>
                <h2 style="font-size:1rem;font-weight:900;color:#1A2744;margin:0 0 3px 0;letter-spacing:-0.01em;">Dernières réservations</h2>
                <p style="font-size:0.75rem;color:#8A8278;margin:0;font-weight:500;">Suivi de vos demandes de talents</p>
            </div>
            <a href="{{ route('client.bookings') }}" class="btn-ghost-orange">
                Voir tout
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
        </div>

        @if($bookings->isEmpty())
            {{-- Empty state --}}
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:72px 24px;text-align:center;">
                <div style="width:76px;height:76px;border-radius:22px;background:#FFF0E8;border:2px solid #FFCAAD;display:flex;align-items:center;justify-content:center;margin-bottom:22px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#FF6B35" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <p style="font-size:1.05rem;font-weight:800;color:#1A2744;margin:0 0 8px 0;">Aucune réservation pour l'instant</p>
                <p style="font-size:0.85rem;color:#8A8278;margin:0 0 28px 0;max-width:280px;line-height:1.6;">Explorez les talents disponibles et faites votre première réservation !</p>
                <a href="{{ route('talents.index') }}" class="btn-cta">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    Réserver un talent
                </a>
            </div>
        @else
            <div style="padding:16px;display:flex;flex-direction:column;gap:10px;">
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
                    $talentName = $booking->talentProfile?->stage_name
                        ?? trim(($booking->talentProfile?->user->first_name ?? '') . ' ' . ($booking->talentProfile?->user->last_name ?? ''))
                        ?: '?';
                    $initial = strtoupper(substr($talentName, 0, 1));
                @endphp
                <a href="{{ route('client.bookings.show', $booking->id) }}" class="booking-row-l">
                    {{-- Avatar --}}
                    <div style="width:46px;height:46px;border-radius:13px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1rem;color:#fff;background:linear-gradient(135deg,{{ $ss['border'] }} 0%,{{ $ss['text'] }} 100%);">
                        {{ $initial }}
                    </div>

                    {{-- Info --}}
                    <div style="flex:1;min-width:0;">
                        <p style="font-weight:800;font-size:0.9rem;color:#1A2744;margin:0 0 3px 0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $talentName }}</p>
                        <p style="font-size:0.75rem;color:#8A8278;margin:0;font-weight:500;">
                            {{ $booking->event_date?->translatedFormat('d M Y') ?? $booking->event_date?->format('d/m/Y') ?? '—' }}
                        </p>
                    </div>

                    {{-- Badge statut --}}
                    <span style="font-size:0.7rem;font-weight:800;padding:5px 13px;border-radius:9999px;background:{{ $ss['bg'] }};color:{{ $ss['text'] }};border:1.5px solid {{ $ss['border'] }};white-space:nowrap;letter-spacing:0.03em;flex-shrink:0;">
                        {{ $ss['label'] }}
                    </span>

                    {{-- Chevron --}}
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#C8C3BC" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M9 5l7 7-7 7"/></svg>
                </a>
                @endforeach
            </div>
        @endif

    </div>

</div>
@endsection
