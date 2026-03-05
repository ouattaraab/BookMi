<div class="space-y-4 text-sm">
    <div>
        <p class="font-semibold text-gray-500 dark:text-gray-400">Email</p>
        <p class="text-gray-900 dark:text-white">{{ $record->email }}</p>
    </div>

    <div>
        <p class="font-semibold text-gray-500 dark:text-gray-400">Adresse IP</p>
        <p class="text-gray-900 dark:text-white font-mono">{{ $record->ip_address ?? '—' }}</p>
    </div>

    <div>
        <p class="font-semibold text-gray-500 dark:text-gray-400">User-Agent</p>
        <p class="text-gray-900 dark:text-white font-mono break-all text-xs bg-gray-100 dark:bg-gray-800 rounded p-2">
            {{ $record->user_agent ?? '—' }}
        </p>
    </div>

    <div>
        <p class="font-semibold text-gray-500 dark:text-gray-400">Canal détecté</p>
        <p class="text-gray-900 dark:text-white">
            @switch($record->client_type)
                @case('web') Navigateur Web @break
                @case('mobile') Application Mobile (Flutter/Dart) @break
                @default Appel API direct @break
            @endswitch
        </p>
    </div>

    <div>
        <p class="font-semibold text-gray-500 dark:text-gray-400">Blocage</p>
        <p class="text-gray-900 dark:text-white">
            Du <strong>{{ $record->locked_at->format('d/m/Y à H:i') }}</strong>
            au <strong>{{ $record->locked_until->format('d/m/Y à H:i') }}</strong>
            après <strong>{{ $record->attempts_count }}</strong> tentatives.
        </p>
    </div>

    @if($record->unlocked_at)
    <div>
        <p class="font-semibold text-gray-500 dark:text-gray-400">Déverrouillé manuellement</p>
        <p class="text-gray-900 dark:text-white">
            Le {{ $record->unlocked_at->format('d/m/Y à H:i') }}
            @if($record->unlockedBy)
                par {{ $record->unlockedBy->first_name }} {{ $record->unlockedBy->last_name }}
            @endif
        </p>
    </div>
    @endif
</div>
