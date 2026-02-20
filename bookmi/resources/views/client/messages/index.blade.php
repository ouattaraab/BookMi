@extends('layouts.client')

@section('title', 'Mes messages — BookMi Client')

@section('content')
<div class="space-y-6">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 rounded-xl p-4 text-green-800 text-sm font-medium">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 rounded-xl p-4 text-red-800 text-sm font-medium">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Messages</h1>
        <p class="text-sm text-gray-500 mt-1">Vos conversations avec les talents</p>
    </div>

    @if($conversations->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-center bg-white rounded-2xl border border-gray-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <p class="text-gray-500 text-lg font-medium mb-2">Aucune conversation</p>
            <p class="text-gray-400 text-sm">Vos échanges avec les talents apparaîtront ici.</p>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-gray-100 divide-y divide-gray-50 overflow-hidden">
            @foreach($conversations as $conversation)
            @php
                $talentUser = $conversation->talentProfile->user ?? null;
                $talentName = $talentUser->name ?? 'Talent';
                $talentInitial = strtoupper(substr($talentName, 0, 1));
                $lastMsg = $conversation->latestMessage;
                $lastMsgPreview = $lastMsg ? Str::limit($lastMsg->content, 60) : 'Aucun message';
                $lastMsgDate = $conversation->last_message_at
                    ? \Carbon\Carbon::parse($conversation->last_message_at)->diffForHumans()
                    : '';
            @endphp
            <a href="{{ route('client.messages.show', $conversation->id) }}"
               class="flex items-center gap-4 px-5 py-4 hover:bg-blue-50 transition-colors group">
                {{-- Avatar --}}
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white font-bold text-lg flex-shrink-0" style="background:#2196F3">
                    {{ $talentInitial }}
                </div>
                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors truncate">{{ $talentName }}</span>
                        <span class="text-xs text-gray-400 flex-shrink-0 ml-2">{{ $lastMsgDate }}</span>
                    </div>
                    <p class="text-sm text-gray-500 truncate">{{ $lastMsgPreview }}</p>
                    @if($conversation->talentProfile->category)
                        <p class="text-xs text-gray-400 mt-0.5">{{ $conversation->talentProfile->category->name }}</p>
                    @endif
                </div>
                {{-- Flèche --}}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-300 group-hover:text-blue-400 transition-colors flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($conversations->hasPages())
            <div class="mt-6">
                {{ $conversations->links() }}
            </div>
        @endif
    @endif

</div>
@endsection
