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

.btn-outline {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 10px 22px;
    border-radius: 12px;
    background: transparent;
    color: #FF6B35;
    font-size: 0.875rem; font-weight: 700;
    border: 1.5px solid #FF6B35; cursor: pointer;
    transition: background 0.15s;
    font-family: 'Nunito', sans-serif;
}
.btn-outline:hover { background: #FFF4EF; }

.btn-danger-text {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 18px;
    border-radius: 12px;
    background: transparent;
    color: #DC2626;
    font-size: 0.82rem; font-weight: 700;
    border: 1.5px solid rgba(220,38,38,0.30); cursor: pointer;
    transition: background 0.15s;
    font-family: 'Nunito', sans-serif;
}
.btn-danger-text:hover { background: #FEF2F2; }

.summary-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #F5F3EF;
    font-size: 0.85rem;
}
.summary-row:last-child { border-bottom: none; }

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
    $mobileMethods  = ['orange_money', 'wave', 'mtn_momo', 'moov_money'];
    $currentMethod  = $profile->payout_method ?? '';
    $currentDetails = $profile->payout_details ?? [];
    $isMobile       = in_array($currentMethod, $mobileMethods);

    $methodIcons = [
        'orange_money'  => '🟠',
        'wave'          => '🌊',
        'mtn_momo'      => '🟡',
        'moov_money'    => '🔵',
        'bank_transfer' => '🏦',
        'card'          => '💳',
    ];

    $statusStyles = [
        'pending'    => ['bg' => '#FFF3E0', 'text' => '#B45309', 'border' => '#FCD34D', 'label' => 'En attente'],
        'approved'   => ['bg' => '#EFF6FF', 'text' => '#1D4ED8', 'border' => '#93C5FD', 'label' => 'Approuvée'],
        'processing' => ['bg' => '#F5F3FF', 'text' => '#5B21B6', 'border' => '#C4B5FD', 'label' => 'En traitement'],
        'completed'  => ['bg' => '#F0FDF4', 'text' => '#15803D', 'border' => '#86EFAC', 'label' => 'Complétée'],
        'rejected'   => ['bg' => '#FEF2F2', 'text' => '#991B1B', 'border' => '#FCA5A5', 'label' => 'Rejetée'],
    ];

    $activeTab          = request('tab', 'account');
    $hasActiveWithdrawals = $withdrawals->whereIn('status.value', ['pending', 'approved', 'processing'])->isNotEmpty()
        || $withdrawals->filter(fn($r) => in_array(
            $r->status instanceof \BackedEnum ? $r->status->value : (string) $r->status,
            ['pending','approved','processing']
        ))->isNotEmpty();
@endphp

<div style="font-family:'Nunito',sans-serif;color:#1A2744;max-width:860px;"
    x-data="paymentPage({{ $isVerified ? 'true' : 'false' }})">

    {{-- Header --}}
    <div class="pay-fade" style="animation-delay:0ms;margin-bottom:28px;">
        <h1 style="font-size:1.85rem;font-weight:900;color:#1A2744;letter-spacing:-0.03em;margin:0 0 5px;line-height:1.15;">Moyens de paiement</h1>
        <p style="font-size:0.875rem;color:#8A8278;font-weight:500;margin:0;">Gérez votre compte de reversement et vos demandes de paiement</p>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="pay-fade" style="animation-delay:20ms;margin-bottom:18px;padding:14px 18px;border-radius:14px;background:#F0FDF4;border:1.5px solid rgba(21,128,61,0.30);display:flex;align-items:center;gap:10px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#15803D" stroke-width="2.5" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <p style="font-size:0.85rem;font-weight:700;color:#14532D;margin:0;">{{ session('success') }}</p>
    </div>
    @endif
    @if(session('error'))
    <div class="pay-fade" style="animation-delay:20ms;margin-bottom:18px;padding:14px 18px;border-radius:14px;background:#FEF2F2;border:1.5px solid rgba(220,38,38,0.30);display:flex;align-items:center;gap:10px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#DC2626" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <p style="font-size:0.85rem;font-weight:700;color:#991B1B;margin:0;">{{ session('error') }}</p>
    </div>
    @endif

    {{-- Tabs --}}
    <div class="pay-fade" style="animation-delay:40ms;display:flex;gap:10px;margin-bottom:24px;flex-wrap:wrap;">
        <button class="tab-btn" :class="{ active: tab === 'account' }" @click="tab = 'account'" type="button">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:inline;vertical-align:middle;margin-right:5px;"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
            Mon compte de paiement
        </button>
        <button class="tab-btn" :class="{ active: tab === 'withdrawals' }" @click="tab = 'withdrawals'" type="button">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:inline;vertical-align:middle;margin-right:5px;"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            Reversements
            @php
                $pendingCount = $withdrawals->filter(fn($r) =>
                    ($r->status instanceof \BackedEnum ? $r->status->value : (string) $r->status) === 'pending'
                )->count();
            @endphp
            @if($pendingCount > 0)
            <span style="background:#FF6B35;color:#fff;border-radius:9999px;font-size:0.65rem;font-weight:800;padding:1px 7px;margin-left:5px;">{{ $pendingCount }}</span>
            @endif
        </button>
    </div>

    {{-- ─── TAB 1 : Mon compte ─── --}}
    <div x-show="tab === 'account'" x-cloak>

        @if($isVerified)

            {{-- ── Bannière validé ── --}}
            <div class="pay-fade" style="animation-delay:80ms;margin-bottom:18px;">
                <div style="display:flex;align-items:flex-start;gap:14px;padding:16px 20px;border-radius:16px;background:#F0FDF4;border:1.5px solid rgba(21,128,61,0.30);">
                    <div style="width:36px;height:36px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:#15803D;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                    </div>
                    <div>
                        <p style="font-size:0.875rem;font-weight:800;color:#14532D;margin:0 0 3px;">Compte validé</p>
                        <p style="font-size:0.78rem;color:#15803D;margin:0;">
                            Validé le {{ $profile->payout_method_verified_at->format('d/m/Y à H:i') }}.
                            Vous pouvez effectuer des demandes de reversement.
                        </p>
                    </div>
                </div>
            </div>

            {{-- ── Carte récapitulative (visible quand pas en mode ajout) ── --}}
            <div class="section-card pay-fade" style="animation-delay:120ms;margin-bottom:18px;" x-show="!addingNew">
                <div class="section-header">
                    <div class="dot" style="background:#FF6B35;"></div>
                    <h2 style="font-size:0.95rem;font-weight:900;color:#1A2744;margin:0;">Mon compte de paiement</h2>
                </div>
                <div style="padding:20px 24px;">
                    {{-- Méthode --}}
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;padding-bottom:16px;border-bottom:1px solid #F5F3EF;">
                        <div style="width:42px;height:42px;border-radius:12px;background:#FFF4EF;border:1.5px solid rgba(255,107,53,0.25);display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0;">
                            {{ $methodIcons[$currentMethod] ?? '💳' }}
                        </div>
                        <div>
                            <p style="font-size:1rem;font-weight:900;color:#1A2744;margin:0;">{{ $payoutMethodLabels[$currentMethod] ?? $currentMethod }}</p>
                            <p style="font-size:0.75rem;color:#8A8278;margin:2px 0 0;font-weight:500;">Compte validé</p>
                        </div>
                    </div>
                    {{-- Détails --}}
                    <div>
                        @if($isMobile)
                        <div class="summary-row">
                            <span style="color:#8A8278;font-weight:600;">Numéro</span>
                            <span style="font-weight:800;color:#1A2744;">{{ $currentDetails['phone'] ?? '—' }}</span>
                        </div>
                        @else
                        <div class="summary-row">
                            <span style="color:#8A8278;font-weight:600;">N° de compte</span>
                            <span style="font-weight:800;color:#1A2744;">{{ $currentDetails['account_number'] ?? '—' }}</span>
                        </div>
                        @if(!empty($currentDetails['bank_code']))
                        <div class="summary-row">
                            <span style="color:#8A8278;font-weight:600;">Code banque</span>
                            <span style="font-weight:800;color:#1A2744;">{{ $currentDetails['bank_code'] }}</span>
                        </div>
                        @endif
                        @endif
                        <div class="summary-row">
                            <span style="color:#8A8278;font-weight:600;">Validé le</span>
                            <span style="font-weight:800;color:#1A2744;">{{ $profile->payout_method_verified_at->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Boutons d'action (mode récapitulatif) ── --}}
            <div class="pay-fade" style="animation-delay:160ms;display:flex;gap:12px;flex-wrap:wrap;" x-show="!addingNew">
                <button type="button" class="btn-outline" @click="addingNew = true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Ajouter un nouveau compte
                </button>
                <form method="POST" action="{{ route('talent.paiement.account.delete') }}" style="margin:0;"
                    onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce compte de paiement ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger-text">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                        Supprimer ce compte
                    </button>
                </form>
            </div>

            {{-- ── Bouton Annuler + formulaire (mode ajout depuis état validé) ── --}}
            <div x-show="addingNew">
                <div class="pay-fade" style="margin-bottom:16px;">
                    <button type="button" @click="addingNew = false"
                        style="display:inline-flex;align-items:center;gap:6px;font-size:0.82rem;color:#8A8278;font-weight:600;background:none;border:none;cursor:pointer;padding:0;font-family:'Nunito',sans-serif;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                        Annuler
                    </button>
                </div>
                @include('talent.paiement._form', ['currentMethod' => '', 'currentDetails' => []])
            </div>

        @else

            {{-- ── Bannière pending / rejected ── --}}
            @if($payoutMethodStatus === 'pending')
            <div class="pay-fade" style="animation-delay:80ms;margin-bottom:18px;">
                <div style="display:flex;align-items:flex-start;gap:14px;padding:16px 20px;border-radius:16px;background:#FFFBEB;border:1.5px solid rgba(180,83,9,0.28);">
                    <div style="width:36px;height:36px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:#B45309;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <p style="font-size:0.875rem;font-weight:800;color:#7C2D12;margin:0 0 3px;">En attente de validation</p>
                        <p style="font-size:0.78rem;color:#B45309;margin:0;">
                            Votre compte est en attente de validation par l'administration. Vous recevrez une notification une fois validé.
                        </p>
                    </div>
                </div>
            </div>
            @elseif($payoutMethodStatus === 'rejected')
            <div class="pay-fade" style="animation-delay:80ms;margin-bottom:18px;">
                <div style="display:flex;align-items:flex-start;gap:14px;padding:16px 20px;border-radius:16px;background:#FEF2F2;border:1.5px solid rgba(220,38,38,0.30);">
                    <div style="width:36px;height:36px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:#DC2626;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    </div>
                    <div>
                        <p style="font-size:0.875rem;font-weight:800;color:#991B1B;margin:0 0 3px;">Compte refusé</p>
                        @if($rejectionReason)
                        <p style="font-size:0.78rem;color:#DC2626;margin:0 0 4px;">Motif : {{ $rejectionReason }}</p>
                        @endif
                        <p style="font-size:0.78rem;color:#B91C1C;margin:0;">Corrigez les informations ci-dessous et enregistrez à nouveau.</p>
                    </div>
                </div>
            </div>
            @endif

            {{-- ── Formulaire (état non validé) ── --}}
            @include('talent.paiement._form', ['currentMethod' => $currentMethod, 'currentDetails' => $currentDetails])

        @endif

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
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                </div>
            </div>
        </div>

        {{-- ── Historique EN PREMIER (quand il existe) ── --}}
        @if($withdrawals->isNotEmpty())
        <div class="section-card pay-fade" style="animation-delay:100ms;margin-bottom:20px;">
            <div class="section-header">
                <div class="dot" style="background:#1A2744;"></div>
                <h2 style="font-size:0.95rem;font-weight:900;color:#1A2744;margin:0;">Historique des demandes</h2>
                <span style="margin-left:auto;font-size:0.72rem;font-weight:700;color:#8A8278;background:#F5F3EF;border-radius:9999px;padding:3px 10px;">
                    {{ $withdrawals->total() }} demande{{ $withdrawals->total() > 1 ? 's' : '' }}
                </span>
            </div>
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
                            $methodVal   = $req->payout_method instanceof \BackedEnum ? $req->payout_method->value : (string) $req->payout_method;
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
                            <td style="padding:13px 16px;font-size:0.82rem;color:#4A4540;white-space:nowrap;">{{ $methodLabel }}</td>
                            <td style="padding:13px 16px;">
                                <span style="display:inline-flex;align-items:center;font-size:0.70rem;font-weight:800;padding:3px 11px;border-radius:9999px;letter-spacing:0.03em;white-space:nowrap;
                                    background:{{ $ss['bg'] }};color:{{ $ss['text'] }};border:1.5px solid {{ $ss['border'] }}">
                                    {{ $ss['label'] }}
                                </span>
                            </td>
                            <td style="padding:13px 24px;font-size:0.78rem;color:#8A8278;">{{ $req->note ?? '—' }}</td>
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
        </div>
        @endif

        {{-- ── Formulaire de demande (après l'historique) ── --}}
        <div class="section-card pay-fade" style="animation-delay:{{ $withdrawals->isNotEmpty() ? '140' : '120' }}ms;">
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
                            <input type="number" name="amount" class="input-field"
                                placeholder="Montant minimum : 1 000 XOF"
                                min="1000" max="{{ $availableBalance }}"
                                value="{{ old('amount') }}">
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

    </div>

</div>

@endsection

@section('scripts')
<script>
function paymentPage(isVerified) {
    return {
        tab: '{{ request('tab', 'account') }}',
        addingNew: false,
        isVerified: isVerified,
    };
}

// Auto-refresh every 15s if there are active withdrawal requests
@if($hasActiveWithdrawals)
(function() {
    setTimeout(function() { window.location.reload(); }, 15000);
})();
@endif
</script>
@endsection
