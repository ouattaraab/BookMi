<div class="p-4 space-y-1">

    {{-- En-tête réservation --}}
    <div class="mb-4 pb-3 border-b border-gray-200 dark:border-white/10">
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">
            Réservation #{{ $booking->id }}
            &mdash;
            <span class="font-normal text-gray-500">
                {{ $booking->client?->first_name }} {{ $booking->client?->last_name }}
                ({{ $booking->client?->email ?? '—' }})
                &rarr;
                {{ $booking->talentProfile?->stage_name ?? 'Talent inconnu' }}
            </span>
        </p>
        <p class="text-xs text-gray-400 mt-0.5">
            Statut : <span class="font-medium">{{ ucfirst($booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status) }}</span>
        </p>
    </div>

    @php
        $totalMessages = $conversations->sum(fn($c) => $c->messages->count());
    @endphp

    @if($conversations->isEmpty() || $totalMessages === 0)
        <div class="flex flex-col items-center justify-center py-10 text-center text-gray-400">
            <x-heroicon-o-chat-bubble-left-right class="w-10 h-10 mb-2 opacity-40" />
            <p class="text-sm">Aucun échange entre les parties pour cette réservation.</p>
        </div>
    @else
        @foreach($conversations as $conversation)
            @if($conversation->messages->isNotEmpty())

                {{-- Label de la conversation --}}
                <div class="flex items-center gap-2 mb-2 mt-4 first:mt-0">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                        @if($conversation->booking_request_id)
                            Conversation liée à cette réservation
                        @else
                            Conversation générale (hors réservation)
                        @endif
                    </span>
                    <span class="flex-1 h-px bg-gray-100 dark:bg-white/10"></span>
                    <span class="text-xs text-gray-400">{{ $conversation->messages->count() }} message(s)</span>
                </div>

                {{-- Messages --}}
                <div class="space-y-3">
                    @foreach($conversation->messages->sortBy('created_at') as $message)
                        @php
                            $isClient   = $message->sender_id === $clientId;
                            $isAutoReply = $message->is_auto_reply;

                            if ($isAutoReply) {
                                $roleBadge   = 'Réponse auto';
                                $badgeClass  = 'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400';
                                $bubbleClass = 'bg-gray-50 border border-gray-200 dark:bg-white/5 dark:border-white/10';
                                $nameClass   = 'text-gray-400';
                            } elseif ($isClient) {
                                $roleBadge   = 'Client';
                                $badgeClass  = 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400';
                                $bubbleClass = 'bg-blue-50 border border-blue-100 dark:bg-blue-900/20 dark:border-blue-800/40';
                                $nameClass   = 'text-blue-700 dark:text-blue-400';
                            } else {
                                $roleBadge   = 'Talent / Manager';
                                $badgeClass  = 'bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400';
                                $bubbleClass = 'bg-purple-50 border border-purple-100 dark:bg-purple-900/20 dark:border-purple-800/40';
                                $nameClass   = 'text-purple-700 dark:text-purple-400';
                            }

                            $senderName = $message->sender
                                ? trim(($message->sender->first_name ?? '') . ' ' . ($message->sender->last_name ?? ''))
                                : 'Inconnu';
                        @endphp

                        <div class="rounded-lg px-3 py-2.5 {{ $bubbleClass }}">
                            <div class="flex items-baseline justify-between gap-2 mb-1">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="text-xs font-semibold {{ $nameClass }} truncate">
                                        {{ $senderName }}
                                    </span>
                                    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium {{ $badgeClass }} shrink-0">
                                        {{ $roleBadge }}
                                    </span>
                                    @if($message->is_flagged)
                                        <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400 shrink-0">
                                            ⚑ Signalé
                                        </span>
                                    @endif
                                </div>
                                <time class="text-[11px] text-gray-400 whitespace-nowrap shrink-0">
                                    {{ $message->created_at?->format('d/m/Y H:i') ?? '—' }}
                                </time>
                            </div>

                            @if($message->type?->value === 'text' || $message->type === null)
                                <p class="text-sm text-gray-700 dark:text-gray-200 leading-relaxed whitespace-pre-wrap break-words">{{ $message->content }}</p>
                            @elseif($message->type?->value === 'image')
                                @if($message->media_path)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($message->media_path) }}" target="_blank" rel="noopener">
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($message->media_path) }}"
                                            alt="Image partagée"
                                            class="mt-1 max-h-64 max-w-xs rounded-lg border border-gray-200 dark:border-white/10 object-contain cursor-pointer hover:opacity-90 transition-opacity"
                                        >
                                    </a>
                                @else
                                    <p class="text-xs text-gray-400 italic">📷 Image partagée</p>
                                @endif
                                @if($message->content)
                                    <p class="text-sm text-gray-700 dark:text-gray-200 mt-1 whitespace-pre-wrap break-words">{{ $message->content }}</p>
                                @endif
                            @elseif($message->type?->value === 'audio')
                                <p class="text-xs text-gray-400 italic">🎵 Message audio</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        @endforeach

        {{-- Résumé --}}
        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-white/10">
            <p class="text-xs text-gray-400">
                {{ $conversations->count() }} conversation(s) &mdash; {{ $totalMessages }} message(s) au total
            </p>
        </div>
    @endif
</div>
