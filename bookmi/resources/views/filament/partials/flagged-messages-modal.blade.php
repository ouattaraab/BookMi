<div class="space-y-3 max-h-96 overflow-y-auto">
    @forelse($messages as $message)
        <div class="p-3 rounded-lg border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800">
            <div class="flex items-center justify-between gap-2 mb-1">
                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                    {{ $message->sender?->name ?? '—' }}
                </span>
                <span class="text-xs text-gray-400">
                    {{ $message->created_at?->format('d/m/Y H:i') }}
                </span>
            </div>
            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $message->content }}</p>
        </div>
    @empty
        <p class="text-sm text-gray-500 text-center py-4">Aucun message signalé.</p>
    @endforelse
</div>
