<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
        <x-filament::card>
            <div class="text-sm font-medium text-gray-500">Utilisateurs total</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($kpis['total_users']) }}</div>
            <div class="text-xs text-gray-400 mt-1">{{ $kpis['total_talents'] }} talents · {{ $kpis['total_clients'] }} clients</div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-sm font-medium text-gray-500">Réservations total</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($kpis['total_bookings']) }}</div>
            <div class="text-xs text-gray-400 mt-1">{{ $kpis['completed_bookings'] }} complétées</div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-sm font-medium text-gray-500">Taux de conversion</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $kpis['conversion_rate'] }}%</div>
            <div class="text-xs text-gray-400 mt-1">Demandes → complétées</div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-sm font-medium text-gray-500">Panier moyen</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($kpis['avg_booking_value']) }}</div>
            <div class="text-xs text-gray-400 mt-1">FCFA par réservation</div>
        </x-filament::card>
    </div>

    <x-filament::card class="mt-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">CA mensuel (12 derniers mois)</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left py-2 px-3 text-gray-500">Mois</th>
                        <th class="text-right py-2 px-3 text-gray-500">CA (FCFA)</th>
                        <th class="py-2 px-3 w-1/2"></th>
                    </tr>
                </thead>
                <tbody>
                    @php $maxRevenue = collect($monthlyRevenue)->max('revenue') ?: 1; @endphp
                    @foreach ($monthlyRevenue as $month)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $month['label'] }}</td>
                            <td class="py-2 px-3 text-right font-mono text-gray-900 dark:text-white">{{ number_format($month['revenue']) }}</td>
                            <td class="py-2 px-3">
                                <div class="h-2 rounded-full bg-primary-500" style="width: {{ round($month['revenue'] / $maxRevenue * 100) }}%"></div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-between text-sm">
            <span class="text-gray-500">CA total plateforme</span>
            <span class="font-bold text-gray-900 dark:text-white">{{ number_format($kpis['total_revenue']) }} FCFA</span>
        </div>
    </x-filament::card>
</x-filament-panels::page>
