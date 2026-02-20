@extends('layouts.talent')

@section('title', 'Conversation — BookMi Talent')

@section('content')
<div class="flex flex-col h-full space-y-4">

    {{-- Back --}}
    <div>
        <a href="{{ route('talent.messages') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-orange-600 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Retour aux messages
        </a>
    </div>

    {{-- Conversation --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm flex flex-col overflow-hidden" style="min-height: 60vh">
        {{-- Header conversation --}}
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3" style="background:#fff8f5">
            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold" style="background:#FF6B35">
                {{ strtoupper(substr($conversation->client->first_name ?? 'C', 0, 1)) }}
            </div>
            <div>
                <p class="font-semibold text-gray-900">{{ $conversation->client->first_name ?? '—' }} {{ $conversation->client->last_name ?? '' }}</p>
                <p class="text-xs text-gray-400">Client</p>
            </div>
        </div>

        {{-- Messages --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-4" id="messages-container">
            @forelse($conversation->messages as $message)
                @php
                    $isMe = $message->sender_id === auth()->id();
                @endphp
                <div class="flex {{ $isMe ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-xs md:max-w-md lg:max-w-lg">
                        <div class="px-4 py-3 rounded-2xl text-sm leading-relaxed
                            {{ $isMe ? 'text-white rounded-br-sm' : 'bg-gray-100 text-gray-800 rounded-bl-sm' }}"
                             @if($isMe) style="background:#FF6B35" @endif>
                            {{ $message->content }}
                        </div>
                        <div class="flex items-center gap-1 mt-1 {{ $isMe ? 'justify-end' : 'justify-start' }}">
                            <span class="text-xs text-gray-400">{{ $message->created_at->format('H:i') }}</span>
                            @if($isMe && $message->read_at)
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12 text-gray-400 text-sm">Aucun message pour l'instant. Démarrez la conversation !</div>
            @endforelse
        </div>

        {{-- Form envoi --}}
        <div class="border-t border-gray-100 p-4">
            <form method="POST" action="{{ route('talent.messages.send', $conversation->id) }}" class="flex items-end gap-3">
                @csrf
                <textarea
                    name="content"
                    rows="2"
                    placeholder="Écrire un message..."
                    maxlength="2000"
                    required
                    class="flex-1 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 resize-none"
                    onkeydown="if(event.ctrlKey && event.key === 'Enter') this.form.submit();"
                ></textarea>
                <button type="submit"
                        class="flex-shrink-0 w-11 h-11 rounded-xl flex items-center justify-center text-white transition-opacity hover:opacity-90"
                        style="background:#FF6B35">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </form>
            <p class="text-xs text-gray-400 mt-1.5">Ctrl+Entrée pour envoyer</p>
        </div>
    </div>
</div>

@section('scripts')
<script>
    // Auto-scroll to bottom
    const container = document.getElementById('messages-container');
    if (container) container.scrollTop = container.scrollHeight;
</script>
@endsection
@endsection
