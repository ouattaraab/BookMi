@extends('layouts.talent')

@section('title', 'Statistiques — BookMi Talent')

@section('head')
<style>
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
}
.stat-fade { opacity: 0; animation: fadeUp 0.52s cubic-bezier(0.16,1,0.3,1) forwards; }

.kpi-card {
    background: #FFFFFF;
    border-radius: 20px;
    border: 1px solid #E5E1DA;
    box-shadow: 0 2px 12px rgba(26,39,68,0.06);
    padding: 22px 24px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(26,39,68,0.10); }

.icon-badge {
    width: 42px; height: 42px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; margin-bottom: 14px; border: 1.5px solid transparent;
}
.kpi-value { font-size: 1.85rem; font-weight: 900; line-height: 1; margin: 0 0 4px; letter-spacing: -0.04em; }
.kpi-label { font-size: 0.70rem; font-weight: 700; color: #8A8278; text-transform: uppercase; letter-spacing: 0.06em; margin: 0; }

.section-card {
    background: #FFFFFF;
    border-radius: 20px;
    border: 1px solid #E5E1DA;
    box-shadow: 0 2px 12px rgba(26,39,68,0.06);
    overflow: hidden;
}
.section-header {
    padding: 16px 24px;
    border-bottom: 1px solid #EAE7E0;
    display: flex; align-items: center; gap: 10px;
}
.dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

/* CSS Bar chart */
.chart-bar-wrap { display: flex; align-items: flex-end; gap: 10px; height: 120px; padding: 0 4px; }
.chart-col { display: flex; flex-direction: column; align-items: center; flex: 1; gap: 6px; height: 100%; }
.chart-col-bar-area { flex: 1; width: 100%; display: flex; align-items: flex-end; }
.chart-bar-inner {
    width: 100%; border-radius: 6px 6px 0 0;
    background: linear-gradient(180deg,#FF6B35 0%,#E85A25 100%);
    transition: height 0.6s cubic-bezier(0.34,1.56,0.64,1);
    min-height: 3px;
}
.chart-bar-inner.zero { background: #E5E1DA; min-height: 4px; border-radius: 3px; }
.chart-label { font-size: 0.65rem; font-weight: 700; color: #B0A89E; white-space: nowrap; }
.chart-amount { font-size: 0.62rem; font-weight: 600; color: #FF6B35; white-space: nowrap; }

@media (max-width: 640px) {
    .stats-grid-4 { grid-template-columns: repeat(2,1fr) !important; }
    .stats-grid-2 { grid-template-columns: 1fr !important; }
}
</style>
@endsection

@section('content')
@php
    $monthNames = ['','Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
    $maxRevenu = max(1, collect($financial['mensuels'])->max('revenus'));
@endphp

<div style="font-family:'Nunito',sans-serif;color:#1A2744;max-width:1100px;">

    {{-- Header --}}
    <div class="stat-fade" style="animation-delay:0ms;margin-bottom:28px;">
        <h1 style="font-size:1.85rem;font-weight:900;color:#1A2744;letter-spacing:-0.03em;margin:0 0 5px;line-height:1.15;">Statistiques</h1>
        <p style="font-size:0.875rem;color:#8A8278;font-weight:500;margin:0;">Vues du profil, performances financières et activité</p>
    </div>

    {{-- ── Section 1 : Vues du profil ── --}}
    <div class="stat-fade" style="animation-delay:60ms;margin-bottom:24px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <div class="dot" style="background:#7C3AED;"></div>
            <h2 style="font-size:0.95rem;font-weight:900;color:#1A2744;margin:0;letter-spacing:-0.01em;">Vues du profil</h2>
        </div>
        <div class="stats-grid-4" style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;">
            <div class="kpi-card">
                <div class="icon-badge" style="background:#F5F3FF;border-color:rgba(124,58,237,0.20);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#7C3AED" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </div>
                <p class="kpi-value" style="color:#7C3AED;">{{ number_format($profileViews['today'], 0, ',', ' ') }}</p>
                <p class="kpi-label">Aujourd'hui</p>
            </div>
            <div class="kpi-card">
                <div class="icon-badge" style="background:#EFF6FF;border-color:rgba(29,78,216,0.20);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#1D4ED8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </div>
                <p class="kpi-value" style="color:#1D4ED8;">{{ number_format($profileViews['week'], 0, ',', ' ') }}</p>
                <p class="kpi-label">Cette semaine</p>
            </div>
            <div class="kpi-card">
                <div class="icon-badge" style="background:#F0FDF4;border-color:rgba(21,128,61,0.22);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#15803D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </div>
                <p class="kpi-value" style="color:#15803D;">{{ number_format($profileViews['month'], 0, ',', ' ') }}</p>
                <p class="kpi-label">Ce mois</p>
            </div>
            <div class="kpi-card">
                <div class="icon-badge" style="background:#FFF4EF;border-color:rgba(255,107,53,0.25);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#FF6B35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </div>
                <p class="kpi-value" style="color:#FF6B35;">{{ number_format($profileViews['total'], 0, ',', ' ') }}</p>
                <p class="kpi-label">Total</p>
            </div>
        </div>
    </div>

    {{-- ── Section 2 : Revenus financiers ── --}}
    <div class="stat-fade" style="animation-delay:120ms;margin-bottom:24px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <div class="dot" style="background:#FF6B35;"></div>
            <h2 style="font-size:0.95rem;font-weight:900;color:#1A2744;margin:0;letter-spacing:-0.01em;">Performances financières</h2>
        </div>
        <div class="stats-grid-4" style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;">

            {{-- Revenus du mois --}}
            <div class="kpi-card">
                <div class="icon-badge" style="background:#FFF4EF;border-color:rgba(255,107,53,0.25);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#FF6B35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                </div>
                <p class="kpi-value" style="color:#FF6B35;">{{ number_format($financial['revenusMoisCourant'], 0, ',', ' ') }}</p>
                <p class="kpi-label">Revenus ce mois (FCFA)</p>
                @if($financial['comparaison'] != 0)
                <div style="margin-top:8px;display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:9999px;font-size:0.72rem;font-weight:700;
                    {{ $financial['comparaison'] >= 0 ? 'background:#F0FDF4;color:#15803D' : 'background:#FEF2F2;color:#991B1B' }}">
                    @if($financial['comparaison'] >= 0)
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                    @endif
                    {{ abs($financial['comparaison']) }}%
                </div>
                @endif
            </div>

            {{-- Total revenus --}}
            <div class="kpi-card">
                <div class="icon-badge" style="background:#F0FDF4;border-color:rgba(21,128,61,0.22);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#15803D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                </div>
                <p class="kpi-value" style="color:#15803D;">{{ number_format($financial['revenusTotal'], 0, ',', ' ') }}</p>
                <p class="kpi-label">Total revenus (FCFA)</p>
            </div>

            {{-- Nombre prestations --}}
            <div class="kpi-card">
                <div class="icon-badge" style="background:#FFF3E0;border-color:rgba(180,83,9,0.20);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#B45309" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <p class="kpi-value" style="color:#B45309;">{{ number_format($financial['nombrePrestations'], 0, ',', ' ') }}</p>
                <p class="kpi-label">Prestations payées</p>
            </div>

            {{-- Cachet moyen --}}
            <div class="kpi-card">
                <div class="icon-badge" style="background:#EFF6FF;border-color:rgba(29,78,216,0.20);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#1D4ED8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="18" x2="18" y1="20" y2="10"/><line x1="12" x2="12" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="14"/></svg>
                </div>
                <p class="kpi-value" style="color:#1D4ED8;">{{ number_format($financial['cachetMoyen'], 0, ',', ' ') }}</p>
                <p class="kpi-label">Cachet moyen (FCFA)</p>
            </div>

        </div>
    </div>

    {{-- ── Section 3 : 2 colonnes — Chart + Réservations ── --}}
    <div class="stat-fade" style="animation-delay:180ms;display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">

        {{-- Bar chart 6 mois --}}
        <div class="section-card">
            <div class="section-header">
                <div class="dot" style="background:#FF6B35;"></div>
                <h2 style="font-size:0.95rem;font-weight:900;color:#1A2744;margin:0;">Revenus — 6 derniers mois</h2>
            </div>
            <div style="padding:20px 24px;">
                <div class="chart-bar-wrap">
                    @foreach($financial['mensuels'] as $m)
                    @php
                        $pct = $maxRevenu > 0 ? round(($m['revenus'] / $maxRevenu) * 100) : 0;
                        $parts = explode('-', $m['mois']);
                        $label = ($monthNames[(int)($parts[1] ?? 1)] ?? '') . ' ' . substr($parts[0] ?? '', 2);
                    @endphp
                    <div class="chart-col">
                        <div class="chart-col-bar-area">
                            <div class="chart-bar-inner {{ $m['revenus'] == 0 ? 'zero' : '' }}" style="height:{{ max(3, $pct) }}%"></div>
                        </div>
                        @if($m['revenus'] > 0)
                            <div class="chart-amount">{{ number_format($m['revenus'] / 1000, 0) }}k</div>
                        @endif
                        <div class="chart-label">{{ $label }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Activité réservations --}}
        <div class="section-card">
            <div class="section-header">
                <div class="dot" style="background:#4CAF50;"></div>
                <h2 style="font-size:0.95rem;font-weight:900;color:#1A2744;margin:0;">Activité réservations</h2>
            </div>
            <div style="padding:20px 24px;display:flex;flex-direction:column;gap:14px;">
                @php
                    $bStats = [
                        ['label' => 'Total réservations',  'value' => $bookingStats['total'],     'color' => '#FF6B35'],
                        ['label' => 'Prestations terminées','value' => $bookingStats['completed'], 'color' => '#15803D'],
                        ['label' => 'En attente',           'value' => $bookingStats['pending'],   'color' => '#B45309'],
                    ];
                @endphp
                @foreach($bStats as $bs)
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                    <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                        <div style="width:8px;height:8px;border-radius:50%;flex-shrink:0;background:{{ $bs['color'] }}"></div>
                        <span style="font-size:0.82rem;font-weight:600;color:#4A4540;">{{ $bs['label'] }}</span>
                    </div>
                    <span style="font-size:1.1rem;font-weight:900;color:{{ $bs['color'] }};letter-spacing:-0.03em;flex-shrink:0;">
                        {{ number_format($bs['value'], 0, ',', ' ') }}
                    </span>
                </div>
                @if(!$loop->last)
                <div style="height:1px;background:#F0EDE8;"></div>
                @endif
                @endforeach

                @if($bookingStats['total'] > 0)
                @php $completionRate = round(($bookingStats['completed'] / $bookingStats['total']) * 100); @endphp
                <div style="margin-top:6px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <span style="font-size:0.78rem;font-weight:700;color:#8A8278;">Taux de completion</span>
                        <span style="font-size:0.82rem;font-weight:900;color:#FF6B35;">{{ $completionRate }}%</span>
                    </div>
                    <div style="background:#EAE7E0;border-radius:9999px;height:7px;overflow:hidden;">
                        <div style="height:7px;border-radius:9999px;background:linear-gradient(90deg,#FF6B35,#E85A25);width:{{ $completionRate }}%;transition:width 0.8s ease;"></div>
                    </div>
                </div>
                @endif
            </div>
        </div>

    </div>

    {{-- ── Section 4 : Tableau mensuel détaillé ── --}}
    <div class="stat-fade" style="animation-delay:240ms;background:#FFFFFF;border-radius:20px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);overflow:hidden;">
        <div style="padding:16px 24px;border-bottom:1px solid #EAE7E0;display:flex;align-items:center;gap:10px;">
            <div class="dot" style="background:#1D4ED8;"></div>
            <h2 style="font-size:0.95rem;font-weight:900;color:#1A2744;margin:0;">Activité mensuelle — 6 derniers mois</h2>
        </div>

        @if($monthly->isEmpty())
            <div style="padding:40px 24px;text-align:center;font-size:0.875rem;color:#8A8278;">
                Aucune activité enregistrée pour la période.
            </div>
        @else
        @php
            $fullMonthNames = ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
            $maxMonthRevenue = $monthly->max('revenue') ?: 1;
        @endphp
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1px solid #EAE7E0;background:#FAFAF8;">
                        <th style="padding:10px 24px;text-align:left;font-size:0.70rem;font-weight:700;color:#8A8278;text-transform:uppercase;letter-spacing:0.06em;">Mois</th>
                        <th style="padding:10px 16px;text-align:right;font-size:0.70rem;font-weight:700;color:#8A8278;text-transform:uppercase;letter-spacing:0.06em;">Réservations</th>
                        <th style="padding:10px 16px;text-align:right;font-size:0.70rem;font-weight:700;color:#8A8278;text-transform:uppercase;letter-spacing:0.06em;">Revenu (FCFA)</th>
                        <th style="padding:10px 24px;text-align:right;font-size:0.70rem;font-weight:700;color:#8A8278;text-transform:uppercase;letter-spacing:0.06em;">Moy./réserv.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($monthly as $row)
                    <tr style="border-bottom:1px solid #F5F3EF;transition:background 0.15s;" onmouseover="this.style.background='#FAFAF8'" onmouseout="this.style.background=''">
                        <td style="padding:13px 24px;font-size:0.875rem;font-weight:600;color:#1A2744;">
                            {{ $fullMonthNames[(int)$row->month] ?? $row->month }} {{ $row->year }}
                        </td>
                        <td style="padding:13px 16px;text-align:right;">
                            <span style="font-size:0.875rem;font-weight:700;color:#1A2744;">{{ $row->count }}</span>
                        </td>
                        <td style="padding:13px 16px;text-align:right;">
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;">
                                <div style="width:80px;background:#EAE7E0;border-radius:9999px;height:5px;overflow:hidden;">
                                    <div style="height:5px;border-radius:9999px;background:#FF6B35;width:{{ round(($row->revenue / $maxMonthRevenue) * 100) }}%"></div>
                                </div>
                                <span style="font-size:0.875rem;font-weight:700;color:#FF6B35;min-width:80px;text-align:right;">{{ number_format($row->revenue, 0, ',', ' ') }}</span>
                            </div>
                        </td>
                        <td style="padding:13px 24px;text-align:right;font-size:0.82rem;color:#8A8278;">
                            {{ $row->count > 0 ? number_format($row->revenue / $row->count, 0, ',', ' ') : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="border-top:2px solid #E5E1DA;background:#F5F3EF;">
                        <td style="padding:11px 24px;font-size:0.75rem;font-weight:800;color:#4A4540;text-transform:uppercase;letter-spacing:0.05em;">Total</td>
                        <td style="padding:11px 16px;text-align:right;font-size:0.875rem;font-weight:800;color:#1A2744;">{{ $monthly->sum('count') }}</td>
                        <td style="padding:11px 16px;text-align:right;font-size:0.875rem;font-weight:800;color:#FF6B35;">{{ number_format($monthly->sum('revenue'), 0, ',', ' ') }}</td>
                        <td style="padding:11px 24px;"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>

</div>
@endsection
