@extends('layouts.talent')

@section('title', 'Calendrier — BookMi Talent')

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
            <h1 class="text-2xl font-bold text-gray-900">Calendrier</h1>
            <p class="text-sm text-gray-500 mt-1">Gérez vos disponibilités</p>
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
        <div class="grid grid-cols-7 border-b border-gray-100">
            @foreach($daysOfWeek as $day)
                <div class="py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $day }}</div>
            @endforeach
        </div>

        {{-- Cases jours --}}
        <div class="grid grid-cols-7">
            {{-- Padding début --}}
            @for($p = 0; $p < $startPad; $p++)
                <div class="h-16 border-b border-r border-gray-50 bg-gray-50/50"></div>
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
                    class="h-16 border-b border-r border-gray-100 relative {{ !$isPast ? 'cursor-pointer hover:ring-2 hover:ring-orange-400 hover:ring-inset transition-all' : 'opacity-50' }}"
                    style="background:{{ $bgColor }}"
                    @if(!$isPast)
                    @click="openModal('{{ $dateStr }}', '{{ $slotStatus ?? '' }}', {{ $slot ? $slot->id : 'null' }})"
                    @endif
                >
                    {{-- Numéro jour --}}
                    <span class="absolute top-1.5 left-2 text-xs font-bold {{ $isToday ? 'text-white w-5 h-5 rounded-full flex items-center justify-center' : 'text-gray-700' }}"
                          @if($isToday) style="background:#FF6B35" @endif>
                        {{ $d }}
                    </span>

                    {{-- Indicateur statut --}}
                    @if($dotColor)
                    <div class="absolute bottom-1.5 left-0 right-0 flex justify-center">
                        <span class="w-1.5 h-1.5 rounded-full" style="background:{{ $dotColor }}"></span>
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
                <div class="h-16 border-b border-r border-gray-50 bg-gray-50/50"></div>
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
