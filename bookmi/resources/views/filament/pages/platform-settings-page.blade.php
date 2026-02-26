<x-filament-panels::page>
    <div class="space-y-4">
        <x-filament::card>
            <div class="flex items-center gap-2 mb-4">
                <x-heroicon-o-information-circle class="w-5 h-5 text-blue-500" />
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Ces valeurs sont définies dans <code class="text-xs bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">config/bookmi.php</code>.
                    Pour les modifier, mettez à jour les variables d'environnement correspondantes et redémarrez l'application.
                </p>
            </div>

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
        </x-filament::card>
    </div>
</x-filament-panels::page>
