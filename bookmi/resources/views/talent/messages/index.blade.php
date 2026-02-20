@extends('layouts.talent')

@section('title', 'Messages — BookMi Talent')

@section('content')
<div class="space-y-6">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-800 text-sm font-medium">{{ session('success') }}</div>
    @endif

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-black text-gray-900">Messages</h1>
        <p class="text-sm text-gray-400 mt-0.5 font-semibold">Vos conversations avec les clients</p>
    </div>

    @if($conversations->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-center bg-white rounded-2xl border border-gray-100">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background:#fff3e0">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" stroke="#FF6B35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </div>
            <p class="text-gray-700 font-semibold mb-1">Aucun message</p>
            <p class="text-gray-400 text-sm">Vos conversations avec les clients apparaîtront ici.</p>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="divide-y divide-gray-50">
                @foreach($conversations as $conversation)
                    @php
                        $latest = $conversation->latestMessage;
                        $unread = $conversation->messages()
                            ->where('sender_id', '!=', auth()->id())
                            ->whereNull('read_at')
                            ->count();
                    @endphp
                    <a href="{{ route('talent.messages.show', $conversation->id) }}"
                       class="flex items-center gap-4 px-5 py-4 hover:bg-orange-50/50 transition-colors group">
                        {{-- Avatar --}}
                        <div class="flex-shrink-0 w-11 h-11 rounded-full flex items-center justify-center text-white font-bold text-base relative" style="background:#FF6B35">
                            {{ strtoupper(substr($conversation->client->first_name ?? 'C', 0, 1)) }}
                            @if($unread > 0)
                                <span class="absolute -top-1 -right-1 w-5 h-5 rounded-full text-white text-xs flex items-center justify-center font-bold" style="background:#f44336">{{ $unread }}</span>
                            @endif
                        </div>
                        {{-- Contenu --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-semibold text-gray-900 {{ $unread > 0 ? 'text-gray-900' : '' }} truncate">
                                    {{ $conversation->client->first_name ?? '—' }} {{ $conversation->client->last_name ?? '' }}
                                </span>
                                @if($conversation->last_message_at)
                                    <span class="text-xs text-gray-400 flex-shrink-0">
                                        {{ $conversation->last_message_at->diffForHumans() }}
                                    </span>
                                @endif
                            </div>
                            @if($latest)
                                <p class="text-sm truncate mt-0.5 {{ $unread > 0 ? 'text-gray-700 font-medium' : 'text-gray-400' }}">
                                    {{ $latest->sender_id === auth()->id() ? 'Vous : ' : '' }}{{ $latest->content }}
                                </p>
                            @else
                                <p class="text-sm text-gray-300 mt-0.5">Aucun message</p>
                            @endif
                        </div>
                        {{-- Flèche --}}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-300 group-hover:text-orange-400 transition-colors flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                @endforeach
            </div>
        </div>

        @if($conversations->hasPages())
            <div>{{ $conversations->links() }}</div>
        @endif
    @endif

</div>
@endsection
