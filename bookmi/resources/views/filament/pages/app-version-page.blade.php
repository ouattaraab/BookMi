<x-filament-panels::page>
    <div class="space-y-6">

        <x-filament::card>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Configuration de la version</h2>
            <p class="text-sm text-gray-500 mb-5">
                Définissez la version minimale requise et le type de mise à jour à afficher dans l'app mobile.
            </p>

            <form wire:submit.prevent="save" class="space-y-5">

                {{-- Version required --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Version minimale requise <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model.defer="version_required" placeholder="ex: 1.0.5"
                           class="w-full max-w-xs rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-primary-500
                                  dark:bg-gray-800 dark:text-white">
                    @error('version_required')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-400 mt-1">Format SemVer : MAJEUR.MINEUR.CORRECTIF (ex: 1.2.3)</p>
                </div>

                {{-- Update type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Type de mise à jour <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.defer="update_type"
                            class="rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-primary-500
                                   dark:bg-gray-800 dark:text-white">
                        <option value="none">Aucune (ne rien afficher)</option>
                        <option value="optional">Optionnelle (dialog — peut être ignorée)</option>
                        <option value="forced">Forcée (bloque l'accès à l'app)</option>
                    </select>
                    @error('update_type')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Update message --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Message de mise à jour
                    </label>
                    <textarea wire:model.defer="update_message" rows="2"
                              placeholder="ex: Cette version apporte des corrections importantes..."
                              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm
                                     focus:outline-none focus:ring-2 focus:ring-primary-500
                                     dark:bg-gray-800 dark:text-white"></textarea>
                </div>

                {{-- Features --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Nouvelles fonctionnalités (une par ligne)
                    </label>
                    <textarea wire:model.defer="features" rows="4"
                              placeholder="Correction du paiement&#10;Nouveau profil talent&#10;..."
                              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm font-mono
                                     focus:outline-none focus:ring-2 focus:ring-primary-500
                                     dark:bg-gray-800 dark:text-white"></textarea>
                </div>

                {{-- Store URLs --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            URL Play Store (Android)
                        </label>
                        <input type="url" wire:model.defer="play_store_url"
                               placeholder="https://play.google.com/store/apps/..."
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-primary-500
                                      dark:bg-gray-800 dark:text-white">
                        @error('play_store_url')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            URL App Store (iOS)
                        </label>
                        <input type="url" wire:model.defer="app_store_url"
                               placeholder="https://apps.apple.com/app/..."
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-primary-500
                                      dark:bg-gray-800 dark:text-white">
                        @error('app_store_url')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="pt-2">
                    <x-filament::button type="submit" icon="heroicon-o-check">
                        Enregistrer
                    </x-filament::button>
                </div>
            </form>
        </x-filament::card>

        {{-- Info --}}
        <x-filament::card>
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Comment ça fonctionne ?</h3>
            <ul class="text-sm text-gray-500 space-y-1 list-disc list-inside">
                <li><strong>none</strong> — Aucun message affiché. Les utilisateurs accèdent normalement.</li>
                <li><strong>optional</strong> — Un dialog s'affiche avec l'option "Plus tard".</li>
                <li><strong>forced</strong> — L'accès est bloqué jusqu'à mise à jour. Le bouton store est affiché.</li>
            </ul>
            <p class="text-xs text-gray-400 mt-3">
                La version installée est comparée à la version requise au démarrage de l'app.
                Si la version installée est inférieure à la version requise, le type de mise à jour s'applique.
            </p>
        </x-filament::card>

    </div>
</x-filament-panels::page>
