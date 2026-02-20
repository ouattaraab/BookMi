@extends('layouts.talent')

@section('title', 'Calendrier — BookMi Talent')

@section('head')
<style>
main.page-content { background: #F2EFE9 !important; }
.cal-grid-7 { display: grid; grid-template-columns: repeat(7, 1fr); }
.cal-cell { min-height: 64px; border-bottom: 1px solid #f3f4f6; border-right: 1px solid #f3f4f6; position: relative; }
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
        <div class="flex items-center gap-4 text-xs font-medium text-gray-600">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full inline-block" style="background:#4CAF50"></span> Disponible</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full inline-block" style="background:#f44336"></span> Bloqué</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full inline-block" style="background:#9E9E9E"></span> Repos</span>
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
            // Pad to Monday (1=Mon ... 7=Sun in isoWeekday)
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
                    $dateStr = $currentDate->copy()->day($d)->format('Y-m-d');
                    $slot    = $slots[$dateStr] ?? null;
                    $slotStatus = $slot ? ($slot->status instanceof \BackedEnum ? $slot->status->value : (string) $slot->status) : null;
                    $isToday = $dateStr === $today;
                    $isPast  = $dateStr < $today;

                    $bgColor = match($slotStatus) {
                        'available' => '#f0fdf4',
                        'blocked'   => '#fff5f5',
                        'rest'      => '#f9fafb',
                        default     => 'white',
                    };
                    $dotColor = match($slotStatus) {
                        'available' => '#4CAF50',
                        'blocked'   => '#f44336',
                        'rest'      => '#9E9E9E',
                        default     => null,
                    };
                    $slotLabel = match($slotStatus) {
                        'available' => 'Disponible',
                        'blocked'   => 'Bloqué',
                        'rest'      => 'Repos',
                        default     => null,
                    };
                @endphp
                <div
                    class="cal-cell {{ !$isPast ? 'cal-cell-active' : 'cal-cell-past' }}"
                    style="background:{{ $bgColor }}"
                    @if(!$isPast)
                    @click="openModal('{{ $dateStr }}', '{{ $slotStatus ?? '' }}', {{ $slot ? $slot->id : 'null' }})"
                    @endif
                >
                    {{-- Numéro jour --}}
                    <span style="position:absolute;top:6px;left:8px;font-size:0.72rem;font-weight:700;{{ $isToday ? 'background:#FF6B35;color:#fff;width:20px;height:20px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;' : 'color:#374151;' }}">
                        {{ $d }}
                    </span>

                    {{-- Indicateur statut --}}
                    @if($dotColor)
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

    {{-- Modal Alpine.js --}}
    <div
        x-show="modalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center"
        style="background:rgba(0,0,0,0.4)"
        @click.self="modalOpen = false"
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
                    <button type="button" @click="modalOpen = false"
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
        modalOpen: false,
        selectedDate: '',
        selectedStatus: '',
        slotId: null,
        openModal(date, status, slotId) {
            this.selectedDate = date;
            this.selectedStatus = status || '';
            this.slotId = slotId;
            this.modalOpen = true;
        }
    }
}
</script>
@endsection
@endsection
