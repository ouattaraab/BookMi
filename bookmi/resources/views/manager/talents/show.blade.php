@extends('layouts.manager')

@section('title', ($talent->stage_name ?? 'Talent') . ' — BookMi Manager')

@section('content')
<div class="space-y-6">

    {{-- Retour --}}
    <div>
        <a href="{{ route('manager.talents') }}" class="inline-flex items-center gap-2 text-sm font-medium hover:underline" style="color:#2196F3">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            Retour aux talents
        </a>
    </div>

    {{-- Header talent --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-start gap-5">
            {{-- Avatar --}}
            <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-2xl font-bold text-white flex-shrink-0"
                 style="background:linear-gradient(135deg,#1A2744,#2196F3)">
                {{ mb_strtoupper(mb_substr($talent->stage_name ?? $talent->user?->first_name ?? '?', 0, 1)) }}
            </div>

            {{-- Info --}}
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ $talent->stage_name ?? ($talent->user?->first_name . ' ' . $talent->user?->last_name) }}
                    </h1>
                    @if($talent->is_verified)
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold" style="background:#d1fae5;color:#065f46">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            Vérifié
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold" style="background:#fef3c7;color:#92400e">
                            Non vérifié
                        </span>
                    @endif
                </div>

                <div class="flex flex-wrap gap-4 mt-2 text-sm text-gray-500">
                    @if($talent->category)
                        <span class="flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                            {{ $talent->category->name ?? $talent->category->label ?? '—' }}
                        </span>
                    @endif
                    @if($talent->city)
                        <span class="flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            {{ $talent->city }}
                        </span>
                    @endif
                    @if($talent->cachet_amount)
                        <span class="flex items-center gap-1 font-semibold text-gray-700">
                            Cachet : {{ number_format($talent->cachet_amount, 0, ',', ' ') }} XOF
                        </span>
                    @endif
                </div>

                @if($talent->bio)
                    <p class="mt-3 text-sm text-gray-600 leading-relaxed">{{ $talent->bio }}</p>
                @endif
            </div>
        </div>

        {{-- Packages --}}
        @if($talent->servicePackages && $talent->servicePackages->isNotEmpty())
        <div class="mt-5 pt-5 border-t border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Packages de services</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($talent->servicePackages as $pkg)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-50 text-blue-800 border border-blue-100">
                    {{ $pkg->name ?? $pkg->title ?? 'Package' }}
                    @if($pkg->price)
                        — {{ number_format($pkg->price, 0, ',', ' ') }} XOF
                    @endif
                </span>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Réservations du talent --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Réservations</h2>
            <span class="text-sm text-gray-500">{{ $bookings->total() }} au total</span>
        </div>

        @if($bookings->isEmpty())
            <div class="p-10 text-center text-gray-500 text-sm">Aucune réservation pour ce talent.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr style="background:#f8fafc">
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date événement</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Package</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Montant</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($bookings as $booking)
                        @php
                            $sk = $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status;
                            $sc = ['pending'=>'#FF9800','accepted'=>'#2196F3','paid'=>'#00BCD4','confirmed'=>'#4CAF50','completed'=>'#9C27B0','cancelled'=>'#f44336','disputed'=>'#FF5722'][$sk] ?? '#6b7280';
                            $sl = ['pending'=>'En attente','accepted'=>'Acceptée','paid'=>'Payée','confirmed'=>'Confirmée','completed'=>'Terminée','cancelled'=>'Annulée','disputed'=>'En litige'][$sk] ?? $sk;
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900">
                                {{ $booking->client?->first_name }} {{ $booking->client?->last_name }}
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ $booking->event_date ? \Carbon\Carbon::parse($booking->event_date)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ $booking->servicePackage?->name ?? $booking->servicePackage?->title ?? '—' }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-800">
                                {{ $booking->total_amount ? number_format($booking->total_amount, 0, ',', ' ') . ' XOF' : '—' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold text-white" style="background:{{ $sc }}">
                                    {{ $sl }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('manager.bookings.show', $booking->id) }}" class="text-xs font-medium hover:underline" style="color:#2196F3">
                                    Détail
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($bookings->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $bookings->links() }}
            </div>
            @endif
        @endif
    </div>

</div>
@endsection
