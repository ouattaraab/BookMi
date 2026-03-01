<div class="p-4 space-y-1">
    {{-- Booking header --}}
    <div class="mb-4 pb-3 border-b border-gray-200 dark:border-white/10">
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">
            Réservation #{{ $booking->id }}
            &mdash;
            <span class="font-normal text-gray-500">
                {{ $booking->client?->email ?? 'Client inconnu' }}
                &rarr;
                {{ $booking->talentProfile?->stage_name ?? 'Talent inconnu' }}
            </span>
        </p>
        <p class="text-xs text-gray-400 mt-0.5">
            Créée le {{ $booking->created_at?->format('d/m/Y à H:i') ?? '—' }}
        </p>
    </div>

    @if($logs->isEmpty())
        <p class="text-sm text-gray-400 py-4 text-center">Aucune entrée de chronologie.</p>
    @else
        <ol class="relative border-l border-gray-200 dark:border-white/10 ml-3">
            @foreach($logs as $log)
                @php
                    $statusColors = [
                        'pending'   => 'bg-yellow-400',
                        'accepted'  => 'bg-blue-500',
                        'paid'      => 'bg-purple-500',
                        'confirmed' => 'bg-cyan-500',
                        'completed' => 'bg-green-500',
                        'cancelled' => 'bg-gray-400',
                        'rejected'  => 'bg-red-500',
                        'disputed'  => 'bg-orange-500',
                    ];
                    $statusLabels = [
                        'pending'   => 'En attente',
                        'accepted'  => 'Acceptée',
                        'paid'      => 'Payée',
                        'confirmed' => 'Confirmée',
                        'completed' => 'Terminée',
                        'cancelled' => 'Annulée',
                        'rejected'  => 'Rejetée',
                        'disputed'  => 'Litige',
                    ];
                    $dotColor   = $statusColors[$log->to_status] ?? 'bg-gray-400';
                    $toLabel    = $statusLabels[$log->to_status] ?? ucfirst($log->to_status ?? '—');
                    $fromLabel  = $log->from_status ? ($statusLabels[$log->from_status] ?? ucfirst($log->from_status)) : 'Création';
                @endphp
                <li class="mb-5 ml-5">
                    <span class="absolute flex items-center justify-center w-3 h-3 rounded-full -left-1.5 ring-2 ring-white dark:ring-gray-900 {{ $dotColor }}"></span>
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 leading-tight">
                                {{ $fromLabel }} &rarr; {{ $toLabel }}
                            </p>
                            @if($log->performer)
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Par {{ $log->performer->first_name ?? '' }} {{ $log->performer->last_name ?? '' }}
                                    ({{ $log->performer->email ?? '' }})
                                </p>
                            @else
                                <p class="text-xs text-gray-400 mt-0.5">Système automatique</p>
                            @endif
                        </div>
                        <time class="text-xs text-gray-400 whitespace-nowrap shrink-0">
                            {{ $log->created_at?->format('d/m/Y H:i') ?? '—' }}
                        </time>
                    </div>
                </li>
            @endforeach
        </ol>

        {{-- Current state footer --}}
        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-white/10">
            <p class="text-xs text-gray-400">
                Statut actuel :
                <span class="font-semibold text-gray-600 dark:text-gray-300">
                    {{ $statusLabels[$booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status] ?? ucfirst((string) $booking->status) }}
                </span>
                &mdash; {{ $logs->count() }} transition(s) enregistrée(s)
            </p>
        </div>
    @endif
</div>
