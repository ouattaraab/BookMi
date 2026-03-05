<x-filament-panels::page>
    <div class="space-y-4">

        {{-- Header --}}
        <div class="flex flex-wrap justify-between items-center gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ count($this->jobs) }} job(s) en échec
            </p>

            @if(count($this->jobs) > 0)
                <div class="flex gap-2">

                    {{-- Relancer tout (max 10) --}}
                    <button
                        wire:click="retryAll"
                        wire:loading.attr="disabled"
                        wire:target="retryAll"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 transition-colors">
                        <span wire:loading.remove wire:target="retryAll" class="inline-flex items-center gap-1">
                            <x-heroicon-m-arrow-path class="w-3.5 h-3.5" />
                            Relancer tout (max 10)
                        </span>
                        <span wire:loading wire:target="retryAll" class="inline-flex items-center gap-1">
                            <x-heroicon-m-arrow-path class="w-3.5 h-3.5 animate-spin" />
                            Exécution…
                        </span>
                    </button>

                    {{-- Vider tout --}}
                    <button
                        wire:click="flushAll"
                        wire:confirm="Supprimer définitivement tous les jobs échoués ?"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-red-600 hover:bg-red-700 transition-colors">
                        <x-heroicon-m-trash class="w-3.5 h-3.5" />
                        Vider tout
                    </button>

                </div>
            @endif
        </div>

        {{-- Jobs list --}}
        @forelse($this->jobs as $job)
            @php
                $jobId  = $job['id'];
                $status = $this->retryStatuses[$jobId] ?? null;

                $borderClass = match($status) {
                    'success' => 'border-green-300 dark:border-green-700',
                    'failed'  => 'border-red-400 dark:border-red-700',
                    'running' => 'border-blue-300 dark:border-blue-700',
                    default   => 'border-gray-200 dark:border-gray-700',
                };
            @endphp

            <div class="rounded-xl border {{ $borderClass }} bg-white dark:bg-gray-900 p-4 space-y-2 transition-colors duration-300">
                <div class="flex justify-between items-start gap-4">

                    <div class="flex-1 min-w-0">

                        {{-- Meta line --}}
                        <div class="flex flex-wrap items-center gap-2 mb-2">

                            {{-- Queue --}}
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                                {{ $job['queue'] }}
                            </span>

                            {{-- Job class (basename only) --}}
                            <span class="text-xs font-mono text-gray-500 dark:text-gray-400 truncate max-w-[260px]" title="{{ $job['job_class'] }}">
                                {{ class_basename($job['job_class']) }}
                            </span>

                            {{-- Failed at --}}
                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                {{ $job['failed_at'] }}
                            </span>

                            {{-- Status badge --}}
                            @if($status === 'running')
                                <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                                    <x-heroicon-m-arrow-path class="w-3 h-3 animate-spin" />
                                    Exécution…
                                </span>
                            @elseif($status === 'success')
                                <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                                    <x-heroicon-m-check-circle class="w-3 h-3" />
                                    Succès
                                </span>
                            @elseif($status === 'failed')
                                <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400">
                                    <x-heroicon-m-x-circle class="w-3 h-3" />
                                    Échec
                                </span>
                            @endif

                        </div>

                        {{-- Exception --}}
                        <pre class="text-xs text-red-500 dark:text-red-400 whitespace-pre-wrap break-all font-mono leading-relaxed">{{ $job['exception'] }}</pre>

                    </div>

                    {{-- Action button --}}
                    @if($status === 'success')
                        <div class="shrink-0 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20">
                            <x-heroicon-m-check-circle class="w-3.5 h-3.5" />
                            OK
                        </div>
                    @else
                        <button
                            wire:click="retry('{{ $jobId }}')"
                            wire:loading.attr="disabled"
                            wire:target="retry('{{ $jobId }}')"
                            @disabled($status === 'running')
                            class="shrink-0 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 transition-colors">
                            <span wire:loading.remove wire:target="retry('{{ $jobId }}')" class="inline-flex items-center gap-1">
                                <x-heroicon-m-arrow-path class="w-3.5 h-3.5" />
                                Relancer
                            </span>
                            <span wire:loading wire:target="retry('{{ $jobId }}')" class="inline-flex items-center gap-1">
                                <x-heroicon-m-arrow-path class="w-3.5 h-3.5 animate-spin" />
                                …
                            </span>
                        </button>
                    @endif

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
