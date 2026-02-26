<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Export Financier --}}
        <x-filament::card>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Export financier</h2>
            <p class="text-sm text-gray-500 mb-4">Téléchargez un fichier CSV contenant toutes les transactions et versements pour la période sélectionnée.</p>

            <form wire:submit.prevent="downloadFinancial" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Date de début</label>
                    <input type="date" wire:model="start_date"
                           class="rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Date de fin</label>
                    <input type="date" wire:model="end_date"
                           class="rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white">
                </div>
                <x-filament::button type="submit" icon="heroicon-o-arrow-down-tray" color="success">
                    Télécharger CSV
                </x-filament::button>
            </form>
        </x-filament::card>

        {{-- Info --}}
        <x-filament::card>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Contenu du rapport</h2>
            <ul class="text-sm text-gray-500 space-y-1 list-disc list-inside">
                <li><strong>Section TRANSACTIONS</strong> : toutes les transactions de paiement (client) sur la période</li>
                <li><strong>Section VERSEMENTS TALENTS</strong> : tous les reversements talents sur la période</li>
                <li>Format CSV UTF-8 avec BOM (compatible Excel et LibreOffice)</li>
            </ul>
        </x-filament::card>

    </div>
</x-filament-panels::page>
