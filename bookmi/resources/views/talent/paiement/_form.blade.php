{{--
    Partial: talent.paiement._form
    Variables:
        $currentMethod  (string)  – currently saved payout method value or ''
        $currentDetails (array)   – currently saved payout_details array or []
--}}
@php
    $mobileMethods = ['orange_money', 'wave', 'mtn_momo', 'moov_money'];
    $methodOptions = [
        'orange_money'  => ['label' => 'Orange Money',       'icon' => '🟠'],
        'wave'          => ['label' => 'Wave',                'icon' => '🌊'],
        'mtn_momo'      => ['label' => 'MTN MoMo',           'icon' => '🟡'],
        'moov_money'    => ['label' => 'Moov Money',         'icon' => '🔵'],
        'bank_transfer' => ['label' => 'Virement bancaire',  'icon' => '🏦'],
    ];
    $initPhone   = $currentDetails['phone']          ?? old('payout_details.phone',          '');
    $initAccount = $currentDetails['account_number'] ?? old('payout_details.account_number', '');
    $initCode    = $currentDetails['bank_code']      ?? old('payout_details.bank_code',      '');
    $initMethod  = old('payout_method', $currentMethod);
@endphp

<div class="section-card pay-fade" style="animation-delay:80ms;"
    x-data="{
        method: '{{ $initMethod }}',
        isMobile() { return ['orange_money','wave','mtn_momo','moov_money'].includes(this.method); },
        isBank()   { return this.method === 'bank_transfer'; }
    }">

    <div class="section-header">
        <div class="dot" style="background:#FF6B35;"></div>
        <h2 style="font-size:0.95rem;font-weight:900;color:#1A2744;margin:0;">
            {{ $currentMethod ? 'Modifier le compte de paiement' : 'Ajouter un compte de paiement' }}
        </h2>
    </div>

    <form method="POST" action="{{ route('talent.paiement.account.update') }}" style="padding:24px;">
        @csrf

        {{-- ── Sélecteur de méthode ── --}}
        <input type="hidden" name="payout_method" :value="method">

        <div style="margin-bottom:20px;">
            <p style="font-size:0.82rem;font-weight:700;color:#4A4540;margin:0 0 10px;">Méthode de paiement</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:10px;">
                @foreach($methodOptions as $value => $opt)
                <button type="button"
                    class="method-btn"
                    :class="{ selected: method === '{{ $value }}' }"
                    @click="method = '{{ $value }}'">
                    <span style="font-size:1.15rem;line-height:1;">{{ $opt['icon'] }}</span>
                    <span>{{ $opt['label'] }}</span>
                </button>
                @endforeach
            </div>
            @error('payout_method')
            <p style="color:#DC2626;font-size:0.78rem;margin:6px 0 0;">{{ $message }}</p>
            @enderror
        </div>

        {{-- ── Champ Téléphone (mobile money) ── --}}
        <div x-show="isMobile()" x-cloak style="margin-bottom:20px;">
            <label style="display:block;font-size:0.82rem;font-weight:700;color:#4A4540;margin-bottom:6px;">
                Numéro de téléphone <span style="color:#DC2626;">*</span>
            </label>
            <input type="tel" name="payout_details[phone]" class="input-field"
                placeholder="+225 07 XX XX XX XX"
                value="{{ $initPhone }}">
            @error('payout_details.phone')
            <p style="color:#DC2626;font-size:0.78rem;margin:6px 0 0;">{{ $message }}</p>
            @enderror
        </div>

        {{-- ── Champs Virement bancaire ── --}}
        <div x-show="isBank()" x-cloak style="margin-bottom:20px;">
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:0.82rem;font-weight:700;color:#4A4540;margin-bottom:6px;">
                    Numéro de compte (IBAN / RIB) <span style="color:#DC2626;">*</span>
                </label>
                <input type="text" name="payout_details[account_number]" class="input-field"
                    placeholder="Ex : CI006 01001 00300 0123456789 01"
                    value="{{ $initAccount }}">
                @error('payout_details.account_number')
                <p style="color:#DC2626;font-size:0.78rem;margin:6px 0 0;">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label style="display:block;font-size:0.82rem;font-weight:700;color:#4A4540;margin-bottom:6px;">
                    Code banque / SWIFT <span style="color:#DC2626;">*</span>
                </label>
                <input type="text" name="payout_details[bank_code]" class="input-field"
                    placeholder="Ex : SGBICIAB"
                    value="{{ $initCode }}">
                @error('payout_details.bank_code')
                <p style="color:#DC2626;font-size:0.78rem;margin:6px 0 0;">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- ── Informations ── --}}
        <div style="margin-bottom:20px;padding:12px 16px;border-radius:12px;background:#FAFAF8;border:1px solid #E5E1DA;">
            <p style="font-size:0.75rem;color:#6B6560;margin:0;line-height:1.55;">
                <strong style="color:#4A4540;">Note :</strong>
                Votre compte sera validé par l'administration sous 24h. Vous serez notifié par email et notification push une fois validé.
            </p>
        </div>

        {{-- ── Bouton soumettre ── --}}
        <div style="display:flex;justify-content:flex-end;">
            <button type="submit" class="btn-primary"
                x-bind:disabled="!method"
                x-bind:style="!method ? 'opacity:0.5;cursor:not-allowed;' : ''">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Enregistrer le compte
            </button>
        </div>

    </form>
</div>
