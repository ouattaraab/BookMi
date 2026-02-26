<x-filament-panels::page>
    {{-- ── Statut du compte ─────────────────────────────────────────────── --}}
    @if($profile && $profile->payout_method)
        <div class="mb-6 rounded-xl border p-4 flex items-start gap-4
            {{ $isVerified
                ? 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800'
                : 'bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-800' }}">
            <div class="shrink-0 mt-0.5">
                @if($isVerified)
                    <x-heroicon-o-check-badge class="w-6 h-6 text-green-600 dark:text-green-400" />
                @else
                    <x-heroicon-o-clock class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                @endif
            </div>
            <div>
                @if($isVerified)
                    <p class="font-semibold text-green-800 dark:text-green-200">Compte validé</p>
                    <p class="text-sm text-green-700 dark:text-green-300 mt-0.5">
                        Votre compte a été validé le {{ $verifiedAt?->format('d/m/Y à H:i') }}.
                        Vous pouvez désormais effectuer des demandes de reversement.
                    </p>
                @else
                    <p class="font-semibold text-amber-800 dark:text-amber-200">En attente de validation</p>
                    <p class="text-sm text-amber-700 dark:text-amber-300 mt-0.5">
                        Votre compte a été enregistré et est en attente de validation par l'administration.
                        Vous recevrez une notification une fois validé.
                    </p>
                @endif
            </div>
        </div>
    @endif

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

    {{-- ── Formulaire ───────────────────────────────────────────────────── --}}
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
</x-filament-panels::page>
