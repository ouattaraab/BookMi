@extends('layouts.talent')

@section('title', 'Mes Revenus — BookMi Talent')

@section('head')
<style>
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
}
.earn-fade { opacity: 0; animation: fadeUp 0.52s cubic-bezier(0.16,1,0.3,1) forwards; }

.summary-card {
    background: #FFFFFF;
    border-radius: 20px;
    border: 1px solid #E5E1DA;
    box-shadow: 0 2px 12px rgba(26,39,68,0.06);
    padding: 22px 24px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.summary-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(26,39,68,0.10); }
.icon-badge {
    width: 42px; height: 42px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; margin-bottom: 14px; border: 1.5px solid transparent;
}
.earn-row {
    padding: 13px 24px;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid #F5F3EF; gap: 12px;
    transition: background 0.15s;
}
.earn-row:last-child { border-bottom: none; }
.earn-row:hover { background: #FAFAF8; }

@media (max-width: 640px) {
    .grid-4 { grid-template-columns: repeat(2,1fr) !important; }
}
</style>
@endsection

@section('content')
<div style="font-family:'Nunito',sans-serif;color:#1A2744;max-width:1100px;">

    {{-- Header --}}
    <div class="earn-fade" style="animation-delay:0ms;margin-bottom:28px;display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <h1 style="font-size:1.85rem;font-weight:900;color:#1A2744;letter-spacing:-0.03em;margin:0 0 5px;line-height:1.15;">Mes Revenus</h1>
            <p style="font-size:0.875rem;color:#8A8278;font-weight:500;margin:0;">Aperçu financier et historique de vos prestations</p>
        </div>
        <a href="{{ route('talent.revenue-certificate') }}"
           style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:14px;font-size:0.82rem;font-weight:800;color:#1A2744;background:#FFFFFF;border:1.5px solid #E5E1DA;text-decoration:none;box-shadow:0 2px 8px rgba(26,39,68,0.06);transition:border-color 0.2s,color 0.2s;white-space:nowrap;flex-shrink:0;"
           onmouseover="this.style.borderColor='#FF6B35';this.style.color='#FF6B35'"
           onmouseout="this.style.borderColor='#E5E1DA';this.style.color='#1A2744'">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Attestation de revenus
        </a>
    </div>

    {{-- ── Solde disponible (carte mise en avant) ── --}}
    <div class="earn-fade" style="animation-delay:50ms;margin-bottom:20px;">
        <div style="background:linear-gradient(135deg,#FF6B35 0%,#C85A20 100%);border-radius:22px;padding:26px 28px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 8px 28px rgba(255,107,53,0.28);">
            <div>
                <p style="font-size:0.78rem;font-weight:700;color:rgba(255,255,255,0.75);text-transform:uppercase;letter-spacing:0.08em;margin:0 0 6px;">Solde disponible</p>
                <p style="font-size:2.2rem;font-weight:900;color:#FFFFFF;margin:0;letter-spacing:-0.04em;line-height:1.1;">
                    {{ number_format($summary['soldeCompte'], 0, ',', ' ') }}
                    <span style="font-size:1rem;font-weight:600;color:rgba(255,255,255,0.70);">XOF</span>
                </p>
                <p style="font-size:0.78rem;font-weight:500;color:rgba(255,255,255,0.65);margin:8px 0 0;">Montant disponible pour reversement</p>
            </div>
            <div style="width:56px;height:56px;border-radius:16px;background:rgba(255,255,255,0.18);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
            </div>
        </div>
    </div>

    {{-- ── 3 cartes financières ── --}}
    <div class="earn-fade grid-4" style="animation-delay:100ms;display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px;">

        <div class="summary-card">
            <div class="icon-badge" style="background:#F0FDF4;border-color:rgba(21,128,61,0.22);">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#15803D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <p style="font-size:1.65rem;font-weight:900;color:#15803D;margin:0 0 4px;letter-spacing:-0.04em;line-height:1;">
                {{ number_format($summary['revenusLiberes'], 0, ',', ' ') }}
            </p>
            <p style="font-size:0.70rem;font-weight:700;color:#8A8278;text-transform:uppercase;letter-spacing:0.06em;margin:0;">Revenus libérés (FCFA)</p>
            <p style="font-size:0.73rem;color:#9A9490;margin:5px 0 0;">Prestations terminées</p>
        </div>

        <div class="summary-card">
            <div class="icon-badge" style="background:#FFF3E0;border-color:rgba(180,83,9,0.20);">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#B45309" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <p style="font-size:1.65rem;font-weight:900;color:#B45309;margin:0 0 4px;letter-spacing:-0.04em;line-height:1;">
                {{ number_format($summary['totalCachetsActifs'], 0, ',', ' ') }}
            </p>
            <p style="font-size:0.70rem;font-weight:700;color:#8A8278;text-transform:uppercase;letter-spacing:0.06em;margin:0;">Cachets à venir (FCFA)</p>
            <p style="font-size:0.73rem;color:#9A9490;margin:5px 0 0;">Prestations confirmées/payées</p>
        </div>

        <div class="summary-card">
            <div class="icon-badge" style="background:#EFF6FF;border-color:rgba(29,78,216,0.20);">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#1D4ED8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            </div>
            <p style="font-size:1.65rem;font-weight:900;color:#1D4ED8;margin:0 0 4px;letter-spacing:-0.04em;line-height:1;">
                {{ number_format($summary['revenusGlobaux'], 0, ',', ' ') }}
            </p>
            <p style="font-size:0.70rem;font-weight:700;color:#8A8278;text-transform:uppercase;letter-spacing:0.06em;margin:0;">Revenus globaux (FCFA)</p>
            <p style="font-size:0.73rem;color:#9A9490;margin:5px 0 0;">Toutes prestations acceptées</p>
        </div>

    </div>

    {{-- ── Historique des prestations ── --}}
    <div class="earn-fade" style="animation-delay:160ms;background:#FFFFFF;border-radius:20px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);overflow:hidden;">

        <div style="padding:16px 24px;border-bottom:1px solid #EAE7E0;display:flex;align-items:center;gap:10px;">
            <div style="width:8px;height:8px;border-radius:50%;background:#FF6B35;flex-shrink:0;"></div>
            <h2 style="font-size:0.95rem;font-weight:900;color:#1A2744;margin:0;">Historique des prestations terminées</h2>
            @if($earnings->total() > 0)
            <span style="margin-left:auto;font-size:0.72rem;font-weight:700;color:#8A8278;background:#F5F3EF;border-radius:9999px;padding:3px 10px;">
                {{ $earnings->total() }} prestation{{ $earnings->total() > 1 ? 's' : '' }}
            </span>
            @endif
        </div>

        @if($earnings->isEmpty())
        <div style="padding:56px 24px;text-align:center;">
            <div style="width:56px;height:56px;border-radius:16px;background:#FFF4EF;border:1.5px solid rgba(255,107,53,0.18);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#FF6B35" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            </div>
            <p style="font-size:0.9rem;font-weight:700;color:#8A8278;margin:0 0 4px;">Aucune prestation terminée</p>
            <p style="font-size:0.78rem;font-weight:500;color:#B0A89E;margin:0;">Vos revenus apparaîtront ici une fois vos prestations terminées.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1px solid #EAE7E0;background:#FAFAF8;">
                        <th style="padding:10px 24px;text-align:left;font-size:0.70rem;font-weight:700;color:#8A8278;text-transform:uppercase;letter-spacing:0.06em;">Client</th>
                        <th style="padding:10px 16px;text-align:left;font-size:0.70rem;font-weight:700;color:#8A8278;text-transform:uppercase;letter-spacing:0.06em;">Prestation</th>
                        <th style="padding:10px 16px;text-align:left;font-size:0.70rem;font-weight:700;color:#8A8278;text-transform:uppercase;letter-spacing:0.06em;">Date</th>
                        <th style="padding:10px 24px;text-align:right;font-size:0.70rem;font-weight:700;color:#8A8278;text-transform:uppercase;letter-spacing:0.06em;">Cachet (FCFA)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($earnings as $booking)
                    <tr class="earn-row">
                        <td style="padding:13px 24px;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:34px;height:34px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:0.82rem;color:#fff;background:linear-gradient(135deg,#1A2744 0%,#2D4A8A 100%);">
                                    {{ strtoupper(substr($booking->client->first_name ?? '?', 0, 1)) }}
                                </div>
                                <span style="font-size:0.875rem;font-weight:700;color:#1A2744;">
                                    {{ trim(($booking->client->first_name ?? '') . ' ' . ($booking->client->last_name ?? '')) ?: '—' }}
                                </span>
                            </div>
                        </td>
                        <td style="padding:13px 16px;font-size:0.82rem;color:#4A4540;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ isset($booking->package_snapshot['name']) ? $booking->package_snapshot['name'] : 'Prestation libre' }}
                        </td>
                        <td style="padding:13px 16px;font-size:0.82rem;color:#6B6560;white-space:nowrap;">
                            {{ $booking->event_date?->translatedFormat('d M Y') ?? '—' }}
                        </td>
                        <td style="padding:13px 24px;text-align:right;">
                            <span style="font-size:0.95rem;font-weight:900;color:#15803D;letter-spacing:-0.02em;">
                                {{ number_format($booking->cachet_amount, 0, ',', ' ') }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($earnings->hasPages())
        <div style="padding:14px 24px;border-top:1px solid #EAE7E0;display:flex;justify-content:center;">
            {{ $earnings->links() }}
        </div>
        @endif
        @endif

    </div>

</div>
@endsection
