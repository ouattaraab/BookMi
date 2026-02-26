<x-filament-panels::page>

    {{-- ── Solde + statut ──────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="rounded-xl bg-primary-50 border border-primary-200 dark:bg-primary-900/20 dark:border-primary-800 p-4">
            <p class="text-sm text-primary-700 dark:text-primary-300 font-medium">Solde disponible</p>
            <p class="text-3xl font-bold text-primary-900 dark:text-primary-100 mt-1">
                {{ number_format($availableBalance, 0, ',', ' ') }} <span class="text-lg font-semibold">XOF</span>
            </p>
        </div>

        <div class="rounded-xl border p-4 flex items-center gap-3
            {{ $isVerified
                ? 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800'
                : 'bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-800' }}">
            @if($isVerified)
                <x-heroicon-o-check-badge class="w-8 h-8 text-green-500 shrink-0" />
                <div>
                    <p class="font-semibold text-green-800 dark:text-green-200">Compte validé</p>
                    <p class="text-xs text-green-600 dark:text-green-400">Vous pouvez effectuer un reversement</p>
                </div>
            @else
                <x-heroicon-o-clock class="w-8 h-8 text-amber-500 shrink-0" />
                <div>
                    <p class="font-semibold text-amber-800 dark:text-amber-200">Compte non validé</p>
                    <p class="text-xs text-amber-600 dark:text-amber-400">
                        Enregistrez et faites valider votre compte de paiement d'abord
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Formulaire de demande ─────────────────────────────────────────── --}}
    @if($canRequest)
        <div class="mb-8">
            <x-filament-panels::form wire:submit="request">
                {{ $this->form }}
                <x-filament-panels::form.actions
                    :actions="[
                        \Filament\Actions\Action::make('request')
                            ->label('Soumettre la demande')
                            ->submit('request')
                            ->color('success')
                    ]"
                />
            </x-filament-panels::form>
        </div>
    @elseif(!$isVerified)
        <div class="mb-8 rounded-lg bg-gray-50 border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4 text-center text-sm text-gray-500 dark:text-gray-400">
            Rendez-vous dans <strong>Mon compte de paiement</strong> pour enregistrer et faire valider votre compte.
        </div>
    @elseif($availableBalance <= 0)
        <div class="mb-8 rounded-lg bg-gray-50 border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4 text-center text-sm text-gray-500 dark:text-gray-400">
            Votre solde disponible est de 0 XOF. Vos revenus seront disponibles après la confirmation d'une prestation par votre client.
        </div>
    @else
        <div class="mb-8 rounded-lg bg-blue-50 border border-blue-200 dark:bg-blue-900/20 dark:border-blue-800 p-4 text-center text-sm text-blue-700 dark:text-blue-300">
            Vous avez déjà une demande de reversement en cours. Attendez son traitement avant d'en soumettre une nouvelle.
        </div>
    @endif

    {{-- ── Historique ───────────────────────────────────────────────────── --}}
    <div>
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Historique des demandes</h3>

        @if($history->isEmpty())
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-6 text-center text-sm text-gray-400">
                Aucune demande effectuée pour l'instant.
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-right">Montant (XOF)</th>
                            <th class="px-4 py-3 text-left">Statut</th>
                            <th class="px-4 py-3 text-left">Note</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($history as $req)
                            @php
                                $badgeClass = match($req->status->value) {
                                    'pending'    => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                                    'approved'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                    'processing' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
                                    'completed'  => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                    'rejected'   => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                    default      => 'bg-gray-100 text-gray-800',
                                };
                            @endphp
                            <tr class="bg-white dark:bg-gray-900">
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                    {{ $req->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">
                                    {{ number_format($req->amount, 0, ',', ' ') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                        {{ $req->status->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                                    {{ $req->note ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</x-filament-panels::page>
