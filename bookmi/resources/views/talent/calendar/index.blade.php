@extends('layouts.talent')

@section('title', 'Calendrier — BookMi Talent')

@section('head')
<style>
main.page-content { background: #F2EFE9 !important; }
.cal-grid-7 { display: grid; grid-template-columns: repeat(7, 1fr); }
.cal-cell { min-height: 80px; border-bottom: 1px solid #f3f4f6; border-right: 1px solid #f3f4f6; position: relative; }
.cal-cell-past { opacity: 0.5; }
.cal-cell-active { cursor: pointer; transition: box-shadow 0.15s; }
.cal-cell-active:hover { box-shadow: inset 0 0 0 2px #FF6B35; }
</style>
@endsection

@section('content')
<div class="space-y-6" x-data="calendarApp()">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-800 text-sm font-medium">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-800 text-sm font-medium">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-black text-gray-900">Calendrier</h1>
            <p class="text-sm text-gray-400 mt-0.5 font-semibold">Gérez vos disponibilités</p>
        </div>

        {{-- Légende --}}
        <div class="flex items-center gap-4 text-xs font-medium text-gray-600 flex-wrap">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full inline-block" style="background:#4CAF50"></span> Disponible</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full inline-block" style="background:#f44336"></span> Bloqué</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full inline-block" style="background:#9E9E9E"></span> Repos</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded inline-block" style="background:#FF6B35"></span> Réservation</span>
        </div>
    </div>

    {{-- Navigation mois --}}
    @php
        $currentDate = \Carbon\Carbon::createFromDate($year, $month, 1);
        $prevMonth   = $currentDate->copy()->subMonth();
        $nextMonth   = $currentDate->copy()->addMonth();
    @endphp
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <a href="{{ route('talent.calendar', ['month' => $prevMonth->month, 'year' => $prevMonth->year]) }}"
               class="flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-orange-600 transition-colors px-3 py-2 rounded-lg hover:bg-orange-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ ucfirst($prevMonth->locale('fr')->isoFormat('MMMM')) }}
            </a>
            <h2 class="text-lg font-bold text-gray-900">
                {{ ucfirst($currentDate->locale('fr')->isoFormat('MMMM YYYY')) }}
            </h2>
            <a href="{{ route('talent.calendar', ['month' => $nextMonth->month, 'year' => $nextMonth->year]) }}"
               class="flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-orange-600 transition-colors px-3 py-2 rounded-lg hover:bg-orange-50">
                {{ ucfirst($nextMonth->locale('fr')->isoFormat('MMMM')) }}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        {{-- Grille calendrier --}}
        @php
            $daysOfWeek = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
            $firstDayOfMonth = $currentDate->copy()->startOfMonth();
            $startPad = ($firstDayOfMonth->isoWeekday() - 1);
            $daysInMonth = $currentDate->daysInMonth;
            $today = now()->format('Y-m-d');
        @endphp

        {{-- En-têtes jours --}}
        <div class="cal-grid-7" style="border-bottom: 1px solid #f3f4f6;">
            @foreach($daysOfWeek as $day)
                <div style="padding:10px 0;text-align:center;font-size:0.72rem;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.06em;">{{ $day }}</div>
            @endforeach
        </div>

        {{-- Cases jours --}}
        <div class="cal-grid-7">
            {{-- Padding début --}}
            @for($p = 0; $p < $startPad; $p++)
                <div class="cal-cell" style="background:#FAFAFA;"></div>
            @endfor

            @for($d = 1; $d <= $daysInMonth; $d++)
                @php
                    $dateStr     = $currentDate->copy()->day($d)->format('Y-m-d');
                    $dateLabel   = ucfirst($currentDate->copy()->day($d)->locale('fr')->isoFormat('dddd D MMMM YYYY'));
                    $slot        = $slots[$dateStr] ?? null;
                    $dayBookings = $bookingsByDate[$dateStr] ?? collect();
                    $hasBookings = $dayBookings->isNotEmpty();

                    $slotStatus = $slot ? ($slot->status instanceof \BackedEnum ? $slot->status->value : (string) $slot->status) : null;
                    $isToday    = $dateStr === $today;
                    $isPast     = $dateStr < $today;

                    $bgColor = match(true) {
                        $hasBookings                => '#FFF3E0',
                        $slotStatus === 'available' => '#f0fdf4',
                        $slotStatus === 'blocked'   => '#fff5f5',
                        $slotStatus === 'rest'      => '#f9fafb',
                        default                     => 'white',
                    };

                    $dotColor = match($slotStatus) {
                        'available' => '#4CAF50',
                        'blocked'   => '#f44336',
                        'rest'      => '#9E9E9E',
                        default     => null,
                    };

                    // Build booking data for the JS modal (only when needed)
                    $bookingData = $hasBookings ? $dayBookings->map(fn($b) => [
                        'id'         => $b->id,
                        'client'     => trim(($b->client?->first_name ?? '') . ' ' . ($b->client?->last_name ?? '')),
                        'start_time' => $b->start_time
                            ? \Carbon\Carbon::createFromTimeString($b->start_time)->format('H:i')
                            : null,
                        'status'     => $b->status instanceof \BackedEnum ? $b->status->value : (string) $b->status,
                    ])->values()->toArray() : [];
                @endphp
                <div
                    class="cal-cell {{ !$isPast ? 'cal-cell-active' : 'cal-cell-past' }}"
                    style="background:{{ $bgColor }}"
                    @if(!$isPast)
                        @if($hasBookings)
                        @click="openBookingModal({{ Js::from($dateLabel) }}, {{ Js::from($bookingData) }})"
                        @else
                        @click="openAvailabilityModal('{{ $dateStr }}', '{{ $slotStatus ?? '' }}', {{ $slot ? $slot->id : 'null' }})"
                        @endif
                    @endif
                >
                    {{-- Numéro jour --}}
                    <span style="position:absolute;top:6px;left:8px;font-size:0.72rem;font-weight:700;z-index:1;{{ $isToday ? 'background:#FF6B35;color:#fff;width:20px;height:20px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;' : 'color:#374151;' }}">
                        {{ $d }}
                    </span>

                    {{-- Événements de réservation --}}
                    @if($hasBookings)
                    <div style="position:absolute;top:26px;left:3px;right:3px;bottom:3px;display:flex;flex-direction:column;gap:2px;overflow:hidden;">
                        @foreach($dayBookings->take(2) as $booking)
                        @php
                            $bStatus = $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status;
                            [$bBg, $bText] = match($bStatus) {
                                'pending'   => ['#FFF3E0', '#B45309'],
                                'accepted'  => ['#EFF6FF', '#1D4ED8'],
                                'paid'      => ['#ECFDF5', '#065F46'],
                                'confirmed' => ['#F0FDF4', '#15803D'],
                                'completed' => ['#F5F3FF', '#5B21B6'],
                                'disputed'  => ['#FEF2F2', '#991B1B'],
                                default     => ['#F3F4F6', '#374151'],
                            };
                            $clientName = $booking->client ? $booking->client->first_name : 'Client';
                            $timeLabel  = $booking->start_time
                                ? \Carbon\Carbon::createFromTimeString($booking->start_time)->format('H:i') . ' · '
                                : '';
                        @endphp
                        <div style="background:{{ $bBg }};color:{{ $bText }};font-size:0.6rem;font-weight:700;padding:2px 4px;border-radius:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.4;border-left:2px solid {{ $bText }};">
                            {{ $timeLabel }}{{ $clientName }}
                        </div>
                        @endforeach
                        @if($dayBookings->count() > 2)
                        <div style="font-size:0.55rem;color:#6B7280;font-weight:600;padding:0 4px;">+{{ $dayBookings->count() - 2 }} autre(s)</div>
                        @endif
                    </div>
                    @elseif($dotColor)
                    {{-- Indicateur statut (seulement si pas de réservation) --}}
                    <div style="position:absolute;bottom:6px;left:0;right:0;display:flex;justify-content:center;">
                        <span style="width:6px;height:6px;border-radius:50%;background:{{ $dotColor }};display:inline-block;"></span>
                    </div>
                    @endif
                </div>
            @endfor

            {{-- Padding fin --}}
            @php
                $lastDayOfMonth = $currentDate->copy()->endOfMonth();
                $endPad = 7 - $lastDayOfMonth->isoWeekday();
            @endphp
            @for($p = 0; $p < $endPad; $p++)
                <div class="cal-cell" style="background:#FAFAFA;"></div>
            @endfor
        </div>
    </div>

    {{-- ── Modal détail réservations ─────────────────────────────────── --}}
    <div
        x-show="bookingModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center"
        style="background:rgba(0,0,0,0.45)"
        @click.self="bookingModalOpen = false"
    >
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 overflow-hidden" @click.stop>
            {{-- Header modal --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100" style="background:#fff8f5">
                <div>
                    <h3 class="text-base font-black text-gray-900">Réservations</h3>
                    <p class="text-xs text-gray-400 mt-0.5 font-semibold capitalize" x-text="bookingModalDate"></p>
                </div>
                <button type="button" @click="bookingModalOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Liste des réservations --}}
            <div class="p-4 space-y-3 max-h-80 overflow-y-auto">
                <template x-for="booking in bookingModalItems" :key="booking.id">
                    <div class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:border-orange-200 transition-colors">
                        {{-- Avatar initiale client --}}
                        <div class="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold text-white" style="background:#FF6B35">
                            <span x-text="booking.client ? booking.client.charAt(0).toUpperCase() : '?'"></span>
                        </div>

                        {{-- Infos --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-gray-900 truncate" x-text="booking.client || 'Client inconnu'"></p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                <span x-show="booking.start_time" x-text="'🕐 ' + booking.start_time"></span>
                                <span x-show="!booking.start_time" class="italic">Heure non précisée</span>
                            </p>
                        </div>

                        {{-- Badge statut + lien --}}
                        <div class="flex flex-col items-end gap-1.5">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                                  :style="statusStyle(booking.status)"
                                  x-text="statusLabel(booking.status)">
                            </span>
                            <a :href="'/talent/bookings/' + booking.id"
                               class="text-xs font-semibold hover:underline"
                               style="color:#FF6B35">
                                Voir les détails →
                            </a>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end">
                <button type="button" @click="bookingModalOpen = false"
                        class="px-5 py-2.5 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">
                    Fermer
                </button>
            </div>
        </div>
    </div>

    {{-- ── Modal disponibilité (inchangé) ────────────────────────────── --}}
    <div
        x-show="availabilityModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center"
        style="background:rgba(0,0,0,0.4)"
        @click.self="availabilityModalOpen = false"
    >
        <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm mx-4" @click.stop>
            <h3 class="text-lg font-bold text-gray-900 mb-1">Définir la disponibilité</h3>
            <p class="text-sm text-gray-500 mb-5" x-text="'Date : ' + selectedDate"></p>

            <form method="POST" action="{{ route('talent.calendar.availability') }}">
                @csrf
                <input type="hidden" name="date" :value="selectedDate">

                <div class="space-y-2 mb-5">
                    @foreach([
                        ['value' => 'available', 'label' => 'Disponible', 'color' => '#4CAF50', 'desc' => 'Ouvert aux réservations'],
                        ['value' => 'blocked',   'label' => 'Bloqué',     'color' => '#f44336', 'desc' => 'Indisponible (événement privé)'],
                        ['value' => 'rest',      'label' => 'Repos',      'color' => '#9E9E9E', 'desc' => 'Journée de repos'],
                    ] as $opt)
                    <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer hover:border-orange-300 transition-colors"
                           :class="selectedStatus === '{{ $opt['value'] }}' ? 'border-orange-400 bg-orange-50' : ''">
                        <input type="radio" name="status" value="{{ $opt['value'] }}"
                               x-model="selectedStatus"
                               class="sr-only">
                        <span class="w-4 h-4 rounded-full flex-shrink-0" style="background:{{ $opt['color'] }}"></span>
                        <div>
                            <span class="text-sm font-semibold text-gray-900">{{ $opt['label'] }}</span>
                            <p class="text-xs text-gray-400">{{ $opt['desc'] }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="availabilityModalOpen = false"
                            class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">
                        Annuler
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
                            style="background:#FF6B35"
                            :disabled="!selectedStatus">
                        Enregistrer
                    </button>
                </div>
            </form>

            {{-- Supprimer le slot --}}
            <template x-if="slotId">
                <form method="POST" :action="'/talent/calendar/availability/' + slotId" class="mt-2">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-full px-4 py-2 rounded-xl text-sm font-medium text-red-600 border border-red-200 hover:bg-red-50 transition-colors"
                            onclick="return confirm('Supprimer ce créneau ?')">
                        Supprimer le créneau
                    </button>
                </form>
            </template>
        </div>
    </div>

</div>

@section('scripts')
<script>
function calendarApp() {
    return {
        // Modal réservations
        bookingModalOpen:  false,
        bookingModalDate:  '',
        bookingModalItems: [],

        // Modal disponibilité
        availabilityModalOpen: false,
        selectedDate:   '',
        selectedStatus: '',
        slotId:         null,

        openBookingModal(dateLabel, bookings) {
            this.bookingModalDate  = dateLabel;
            this.bookingModalItems = bookings;
            this.bookingModalOpen  = true;
        },

        openAvailabilityModal(date, status, slotId) {
            this.selectedDate   = date;
            this.selectedStatus = status || '';
            this.slotId         = slotId;
            this.availabilityModalOpen = true;
        },

        statusLabel(status) {
            const labels = {
                pending:   'En attente',
                accepted:  'Acceptée',
                paid:      'Payée',
                confirmed: 'Confirmée',
                completed: 'Terminée',
                cancelled: 'Annulée',
                disputed:  'Litige',
            };
            return labels[status] || status;
        },

        statusStyle(status) {
            const styles = {
                pending:   'background:#FFF3E0;color:#B45309',
                accepted:  'background:#EFF6FF;color:#1D4ED8',
                paid:      'background:#ECFDF5;color:#065F46',
                confirmed: 'background:#F0FDF4;color:#15803D',
                completed: 'background:#F5F3FF;color:#5B21B6',
                cancelled: 'background:#F9FAFB;color:#4B5563',
                disputed:  'background:#FEF2F2;color:#991B1B',
            };
            return styles[status] || 'background:#F3F4F6;color:#374151';
        },
    }
}
</script>
@endsection
@endsection
