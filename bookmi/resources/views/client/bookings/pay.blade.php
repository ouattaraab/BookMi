@extends('layouts.client')

@section('title', 'Paiement — BookMi Client')

@section('head')
<style>
main.page-content { background: #F2EFE9 !important; }

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(22px); }
    to   { opacity: 1; transform: translateY(0); }
}
.dash-fade { opacity: 0; animation: fadeUp 0.6s cubic-bezier(0.16,1,0.3,1) forwards; }

/* ── Payment method card ── */
.pm-label {
    display: flex; align-items: center; gap: 10px;
    padding: 13px 14px;
    border-radius: 12px;
    border: 2px solid #E5E1DA;
    cursor: pointer;
    transition: all 0.18s cubic-bezier(0.16,1,0.3,1);
    background: #FFFFFF;
    position: relative;
    user-select: none;
    font-family: 'Nunito', sans-serif;
}
.pm-label:hover {
    border-color: #FF6B35;
    box-shadow: 0 2px 10px rgba(255,107,53,0.10);
}
.pm-label.selected {
    border-color: #FF6B35;
    background: #FFF8F5;
    box-shadow: 0 0 0 1px rgba(255,107,53,0.15), 0 4px 14px rgba(255,107,53,0.12);
}
.pm-check {
    position: absolute; top: 7px; right: 7px;
    width: 16px; height: 16px; border-radius: 50%;
    background: #FF6B35; display: none;
    align-items: center; justify-content: center;
    flex-shrink: 0;
}
.pm-label.selected .pm-check { display: flex; }

