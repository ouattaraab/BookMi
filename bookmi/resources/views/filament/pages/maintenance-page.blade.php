<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Status badge --}}
        <x-filament::card>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Statut actuel</h2>
                    <p class="text-sm text-gray-500 mt-1">L'application est actuellement :</p>
                </div>
                @if($enabled)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                        <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                        EN MAINTENANCE
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                        EN LIGNE
                    </span>
                @endif
            </div>
        </x-filament::card>

        {{-- Form --}}
        <x-filament::card>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Configuration</h2>

            <form wire:submit.prevent="save" class="space-y-5">

                {{-- Toggle --}}
                <div class="flex items-center gap-3">
                    <button type="button"
                        wire:click="$set('enabled', {{ $enabled ? 'false' : 'true' }})"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors
                               {{ $enabled ? 'bg-red-500' : 'bg-gray-300 dark:bg-gray-600' }}">
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform
                                     {{ $enabled ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Activer le mode maintenance
                    </label>
                </div>

                {{-- Message --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Message affiché aux utilisateurs
                    </label>
                    <textarea wire:model.defer="message" rows="3"
                              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm
                                     focus:outline-none focus:ring-2 focus:ring-primary-500
                                     dark:bg-gray-800 dark:text-white"></textarea>
                </div>

                {{-- End at --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Fin prévue (optionnel — affiche un compte à rebours)
                    </label>
                    <input type="datetime-local" wire:model.defer="end_at"
                           class="rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-primary-500
                                  dark:bg-gray-800 dark:text-white">
                    <p class="text-xs text-gray-400 mt-1">Laisser vide pour ne pas afficher de compte à rebours.</p>
                </div>

                <div class="pt-2">
                    <x-filament::button type="submit" icon="heroicon-o-check">
                        Enregistrer
                    </x-filament::button>
                </div>
            </form>
        </x-filament::card>

        {{-- Warning --}}
        @if($enabled)
        <x-filament::card>
            <div class="flex gap-3 text-sm text-amber-700 dark:text-amber-400">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 shrink-0 mt-0.5" />
                <div>
                    <strong>Attention :</strong> Le mode maintenance est <strong>actif</strong>.
                    Les utilisateurs (clients et talents) ne peuvent plus accéder à l'application web ni à l'API.
                    Seuls les administrateurs contournent ce blocage.
                </div>
            </div>
        </x-filament::card>
        @endif

    </div>
</x-filament-panels::page>
