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
                    <x-filament::button
                        wire:click="retryAll"
                        wire:loading.attr="disabled"
                        wire:target="retryAll"
                        color="info"
                        size="sm"
                        icon="heroicon-m-arrow-path">
                        <span wire:loading.remove wire:target="retryAll">
                            Relancer tout (max 10)
                        </span>
                        <span wire:loading wire:target="retryAll" class="inline-flex items-center gap-1">
                            <x-heroicon-m-arrow-path class="w-3.5 h-3.5 animate-spin" />
                            Exécution…
                        </span>
                    </x-filament::button>

                    {{-- Vider tout --}}
                    <x-filament::button
                        wire:click="flushAll"
                        wire:confirm="Supprimer définitivement tous les jobs échoués ?"
                        color="danger"
                        size="sm"
                        icon="heroicon-m-trash">
                        Vider tout
                    </x-filament::button>

                </div>
            @endif
        </div>

        {{-- Jobs list --}}
        @forelse($this->jobs as $job)
            @php
                $jobId  = $job['id'];
                $status = $this->retryStatuses[$jobId] ?? null;

                $borderClass = match($status) {
                    'success' => 'border-green-400 dark:border-green-600',
                    'failed'  => 'border-red-400 dark:border-red-600',
                    'running' => 'border-blue-400 dark:border-blue-600',
                    default   => 'border-gray-200 dark:border-gray-700',
                };
            @endphp

            <div class="rounded-xl border-2 {{ $borderClass }} bg-white dark:bg-gray-900 p-4 space-y-2 transition-all duration-300">
                <div class="flex justify-between items-start gap-4">

                    <div class="flex-1 min-w-0">

                        {{-- Meta line --}}
                        <div class="flex flex-wrap items-center gap-2 mb-2">

                            {{-- Queue --}}
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                {{ $job['queue'] }}
                            </span>

                            {{-- Job class (basename only) --}}
                            <span class="text-xs font-mono text-gray-600 dark:text-gray-400 truncate max-w-[260px]" title="{{ $job['job_class'] }}">
                                {{ class_basename($job['job_class']) }}
                            </span>

                            {{-- Failed at --}}
                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                {{ $job['failed_at'] }}
                            </span>

                            {{-- Status badge --}}
                            @if($status === 'running')
                                <x-filament::badge color="info" icon="heroicon-m-arrow-path">
                                    Exécution…
                                </x-filament::badge>
                            @elseif($status === 'success')
                                <x-filament::badge color="success" icon="heroicon-m-check-circle">
                                    Succès
                                </x-filament::badge>
                            @elseif($status === 'failed')
                                <x-filament::badge color="danger" icon="heroicon-m-x-circle">
                                    Échec
                                </x-filament::badge>
                            @endif

                        </div>

                        {{-- Exception --}}
                        <pre class="text-xs text-red-600 dark:text-red-400 whitespace-pre-wrap break-all font-mono leading-relaxed bg-red-50 dark:bg-red-950/20 rounded-lg p-3">{{ $job['exception'] }}</pre>

                    </div>

                    {{-- Action button --}}
                    <div class="shrink-0">
                        @if($status === 'success')
                            <x-filament::badge color="success" icon="heroicon-m-check-circle" size="lg">
                                OK
                            </x-filament::badge>
                        @else
                            <x-filament::button
                                wire:click="retry('{{ $jobId }}')"
                                wire:loading.attr="disabled"
                                wire:target="retry('{{ $jobId }}')"
                                :disabled="$status === 'running'"
                                color="primary"
                                size="sm"
                                icon="heroicon-m-arrow-path">
                                <span wire:loading.remove wire:target="retry('{{ $jobId }}')">
                                    Relancer
                                </span>
                                <span wire:loading wire:target="retry('{{ $jobId }}')" class="inline-flex items-center gap-1">
                                    <x-heroicon-m-arrow-path class="w-3.5 h-3.5 animate-spin" />
                                    …
                                </span>
                            </x-filament::button>
                        @endif
                    </div>

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