/* ── Light input ── */
.pay-input {
    width: 100%;
    background: #FFFFFF;
    border: 1.5px solid #E5E1DA;
    border-radius: 12px;
    padding: 11px 14px;
    font-size: 0.875rem;
    font-family: 'Nunito', sans-serif;
    color: #1A2744;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.pay-input::placeholder { color: #B0A89E; }
.pay-input:focus { border-color: #FF6B35; box-shadow: 0 0 0 3px rgba(255,107,53,0.10); }

/* ── OTP input ── */
.otp-input {
    text-align: center; font-size: 1.6rem; font-family: monospace;
    font-weight: 900; letter-spacing: 0.35em;
}

/* ── Pay CTA ── */
.pay-cta {
    width: 100%; padding: 15px 24px;
    border-radius: 14px;
    font-size: 0.95rem; font-weight: 900; color: white;
    background: linear-gradient(135deg, #FF6B35, #E85520);
    box-shadow: 0 4px 20px rgba(255,107,53,0.32);
    transition: transform 0.2s, box-shadow 0.2s, opacity 0.2s;
    border: none; cursor: pointer;
    font-family: 'Nunito', sans-serif;
}
.pay-cta:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 28px rgba(255,107,53,0.42); }
.pay-cta:disabled { opacity: 0.38; cursor: not-allowed; }
</style>
@endsection

@section('content')
@php
    $talentName = $booking->talentProfile->stage_name
        ?? trim(($booking->talentProfile->user->first_name ?? '') . ' ' . ($booking->talentProfile->user->last_name ?? ''))
        ?: '?';
    $isPending   = session('payment_pending');
    $methodLabel = session('payment_method_label');
@endphp

<div style="font-family:'Nunito',sans-serif;color:#1A2744;max-width:620px;">

    {{-- Flash errors --}}
    @if($errors->any())
    <div class="dash-fade" style="animation-delay:0ms;margin-bottom:16px;padding:14px 18px;border-radius:12px;font-size:0.875rem;font-weight:600;background:#FEF2F2;border:1px solid #FCA5A5;color:#991B1B;">
        <ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Flash sessions --}}
    @if(session('error'))
    <div class="dash-fade" style="animation-delay:0ms;margin-bottom:16px;padding:14px 18px;border-radius:12px;font-size:0.875rem;font-weight:600;background:#FEF2F2;border:1px solid #FCA5A5;color:#991B1B;">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="dash-fade" style="animation-delay:0ms;display:flex;align-items:center;gap:16px;margin-bottom:28px;">
        <a href="{{ route('client.bookings.show', $booking->id) }}"
           style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:12px;font-size:0.8rem;font-weight:700;background:#FFFFFF;border:1.5px solid #E5E1DA;color:#8A8278;text-decoration:none;box-shadow:0 1px 4px rgba(26,39,68,0.06);transition:all 0.2s;"
           onmouseover="this.style.borderColor='#FF6B35';this.style.color='#FF6B35'"
           onmouseout="this.style.borderColor='#E5E1DA';this.style.color='#8A8278'">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
            Retour
        </a>
        <div>
            <h1 style="font-size:1.8rem;font-weight:900;color:#1A2744;letter-spacing:-0.025em;margin:0 0 4px;line-height:1.15;">
                Paiement <span style="color:#FF6B35;">sécurisé</span>
            </h1>
            <p style="font-size:0.875rem;color:#8A8278;font-weight:500;margin:0;">Complétez votre paiement pour confirmer la réservation</p>
        </div>
    </div>

    {{-- ── Résumé de la commande ── --}}
    <div class="dash-fade" style="animation-delay:60ms;background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);overflow:hidden;margin-bottom:16px;">

        {{-- Talent header --}}
        <div style="padding:18px 22px;border-bottom:1px solid #EAE7E0;display:flex;align-items:center;gap:14px;">
            <div style="width:48px;height:48px;border-radius:13px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1.1rem;color:#fff;background:linear-gradient(135deg,#FF6B35,#E85520);">
                {{ strtoupper(substr($talentName, 0, 1)) }}
            </div>
            <div>
                <p style="font-weight:900;font-size:1rem;color:#1A2744;margin:0 0 2px;">{{ $talentName }}</p>
                @if($booking->servicePackage)
                <p style="font-size:0.78rem;color:#8A8278;font-weight:600;margin:0;">{{ $booking->servicePackage->name }}</p>
                @endif
            </div>
        </div>

        {{-- Détail financier --}}
        <div style="padding:16px 22px 20px;">
            @if($booking->cachet_amount)
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <span style="font-size:0.85rem;color:#6B7280;font-weight:600;">Cachet artiste</span>
                <span style="font-size:0.875rem;font-weight:800;color:#1A2744;">{{ number_format($booking->cachet_amount, 0, ',', ' ') }} <span style="color:#8A8278;font-weight:600;font-size:0.75rem;">FCFA</span></span>
            </div>
            @endif
            @if($booking->commission_amount)
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                <span style="font-size:0.85rem;color:#6B7280;font-weight:600;">Commission BookMi</span>
                <span style="font-size:0.875rem;font-weight:800;color:#1A2744;">{{ number_format($booking->commission_amount, 0, ',', ' ') }} <span style="color:#8A8278;font-weight:600;font-size:0.75rem;">FCFA</span></span>
            </div>
            @endif
            <div style="display:flex;justify-content:space-between;align-items:center;padding-top:14px;border-top:1px solid #E5E1DA;">
                <span style="font-size:0.95rem;font-weight:900;color:#1A2744;">Total à payer</span>
                <span style="font-size:1.3rem;font-weight:900;color:#FF6B35;">{{ number_format($booking->total_amount, 0, ',', ' ') }} <span style="font-size:0.8rem;font-weight:700;color:rgba(255,107,53,0.7);">FCFA</span></span>
            </div>
        </div>
    </div>

    {{-- ── OTP form (si paiement mobile en attente) ── --}}
    @if($isPending)
    <div class="dash-fade" style="animation-delay:120ms;background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);overflow:hidden;margin-bottom:16px;">
        <div style="padding:18px 22px;border-bottom:1px solid #EAE7E0;display:flex;align-items:center;gap:12px;">
            <div style="width:40px;height:40px;border-radius:11px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:#FFF3E0;border:1.5px solid #FCD34D;">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="#B45309" stroke-width="2"><path d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <h2 style="font-weight:900;font-size:0.95rem;color:#1A2744;margin:0 0 2px;">Entrez votre code OTP</h2>
                <p style="font-size:0.78rem;color:#8A8278;font-weight:600;margin:0;">Code envoyé via {{ $methodLabel }}</p>
            </div>
        </div>
        <div style="padding:20px 22px;">
            <div style="padding:13px 16px;border-radius:12px;font-size:0.83rem;font-weight:600;background:#FFF3E0;border:1.5px solid #FCD34D;color:#92400E;margin-bottom:18px;line-height:1.5;">
                Vérifiez votre téléphone et entrez le code reçu de <strong>{{ $methodLabel }}</strong> pour valider le paiement de <strong>{{ number_format($booking->total_amount, 0, ',', ' ') }} FCFA</strong>.
            </div>
            <form action="{{ route('client.bookings.pay.otp', $booking->id) }}" method="POST"
                  x-data="{ otp: '', submitting: false }" @submit="submitting = true">
                @csrf
                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#B0A89E;margin-bottom:8px;">Code OTP</label>
                    <input type="text" name="otp" x-model="otp"
                           inputmode="numeric" maxlength="8" autocomplete="one-time-code"
                           placeholder="000000"
                           class="pay-input otp-input" required autofocus>
                </div>
                <button type="submit" class="pay-cta"
                        :disabled="otp.length < 4 || submitting"
                        x-text="submitting ? 'Validation en cours…' : 'Valider le paiement'">
                </button>
            </form>
            <p style="text-align:center;font-size:0.75rem;color:#B0A89E;margin-top:14px;">
                Code non reçu ?
                <a href="{{ route('client.bookings.pay', $booking->id) }}" style="color:#FF6B35;text-decoration:underline;font-weight:700;">Recommencer le paiement</a>
            </p>
        </div>
    </div>

    {{-- ── Sélection méthode de paiement ── --}}
    @else
    <div class="dash-fade" style="animation-delay:120ms;background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);overflow:hidden;margin-bottom:16px;"
         x-data="{
            method: '',
            mobileMethods: ['orange_money', 'wave', 'mtn_momo', 'moov_money'],
            get isMobile() { return this.mobileMethods.includes(this.method) },
            get isCard()   { return this.method === 'card' },
            get isBank()   { return this.method === 'bank_transfer' },
            submitting: false
         }">

        <div style="padding:18px 22px;border-bottom:1px solid #EAE7E0;">
            <h2 style="font-weight:900;font-size:0.95rem;color:#1A2744;margin:0 0 3px;">Choisissez votre méthode de paiement</h2>
            <p style="font-size:0.78rem;color:#8A8278;font-weight:600;margin:0;">Paiement sécurisé via Paystack</p>
        </div>

        <form action="{{ route('client.bookings.pay.process', $booking->id) }}" method="POST"
              style="padding:20px 22px;" @submit="submitting = true">
            @csrf

            {{-- Mobile Money --}}
            <div style="margin-bottom:22px;">
                <p style="font-size:0.68rem;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;color:#B0A89E;margin:0 0 10px;">Mobile Money</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    @foreach([
                        ['value' => 'orange_money', 'label' => 'Orange Money', 'emoji' => '📱'],
                        ['value' => 'wave',         'label' => 'Wave',         'emoji' => '🌊'],
                        ['value' => 'mtn_momo',     'label' => 'MTN MoMo',    'emoji' => '📲'],
                        ['value' => 'moov_money',   'label' => 'Moov Money',  'emoji' => '💳'],
                    ] as $m)
                    <label class="pm-label" :class="method === '{{ $m['value'] }}' ? 'selected' : ''">
                        <input type="radio" name="payment_method" value="{{ $m['value'] }}"
                               x-model="method" style="position:absolute;opacity:0;width:0;height:0;">
                        <span style="font-size:1.2rem;line-height:1;">{{ $m['emoji'] }}</span>
                        <span style="font-size:0.82rem;font-weight:800;color:#1A2744;">{{ $m['label'] }}</span>
                        <div class="pm-check">
                            <svg width="8" height="8" fill="white" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </div>
                    </label>
                    @endforeach
                </div>

                {{-- Numéro de téléphone --}}
                <div x-show="isMobile" x-transition style="margin-top:12px;">
                    <label style="display:block;font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#B0A89E;margin-bottom:8px;">Numéro Mobile Money *</label>
                    <input type="tel" name="phone_number"
                           placeholder="+225 07 XX XX XX XX"
                           class="pay-input"
                           :required="isMobile">
                    <p style="font-size:0.72rem;color:#B0A89E;font-weight:500;margin:6px 0 0;">Format : +225XXXXXXXXXX (avec indicatif pays)</p>
                </div>
            </div>

            {{-- Carte & Virement --}}
            <div style="margin-bottom:22px;">
                <p style="font-size:0.68rem;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;color:#B0A89E;margin:0 0 10px;">Carte & Virement</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    @foreach([
                        ['value' => 'card',          'label' => 'Carte bancaire', 'emoji' => '💳'],
                        ['value' => 'bank_transfer',  'label' => 'Virement',       'emoji' => '🏦'],
                    ] as $m)
                    <label class="pm-label" :class="method === '{{ $m['value'] }}' ? 'selected' : ''">
                        <input type="radio" name="payment_method" value="{{ $m['value'] }}"
                               x-model="method" style="position:absolute;opacity:0;width:0;height:0;">
                        <span style="font-size:1.2rem;line-height:1;">{{ $m['emoji'] }}</span>
                        <span style="font-size:0.82rem;font-weight:800;color:#1A2744;">{{ $m['label'] }}</span>
                        <div class="pm-check">
                            <svg width="8" height="8" fill="white" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </div>
                    </label>
                    @endforeach
                </div>

                <div x-show="isCard || isBank" x-transition
                     style="margin-top:12px;padding:12px 15px;border-radius:12px;font-size:0.82rem;font-weight:600;background:#EFF6FF;border:1.5px solid #BFDBFE;color:#1D4ED8;line-height:1.5;">
                    <p x-show="isCard" style="margin:0;">Vous serez redirigé vers la page de paiement sécurisée Paystack pour entrer vos informations de carte.</p>
                    <p x-show="isBank" style="margin:0;">Vous serez redirigé vers Paystack pour obtenir les coordonnées bancaires et effectuer votre virement.</p>
                </div>
            </div>

            {{-- CTA --}}
            <button type="submit" class="pay-cta"
                    :disabled="!method || submitting"
                    x-text="submitting
                        ? 'Traitement en cours…'
                        : (method
                            ? 'Payer ' + ({{ $booking->total_amount ?? 0 }}).toLocaleString('fr-FR') + ' FCFA'
                            : 'Sélectionnez une méthode')">
            </button>

            {{-- Sécurité --}}
            <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:14px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#16A34A" stroke-width="2.5"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span style="font-size:0.75rem;font-weight:600;color:#8A8278;">Paiement sécurisé via <strong style="color:#1A2744;">Paystack</strong> — SSL 256 bits</span>
            </div>
        </form>
    </div>
    @endif

    {{-- ── Notice séquestre ── --}}
    <div class="dash-fade" style="animation-delay:200ms;display:flex;align-items:flex-start;gap:12px;padding:16px 18px;border-radius:14px;background:#F0FDF4;border:1.5px solid #86EFAC;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#15803D" stroke-width="2" style="flex-shrink:0;margin-top:1px;"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
            <p style="font-size:0.82rem;font-weight:800;color:#14532D;margin:0 0 4px;">Protection séquestre (escrow)</p>
            <p style="font-size:0.78rem;font-weight:500;color:#15803D;margin:0;line-height:1.6;">Votre paiement est sécurisé par un compte séquestre. Les fonds ne sont versés au talent qu'après confirmation de la prestation. En cas de litige, le montant peut être remboursé.</p>
        </div>
    </div>

</div>
@endsection
