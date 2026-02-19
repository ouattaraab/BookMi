<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 mb-6">
        <x-filament::card>
            <div class="text-sm font-medium text-gray-500">Prestations aujourd'hui</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $todayBookings->count() }}</div>
        </x-filament::card>
        <x-filament::card>
            <div class="text-sm font-medium text-gray-500">Check-ins effectués</div>
            <div class="text-3xl font-bold text-green-600 mt-1">{{ $checkedIn }}</div>
        </x-filament::card>
        <x-filament::card>
            <div class="text-sm font-medium text-gray-500">Check-ins manquants</div>
            <div class="text-3xl font-bold {{ $pendingCheckin > 0 ? 'text-red-600' : 'text-gray-400' }} mt-1">{{ $pendingCheckin }}</div>
        </x-filament::card>
    </div>

    <x-filament::card>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Prestations du {{ now()->format('d/m/Y') }}
        </h3>

        @if ($todayBookings->isEmpty())
            <p class="text-gray-500 text-center py-8">Aucune prestation confirmée pour aujourd'hui.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 text-left">
                            <th class="py-2 px-3 text-gray-500">#</th>
                            <th class="py-2 px-3 text-gray-500">Talent</th>
                            <th class="py-2 px-3 text-gray-500">Client</th>
                            <th class="py-2 px-3 text-gray-500">Lieu</th>
                            <th class="py-2 px-3 text-gray-500">Check-in</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($todayBookings as $booking)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 px-3 text-gray-500">#{{ $booking->id }}</td>
                                <td class="py-2 px-3 font-medium text-gray-900 dark:text-white">
                                    {{ $booking->talentProfile->stage_name ?? '—' }}
                                </td>
                                <td class="py-2 px-3 text-gray-700 dark:text-gray-300">
                                    {{ $booking->client->email ?? '—' }}
                                </td>
                                <td class="py-2 px-3 text-gray-700 dark:text-gray-300">
                                    {{ $booking->event_location }}
                                </td>
                                <td class="py-2 px-3">
                                    @if ($booking->trackingEvents()->where('type', 'checkin')->exists())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            ✓ Effectué
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                            ✗ Manquant
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::card>
</x-filament-panels::page>
