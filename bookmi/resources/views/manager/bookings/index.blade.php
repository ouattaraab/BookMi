@extends('layouts.manager')

@section('title', 'Réservations — BookMi Manager')

@section('content')
<div class="space-y-6" x-data="{ activeTab: '{{ request('status', '') }}' }">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Réservations</h1>
        <p class="text-gray-500 text-sm mt-1">Toutes les réservations de vos talents</p>
    </div>

    {{-- Filtres --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-4">

        {{-- Tabs statut --}}
        @php
            $tabs = [
                '' => 'Toutes',
                'pending' => 'En attente',
                'accepted' => 'Acceptées',
                'confirmed' => 'Confirmées',
                'completed' => 'Terminées',
                'cancelled' => 'Annulées',
            ];
            $currentStatus = request('status', '');
            $currentTalentId = request('talent_id', '');
        @endphp

        <div class="flex flex-wrap gap-2">
            @foreach($tabs as $value => $label)
            <a href="{{ route('manager.bookings', array_filter(['status' => $value, 'talent_id' => $currentTalentId])) }}"
               class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-150 {{ $currentStatus === $value ? 'text-white' : 'text-gray-600 bg-gray-100 hover:bg-gray-200' }}"
               style="{{ $currentStatus === $value ? 'background:linear-gradient(135deg,#1A2744,#2196F3)' : '' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>

        {{-- Filtre talent --}}
        @if($talents->isNotEmpty())
        <form method="GET" action="{{ route('manager.bookings') }}" class="flex items-center gap-3">
            @if($currentStatus)
                <input type="hidden" name="status" value="{{ $currentStatus }}">
            @endif
            <label class="text-sm font-medium text-gray-600 flex-shrink-0">Talent :</label>
            <select name="talent_id" onchange="this.form.submit()"
                    class="flex-1 max-w-xs rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-300 bg-white">
                <option value="">Tous les talents</option>
                @foreach($talents as $t)
                    <option value="{{ $t->id }}" {{ $currentTalentId == $t->id ? 'selected' : '' }}>
                        {{ $t->user?->first_name }} {{ $t->user?->last_name }}
                        @if($t->stage_name) ({{ $t->stage_name }}) @endif
                    </option>
                @endforeach
            </select>
            @if($currentTalentId)
            <a href="{{ route('manager.bookings', array_filter(['status' => $currentStatus])) }}"
               class="text-xs text-gray-400 hover:text-gray-600 underline">
                Réinitialiser
            </a>
            @endif
        </form>
        @endif
    </div>

    {{-- Tableau réservations --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

        @if($bookings->isEmpty())
            <div class="p-16 text-center">
                <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4" style="background:#fff3e0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#FF6B35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                </div>
                <h3 class="text-base font-semibold text-gray-900 mb-1">Aucune réservation</h3>
                <p class="text-gray-500 text-sm">Aucune réservation ne correspond aux filtres sélectionnés.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr style="background:#f8fafc">
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Talent</th>
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
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">
                                    {{ $booking->talentProfile?->stage_name ?? ($booking->talentProfile?->user?->first_name . ' ' . $booking->talentProfile?->user?->last_name) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
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
                                <a href="{{ route('manager.bookings.show', $booking->id) }}"
                                   class="text-xs font-semibold hover:underline" style="color:#2196F3">
                                    Voir
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

    <p class="text-xs text-gray-400 text-right">{{ $bookings->total() }} réservation(s) au total</p>

</div>
@endsection
