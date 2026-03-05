<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Header with count and flush button --}}
        <div class="flex justify-between items-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ count($this->jobs) }} job(s) en échec
            </p>
            @if(count($this->jobs) > 0)
                <button
                    wire:click="flushAll"
                    wire:confirm="Supprimer définitivement tous les jobs échoués ?"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-red-600 hover:bg-red-700 transition-colors">
                    <x-heroicon-m-trash class="w-3.5 h-3.5" />
                    Vider tout
                </button>
            @endif
        </div>

        {{-- Jobs list --}}
        @forelse($this->jobs as $job)
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 space-y-2">
                <div class="flex justify-between items-start gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                                {{ $job['queue'] }}
                            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                {{ $job['failed_at'] }}
                            </span>
                        </div>
                        <pre class="mt-2 text-xs text-red-500 dark:text-red-400 whitespace-pre-wrap break-all font-mono leading-relaxed">{{ $job['exception'] }}</pre>
                    </div>
                    <button
                        wire:click="retry('{{ $job['id'] }}')"
                        class="shrink-0 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                        <x-heroicon-m-arrow-path class="w-3.5 h-3.5" />
                        Réessayer
                    </button>
                </div>
            </div>
        @empty
            <div class="text-center py-16">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-50 dark:bg-green-900/20 mb-4">
                    <x-heroicon-o-check-circle class="w-8 h-8 text-green-500" />
                </div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Aucun job en échec</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">La queue est propre</p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
