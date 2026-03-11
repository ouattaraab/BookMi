@extends('layouts.client')

@section('title', 'Mes Analytiques — BookMi Client')

@section('head')
<style>
main.page-content { background: #F2EFE9 !important; }

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(22px); }
    to   { opacity: 1; transform: translateY(0); }
}
.dash-fade { opacity: 0; animation: fadeUp 0.6s cubic-bezier(0.16,1,0.3,1) forwards; }

.kpi-card {
    background: #FFFFFF;
    border-radius: 18px;
    border: 1px solid #E5E1DA;
    box-shadow: 0 2px 12px rgba(26,39,68,0.06);
    padding: 20px 24px;
}
.kpi-label {
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #B0A89E;
    margin: 0 0 8px;
}
.kpi-value {
    font-size: 1.7rem;
    font-weight: 900;
    color: #1A2744;
    margin: 0;
    line-height: 1.1;
}
.kpi-sub {
    font-size: 0.75rem;
    font-weight: 600;
    color: #8A8278;
    margin: 4px 0 0;
}

.bar-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}
.bar-label {
    font-size: 0.78rem;
    font-weight: 700;
    color: #6B7280;
    width: 90px;
    flex-shrink: 0;
}
.bar-track {
    flex: 1;
    height: 10px;
    background: #EAE7E0;
    border-radius: 9999px;
    overflow: hidden;
}
.bar-fill {
    height: 100%;
    border-radius: 9999px;
    transition: width 0.6s ease;
}
.bar-count {
    font-size: 0.75rem;
    font-weight: 800;
    color: #1A2744;
    min-width: 28px;
    text-align: right;
    flex-shrink: 0;
}
</style>
@endsection

@section('content')
@php
    $monthNames = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
    $statusLabels = [
        'pending'   => ['label' => 'En attente', 'color' => '#B45309', 'bg' => '#FFF3E0'],
        'accepted'  => ['label' => 'Acceptées',  'color' => '#1D4ED8', 'bg' => '#EFF6FF'],
        'paid'      => ['label' => 'Payées',      'color' => '#065F46', 'bg' => '#ECFDF5'],
        'confirmed' => ['label' => 'Confirmées',  'color' => '#15803D', 'bg' => '#F0FDF4'],
        'completed' => ['label' => 'Terminées',   'color' => '#5B21B6', 'bg' => '#F5F3FF'],
        'cancelled' => ['label' => 'Annulées',    'color' => '#4B5563', 'bg' => '#F9FAFB'],
        'disputed'  => ['label' => 'En litige',   'color' => '#991B1B', 'bg' => '#FEF2F2'],
        'rejected'  => ['label' => 'Rejetées',    'color' => '#9A3412', 'bg' => '#FFF7ED'],
    ];
    $topCatMax = $topCategories->max('count') ?: 1;
    $byMonthMax = $byMonth->max('count') ?: 1;
@endphp

