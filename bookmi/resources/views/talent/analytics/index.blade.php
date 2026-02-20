@extends('layouts.talent')

@section('title', 'Analytiques — BookMi Talent')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Analytiques</h1>
        <p class="text-sm text-gray-500 mt-1">Vue d'ensemble de vos performances</p>
    </div>

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
            <p class="text-2xl font-extrabold" style="color:#FF6B35">{{ $stats['total'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Total réservations</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
            <p class="text-2xl font-extrabold" style="color:#4CAF50">{{ $stats['completed'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Terminées</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
            <p class="text-2xl font-extrabold" style="color:#9C27B0">{{ number_format($stats['revenue'], 0, ',', ' ') }}</p>
            <p class="text-xs text-gray-500 mt-1">Revenus (FCFA)</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
            <p class="text-2xl font-extrabold" style="color:#FF9800">{{ $stats['pending'] }}</p>
            <p class="text-xs text-gray-500 mt-1">En attente</p>
        </div>
    </div>

    {{-- Barres de progression revenue (visuel simple) --}}
    @if($stats['total'] > 0)
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
            <h3 class="text-sm font-bold text-gray-900 mb-4">Taux de completion</h3>
            @php
                $completionRate = $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100) : 0;
            @endphp
            <div class="flex items-center gap-3">
                <div class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
                    <div class="h-3 rounded-full transition-all" style="width:{{ $completionRate }}%; background:#FF6B35"></div>
                </div>
                <span class="text-sm font-bold text-gray-900 w-10 text-right">{{ $completionRate }}%</span>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
            <h3 class="text-sm font-bold text-gray-900 mb-3">Revenu moyen par prestation</h3>
            @php $avgRevenue = $stats['completed'] > 0 ? $stats['revenue'] / $stats['completed'] : 0; @endphp
            <p class="text-2xl font-bold" style="color:#FF6B35">{{ number_format($avgRevenue, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-400">FCFA</span></p>
        </div>
    </div>
    @endif

    {{-- Tableau mensuel --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-bold text-gray-900">Activité des 6 derniers mois</h2>
        </div>

        @if($monthly->isEmpty())
            <div class="text-center py-12 text-gray-400 text-sm">Aucune activité enregistrée pour la période.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mois</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Réservations</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Revenu (FCFA)</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Moy./réserv.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @php
                            $monthNames = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                            $maxRevenue = $monthly->max('revenue') ?: 1;
                        @endphp
                        @foreach($monthly as $row)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                {{ $monthNames[(int)$row->month] ?? $row->month }} {{ $row->year }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm font-semibold text-gray-900">{{ $row->count }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <div class="w-24 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                        <div class="h-1.5 rounded-full" style="width:{{ round(($row->revenue / $maxRevenue) * 100) }}%; background:#FF6B35"></div>
                                    </div>
                                    <span class="text-sm font-semibold" style="color:#FF6B35">{{ number_format($row->revenue, 0, ',', ' ') }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-gray-500">
                                {{ $row->count > 0 ? number_format($row->revenue / $row->count, 0, ',', ' ') : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-gray-200">
                        <tr class="bg-gray-50">
                            <td class="px-6 py-3 text-xs font-bold text-gray-700 uppercase">Total</td>
                            <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">{{ $monthly->sum('count') }}</td>
                            <td class="px-6 py-3 text-right text-sm font-bold" style="color:#FF6B35">{{ number_format($monthly->sum('revenue'), 0, ',', ' ') }}</td>
                            <td class="px-6 py-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
