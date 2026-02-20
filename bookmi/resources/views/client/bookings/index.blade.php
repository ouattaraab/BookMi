@extends('layouts.client')

@section('title', 'Mes réservations — BookMi Client')

@section('head')
<style>
main.page-content { background: #F2EFE9 !important; }

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(22px); }
    to   { opacity: 1; transform: translateY(0); }
}
.dash-fade { opacity: 0; animation: fadeUp 0.6s cubic-bezier(0.16,1,0.3,1) forwards; }

/* ── Status tabs ── */
.status-tab {
    padding: 8px 18px;
    border-radius: 9999px;
    font-size: 0.78rem;
    font-weight: 700;
    border: 1.5px solid #E5E1DA;
    background: #FFFFFF;
    color: #8A8278;
    text-decoration: none;
    transition: all 0.2s cubic-bezier(0.16,1,0.3,1);
    white-space: nowrap;
    letter-spacing: 0.01em;
}
.status-tab:hover {
    border-color: #FF6B35;
    color: #FF6B35;
    background: #FFF5F0;
}
.status-tab.active {
    color: #FFFFFF;
    background: linear-gradient(135deg, #1A2744 0%, #2563EB 100%);
    border-color: transparent;
    box-shadow: 0 3px 12px rgba(26,39,68,0.25);
}

/* ── Booking cards ── */
.booking-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 20px;
    border-radius: 14px;
    background: #FFFFFF;
    border: 1px solid #E5E1DA;
    text-decoration: none;
    transition: transform 0.22s cubic-bezier(0.16,1,0.3,1), box-shadow 0.22s, border-color 0.18s;
    box-shadow: 0 1px 6px rgba(26,39,68,0.05);
}
.booking-card:hover {
    transform: translateY(-3px) translateX(2px);
    border-color: #FF6B35;
    box-shadow: 0 8px 24px rgba(255,107,53,0.12);
}

/* ── Section card ── */
.section-card-l {
    background: #FFFFFF;
    border-radius: 18px;
    border: 1px solid #E5E1DA;
    box-shadow: 0 2px 12px rgba(26,39,68,0.06);
    overflow: hidden;
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
    @if(session('info'))
    <div class="dash-fade" style="animation-delay:0ms;margin-bottom:16px;padding:14px 18px;border-radius:12px;font-size:0.875rem;font-weight:600;background:#EFF6FF;border:1px solid #93C5FD;color:#1D4ED8;">{{ session('info') }}</div>
    @endif

    {{-- Header --}}
    <div class="dash-fade" style="animation-delay:0ms;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:28px;">
        <div>
            <h1 style="font-size:1.8rem;font-weight:900;color:#1A2744;letter-spacing:-0.025em;margin:0 0 5px 0;line-height:1.15;">Mes réservations</h1>
            <p style="font-size:0.875rem;color:#8A8278;font-weight:500;margin:0;">Gérez toutes vos demandes de prestation</p>
        </div>
        <a href="{{ route('talents.index') }}" class="btn-cta">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v16m8-8H4"/></svg>
            Réserver un talent
        </a>
    </div>

    {{-- Status tabs --}}
    @php
        $tabs = ['' => 'Toutes', 'pending' => 'En attente', 'accepted' => 'Acceptées', 'paid' => 'Payées', 'confirmed' => 'Confirmées', 'completed' => 'Terminées', 'cancelled' => 'Annulées'];
        $currentStatus = request('status', '');
    @endphp
    <div class="dash-fade" style="animation-delay:70ms;display:flex;gap:8px;overflow-x:auto;padding-bottom:4px;margin-bottom:24px;scrollbar-width:none;">
        @foreach($tabs as $value => $label)
            <a href="{{ route('client.bookings', $value ? ['status' => $value] : []) }}"
               class="status-tab {{ $currentStatus === $value ? 'active' : '' }} flex-shrink-0">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Booking list --}}
    @if($bookings->isEmpty())
        <div class="dash-fade section-card-l" style="animation-delay:140ms;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:72px 24px;text-align:center;">
            <div style="width:76px;height:76px;border-radius:22px;background:#FFF0E8;border:2px solid #FFCAAD;display:flex;align-items:center;justify-content:center;margin-bottom:22px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#FF6B35" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <p style="font-size:1.05rem;font-weight:800;color:#1A2744;margin:0 0 8px 0;">Aucune réservation trouvée</p>
            <p style="font-size:0.875rem;color:#8A8278;margin:0 0 28px 0;max-width:280px;line-height:1.6;">
                {{ $currentStatus ? 'Aucune réservation avec ce statut.' : 'Commencez par réserver un talent !' }}
            </p>
            <a href="{{ route('talents.index') }}" class="btn-cta">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                Découvrir les talents
            </a>
        </div>
    @else
        <div class="dash-fade" style="animation-delay:140ms;display:flex;flex-direction:column;gap:10px;">
            @foreach($bookings as $i => $booking)
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
                $talentName = $booking->talentProfile->stage_name
                    ?? trim(($booking->talentProfile->user->first_name ?? '') . ' ' . ($booking->talentProfile->user->last_name ?? ''))
                    ?: '?';
            @endphp
            <a href="{{ route('client.bookings.show', $booking->id) }}" class="booking-card">
                {{-- Avatar --}}
                <div style="width:48px;height:48px;border-radius:13px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1.1rem;color:#fff;background:linear-gradient(135deg,{{ $ss['border'] }},{{ $ss['text'] }});">
                    {{ strtoupper(substr($talentName, 0, 1)) }}
                </div>
                {{-- Info --}}
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;">
                        <span style="font-weight:800;font-size:0.9rem;color:#1A2744;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $talentName }}</span>
                        @if($booking->talentProfile->category)
                        <span style="font-size:0.72rem;color:#B0A89E;flex-shrink:0;">· {{ $booking->talentProfile->category->name ?? '' }}</span>
                        @endif
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;font-size:0.75rem;color:#8A8278;">
                        <span>{{ \Carbon\Carbon::parse($booking->event_date)->format('d/m/Y') }}</span>
                        @if($booking->servicePackage)
                            <span style="color:#C8C3BC;">|</span>
                            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $booking->servicePackage->name }}</span>
                        @endif
                    </div>
                </div>
                {{-- Amount + badge --}}
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex-shrink:0;">
                    <span style="font-weight:900;font-size:0.9rem;color:#1A2744;">{{ number_format($booking->cachet_amount, 0, ',', ' ') }} <span style="color:#8A8278;font-weight:600;font-size:0.75rem;">FCFA</span></span>
                    <span style="font-size:0.7rem;font-weight:800;padding:4px 12px;border-radius:9999px;background:{{ $ss['bg'] }};color:{{ $ss['text'] }};border:1.5px solid {{ $ss['border'] }};letter-spacing:0.03em;">{{ $ss['label'] }}</span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#C8C3BC" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M9 5l7 7-7 7"/></svg>
            </a>
            @endforeach
        </div>

        @if($bookings->hasPages())
            <div style="margin-top:28px;display:flex;justify-content:center;">
                {{ $bookings->links() }}
            </div>
        @endif
    @endif

</div>
@endsection
