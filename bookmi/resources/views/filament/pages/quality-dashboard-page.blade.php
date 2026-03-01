<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Bouton refresh --}}
        <div class="flex justify-end">
            <x-filament::button wire:click="refresh" icon="heroicon-o-arrow-path" color="gray" size="sm">
                Actualiser
            </x-filament::button>
        </div>

        {{-- Métriques globales --}}
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <div class="fi-stats-overview-stat rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Note moyenne</p>
                <p class="mt-1 text-2xl font-bold text-yellow-500">★ {{ number_format($globalMetrics['avg_rating'] ?? 0, 2) }}</p>
                <p class="text-xs text-gray-400">{{ $globalMetrics['total_reviews'] ?? 0 }} avis au total</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Taux de litiges</p>
                <p class="mt-1 text-2xl font-bold {{ ($globalMetrics['dispute_rate'] ?? 0) > 5 ? 'text-red-500' : 'text-green-500' }}">
                    {{ $globalMetrics['dispute_rate'] ?? 0 }} %
                </p>
                <p class="text-xs text-gray-400">Objectif MVP &lt; 5 %</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Temps de réponse moyen</p>
                <p class="mt-1 text-2xl font-bold text-blue-500">
                    @if($globalMetrics['avg_response_h'] !== null)
                        {{ $globalMetrics['avg_response_h'] }} h
                    @else
                        —
                    @endif
                </p>
                <p class="text-xs text-gray-400">Délai acceptation réservation</p>
            </div>
            <div class="fi-stats-overview-stat rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Alertes actives</p>
                <p class="mt-1 text-2xl font-bold text-orange-500">
                    {{ count($alertTalents) + count($disputeAlerts) }}
                </p>
                <p class="text-xs text-gray-400">Talents à surveiller</p>
            </div>
        </div>

        {{-- Notes par catégorie --}}
        @if(count($categoryRatings))
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Notes moyennes par catégorie</h3>
            <div class="space-y-2">
                @foreach($categoryRatings as $cat)
                <div class="flex items-center gap-3">
                    <span class="w-32 truncate text-sm text-gray-600 dark:text-gray-300">{{ $cat['category'] }}</span>
                    <div class="flex-1 rounded-full bg-gray-100 dark:bg-gray-700" style="height: 8px;">
                        <div class="rounded-full bg-yellow-400" style="width: {{ min(($cat['avg_rating'] / 5) * 100, 100) }}%; height: 8px;"></div>
                    </div>
                    <span class="text-sm font-semibold text-yellow-500">★ {{ number_format($cat['avg_rating'], 2) }}</span>
                    <span class="text-xs text-gray-400">({{ $cat['review_count'] }})</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            {{-- Top talents --}}
            @if(count($topTalents))
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">🏆 Top 10 Talents (note)</h3>
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-xs text-gray-500">
                        <th class="pb-2">Artiste</th>
                        <th class="pb-2 text-right">Note</th>
                        <th class="pb-2 text-right">Résa.</th>
                    </tr></thead>
                    <tbody>
                    @foreach($topTalents as $i => $t)
                    <tr class="border-t border-gray-100 dark:border-gray-700">
                        <td class="py-1">
                            <span class="mr-1 text-gray-400">{{ $i + 1 }}.</span>
                            {{ $t['stage_name'] }}
                            @if($t['city']) <span class="text-xs text-gray-400">· {{ $t['city'] }}</span> @endif
                        </td>
                        <td class="py-1 text-right font-semibold text-yellow-500">★ {{ number_format($t['avg_rating'], 1) }}</td>
                        <td class="py-1 text-right text-gray-500">{{ $t['total_bookings'] }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            {{-- Litiges par mois --}}
            @if(count($monthlyDisputes))
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">📊 Litiges / mois (6 derniers mois)</h3>
                <div class="space-y-2">
                    @foreach($monthlyDisputes as $m)
                    <div class="flex items-center gap-3">
                        <span class="w-20 text-sm text-gray-600 dark:text-gray-300">{{ $m['month'] }}</span>
                        <div class="flex-1 rounded-full bg-gray-100 dark:bg-gray-700" style="height: 8px;">
                            @php $maxCount = max(array_column($monthlyDisputes, 'count')); @endphp
                            <div class="rounded-full bg-red-400" style="width: {{ $maxCount > 0 ? min(($m['count'] / $maxCount) * 100, 100) : 0 }}%; height: 8px;"></div>
                        </div>
                        <span class="text-sm font-semibold text-red-500">{{ $m['count'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Alertes talents : note faible --}}
        @if(count($alertTalents))
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm dark:border-red-800 dark:bg-red-900/20">
            <h3 class="mb-4 text-base font-semibold text-red-700 dark:text-red-400">⚠️ Talents avec note &lt; 3.5 (min 3 avis)</h3>
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-red-500">
                    <th class="pb-2">Artiste</th>
                    <th class="pb-2">Email</th>
                    <th class="pb-2 text-right">Note</th>
                    <th class="pb-2 text-right">Résa.</th>
                </tr></thead>
                <tbody>
                @foreach($alertTalents as $t)
                <tr class="border-t border-red-100 dark:border-red-800">
                    <td class="py-1">{{ $t['stage_name'] }} @if($t['city'])<span class="text-xs text-gray-400">· {{ $t['city'] }}</span>@endif</td>
                    <td class="py-1 text-gray-500">{{ $t['email'] }}</td>
                    <td class="py-1 text-right font-semibold text-red-500">★ {{ number_format($t['avg_rating'], 2) }}</td>
                    <td class="py-1 text-right text-gray-500">{{ $t['total_bookings'] }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Alertes litiges récents --}}
        @if(count($disputeAlerts))
        <div class="rounded-xl border border-orange-200 bg-orange-50 p-4 shadow-sm dark:border-orange-800 dark:bg-orange-900/20">
            <h3 class="mb-4 text-base font-semibold text-orange-700 dark:text-orange-400">🔥 Talents avec ≥ 2 litiges (30 derniers jours)</h3>
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-orange-500">
                    <th class="pb-2">Artiste</th>
                    <th class="pb-2">Email</th>
                    <th class="pb-2 text-right">Litiges (30j)</th>
                </tr></thead>
                <tbody>
                @foreach($disputeAlerts as $t)
                <tr class="border-t border-orange-100 dark:border-orange-800">
                    <td class="py-1">{{ $t['stage_name'] }} @if($t['city'])<span class="text-xs text-gray-400">· {{ $t['city'] }}</span>@endif</td>
                    <td class="py-1 text-gray-500">{{ $t['email'] }}</td>
                    <td class="py-1 text-right font-bold text-orange-600">{{ $t['disputes_last_30d'] }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if(!count($alertTalents) && !count($disputeAlerts))
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-center text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            ✅ Aucune alerte qualité active — la plateforme est en bonne santé.
        </div>
        @endif

    </div>
</x-filament-panels::page>
