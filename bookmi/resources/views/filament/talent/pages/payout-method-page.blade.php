<x-filament-panels::page>

    {{-- ── Solde disponible ─────────────────────────────────────────────── --}}
    @if($profile)
        <div class="mb-6 rounded-xl bg-primary-50 border border-primary-200 dark:bg-primary-900/20 dark:border-primary-800 p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-primary-700 dark:text-primary-300 font-medium">Solde disponible</p>
                <p class="text-2xl font-bold text-primary-900 dark:text-primary-100 mt-0.5">
                    {{ number_format($availableBalance, 0, ',', ' ') }} XOF
                </p>
            </div>
            <x-heroicon-o-wallet class="w-10 h-10 text-primary-400" />
        </div>
    @endif

    @if($isVerified && !$this->showingForm)

        {{-- ── Vue récapitulative (compte validé) ───────────────────────── --}}
        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 dark:bg-green-900/20 dark:border-green-800 p-4 flex items-start gap-3">
            <x-heroicon-o-check-badge class="w-6 h-6 text-green-600 dark:text-green-400 shrink-0 mt-0.5" />
            <div>
                <p class="font-semibold text-green-800 dark:text-green-200">Compte validé</p>
                <p class="text-sm text-green-700 dark:text-green-300 mt-0.5">
                    Votre compte a été validé le {{ $verifiedAt?->format('d/m/Y à H:i') }}.
                    Vous pouvez désormais effectuer des demandes de reversement.
                </p>
            </div>
        </div>

        @php
            $methodLabels = [
                'orange_money'  => 'Orange Money',
                'wave'          => 'Wave',
                'mtn_momo'      => 'MTN Mobile Money',
                'moov_money'    => 'Moov Money',
                'bank_transfer' => 'Virement bancaire',
            ];
            $method  = $profile->payout_method;
            $details = $profile->payout_details ?? [];
            $isMobile = $method !== 'bank_transfer';
            $accountInfo = $isMobile
                ? ($details['phone'] ?? '—')
                : ($details['account_number'] ?? '—');
        @endphp

        <div class="mb-6 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5">
            <div class="flex items-center gap-3 mb-4">
                @if($isMobile)
                    <div class="w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                        <x-heroicon-o-device-phone-mobile class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                    </div>
                @else
                    <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <x-heroicon-o-building-library class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                    </div>
                @endif
                <p class="font-semibold text-gray-900 dark:text-white text-base">
                    {{ $methodLabels[$method] ?? $method }}
                </p>
            </div>

            <div class="border-t border-gray-100 dark:border-gray-800 pt-4 space-y-2.5">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">{{ $isMobile ? 'Numéro' : 'N° de compte' }}</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $accountInfo }}</span>
                </div>
                @if(!$isMobile && isset($details['bank_code']) && $details['bank_code'])
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Code banque</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $details['bank_code'] }}</span>
                    </div>
                @endif
                @if($verifiedAt)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Validé le</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $verifiedAt->format('d/m/Y') }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Action buttons --}}
        <div class="flex flex-col sm:flex-row gap-3">
            <button wire:click="showForm"
                class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg border border-primary-500 text-primary-600 dark:text-primary-400 dark:border-primary-400 px-4 py-2.5 text-sm font-semibold hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors">
                <x-heroicon-o-plus class="w-4 h-4" />
                Ajouter un nouveau compte
            </button>
            <button wire:click="deletePayoutMethod"
                wire:confirm="Êtes-vous sûr de vouloir supprimer ce compte de paiement ?"
                wire:loading.attr="disabled"
                class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg border border-red-300 text-red-600 dark:text-red-400 dark:border-red-500 px-4 py-2.5 text-sm font-semibold hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                <x-heroicon-o-trash class="w-4 h-4" />
                Supprimer ce compte
            </button>
        </div>

    @else

        {{-- ── Bannière de statut (pending / rejected) ──────────────────── --}}
        @if($payoutMethodStatus === 'pending')
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800 p-4 flex items-start gap-3">
                <x-heroicon-o-clock class="w-6 h-6 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                <div>
                    <p class="font-semibold text-amber-800 dark:text-amber-200">En attente de validation</p>
                    <p class="text-sm text-amber-700 dark:text-amber-300 mt-0.5">
                        Votre compte est en attente de validation par l'administration.
                        Vous recevrez une notification une fois validé.
                    </p>
                </div>
            </div>
        @elseif($payoutMethodStatus === 'rejected')
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-4 flex items-start gap-3">
                <x-heroicon-o-x-circle class="w-6 h-6 text-red-600 dark:text-red-400 shrink-0 mt-0.5" />
                <div>
                    <p class="font-semibold text-red-800 dark:text-red-200">Compte refusé</p>
                    @if($rejectionReason)
                        <p class="text-sm text-red-700 dark:text-red-300 mt-0.5">
                            Motif : {{ $rejectionReason }}
                        </p>
                    @endif
                    <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                        Corrigez les informations ci-dessous et enregistrez à nouveau.
                    </p>
                </div>
            </div>
        @endif

        {{-- Cancel button when switching back from verified state --}}
        @if($isVerified && $this->showingForm)
            <div class="mb-4">
                <button wire:click="hideForm"
                    class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                    <x-heroicon-o-arrow-left class="w-4 h-4" />
                    Annuler
                </button>
            </div>
        @endif

        {{-- ── Formulaire ─────────────────────────────────────────────────── --}}
        <x-filament-panels::form wire:submit="save">
            {{ $this->form }}

            <x-filament-panels::form.actions
                :actions="[
                    \Filament\Actions\Action::make('save')
                        ->label('Enregistrer le compte')
                        ->submit('save')
                ]"
            />
        </x-filament-panels::form>

    @endif

</x-filament-panels::page>
