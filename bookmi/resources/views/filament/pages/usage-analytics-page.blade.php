<x-filament-panels::page>
    {{-- Top cards --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        {{-- Top pages --}}
        <x-filament::card>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
                Pages les plus visitées <span class="text-xs font-normal text-gray-400">(14 derniers jours)</span>
            </h3>
            @if(count($topPages) === 0)
                <p class="text-sm text-gray-400">Aucune donnée pour l'instant.</p>
            @else
                @php $maxPages = collect($topPages)->max('count') ?: 1; @endphp
                <div class="space-y-2">
                    @foreach($topPages as $page)
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-700 dark:text-gray-300 w-40 truncate" title="{{ $page['name'] }}">
                                {{ $page['name'] }}
                            </span>
                            <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-2">
                                <div class="h-2 rounded-full bg-blue-500"
                                     style="width: {{ round($page['count'] / $maxPages * 100) }}%"></div>
                            </div>
                            <span class="text-sm font-mono font-semibold text-gray-900 dark:text-white w-10 text-right">
                                {{ number_format($page['count']) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::card>

        {{-- Top buttons --}}
        <x-filament::card>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
                Boutons les plus cliqués <span class="text-xs font-normal text-gray-400">(14 derniers jours)</span>
            </h3>
            @if(count($topButtons) === 0)
                <p class="text-sm text-gray-400">Aucune donnée pour l'instant.</p>
            @else
                @php $maxBtns = collect($topButtons)->max('count') ?: 1; @endphp
                <div class="space-y-2">
                    @foreach($topButtons as $btn)
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-700 dark:text-gray-300 w-40 truncate" title="{{ $btn['name'] }}">
                                {{ $btn['name'] }}
                            </span>
                            <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-2">
                                <div class="h-2 rounded-full bg-indigo-500"
                                     style="width: {{ round($btn['count'] / $maxBtns * 100) }}%"></div>
                            </div>
                            <span class="text-sm font-mono font-semibold text-gray-900 dark:text-white w-10 text-right">
                                {{ number_format($btn['count']) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::card>
    </div>

    {{-- Heatmap --}}
    <x-filament::card class="mt-6">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
            Heatmap des vues par page <span class="text-xs font-normal text-gray-400">(14 derniers jours)</span>
        </h3>

        @if(count($heatmap) === 0)
            <p class="text-sm text-gray-400">Aucune donnée pour l'instant.</p>
        @else
            @php
                $allCounts = [];
                foreach ($heatmap as $row) {
                    foreach ($row['days'] as $count) {
                        $allCounts[] = $count;
                    }
                }
                $maxHeatmap = count($allCounts) > 0 ? max($allCounts) : 1;
            @endphp

            <div class="overflow-x-auto">
                <table class="text-xs border-collapse w-full">
                    <thead>
                        <tr>
                            <th class="text-left py-1 pr-3 text-gray-500 font-normal min-w-[120px]">Page</th>
                            @foreach($days as $day)
                                <th class="text-center px-1 text-gray-400 font-normal whitespace-nowrap"
                                    style="min-width: 36px;">
                                    {{ \Carbon\Carbon::parse($day)->format('d/m') }}
                                </th>
                            @endforeach
                            <th class="text-right pl-2 text-gray-500 font-normal">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($heatmap as $row)
                            @php
                                $total = array_sum($row['days']);
                            @endphp
                            <tr class="border-t border-gray-100 dark:border-gray-800">
                                <td class="py-1 pr-3 text-gray-700 dark:text-gray-300 truncate max-w-[120px]"
                                    title="{{ $row['page'] }}">
                                    {{ $row['page'] }}
                                </td>
                                @foreach($days as $day)
                                    @php
                                        $count = $row['days'][$day] ?? 0;
                                        $cls   = $this->colorClass($count, $maxHeatmap);
                                    @endphp
                                    <td class="text-center px-1 py-1">
                                        <div class="rounded text-xs py-1 {{ $cls }}" title="{{ $count }}">
                                            {{ $count ?: '' }}
                                        </div>
                                    </td>
                                @endforeach
                                <td class="text-right pl-2 font-mono font-semibold text-gray-900 dark:text-white">
                                    {{ number_format($total) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex items-center gap-2 text-xs text-gray-500">
                <span>Intensité :</span>
                <div class="flex gap-1">
                    <div class="w-5 h-5 rounded bg-blue-100"></div>
                    <div class="w-5 h-5 rounded bg-blue-200"></div>
                    <div class="w-5 h-5 rounded bg-blue-300"></div>
                    <div class="w-5 h-5 rounded bg-blue-400"></div>
                    <div class="w-5 h-5 rounded bg-blue-500"></div>
                    <div class="w-5 h-5 rounded bg-blue-700"></div>
                </div>
                <span>Faible → Élevé</span>
            </div>
        @endif
    </x-filament::card>
</x-filament-panels::page>
