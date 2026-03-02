@extends('layouts.client')

@section('title', 'Réservation #' . $booking->id . ' — BookMi Client')

@section('head')
<style>
main.page-content { background: #F2EFE9 !important; }

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(22px); }
    to   { opacity: 1; transform: translateY(0); }
}
.dash-fade { opacity: 0; animation: fadeUp 0.6s cubic-bezier(0.16,1,0.3,1) forwards; }

/* ── Detail row ── */
.detail-row {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px 0;
    border-bottom: 1px solid #EAE7E0;
}
.detail-row:last-child { border-bottom: none; }

.detail-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

/* ── Buttons ── */
.btn-pay {
    flex: 1;
    display: block;
    text-align: center;
    padding: 14px 24px;
    border-radius: 14px;
    font-size: 0.875rem; font-weight: 800;
    color: white; text-decoration: none;
    background: linear-gradient(135deg, #16A34A, #15803D);
    box-shadow: 0 4px 16px rgba(22,163,74,0.30);
    transition: transform 0.2s, box-shadow 0.2s;
    font-family: 'Nunito', sans-serif;
}
.btn-pay:hover { transform: translateY(-2px); box-shadow: 0 6px 22px rgba(22,163,74,0.40); }

.btn-cancel {
    flex: 1; width: 100%;
    padding: 14px 24px;
    border-radius: 14px;
    font-size: 0.875rem; font-weight: 700;
    background: #FEF2F2;
    border: 1.5px solid #FCA5A5;
    color: #EF4444;
    transition: background 0.2s, box-shadow 0.2s;
    cursor: pointer;
    font-family: 'Nunito', sans-serif;
}
.btn-cancel:hover { background: #FEE2E2; box-shadow: 0 4px 14px rgba(239,68,68,0.15); }
</style>
@endsection

@section('content')
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
@php
    $eventDate      = \Carbon\Carbon::parse($booking->event_date)->startOfDay();
    $daysUntilEvent = (int) now()->startOfDay()->diffInDays($eventDate, false);
    $cancelPolicy   = match(true) {
        $daysUntilEvent >= 14 => ['tier' => 'full',      'label' => 'Remboursement intégral (J-14+)',       'color' => '#15803D', 'bg' => '#F0FDF4', 'border' => '#86EFAC'],
        $daysUntilEvent >= 7  => ['tier' => 'partial',   'label' => 'Remboursement 50% (J-7 à J-14)',       'color' => '#B45309', 'bg' => '#FFFBEB', 'border' => '#FCD34D'],
        $daysUntilEvent >= 2  => ['tier' => 'mediation', 'label' => 'Médiation requise (J-2 à J-7)',        'color' => '#9333EA', 'bg' => '#FAF5FF', 'border' => '#D8B4FE'],
        default               => ['tier' => 'none',      'label' => 'Annulation non disponible (< J-2)',    'color' => '#991B1B', 'bg' => '#FEF2F2', 'border' => '#FCA5A5'],
    };
    $refundAmount = match($cancelPolicy['tier']) {
        'full'    => $booking->total_amount ?? 0,
        'partial' => (int) round(($booking->total_amount ?? 0) * 0.5),
        default   => 0,
    };
@endphp

<div style="font-family:'Nunito',sans-serif;color:#1A2744;max-width:780px;">

    {{-- Flash --}}
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
    <div class="dash-fade" style="animation-delay:0ms;display:flex;align-items:center;gap:16px;margin-bottom:28px;">
        <a href="{{ route('client.bookings') }}"
           style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:12px;font-size:0.8rem;font-weight:700;background:#FFFFFF;border:1.5px solid #E5E1DA;color:#8A8278;text-decoration:none;box-shadow:0 1px 4px rgba(26,39,68,0.06);transition:all 0.2s;"
           onmouseover="this.style.borderColor='#FF6B35';this.style.color='#FF6B35'"
           onmouseout="this.style.borderColor='#E5E1DA';this.style.color='#8A8278'">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
            Retour
        </a>
        <div>
            <h1 style="font-size:1.8rem;font-weight:900;color:#1A2744;letter-spacing:-0.025em;margin:0 0 4px;line-height:1.15;">
                Réservation <span style="color:#2563EB;">#{{ $booking->id }}</span>
            </h1>
            <p style="font-size:0.875rem;color:#8A8278;font-weight:500;margin:0;">Détails de votre demande de prestation</p>
        </div>
    </div>

    {{-- Main card --}}
    <div class="dash-fade" style="animation-delay:80ms;background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);overflow:hidden;margin-bottom:16px;">

        {{-- Talent header --}}
        <div style="padding:20px 24px;border-bottom:1px solid #EAE7E0;display:flex;align-items:center;gap:16px;">
            <div style="width:52px;height:52px;border-radius:14px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1.2rem;color:#fff;background:linear-gradient(135deg,{{ $ss['border'] }},{{ $ss['text'] }});">
                {{ strtoupper(substr($talentName, 0, 1)) }}
            </div>
            <div style="flex:1;min-width:0;">
                <h2 style="font-weight:900;font-size:1.05rem;color:#1A2744;margin:0 0 3px;">{{ $talentName }}</h2>
                <p style="font-size:0.78rem;color:#8A8278;font-weight:500;margin:0;">{{ $booking->talentProfile->category->name ?? '—' }}</p>
            </div>
            <span style="font-size:0.72rem;font-weight:800;padding:5px 14px;border-radius:9999px;background:{{ $ss['bg'] }};color:{{ $ss['text'] }};border:1.5px solid {{ $ss['border'] }};letter-spacing:0.03em;flex-shrink:0;">
                {{ $ss['label'] }}
            </span>
        </div>

        {{-- Detail rows --}}
        <div style="padding:8px 24px;">

            {{-- Date --}}
            <div class="detail-row">
                <div class="detail-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <p style="font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#B0A89E;margin:0 0 4px;">Date de l'événement</p>
                    <p style="font-size:0.9rem;font-weight:800;color:#1A2744;margin:0;">
                        {{ \Carbon\Carbon::parse($booking->event_date)->translatedFormat('l d F Y') }}
                        @if($booking->start_time)
                            <span style="color:#FF6B35;font-weight:700;"> · {{ \Carbon\Carbon::createFromTimeString($booking->start_time)->format('H\hi') }}</span>
                        @endif
                    </p>
                </div>
            </div>

            {{-- Lieu --}}
            @if($booking->event_location)
            <div class="detail-row">
                <div class="detail-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <p style="font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#B0A89E;margin:0 0 4px;">Lieu</p>
                    <p style="font-size:0.9rem;font-weight:800;color:#1A2744;margin:0;">{{ $booking->event_location }}</p>
                </div>
            </div>
            @endif

            {{-- Package --}}
            @if($booking->servicePackage)
            <div class="detail-row">
                <div class="detail-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div>
                    <p style="font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#B0A89E;margin:0 0 4px;">Package</p>
                    <p style="font-size:0.9rem;font-weight:800;color:#1A2744;margin:0 0 3px;">{{ $booking->servicePackage->name }}</p>
                    @if($booking->servicePackage->duration_minutes)
                    <p style="font-size:0.75rem;color:#8A8278;font-weight:600;margin:0;display:flex;align-items:center;gap:4px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        {{ $booking->servicePackage->duration_minutes >= 60 ? floor($booking->servicePackage->duration_minutes / 60).'h' : $booking->servicePackage->duration_minutes.'min' }} de prestation
                    </p>
                    @endif
                </div>
            </div>
            @endif

            {{-- Message --}}
            @if($booking->message)
            <div class="detail-row">
                <div class="detail-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24"><path d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                </div>
                <div style="flex:1;">
                    <p style="font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#B0A89E;margin:0 0 4px;">Votre message</p>
                    <p style="font-size:0.875rem;color:#6B7280;font-weight:500;line-height:1.6;margin:0;">{{ $booking->message }}</p>
                </div>
            </div>
            @endif

            {{-- Commentaire d'acceptation du talent --}}
            @if($booking->accept_comment)
            <div class="detail-row">
                <div class="detail-icon" style="background:#F0FDF4;border-color:#86EFAC;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="#15803D" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div style="flex:1;">
                    <p style="font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#15803D;margin:0 0 4px;">Message du talent</p>
                    <p style="font-size:0.875rem;color:#374151;font-weight:500;line-height:1.6;margin:0;">{{ $booking->accept_comment }}</p>
                </div>
            </div>
            @endif

        </div>

        {{-- Détail financier --}}
        <div style="padding:0 24px 24px;">
            <div style="background:#F9F8F5;border-radius:14px;border:1px solid #EAE7E0;padding:18px 20px;">
                <p style="font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#B0A89E;margin:0 0 14px;">Détail financier</p>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                    <span style="font-size:0.85rem;color:#6B7280;font-weight:600;">Cachet talent</span>
                    <span style="font-size:0.875rem;font-weight:800;color:#1A2744;">{{ number_format($booking->cachet_amount, 0, ',', ' ') }} <span style="color:#8A8278;font-weight:600;font-size:0.75rem;">FCFA</span></span>
                </div>
                @if($booking->is_express)
                <div style="display:flex;justify-content:flex-end;margin:-4px 0 8px;">
                    <span style="font-size:0.7rem;font-weight:800;padding:2px 8px;border-radius:9999px;background:#FFF8F5;border:1px solid #FECDBB;color:#C85A20;">⚡ Réservation express</span>
                </div>
                @endif
                @if($booking->commission_amount)
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                    <span style="font-size:0.85rem;color:#6B7280;font-weight:600;">Commission BookMi</span>
                    <span style="font-size:0.875rem;font-weight:800;color:#1A2744;">{{ number_format($booking->commission_amount, 0, ',', ' ') }} <span style="color:#8A8278;font-weight:600;font-size:0.75rem;">FCFA</span></span>
                </div>
                @endif
                @if($booking->express_fee > 0)
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                    <span style="font-size:0.85rem;color:#6B7280;font-weight:600;">⚡ Supplément express (10%)</span>
                    <span style="font-size:0.875rem;font-weight:800;color:#FF6B35;">{{ number_format($booking->express_fee, 0, ',', ' ') }} <span style="color:#8A8278;font-weight:600;font-size:0.75rem;">FCFA</span></span>
                </div>
                @endif
                @if($booking->travel_cost > 0)
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                    <span style="font-size:0.85rem;color:#6B7280;font-weight:600;">Frais de déplacement</span>
                    <span style="font-size:0.875rem;font-weight:800;color:#1A2744;">{{ number_format($booking->travel_cost, 0, ',', ' ') }} <span style="color:#8A8278;font-weight:600;font-size:0.75rem;">FCFA</span></span>
                </div>
                @endif
                <div style="display:flex;justify-content:space-between;align-items:center;padding-top:14px;border-top:1px solid #E5E1DA;">
                    <span style="font-size:0.95rem;font-weight:900;color:#1A2744;">Total</span>
                    <span style="font-size:1.25rem;font-weight:900;color:#FF6B35;">{{ number_format($booking->total_amount ?? $booking->cachet_amount, 0, ',', ' ') }} <span style="font-size:0.8rem;font-weight:700;color:rgba(255,107,53,0.7);">FCFA</span></span>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        @if(in_array($sk, ['pending', 'accepted']))
        <div style="padding:0 24px 24px;display:flex;gap:12px;flex-wrap:wrap;">
            @if($sk === 'accepted')
            <a href="{{ route('client.bookings.pay', $booking->id) }}" class="btn-pay">
                💳 Payer maintenant
            </a>
            @endif
            {{-- Cancellation policy info --}}
            <div style="margin-bottom:12px;padding:12px 14px;border-radius:12px;background:{{ $cancelPolicy['bg'] }};border:1px solid {{ $cancelPolicy['border'] }};">
                <p style="font-size:0.75rem;font-weight:700;color:{{ $cancelPolicy['color'] }};margin:0;display:flex;align-items:center;gap:6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Politique d'annulation : {{ $cancelPolicy['label'] }}
                </p>
            </div>
            <form action="{{ route('client.bookings.cancel', $booking->id) }}" method="POST"
                  style="flex:1;" onsubmit="return confirm('Annuler cette réservation ?\n\nPolitique applicable : {{ addslashes($cancelPolicy["label"]) }}')">
                @csrf
                <button type="submit" class="btn-cancel">Annuler la réservation</button>
            </form>
        </div>
        @endif

        {{-- Cancel for paid/confirmed (graduated policy) --}}
        @if(in_array($sk, ['paid', 'confirmed']) && $cancelPolicy['tier'] !== 'none')
        <div style="padding:0 24px 16px;">
            <div style="padding:12px 14px;border-radius:12px;background:{{ $cancelPolicy['bg'] }};border:1px solid {{ $cancelPolicy['border'] }};margin-bottom:10px;">
                <p style="font-size:0.75rem;font-weight:700;color:{{ $cancelPolicy['color'] }};margin:0;">
                    Politique d'annulation : {{ $cancelPolicy['label'] }}
                    @if($cancelPolicy['tier'] === 'full') — Remboursement : {{ number_format($refundAmount, 0, ',', ' ') }} FCFA
                    @elseif($cancelPolicy['tier'] === 'partial') — Remboursement estimé : {{ number_format($refundAmount, 0, ',', ' ') }} FCFA (50%)
                    @elseif($cancelPolicy['tier'] === 'mediation') — L'équipe BookMi vous contactera pour médiation.
                    @endif
                </p>
            </div>
            @if($cancelPolicy['tier'] !== 'mediation')
            <form action="{{ route('client.bookings.cancel', $booking->id) }}" method="POST"
                  onsubmit="return confirm('Annuler cette réservation ?

{{ addslashes($cancelPolicy['label']) }}
Remboursement estimé : {{ number_format($refundAmount, 0, ",", " ") }} FCFA

Cette action est irréversible.')">
                @csrf
                <button type="submit" style="width:100%;padding:12px 20px;border-radius:12px;font-size:0.85rem;font-weight:800;color:#991B1B;background:#FEF2F2;border:1.5px solid #FCA5A5;cursor:pointer;font-family:'Nunito',sans-serif;transition:background 0.15s;"
                        onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='#FEF2F2'">
                    Annuler la réservation
                </button>
            </form>
            @endif
        </div>
        @endif

        {{-- Bouton messagerie (uniquement pour réservations actives) --}}
        @if(in_array($sk, ['paid', 'confirmed']))
        <div style="padding:0 24px 24px;">
            <form action="{{ route('client.messages.start', $booking->id) }}" method="POST">
                @csrf
                <button type="submit" style="width:100%;padding:14px 24px;border-radius:14px;font-size:0.875rem;font-weight:800;color:white;background:linear-gradient(135deg,#FF6B35,#C85A20);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;font-family:'Nunito',sans-serif;box-shadow:0 4px 16px rgba(255,107,53,0.30);transition:transform 0.2s,box-shadow 0.2s;"
                        onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 22px rgba(255,107,53,0.40)'"
                        onmouseout="this.style.transform='';this.style.boxShadow='0 4px 16px rgba(255,107,53,0.30)'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    Contacter {{ $talentName }}
                </button>
            </form>
        </div>
        @endif

        {{-- Bouton litige (réservation payée ou confirmée seulement) --}}
        @if(in_array($sk, ['paid', 'confirmed']))
        <div style="padding:0 24px 24px;">
            <form action="{{ route('client.bookings.dispute', $booking->id) }}" method="POST"
                  onsubmit="return confirm('Confirmer l\'ouverture d\'un litige ? L\'équipe BookMi sera notifiée.')">
                @csrf
                <button type="submit"
                        style="width:100%;padding:12px 24px;border-radius:14px;font-size:0.85rem;font-weight:800;color:#991B1B;background:#FEF2F2;border:1.5px solid #FCA5A5;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;font-family:'Nunito',sans-serif;transition:background 0.2s,border-color 0.2s;"
                        onmouseover="this.style.background='#FEE2E2';this.style.borderColor='#F87171'"
                        onmouseout="this.style.background='#FEF2F2';this.style.borderColor='#FCA5A5'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Ouvrir un litige
                </button>
            </form>
        </div>
        @endif

    </div>

    {{-- Téléchargements (reçu + contrat) --}}
    @if(in_array($sk, ['paid', 'confirmed', 'completed']))
    <div class="dash-fade" style="animation-delay:160ms;background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);margin-bottom:16px;overflow:hidden;">
        <div style="padding:16px 24px;border-bottom:1px solid #EAE7E0;display:flex;align-items:center;gap:10px;">
            <div style="width:8px;height:8px;border-radius:50%;background:#FF6B35;flex-shrink:0;"></div>
            <h3 style="font-size:0.9rem;font-weight:900;color:#1A2744;margin:0;">Documents</h3>
        </div>
        <div style="padding:16px 24px;display:flex;gap:12px;flex-wrap:wrap;">
            <a href="{{ route('client.bookings.receipt', $booking->id) }}"
               style="display:inline-flex;align-items:center;gap:8px;padding:12px 20px;border-radius:14px;font-size:0.85rem;font-weight:800;color:white;background:linear-gradient(135deg,#FF6B35,#C85A20);text-decoration:none;box-shadow:0 4px 14px rgba(255,107,53,0.28);transition:transform 0.2s,box-shadow 0.2s;font-family:'Nunito',sans-serif;"
               onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(255,107,53,0.38)'"
               onmouseout="this.style.transform='';this.style.boxShadow='0 4px 14px rgba(255,107,53,0.28)'">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                Télécharger le reçu
            </a>
            @if($booking->contract_path)
            <a href="{{ route('client.bookings.contract', $booking->id) }}"
               style="display:inline-flex;align-items:center;gap:8px;padding:12px 20px;border-radius:14px;font-size:0.85rem;font-weight:800;color:#1A2744;background:#FFFFFF;border:1.5px solid #E5E1DA;text-decoration:none;box-shadow:0 2px 8px rgba(26,39,68,0.06);transition:transform 0.2s,border-color 0.2s;font-family:'Nunito',sans-serif;"
               onmouseover="this.style.transform='translateY(-2px)';this.style.borderColor='#FF6B35';this.style.color='#FF6B35'"
               onmouseout="this.style.transform='';this.style.borderColor='#E5E1DA';this.style.color='#1A2744'">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Télécharger le contrat
            </a>
            @endif
        </div>
    </div>
    @endif

    {{-- Timeline suivi jour-J (lecture seule) --}}
    @if(in_array($sk, ['paid', 'confirmed', 'completed']) && $booking->trackingEvents->isNotEmpty())
    <div class="dash-fade" style="animation-delay:180ms;background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);margin-bottom:16px;overflow:hidden;">
        <div style="padding:16px 24px;border-bottom:1px solid #EAE7E0;display:flex;align-items:center;gap:10px;">
            <div style="width:8px;height:8px;border-radius:50%;background:#FF6B35;flex-shrink:0;"></div>
            <h3 style="font-size:0.9rem;font-weight:900;color:#1A2744;margin:0;">Suivi de la prestation</h3>
        </div>
        <div style="padding:20px 24px;">
            @foreach($booking->trackingEvents as $index => $event)
            @php
                $isLast = $loop->last;
                $dotColor = $isLast ? '#15803D' : '#FF6B35';
            @endphp
            <div style="display:flex;gap:14px;{{ $isLast ? '' : 'margin-bottom:4px;' }}">
                <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;">
                    <div style="width:12px;height:12px;border-radius:50%;background:{{ $dotColor }};border:2px solid white;box-shadow:0 0 0 2px {{ $dotColor }};flex-shrink:0;"></div>
                    @if(!$isLast)
                    <div style="width:2px;flex:1;background:#EAE7E0;margin:4px 0;min-height:28px;"></div>
                    @endif
                </div>
                <div style="padding-bottom:{{ $isLast ? '0' : '20px' }};">
                    <p style="font-size:0.875rem;font-weight:800;color:#1A2744;margin:0 0 2px;">
                        {{ $event->status instanceof \App\Enums\TrackingStatus ? $event->status->label() : (string) $event->status }}
                    </p>
                    <p style="font-size:0.75rem;color:#8A8278;font-weight:500;margin:0;">
                        {{ $event->occurred_at->format('d/m H:i') }}
                    </p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Chronologie des statuts ── --}}
    @if($booking->statusLogs->isNotEmpty())
    @php
        $sColors = [
            'pending'   => '#F59E0B', 'accepted'  => '#3B82F6', 'paid'      => '#8B5CF6',
            'confirmed' => '#06B6D4', 'completed' => '#10B981', 'cancelled' => '#9CA3AF',
            'rejected'  => '#EF4444', 'disputed'  => '#F97316',
        ];
        $sLabels = [
            'pending'   => 'En attente', 'accepted'  => 'Acceptée',  'paid'      => 'Payée',
            'confirmed' => 'Confirmée',  'completed' => 'Terminée',  'cancelled' => 'Annulée',
            'rejected'  => 'Rejetée',    'disputed'  => 'Litige',
        ];
    @endphp
    <div class="dash-fade" style="animation-delay:185ms;background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);margin-bottom:16px;overflow:hidden;">
        <div style="padding:16px 24px;border-bottom:1px solid #EAE7E0;display:flex;align-items:center;gap:10px;">
            <div style="width:8px;height:8px;border-radius:50%;background:#2563EB;flex-shrink:0;"></div>
            <h3 style="font-size:0.9rem;font-weight:900;color:#1A2744;margin:0;">Chronologie de la réservation</h3>
        </div>
        <div style="padding:20px 24px;">
            @foreach($booking->statusLogs as $log)
            @php
                $isLast = $loop->last;
                $dotBg  = $sColors[$log->to_status] ?? '#9CA3AF';
                $toL    = $sLabels[$log->to_status] ?? ucfirst($log->to_status ?? '—');
                $fromL  = $log->from_status ? ($sLabels[$log->from_status] ?? ucfirst($log->from_status)) : 'Création';
            @endphp
            <div style="display:flex;gap:14px;{{ $isLast ? '' : 'margin-bottom:4px;' }}">
                <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;">
                    <div style="width:12px;height:12px;border-radius:50%;background:{{ $dotBg }};border:2px solid white;box-shadow:0 0 0 2px {{ $dotBg }};flex-shrink:0;"></div>
                    @if(!$isLast)
                    <div style="width:2px;flex:1;background:#EAE7E0;margin:4px 0;min-height:28px;"></div>
                    @endif
                </div>
                <div style="padding-bottom:{{ $isLast ? '0' : '20px' }};flex:1;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
                        <div>
                            <p style="font-size:0.875rem;font-weight:800;color:#1A2744;margin:0 0 2px;">{{ $fromL }} &rarr; {{ $toL }}</p>
                            @if($log->performer)
                            <p style="font-size:0.75rem;color:#8A8278;font-weight:500;margin:0;">Par {{ $log->performer->first_name ?? '' }} {{ $log->performer->last_name ?? '' }}</p>
                            @else
                            <p style="font-size:0.75rem;color:#B0A89E;font-weight:500;margin:0;">Système automatique</p>
                            @endif
                        </div>
                        <span style="font-size:0.7rem;color:#B0A89E;font-weight:500;white-space:nowrap;flex-shrink:0;">{{ $log->created_at?->format('d/m/Y H:i') ?? '—' }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Formulaire avis (status completed seulement, pas encore évalué) --}}
    @if($sk === 'completed' && !$hasReview)
    <div class="dash-fade" style="animation-delay:190ms;background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);margin-bottom:16px;overflow:hidden;"
         x-data="{ rating: 0, hovered: 0 }">
        <div style="padding:16px 24px;border-bottom:1px solid #EAE7E0;display:flex;align-items:center;gap:10px;">
            <div style="width:8px;height:8px;border-radius:50%;background:#FF6B35;flex-shrink:0;"></div>
            <h3 style="font-size:0.9rem;font-weight:900;color:#1A2744;margin:0;">Laisser un avis</h3>
        </div>
        <div style="padding:20px 24px;">
            <form action="{{ route('client.bookings.review.store', $booking->id) }}" method="POST">
                @csrf
                {{-- Étoiles --}}
                <div style="margin-bottom:18px;">
                    <p style="font-size:0.75rem;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#8A8278;margin:0 0 10px;">Note *</p>
                    <div style="display:flex;gap:6px;">
                        @for($i = 1; $i <= 5; $i++)
                        <label style="cursor:pointer;position:relative;">
                            <input type="radio" name="rating" value="{{ $i }}" required
                                   x-on:change="rating = {{ $i }}"
                                   style="position:absolute;opacity:0;width:0;height:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                                 x-on:mouseenter="hovered = {{ $i }}"
                                 x-on:mouseleave="hovered = 0"
                                 :fill="(hovered >= {{ $i }} || (hovered === 0 && rating >= {{ $i }})) ? '#FF6B35' : '#E5E1DA'"
                                 style="transition:fill 0.15s;">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                        </label>
                        @endfor
                    </div>
                </div>
                {{-- Commentaire --}}
                <div style="margin-bottom:18px;">
                    <label style="display:block;font-size:0.75rem;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#8A8278;margin-bottom:8px;">Commentaire (optionnel)</label>
                    <textarea name="comment" rows="3" maxlength="1000"
                              placeholder="Partagez votre expérience avec ce talent..."
                              style="width:100%;border:1.5px solid #E5E1DA;border-radius:12px;padding:12px 16px;font-size:0.875rem;font-family:'Nunito',sans-serif;color:#1A2744;resize:none;outline:none;transition:border-color 0.2s;box-sizing:border-box;"
                              onfocus="this.style.borderColor='#FF6B35'"
                              onblur="this.style.borderColor='#E5E1DA'">{{ old('comment') }}</textarea>
                </div>

                {{-- Évaluation détaillée multi-critères (optionnel) --}}
                <div style="margin-bottom:20px;padding:16px;background:#F9F8F5;border-radius:14px;border:1px solid #EAE7E0;">
                    <p style="font-size:0.75rem;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#8A8278;margin:0 0 14px;">Évaluation détaillée (optionnel)</p>
                    @foreach([
                        ['name' => 'punctuality_score',      'label' => 'Ponctualité'],
                        ['name' => 'quality_score',          'label' => 'Qualité de la prestation'],
                        ['name' => 'professionalism_score',  'label' => 'Professionnalisme'],
                        ['name' => 'contract_respect_score', 'label' => 'Respect du contrat'],
                    ] as $criterion)
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:6px;">
                        <label style="font-size:0.875rem;color:#4A4540;font-weight:600;min-width:180px;">{{ $criterion['label'] }}</label>
                        <div style="display:flex;gap:4px;">
                            @for($star = 1; $star <= 5; $star++)
                            <label style="cursor:pointer;line-height:1;">
                                <input type="radio" name="{{ $criterion['name'] }}" value="{{ $star }}" style="position:absolute;opacity:0;width:0;height:0;">
                                <span class="criteria-star" data-value="{{ $star }}" data-name="{{ $criterion['name'] }}"
                                      style="font-size:1.5rem;color:#D1C7BC;cursor:pointer;transition:color 0.15s;user-select:none;">&#9733;</span>
                            </label>
                            @endfor
                        </div>
                    </div>
                    @endforeach
                </div>

                <button type="submit"
                        style="padding:12px 28px;border-radius:14px;font-size:0.875rem;font-weight:800;color:white;background:linear-gradient(135deg,#FF6B35,#C85A20);border:none;cursor:pointer;font-family:'Nunito',sans-serif;box-shadow:0 4px 14px rgba(255,107,53,0.28);transition:transform 0.2s;"
                        onmouseover="this.style.transform='translateY(-2px)'"
                        onmouseout="this.style.transform=''">
                    Publier mon avis
                </button>

                <script>
                document.querySelectorAll('.criteria-star').forEach(function(star) {
                    star.addEventListener('click', function() {
                        var name = this.dataset.name;
                        var value = parseInt(this.dataset.value);
                        document.querySelector('input[name="' + name + '"][value="' + value + '"]').checked = true;
                        document.querySelectorAll('.criteria-star[data-name="' + name + '"]').forEach(function(s) {
                            s.style.color = parseInt(s.dataset.value) <= value ? '#FF6B35' : '#D1C7BC';
                        });
                    });
                    star.addEventListener('mouseover', function() {
                        var name = this.dataset.name;
                        var value = parseInt(this.dataset.value);
                        document.querySelectorAll('.criteria-star[data-name="' + name + '"]').forEach(function(s) {
                            s.style.color = parseInt(s.dataset.value) <= value ? '#FF6B35' : '#D1C7BC';
                        });
                    });
                    star.addEventListener('mouseout', function() {
                        var name = this.dataset.name;
                        var checkedInput = document.querySelector('input[name="' + name + '"]:checked');
                        var checkedValue = checkedInput ? parseInt(checkedInput.value) : 0;
                        document.querySelectorAll('.criteria-star[data-name="' + name + '"]').forEach(function(s) {
                            s.style.color = parseInt(s.dataset.value) <= checkedValue ? '#FF6B35' : '#D1C7BC';
                        });
                    });
                });
                </script>
            </form>
        </div>
    </div>
    @elseif($sk === 'completed' && $hasReview)
    <div class="dash-fade" style="animation-delay:190ms;display:flex;align-items:center;gap:12px;padding:14px 18px;border-radius:14px;background:#F0FDF4;border:1px solid #86EFAC;margin-bottom:16px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#15803D" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span style="font-size:0.875rem;font-weight:700;color:#15803D;">Vous avez déjà évalué cette prestation. Merci !</span>
    </div>
    @endif

    {{-- Meta --}}
    <p class="dash-fade" style="animation-delay:200ms;text-align:center;font-size:0.75rem;color:#B0A89E;font-weight:500;">
        Réservation créée le {{ $booking->created_at->format('d/m/Y à H:i') }}
    </p>

</div>
@endsection
