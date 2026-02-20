@extends('layouts.manager')

@section('title', 'Messages — BookMi Manager')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-black text-gray-900">Messages</h1>
        <p class="text-gray-500 text-sm mt-1">Conversations de vos talents avec leurs clients</p>
    </div>

    {{-- Liste conversations --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

        @if($conversations->isEmpty())
            <div class="p-16 text-center">
                <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4" style="background:#d1fae5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4CAF50" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-gray-900 mb-1">Aucune conversation</h3>
                <p class="text-gray-500 text-sm">Vos talents n'ont pas encore de conversations.</p>
            </div>
        @else
            <ul class="divide-y divide-gray-50">
                @foreach($conversations as $conv)
                @php
                    $talentName = $conv->talentProfile?->stage_name
                        ?? ($conv->talentProfile?->user?->first_name . ' ' . $conv->talentProfile?->user?->last_name);
                    $clientName = $conv->client?->first_name . ' ' . $conv->client?->last_name;
                    $latestMsg  = $conv->latestMessage;
                    $preview    = $latestMsg ? \Illuminate\Support\Str::limit($latestMsg->content, 80) : 'Aucun message';
                    $date       = $conv->last_message_at ?? $conv->updated_at;
                    $isToday    = $date && $date->isToday();
                    $dateLabel  = $date ? ($isToday ? $date->format('H:i') : $date->format('d/m/Y')) : '';
                @endphp
                <li>
                    <a href="{{ route('manager.messages.show', $conv->id) }}"
                       class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 transition-colors group">

                        {{-- Avatar double --}}
                        <div class="relative flex-shrink-0 w-12 h-12">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold text-white absolute top-0 left-0"
                                 style="background:linear-gradient(135deg,#1A2744,#2196F3)">
                                {{ mb_strtoupper(mb_substr($talentName, 0, 1)) }}
                            </div>
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white absolute bottom-0 right-0 border-2 border-white"
                                 style="background:#6b7280">
                                {{ mb_strtoupper(mb_substr($clientName, 0, 1)) }}
                            </div>
                        </div>

                        {{-- Contenu --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-semibold text-gray-900 text-sm truncate">
                                    {{ $talentName }}
                                    <span class="font-normal text-gray-400 mx-1">↔</span>
                                    {{ $clientName }}
                                </p>
                                <span class="text-xs text-gray-400 flex-shrink-0">{{ $dateLabel }}</span>
                            </div>
                            <p class="text-sm text-gray-500 truncate mt-0.5">{{ $preview }}</p>
                        </div>

                        {{-- Chevron --}}
                        <div class="flex-shrink-0 text-gray-300 group-hover:text-gray-400 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                        </div>
                    </a>
                </li>
                @endforeach
            </ul>

            {{-- Pagination --}}
            @if($conversations->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $conversations->links() }}
            </div>
            @endif
        @endif
    </div>

</div>
@endsection
