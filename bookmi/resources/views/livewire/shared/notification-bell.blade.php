<div wire:poll.30s class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button
        @click="open = !open"
        class="relative p-2 rounded-xl text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
        </svg>
        @if($this->unreadCount > 0)
            <span
                class="absolute rounded-full flex items-center justify-center text-white font-bold"
                style="top: 1px; right: 1px; width: 13px; height: 13px; font-size: 7px; background: {{ $accentColor }}; line-height: 1;"
            >
                {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute top-12 w-80 rounded-2xl bg-white shadow-2xl border border-gray-100 overflow-hidden z-50"
        style="right: 0; left: auto; width: 20rem; box-shadow: 0 16px 48px rgba(0,0,0,0.15)"
    >
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <p class="text-sm font-bold text-gray-900">Notifications</p>
            @if($this->unreadCount > 0)
                <button
                    wire:click="markAllRead"
                    class="text-xs font-semibold flex items-center gap-1"
                    style="color: {{ $accentColor }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 7 17l-5-5"/><path d="m22 10-7.5 7.5L13 16"/>
                    </svg>
                    Tout lire
                </button>
            @endif
        </div>
        <div class="max-h-72 overflow-y-auto">
            @forelse($this->notifications as $notif)
                <button
                    wire:click="markRead({{ $notif->id }})"
                    class="w-full text-left px-4 py-3 border-b border-gray-50 hover:bg-gray-50 transition-colors"
                >
                    <div class="flex items-start gap-2">
                        @if(! $notif->read_at)
                            <span class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0" style="background: {{ $accentColor }}"></span>
                        @endif
                        <div class="{{ $notif->read_at ? 'ml-4' : '' }}">
                            <p class="text-xs font-semibold text-gray-800">{{ $notif->title ?? 'Notification' }}</p>
                            @if($notif->body)
                                <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ $notif->body }}</p>
                            @endif
                            <p class="text-[10px] text-gray-300 mt-1">
                                {{ $notif->created_at->translatedFormat('j M, H:i') }}
                            </p>
                        </div>
                    </div>
                </button>
            @empty
                <p class="text-sm text-gray-400 text-center py-8">Aucune notification</p>
            @endforelse
        </div>
    </div>
</div>