<div style="font-family:'Nunito',sans-serif;color:#1A2744;width:100%;">

    {{-- Header --}}
    <div class="dash-fade" style="animation-delay:0ms;margin-bottom:28px;">
        <h1 style="font-size:1.8rem;font-weight:900;color:#1A2744;letter-spacing:-0.025em;margin:0 0 5px;line-height:1.15;">Mes Analytiques</h1>
        <p style="font-size:0.875rem;color:#8A8278;font-weight:500;margin:0;">Vue d'ensemble de votre activité sur BookMi</p>
    </div>

    {{-- ── KPIs ── --}}
    <div class="dash-fade" style="animation-delay:60ms;display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;margin-bottom:24px;">
        {{-- Total réservations --}}
        <div class="kpi-card">
            <p class="kpi-label">Total réservations</p>
            <p class="kpi-value">{{ $totalBookings }}</p>
            <p class="kpi-sub">toutes périodes</p>
        </div>
        {{-- Complétées --}}
        <div class="kpi-card">
            <p class="kpi-label">Prestations terminées</p>
            <p class="kpi-value" style="color:#15803D;">{{ $completedBookings }}</p>
            <p class="kpi-sub">
                @if($totalBookings > 0)
                    {{ number_format($completedBookings / $totalBookings * 100, 0) }}% de taux de réalisation
                @else
                    —
                @endif
            </p>
        </div>
        {{-- Total dépensé --}}
        <div class="kpi-card">
            <p class="kpi-label">Total dépensé</p>
            <p class="kpi-value" style="color:#FF6B35;">{{ number_format($totalSpent, 0, ',', '\u202f') }}</p>
            <p class="kpi-sub">XOF (payé/confirmé/terminé)</p>
        </div>
        {{-- Avis donnés --}}
        <div class="kpi-card">
            <p class="kpi-label">Avis donnés</p>
            <p class="kpi-value" style="color:#7C3AED;">{{ $reviewsGiven }}</p>
            <p class="kpi-sub">
                @if($avgRatingGiven > 0)
                    Note moyenne : {{ number_format($avgRatingGiven, 1) }}/5
                @else
                    aucun avis encore
                @endif
            </p>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">

        {{-- ── Colonne principale ── --}}
        <div>

            {{-- Réservations par mois --}}
            <div class="dash-fade" style="animation-delay:120ms;background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);padding:22px 24px;margin-bottom:20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px;flex-wrap:wrap;">
                    <h3 style="font-size:0.9rem;font-weight:900;color:#1A2744;margin:0;">Réservations par mois</h3>
                    {{-- Sélecteur d'année --}}
                    <form method="GET" action="{{ route('client.analytics') }}" style="display:flex;align-items:center;gap:8px;">
                        <select name="year" onchange="this.form.submit()"
                                style="padding:7px 12px;border-radius:10px;border:1.5px solid #E5E1DA;font-size:0.8rem;font-weight:700;color:#1A2744;background:#FDFCFA;font-family:'Nunito',sans-serif;cursor:pointer;">
                            @foreach($availableYears as $y)
                                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>

                @if($byMonth->isEmpty())
                    <p style="font-size:0.875rem;color:#B0A89E;font-weight:600;text-align:center;padding:20px 0;">Aucune réservation payée en {{ $year }}.</p>
                @else
                    <div style="display:flex;flex-direction:column;gap:6px;">
                        @for($m = 1; $m <= 12; $m++)
                            @php
                                $mData  = $byMonth->get($m);
                                $mCount = $mData ? (int) $mData->count : 0;
                                $mAmt   = $mData ? (int) $mData->amount : 0;
                                $pct    = $byMonthMax > 0 ? round($mCount / $byMonthMax * 100) : 0;
                            @endphp
                            <div class="bar-row">
                                <span class="bar-label">{{ $monthNames[$m-1] }}</span>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width:{{ $pct }}%;background:{{ $pct > 0 ? 'linear-gradient(90deg,#FF6B35,#C85A20)' : '#EAE7E0' }};"></div>
                                </div>
                                <span class="bar-count">{{ $mCount }}</span>
                                @if($mAmt > 0)
                                <span style="font-size:0.7rem;font-weight:600;color:#8A8278;min-width:80px;text-align:right;">{{ number_format($mAmt, 0, ',', ' ') }} XOF</span>
                                @endif
                            </div>
                        @endfor
                    </div>
                @endif
            </div>

            {{-- Catégories favorites --}}
            @if($topCategories->isNotEmpty())
            <div class="dash-fade" style="animation-delay:160ms;background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);padding:22px 24px;margin-bottom:20px;">
                <h3 style="font-size:0.9rem;font-weight:900;color:#1A2744;margin:0 0 18px;">Catégories favorites</h3>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    @foreach($topCategories as $cat)
                    @php $pct = round($cat->count / $topCatMax * 100); @endphp
                    <div class="bar-row">
                        <span class="bar-label" style="width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $cat->category }}">{{ $cat->category }}</span>
                        <div class="bar-track">
                            <div class="bar-fill" style="width:{{ $pct }}%;background:linear-gradient(90deg,#2563EB,#1D4ED8);"></div>
                        </div>
                        <span class="bar-count">{{ $cat->count }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>

        {{-- ── Sidebar statuts ── --}}
        <div>
            <div class="dash-fade" style="animation-delay:140ms;background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);padding:22px 24px;">
                <h3 style="font-size:0.9rem;font-weight:900;color:#1A2744;margin:0 0 16px;">Réservations par statut</h3>
                @if($byStatus->isEmpty())
                    <p style="font-size:0.875rem;color:#B0A89E;font-weight:600;text-align:center;padding:12px 0;">Aucune réservation.</p>
                @else
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        @foreach($byStatus as $row)
                        @php
                            $sv = $row->status instanceof \BackedEnum ? $row->status->value : (string) $row->status;
                            $sl = $statusLabels[$sv] ?? ['label' => ucfirst($sv), 'color' => '#6B7280', 'bg' => '#F3F4F6'];
                        @endphp
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border-radius:12px;background:{{ $sl['bg'] }};border:1px solid rgba(0,0,0,0.04);">
                            <span style="font-size:0.82rem;font-weight:700;color:{{ $sl['color'] }};">{{ $sl['label'] }}</span>
                            <span style="font-size:0.9rem;font-weight:900;color:{{ $sl['color'] }};">{{ $row->count }}</span>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Résumé avis --}}
            @if($reviewsGiven > 0)
            <div class="dash-fade" style="animation-delay:150ms;background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);padding:22px 24px;margin-top:14px;">
                <h3 style="font-size:0.9rem;font-weight:900;color:#1A2744;margin:0 0 14px;">Mes avis</h3>
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#FFF3E0,#FFE0B2);border:1.5px solid #FCD34D;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span style="font-size:1.5rem;">⭐</span>
                    </div>
                    <div>
                        <p style="font-size:1.4rem;font-weight:900;color:#B45309;margin:0;line-height:1.1;">{{ number_format($avgRatingGiven, 1) }}<span style="font-size:0.8rem;font-weight:700;color:#D97706;">/5</span></p>
                        <p style="font-size:0.75rem;font-weight:600;color:#8A8278;margin:3px 0 0;">sur {{ $reviewsGiven }} avis donnés</p>
                    </div>
                </div>
            </div>
            @endif
        </div>

    </div>

</div>
@endsection
