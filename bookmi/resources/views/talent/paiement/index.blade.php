@extends('layouts.talent')

@section('title', 'Moyens de paiement — BookMi Talent')

@section('head')
<style>
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
}
.pay-fade { opacity: 0; animation: fadeUp 0.52s cubic-bezier(0.16,1,0.3,1) forwards; }

.tab-btn {
    padding: 9px 22px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 700;
    border: 1.5px solid transparent;
    cursor: pointer;
    transition: all 0.18s;
    white-space: nowrap;
}
.tab-btn.active {
    background: #FF6B35;
    color: #FFFFFF;
    border-color: #FF6B35;
    box-shadow: 0 4px 14px rgba(255,107,53,0.28);
}
.tab-btn:not(.active) {
    background: #FFFFFF;
    color: #8A8278;
    border-color: #E5E1DA;
}
.tab-btn:not(.active):hover {
    border-color: rgba(255,107,53,0.45);
    color: #FF6B35;
}

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

.method-btn {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 16px;
    border-radius: 12px;
    border: 2px solid #E5E1DA;
    cursor: pointer; transition: all 0.15s;
    background: #FFFFFF;
    color: #4A4540;
    font-size: 0.85rem; font-weight: 700;
    text-align: left; width: 100%;
}
.method-btn:hover { border-color: rgba(255,107,53,0.50); color: #FF6B35; }
.method-btn.selected { border-color: #FF6B35; background: #FFF4EF; color: #C85A20; }

.input-field {
    width: 100%;
    padding: 11px 14px;
    border-radius: 10px;
    border: 1.5px solid #E5E1DA;
    font-size: 0.875rem;
    font-family: 'Nunito', sans-serif;
    color: #1A2744;
    background: #FAFAF8;
    transition: border-color 0.15s, box-shadow 0.15s;
    outline: none;
    box-sizing: border-box;
}
.input-field:focus { border-color: #FF6B35; box-shadow: 0 0 0 3px rgba(255,107,53,0.12); background: #FFFFFF; }

.btn-primary {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 12px 28px;
    border-radius: 12px;
    background: #FF6B35;
    color: #FFFFFF;
    font-size: 0.875rem; font-weight: 800;
    border: none; cursor: pointer;
    transition: opacity 0.18s, transform 0.18s, box-shadow 0.18s;
    box-shadow: 0 4px 14px rgba(255,107,53,0.28);
    font-family: 'Nunito', sans-serif;
    text-decoration: none;
}
.btn-primary:hover { opacity: 0.88; transform: translateY(-1px); }
.btn-primary:active { transform: translateY(0); }

.withdrawal-row {
    padding: 13px 24px;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid #F5F3EF; gap: 12px;
    transition: background 0.15s;
}
.withdrawal-row:last-child { border-bottom: none; }
.withdrawal-row:hover { background: #FAFAF8; }
</style>
@endsection

@section('content')
@php
    $payoutMethodLabels = [
        'orange_money'  => 'Orange Money',
        'wave'          => 'Wave',
        'mtn_momo'      => 'MTN MoMo',
        'moov_money'    => 'Moov Money',
        'bank_transfer' => 'Virement bancaire',
        'card'          => 'Carte bancaire',
    ];
    $mobileMethods = ['orange_money', 'wave', 'mtn_momo', 'moov_money'];
    $currentMethod = $profile->payout_method ?? '';
    $currentDetails = $profile->payout_details ?? [];

    $statusStyles = [
        'pending'    => ['bg' => '#FFF3E0', 'text' => '#B45309', 'border' => '#FCD34D', 'label' => 'En attente'],
        'approved'   => ['bg' => '#EFF6FF', 'text' => '#1D4ED8', 'border' => '#93C5FD', 'label' => 'Approuvée'],
        'processing' => ['bg' => '#F5F3FF', 'text' => '#5B21B6', 'border' => '#C4B5FD', 'label' => 'En traitement'],
        'completed'  => ['bg' => '#F0FDF4', 'text' => '#15803D', 'border' => '#86EFAC', 'label' => 'Complétée'],
        'rejected'   => ['bg' => '#FEF2F2', 'text' => '#991B1B', 'border' => '#FCA5A5', 'label' => 'Rejetée'],
    ];

    $activeTab = request('tab', 'account');
@endphp

<div style="font-family:'Nunito',sans-serif;color:#1A2744;max-width:860px;" x-data="paymentPage()">

    {{-- Header --}}
    <div class="pay-fade" style="animation-delay:0ms;margin-bottom:28px;">
        <h1 style="font-size:1.85rem;font-weight:900;color:#1A2744;letter-spacing:-0.03em;margin:0 0 5px;line-height:1.15;">Moyens de paiement</h1>
        <p style="font-size:0.875rem;color:#8A8278;font-weight:500;margin:0;">Gérez votre compte de reversement et vos demandes de paiement</p>
    </div>

    {{-- Tabs --}}
    <div class="pay-fade" style="animation-delay:40ms;display:flex;gap:10px;margin-bottom:24px;flex-wrap:wrap;">
        <button
            class="tab-btn"
            :class="{ active: tab === 'account' }"
            @click="tab = 'account'"
            type="button"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:inline;vertical-align:middle;margin-right:5px;"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
            Mon compte de paiement
        </button>
        <button
            class="tab-btn"
            :class="{ active: tab === 'withdrawals' }"
            @click="tab = 'withdrawals'"
            type="button"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:inline;vertical-align:middle;margin-right:5px;"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            Reversements
            @if($withdrawals->where('status.value', 'pending')->count() > 0)
            <span style="background:#FF6B35;color:#fff;border-radius:9999px;font-size:0.65rem;font-weight:800;padding:1px 7px;margin-left:5px;">
                {{ $withdrawals->where('status.value', 'pending')->count() }}
            </span>
            @endif
        </button>
    </div>

    {{-- ─── TAB 1 : Mon compte ─── --}}
    <div x-show="tab === 'account'" x-cloak>

        {{-- Statut compte --}}
        <div class="pay-fade" style="animation-delay:80ms;margin-bottom:18px;">
            @if($currentMethod)
            <div style="display:flex;align-items:flex-start;gap:14px;padding:16px 20px;border-radius:16px;
                {{ $isVerified
                    ? 'background:#F0FDF4;border:1.5px solid rgba(21,128,61,0.30);'
                    : 'background:#FFFBEB;border:1.5px solid rgba(180,83,9,0.28);' }}">
                <div style="width:36px;height:36px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center;
                    {{ $isVerified ? 'background:#15803D;' : 'background:#B45309;' }}">
                    @if($isVerified)
                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                    @else
                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    @endif
                </div>
                <div>
                    @if($isVerified)
                    <p style="font-size:0.875rem;font-weight:800;color:#14532D;margin:0 0 3px;">Compte validé</p>
                    <p style="font-size:0.78rem;color:#15803D;margin:0;">
                        Validé le {{ $profile->payout_method_verified_at->format('d/m/Y à H:i') }}.
                        Vous pouvez effectuer des demandes de reversement.
                    </p>
                    @else
                    <p style="font-size:0.875rem;font-weight:800;color:#7C2D12;margin:0 0 3px;">En attente de validation</p>
                    <p style="font-size:0.78rem;color:#B45309;margin:0;">
                        Votre compte a été enregistré et est en attente de validation par l'administration.
                        Vous recevrez une notification une fois validé.
                    </p>
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- Formulaire de compte --}}
        <div class="section-card pay-fade" style="animation-delay:120ms;">
            <div class="section-header">
                <div class="dot" style="background:#FF6B35;"></div>
                <h2 style="font-size:0.95rem;font-weight:900;color:#1A2744;margin:0;">{{ $currentMethod ? 'Modifier mon compte' : 'Enregistrer un compte' }}</h2>
            </div>
            <div style="padding:24px;">
                <form method="POST" action="{{ route('talent.paiement.account.update') }}" x-data="{ method: '{{ $currentMethod }}' }">
                    @csrf

                    {{-- Sélection méthode --}}
                    <div style="margin-bottom:22px;">
                        <p style="font-size:0.82rem;font-weight:700;color:#4A4540;margin:0 0 12px;">Choisissez votre méthode de paiement</p>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;">
                            @foreach($paymentMethods as $pm)
                            @php
                                $pmVal = $pm->value;
                                $pmLabel = $payoutMethodLabels[$pmVal] ?? $pmVal;
                            @endphp
                            <button
                                type="button"
                                class="method-btn"
                                :class="{ selected: method === '{{ $pmVal }}' }"
                                @click="method = '{{ $pmVal }}'"
                                x-bind:style="method === '{{ $pmVal }}' ? 'border-color:#FF6B35;background:#FFF4EF;color:#C85A20' : ''"
                            >
                                @if($pmVal === 'orange_money')
                                    <span style="font-size:1.1rem;">🟠</span>
                                @elseif($pmVal === 'wave')
                                    <span style="font-size:1.1rem;">🌊</span>
                                @elseif($pmVal === 'mtn_momo')
                                    <span style="font-size:1.1rem;">🟡</span>
                                @elseif($pmVal === 'moov_money')
                                    <span style="font-size:1.1rem;">🔵</span>
                                @elseif($pmVal === 'bank_transfer')
                                    <span style="font-size:1.1rem;">🏦</span>
                                @else
                                    <span style="font-size:1.1rem;">💳</span>
                                @endif
                                {{ $pmLabel }}
                            </button>
                            @endforeach
                        </div>
                        <input type="hidden" name="payout_method" x-bind:value="method">
                        @error('payout_method') <p style="color:#DC2626;font-size:0.78rem;margin:6px 0 0;">{{ $message }}</p> @enderror
                    </div>

                    {{-- Champs Mobile Money --}}
                    <div x-show="['orange_money','wave','mtn_momo','moov_money'].includes(method)" x-cloak style="margin-bottom:20px;">
                        <label style="display:block;font-size:0.82rem;font-weight:700;color:#4A4540;margin-bottom:6px;">
                            Numéro de téléphone
                        </label>
                        <input
                            type="text"
                            name="payout_details[phone]"
                            class="input-field"
                            placeholder="+225 07 XX XX XX XX"
                            value="{{ old('payout_details.phone', is_array($currentDetails) ? ($currentDetails['phone'] ?? '') : '') }}"
                        >
                        @error('payout_details.phone') <p style="color:#DC2626;font-size:0.78rem;margin:6px 0 0;">{{ $message }}</p> @enderror
                    </div>

                    {{-- Champs Virement bancaire --}}
                    <div x-show="method === 'bank_transfer'" x-cloak style="margin-bottom:20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div>
                            <label style="display:block;font-size:0.82rem;font-weight:700;color:#4A4540;margin-bottom:6px;">
                                Numéro de compte (IBAN/RIB)
                            </label>
                            <input
                                type="text"
                                name="payout_details[account_number]"
                                class="input-field"
                                placeholder="IBAN ou RIB"
                                value="{{ old('payout_details.account_number', is_array($currentDetails) ? ($currentDetails['account_number'] ?? '') : '') }}"
                            >
                            @error('payout_details.account_number') <p style="color:#DC2626;font-size:0.78rem;margin:6px 0 0;">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label style="display:block;font-size:0.82rem;font-weight:700;color:#4A4540;margin-bottom:6px;">
                                Code banque / BIC
                            </label>
                            <input
                                type="text"
                                name="payout_details[bank_code]"
                                class="input-field"
                                placeholder="BIC ou code banque"
                                value="{{ old('payout_details.bank_code', is_array($currentDetails) ? ($currentDetails['bank_code'] ?? '') : '') }}"
                            >
                            @error('payout_details.bank_code') <p style="color:#DC2626;font-size:0.78rem;margin:6px 0 0;">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Note admin --}}
                    <div style="background:#FFF4EF;border-radius:12px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="#FF6B35" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <p style="font-size:0.78rem;color:#B45309;font-weight:600;margin:0;">
                            Toute modification de compte nécessite une revalidation par l'administration avant de pouvoir effectuer un reversement.
                        </p>
                    </div>

                    <div style="display:flex;justify-content:flex-end;">
                        <button type="submit" class="btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            Enregistrer le compte
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ─── TAB 2 : Reversements ─── --}}
    <div x-show="tab === 'withdrawals'" x-cloak>

        {{-- Solde disponible --}}
        <div class="pay-fade" style="animation-delay:80ms;margin-bottom:18px;">
            <div style="background:linear-gradient(135deg,#FF6B35 0%,#C85A20 100%);border-radius:18px;padding:22px 24px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 6px 22px rgba(255,107,53,0.28);">
                <div>
                    <p style="font-size:0.75rem;font-weight:700;color:rgba(255,255,255,0.70);text-transform:uppercase;letter-spacing:0.08em;margin:0 0 5px;">Solde disponible</p>
                    <p style="font-size:2rem;font-weight:900;color:#FFFFFF;margin:0;letter-spacing:-0.04em;line-height:1.1;">
                        {{ number_format($availableBalance, 0, ',', ' ') }}
                        <span style="font-size:0.95rem;font-weight:600;color:rgba(255,255,255,0.65);">XOF</span>
                    </p>
                </div>
                <div style="width:48px;height:48px;border-radius:13px;background:rgba(255,255,255,0.18);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                </div>
            </div>
        </div>

        {{-- Formulaire de demande --}}
        <div class="section-card pay-fade" style="animation-delay:120ms;margin-bottom:20px;">
            <div class="section-header">
                <div class="dot" style="background:#15803D;"></div>
                <h2 style="font-size:0.95rem;font-weight:900;color:#1A2744;margin:0;">Demander un reversement</h2>
            </div>
            <div style="padding:24px;">
                @if(!$isVerified)
                <div style="background:#FFFBEB;border:1.5px solid rgba(180,83,9,0.28);border-radius:12px;padding:14px 18px;display:flex;align-items:flex-start;gap:10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#B45309" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <div>
                        <p style="font-size:0.875rem;font-weight:800;color:#7C2D12;margin:0 0 3px;">Compte non validé</p>
                        <p style="font-size:0.78rem;color:#B45309;margin:0;">
                            Enregistrez et faites valider votre compte de paiement avant de pouvoir demander un reversement.
                            <button type="button" @click="tab = 'account'" style="font-weight:700;color:#B45309;text-decoration:underline;border:none;background:none;cursor:pointer;padding:0;font-family:inherit;font-size:inherit;">
                                Configurer mon compte →
                            </button>
                        </p>
                    </div>
                </div>
                @elseif($hasActiveWithdrawal)
                <div style="background:#EFF6FF;border:1.5px solid rgba(29,78,216,0.25);border-radius:12px;padding:14px 18px;display:flex;align-items:flex-start;gap:10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#1D4ED8" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <p style="font-size:0.82rem;font-weight:600;color:#1D4ED8;margin:0;">
                        Vous avez une demande de reversement en cours. Attendez son traitement avant d'en soumettre une nouvelle.
                    </p>
                </div>
                @elseif($availableBalance <= 0)
                <div style="background:#F9FAFB;border:1.5px solid #E5E7EB;border-radius:12px;padding:14px 18px;text-align:center;">
                    <p style="font-size:0.82rem;color:#6B7280;margin:0;">
                        Votre solde disponible est de 0 XOF. Vos revenus seront disponibles après la confirmation d'une prestation.
                    </p>
                </div>
                @else
                <form method="POST" action="{{ route('talent.paiement.withdrawal.store') }}">
                    @csrf
                    <div style="display:flex;gap:12px;align-items:flex-end;">
                        <div style="flex:1;">
                            <label style="display:block;font-size:0.82rem;font-weight:700;color:#4A4540;margin-bottom:6px;">
                                Montant à reverser (XOF)
                            </label>
                            <input
                                type="number"
                                name="amount"
                                class="input-field"
                                placeholder="Montant minimum : 1 000 XOF"
                                min="1000"
                                max="{{ $availableBalance }}"
                                value="{{ old('amount') }}"
                            >
                            @error('amount') <p style="color:#DC2626;font-size:0.78rem;margin:6px 0 0;">{{ $message }}</p> @enderror
                            <p style="font-size:0.73rem;color:#8A8278;margin:5px 0 0;">
                                Disponible : <strong style="color:#1A2744;">{{ number_format($availableBalance, 0, ',', ' ') }} XOF</strong>
                                — Minimum : 1 000 XOF
                            </p>
                        </div>
                        <button type="submit" class="btn-primary" style="flex-shrink:0;"
                            onclick="return confirm('Confirmer la demande de reversement de ' + document.querySelector('[name=amount]').value + ' XOF ?')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                            Demander
                        </button>
                    </div>
                </form>
                @endif
            </div>
        </div>

        {{-- Historique des demandes --}}
        <div class="section-card pay-fade" style="animation-delay:160ms;">
            <div class="section-header">
                <div class="dot" style="background:#1A2744;"></div>
                <h2 style="font-size:0.95rem;font-weight:900;color:#1A2744;margin:0;">Historique des demandes</h2>
                @if($withdrawals->total() > 0)
                <span style="margin-left:auto;font-size:0.72rem;font-weight:700;color:#8A8278;background:#F5F3EF;border-radius:9999px;padding:3px 10px;">
                    {{ $withdrawals->total() }} demande{{ $withdrawals->total() > 1 ? 's' : '' }}
                </span>
                @endif
            </div>

            @if($withdrawals->isEmpty())
            <div style="padding:48px 24px;text-align:center;">
                <div style="width:52px;height:52px;border-radius:15px;background:#F5F3EF;border:1.5px solid #E5E1DA;display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="#8A8278" stroke-width="1.75" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                </div>
                <p style="font-size:0.875rem;font-weight:700;color:#8A8278;margin:0 0 4px;">Aucune demande effectuée</p>
                <p style="font-size:0.78rem;color:#B0A89E;margin:0;">Vos demandes de reversement apparaîtront ici.</p>
            </div>
            @else
            <div class="overflow-x-auto">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom:1px solid #EAE7E0;background:#FAFAF8;">
                            <th style="padding:10px 24px;text-align:left;font-size:0.70rem;font-weight:700;color:#8A8278;text-transform:uppercase;letter-spacing:0.06em;">Date</th>
                            <th style="padding:10px 16px;text-align:right;font-size:0.70rem;font-weight:700;color:#8A8278;text-transform:uppercase;letter-spacing:0.06em;">Montant (XOF)</th>
                            <th style="padding:10px 16px;text-align:left;font-size:0.70rem;font-weight:700;color:#8A8278;text-transform:uppercase;letter-spacing:0.06em;">Méthode</th>
                            <th style="padding:10px 16px;text-align:left;font-size:0.70rem;font-weight:700;color:#8A8278;text-transform:uppercase;letter-spacing:0.06em;">Statut</th>
                            <th style="padding:10px 24px;text-align:left;font-size:0.70rem;font-weight:700;color:#8A8278;text-transform:uppercase;letter-spacing:0.06em;">Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($withdrawals as $req)
                        @php
                            $sk = $req->status instanceof \BackedEnum ? $req->status->value : (string) $req->status;
                            $ss = $statusStyles[$sk] ?? ['bg' => '#F3F4F6', 'text' => '#6B7280', 'border' => '#E5E7EB', 'label' => $sk];
                            $methodVal = $req->payout_method instanceof \BackedEnum ? $req->payout_method->value : (string) $req->payout_method;
                            $methodLabel = $payoutMethodLabels[$methodVal] ?? $methodVal;
                        @endphp
                        <tr class="withdrawal-row">
                            <td style="padding:13px 24px;font-size:0.82rem;color:#6B6560;white-space:nowrap;">
                                {{ $req->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td style="padding:13px 16px;text-align:right;">
                                <span style="font-size:0.95rem;font-weight:900;color:#1A2744;letter-spacing:-0.02em;">
                                    {{ number_format($req->amount, 0, ',', ' ') }}
                                </span>
                            </td>
                            <td style="padding:13px 16px;font-size:0.82rem;color:#4A4540;white-space:nowrap;">
                                {{ $methodLabel }}
                            </td>
                            <td style="padding:13px 16px;">
                                <span style="display:inline-flex;align-items:center;font-size:0.70rem;font-weight:800;padding:3px 11px;border-radius:9999px;letter-spacing:0.03em;white-space:nowrap;
                                    background:{{ $ss['bg'] }};color:{{ $ss['text'] }};border:1.5px solid {{ $ss['border'] }}">
                                    {{ $ss['label'] }}
                                </span>
                            </td>
                            <td style="padding:13px 24px;font-size:0.78rem;color:#8A8278;">
                                {{ $req->note ?? '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($withdrawals->hasPages())
            <div style="padding:14px 24px;border-top:1px solid #EAE7E0;display:flex;justify-content:center;">
                {{ $withdrawals->links() }}
            </div>
            @endif
            @endif
        </div>
    </div>

</div>

@endsection

@section('scripts')
<script>
function paymentPage() {
    return {
        tab: '{{ request('tab', 'account') }}',
    };
}
</script>
@endsection
