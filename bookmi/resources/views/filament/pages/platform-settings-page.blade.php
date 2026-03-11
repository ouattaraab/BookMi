<x-filament-panels::page>
    <div class="space-y-6">

        @if(app()->environment('production'))
        <x-filament::section>
            <div class="flex items-center gap-2">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-amber-500" />
                <p class="text-sm font-medium text-amber-700 dark:text-amber-400">
                    Vous êtes en <strong>production</strong>. Toute modification prend effet immédiatement.
                </p>
            </div>
        </x-filament::section>
        @endif

        <x-filament::section>
            <x-slot name="heading">Paramètres éditables</x-slot>
            <x-slot name="description">Ces valeurs sont stockées en base de données et prennent effet immédiatement.</x-slot>

            <form wire:submit.prevent="save">
                {{ $this->form }}

                <div class="mt-6 flex justify-end">
                    <x-filament::button type="submit" color="primary" icon="heroicon-o-check">
                        Sauvegarder
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        @if(!empty($settings))
        <x-filament::section>
            <x-slot name="heading">Paramètres en lecture seule (config)</x-slot>
            <x-slot name="description">Ces valeurs sont définies dans <code class="text-xs bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">config/bookmi.php</code>. Modifiez les variables d'environnement pour les changer.</x-slot>

            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($settings as $key => $setting)
                <div class="py-4 flex items-center justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $setting['label'] }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $setting['description'] }}</p>
                        <code class="text-xs text-gray-400 mt-0.5 block">bookmi.{{ $setting['env_key'] }}</code>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                            {{ $setting['value'] }}
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                            config
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
