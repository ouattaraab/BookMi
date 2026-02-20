@extends('layouts.client')

@section('title', 'Paiement — BookMi Client')

@section('head')
<style>
/* ── Summary card ── */
.summary-card {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 1.125rem;
    overflow: hidden;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}
/* ── Payment method card ── */
.pm-label {
    display: flex; align-items: center; gap: 0.625rem;
    padding: 0.75rem 0.875rem;
    border-radius: 0.75rem;
    border: 2px solid var(--glass-border);
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16,1,0.3,1);
    background: var(--glass-bg);
    position: relative;
    user-select: none;
}
.pm-label:hover {
    border-color: rgba(100,181,246,0.25);
    background: rgba(255,255,255,0.06);
}
.pm-label.selected {
    border-color: rgba(33,150,243,0.55);
    background: rgba(33,150,243,0.08);
    box-shadow: 0 0 0 1px rgba(33,150,243,0.20), 0 4px 14px rgba(33,150,243,0.15);
}
.pm-check {
    position: absolute; top: 0.5rem; right: 0.5rem;
    width: 1rem; height: 1rem; border-radius: 50%;
    background: var(--blue); display: none;
    align-items: center; justify-content: center;
}
.pm-label.selected .pm-check { display: flex; }
/* ── Dark input ── */
.pay-input {
    width: 100%;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 0.75rem;
    padding: 0.7rem 1rem;
    font-size: 0.875rem;
    font-family: 'Nunito', sans-serif;
    color: rgba(255,255,255,0.90);
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.pay-input::placeholder { color: rgba(255,255,255,0.28); }
.pay-input:focus { border-color: rgba(100,181,246,0.50); box-shadow: 0 0 0 3px rgba(100,181,246,0.10); }
/* ── OTP input ── */
.otp-input {
    text-align: center; font-size: 1.5rem; font-family: monospace; font-weight: 900; letter-spacing: 0.3em;
}
/* ── Section label ── */
.section-label {
    font-size: 0.7rem; font-weight: 800; letter-spacing: 0.10em; text-transform: uppercase;
    color: var(--text-faint); margin-bottom: 0.75rem;
}
/* ── Pay CTA ── */
.pay-cta {
    width: 100%; padding: 1rem 1.5rem;
    border-radius: 0.875rem;
    font-size: 1rem; font-weight: 900; color: white;
    background: linear-gradient(135deg, #1565C0, #2196F3);
    box-shadow: 0 4px 20px rgba(33,150,243,0.38);
    transition: transform 0.2s, box-shadow 0.2s, opacity 0.2s;
    border: none; cursor: pointer;
}
.pay-cta:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 28px rgba(33,150,243,0.48); }
.pay-cta:disabled { opacity: 0.38; cursor: not-allowed; }
/* ── Back link ── */
.back-link {
    display: inline-flex; align-items: center; gap: 0.35rem;
    font-size: 0.8rem; font-weight: 700;
    color: var(--text-muted); text-decoration: none;
    transition: color 0.15s;
}
.back-link:hover { color: var(--text); }
/* ── Reveal ── */
.reveal-item { opacity:0; transform:translateY(16px); transition:opacity 0.45s cubic-bezier(0.16,1,0.3,1), transform 0.45s cubic-bezier(0.16,1,0.3,1); }
.reveal-item.visible { opacity:1; transform:none; }
</style>
@endsection

@section('content')
@php
    $talentName = $booking->talentProfile->stage_name
        ?? trim(($booking->talentProfile->user->first_name ?? '') . ' ' . ($booking->talentProfile->user->last_name ?? ''))
        ?: '?';
    $isPending = session('payment_pending');
    $methodLabel = session('payment_method_label');
@endphp
<div class="max-w-xl space-y-6">

    {{-- Errors --}}
    @if($errors->any())
    <div class="p-4 rounded-xl text-sm" style="background:rgba(244,67,54,0.12);border:1px solid rgba(244,67,54,0.25);color:rgba(252,165,165,0.95)">
        <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Back --}}
    <a href="{{ route('client.bookings.show', $booking->id) }}" class="back-link reveal-item">
        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M15 19l-7-7 7-7"/></svg>
        Retour à la réservation
    </a>

    {{-- Header --}}
    <div class="reveal-item" style="transition-delay:0.04s">
        <h1 class="section-title">Paiement sécurisé</h1>
        <p class="section-sub">Complétez votre paiement pour confirmer la réservation</p>
    </div>

    {{-- ── Summary ── --}}
    <div class="summary-card reveal-item" style="transition-delay:0.08s">
        <div class="flex items-center gap-4 px-5 py-4" style="border-bottom:1px solid var(--glass-border)">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-black text-lg flex-shrink-0"
                 style="background:linear-gradient(135deg,var(--navy),var(--blue));box-shadow:0 0 14px rgba(33,150,243,0.28)">
                {{ strtoupper(substr($talentName, 0, 1)) }}
            </div>
            <div>
                <p class="font-black text-sm" style="color:var(--text)">{{ $talentName }}</p>
                @if($booking->servicePackage)
                    <p class="text-xs" style="color:var(--text-muted)">{{ $booking->servicePackage->name }}</p>
                @endif
            </div>
        </div>

        <div class="px-5 py-4 space-y-2.5">
            @if($booking->cachet_amount)
            <div class="flex justify-between text-sm">
                <span style="color:var(--text-muted)">Cachet artiste</span>
                <span class="font-semibold" style="color:var(--text)">{{ number_format($booking->cachet_amount, 0, ',', ' ') }} XOF</span>
            </div>
            @endif
            @if($booking->commission_amount)
            <div class="flex justify-between text-sm">
                <span style="color:var(--text-muted)">Commission plateforme</span>
                <span class="font-semibold" style="color:var(--text)">{{ number_format($booking->commission_amount, 0, ',', ' ') }} XOF</span>
            </div>
            @endif
            <div class="flex justify-between pt-2.5" style="border-top:1px solid var(--glass-border)">
                <span class="font-black" style="color:var(--text)">Total</span>
                <span class="text-xl font-black" style="color:var(--blue-light)">{{ number_format($booking->total_amount, 0, ',', ' ') }} XOF</span>
            </div>
        </div>
    </div>

    {{-- ── OTP form ── --}}
    @if($isPending)
    <div class="summary-card reveal-item" style="transition-delay:0.12s">
        <div class="flex items-center gap-3 px-5 py-4" style="border-bottom:1px solid var(--glass-border)">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(255,152,0,0.10);border:1px solid rgba(255,152,0,0.22)">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#FF9800" stroke-width="2"><path d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <h2 class="font-black text-sm" style="color:var(--text)">Entrez votre code OTP</h2>
                <p class="text-xs" style="color:var(--text-muted)">Code envoyé via {{ $methodLabel }}</p>
            </div>
        </div>
        <div class="p-5 space-y-4">
            <div class="p-3.5 rounded-xl text-sm" style="background:rgba(255,152,0,0.08);border:1px solid rgba(255,152,0,0.20);color:rgba(253,186,116,0.90)">
                Vérifiez votre téléphone et entrez le code reçu de <strong>{{ $methodLabel }}</strong> pour valider le paiement de <strong>{{ number_format($booking->total_amount, 0, ',', ' ') }} XOF</strong>.
            </div>
            <form action="{{ route('client.bookings.pay.otp', $booking->id) }}" method="POST"
                  x-data="{ otp: '', submitting: false }" @submit="submitting = true">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs font-bold mb-2" style="color:var(--text-muted)">Code OTP</label>
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
            <p class="text-xs text-center" style="color:var(--text-faint)">
                Code non reçu ?
                <a href="{{ route('client.bookings.pay', $booking->id) }}" style="color:var(--blue-light);text-decoration:underline">Recommencer le paiement</a>
            </p>
        </div>
    </div>

    {{-- ── Payment method selection ── --}}
    @else
    <div class="summary-card reveal-item" style="transition-delay:0.12s"
         x-data="{
            method: '',
            mobileMethods: ['orange_money', 'wave', 'mtn_momo', 'moov_money'],
            get isMobile() { return this.mobileMethods.includes(this.method) },
            get isCard()   { return this.method === 'card' },
            get isBank()   { return this.method === 'bank_transfer' },
            submitting: false
         }">

        <div class="px-5 py-4" style="border-bottom:1px solid var(--glass-border)">
            <h2 class="font-black text-sm" style="color:var(--text)">Choisissez votre méthode de paiement</h2>
            <p class="text-xs mt-0.5" style="color:var(--text-muted)">Paiement sécurisé via Paystack</p>
        </div>

        <form action="{{ route('client.bookings.pay.process', $booking->id) }}" method="POST"
              class="p-5 space-y-6" @submit="submitting = true">
            @csrf

            {{-- Mobile Money --}}
            <div>
                <p class="section-label">Mobile Money</p>
                <div class="grid grid-cols-2 gap-2.5">
                    @foreach([
                        ['value' => 'orange_money', 'label' => 'Orange Money', 'emoji' => '📱'],
                        ['value' => 'wave',         'label' => 'Wave',         'emoji' => '🌊'],
                        ['value' => 'mtn_momo',     'label' => 'MTN MoMo',    'emoji' => '📲'],
                        ['value' => 'moov_money',   'label' => 'Moov Money',  'emoji' => '💳'],
                    ] as $m)
                    <label class="pm-label" :class="method === '{{ $m['value'] }}' ? 'selected' : ''">
                        <input type="radio" name="payment_method" value="{{ $m['value'] }}"
                               x-model="method" class="sr-only">
                        <span class="text-lg leading-none">{{ $m['emoji'] }}</span>
                        <span class="text-sm font-bold" style="color:var(--text)">{{ $m['label'] }}</span>
                        <div class="pm-check">
                            <svg width="8" height="8" fill="white" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </div>
                    </label>
                    @endforeach
                </div>

                {{-- Phone number --}}
                <div x-show="isMobile" x-transition class="mt-3">
                    <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted)">Numéro de téléphone Mobile Money *</label>
                    <input type="tel" name="phone_number"
                           placeholder="+225 07 XX XX XX XX"
                           class="pay-input"
                           :required="isMobile">
                    <p class="text-xs mt-1" style="color:var(--text-faint)">Format : +225XXXXXXXXXX (avec indicatif pays)</p>
                </div>
            </div>

            {{-- Card & Bank --}}
            <div>
                <p class="section-label">Carte & Virement</p>
                <div class="grid grid-cols-2 gap-2.5">
                    @foreach([
                        ['value' => 'card',         'label' => 'Carte bancaire', 'emoji' => '💳'],
                        ['value' => 'bank_transfer', 'label' => 'Virement',      'emoji' => '🏦'],
                    ] as $m)
                    <label class="pm-label" :class="method === '{{ $m['value'] }}' ? 'selected' : ''">
                        <input type="radio" name="payment_method" value="{{ $m['value'] }}"
                               x-model="method" class="sr-only">
                        <span class="text-lg leading-none">{{ $m['emoji'] }}</span>
                        <span class="text-sm font-bold" style="color:var(--text)">{{ $m['label'] }}</span>
                        <div class="pm-check">
                            <svg width="8" height="8" fill="white" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </div>
                    </label>
                    @endforeach
                </div>

                <div x-show="isCard || isBank" x-transition class="mt-3 p-3.5 rounded-xl text-xs" style="background:rgba(33,150,243,0.07);border:1px solid rgba(33,150,243,0.18);color:rgba(147,197,253,0.90)">
                    <p x-show="isCard">Vous serez redirigé vers la page de paiement sécurisée Paystack pour entrer vos informations de carte.</p>
                    <p x-show="isBank">Vous serez redirigé vers Paystack pour obtenir les coordonnées bancaires et effectuer votre virement.</p>
                </div>
            </div>

            {{-- CTA --}}
            <div class="pt-1">
                <button type="submit" class="pay-cta"
                        :disabled="!method || submitting"
                        x-text="submitting
                            ? 'Traitement en cours…'
                            : (method
                                ? 'Payer ' + {{ $booking->total_amount ?? 0 }} .toLocaleString('fr-FR') + ' XOF'
                                : 'Sélectionnez une méthode')">
                </button>

                <div class="flex items-center justify-center gap-1.5 mt-3 text-xs" style="color:var(--text-faint)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#4CAF50" stroke-width="2"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span>Paiement sécurisé via <strong style="color:rgba(255,255,255,0.55)">Paystack</strong> — SSL 256 bits</span>
                </div>
            </div>
        </form>
    </div>
    @endif

    {{-- Escrow notice --}}
    <div class="flex items-start gap-3 p-4 rounded-xl text-xs reveal-item" style="transition-delay:0.20s;background:rgba(33,150,243,0.05);border:1px solid rgba(33,150,243,0.12);color:var(--text-muted)">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#64B5F6" stroke-width="2" class="flex-shrink-0 mt-0.5"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
            <p class="font-bold mb-0.5" style="color:rgba(255,255,255,0.65)">Protection séquestre (escrow)</p>
            <p>Votre paiement est sécurisé par un compte séquestre. Les fonds ne sont versés au talent qu'après confirmation de la prestation. En cas de litige, le montant peut être remboursé.</p>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const io = new IntersectionObserver(entries => {
        entries.forEach(e => { if(e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); } });
    }, { threshold: 0.04 });
    document.querySelectorAll('.reveal-item').forEach(el => io.observe(el));
});
</script>
@endsection
