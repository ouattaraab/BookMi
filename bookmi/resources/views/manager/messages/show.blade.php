@extends('layouts.manager')

@section('title', 'Conversation — BookMi Manager')

@section('content')
@php
    $talentName = $conversation->talentProfile?->stage_name
        ?? ($conversation->talentProfile?->user?->first_name . ' ' . $conversation->talentProfile?->user?->last_name);
    $clientName = $conversation->client?->first_name . ' ' . $conversation->client?->last_name;
    $myId = auth()->id();
@endphp

<div class="space-y-4">

    {{-- Retour --}}
    <div>
        <a href="{{ route('manager.messages') }}" class="inline-flex items-center gap-2 text-sm font-medium hover:underline" style="color:#2196F3">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            Retour aux messages
        </a>
    </div>

    {{-- Header conversation --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <div class="flex items-center gap-4">
            {{-- Avatars --}}
            <div class="relative flex-shrink-0 w-14 h-14">
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white absolute top-0 left-0"
                     style="background:linear-gradient(135deg,#1A2744,#2196F3)">
                    {{ mb_strtoupper(mb_substr($talentName, 0, 1)) }}
                </div>
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white absolute bottom-0 right-0 border-2 border-white"
                     style="background:#6b7280">
                    {{ mb_strtoupper(mb_substr($clientName, 0, 1)) }}
                </div>
            </div>

            <div>
                <h1 class="text-lg font-bold text-gray-900">
                    {{ $talentName }}
                    <span class="font-normal text-gray-400 mx-1">↔</span>
                    {{ $clientName }}
                </h1>
                <p class="text-sm text-gray-500">
                    Talent géré : <span class="font-medium" style="color:#1A2744">{{ $talentName }}</span>
                    &nbsp;·&nbsp;
                    Client : <span class="font-medium text-gray-700">{{ $clientName }}</span>
                </p>
            </div>
        </div>
    </div>

    {{-- Zone messages --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col" style="min-height:400px">

        {{-- Fil de messages --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-4" id="messages-container">
            @forelse($conversation->messages as $msg)
            @php
                $isMine = $msg->sender_id === $myId;
                $senderName = $msg->sender?->first_name . ' ' . $msg->sender?->last_name;
                $msgDate = $msg->created_at?->format('d/m/Y à H:i');
            @endphp
            <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[75%] space-y-1">
                    @if(!$isMine)
                        <p class="text-xs font-medium px-1" style="color:#6b7280">{{ $senderName }}</p>
                    @endif
                    <div class="rounded-2xl px-4 py-3 text-sm leading-relaxed {{ $isMine ? 'text-white rounded-tr-sm' : 'text-gray-800 rounded-tl-sm' }}"
                         style="{{ $isMine ? 'background:#1A2744' : 'background:#f1f5f9;border:1px solid #e2e8f0' }}">
                        {{ $msg->content }}
                    </div>
                    <p class="text-xs px-1 {{ $isMine ? 'text-right' : '' }}" style="color:#9ca3af">
                        {{ $msgDate }}
                        @if($isMine && $msg->read_at)
                            &nbsp;· Lu
                        @endif
                    </p>
                </div>
            </div>
            @empty
            <div class="flex items-center justify-center h-32 text-gray-400 text-sm">
                Aucun message dans cette conversation.
            </div>
            @endforelse
        </div>

        {{-- Formulaire d'envoi --}}
        <div class="border-t border-gray-100 p-4" style="background:#f8fafc">
            <form method="POST" action="{{ route('manager.messages.send', $conversation->id) }}"
                  x-data="{ content: '', submitting: false }"
                  @submit="submitting = true">
                @csrf
                @if($errors->has('content'))
                    <p class="text-xs text-red-500 mb-2">{{ $errors->first('content') }}</p>
                @endif
                <div class="flex items-end gap-3">
                    <div class="flex-1">
                        <textarea
                            name="content"
                            x-model="content"
                            rows="2"
                            maxlength="2000"
                            placeholder="Écrivez votre message..."
                            class="w-full resize-none rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:border-transparent placeholder-gray-400 bg-white"
                            style="focus-ring-color:#2196F3"
                            @keydown.enter.prevent="if(!$event.shiftKey && content.trim()) { $el.closest('form').submit(); }"
                        >{{ old('content') }}</textarea>
                        <p class="text-xs text-gray-400 mt-1 text-right" x-text="content.length + '/2000'"></p>
                    </div>
                    <button
                        type="submit"
                        :disabled="!content.trim() || submitting"
                        class="flex-shrink-0 inline-flex items-center justify-center w-11 h-11 rounded-xl text-white transition-all duration-150 disabled:opacity-40 disabled:cursor-not-allowed"
                        style="background:linear-gradient(135deg,#1A2744,#2196F3)"
                        onmouseover="if(!this.disabled) this.style.opacity='0.85'"
                        onmouseout="this.style.opacity='1'"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                    </button>
                </div>
                <p class="text-xs text-gray-400 mt-1">Entrée pour envoyer · Maj+Entrée pour nouvelle ligne</p>
            </form>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    // Scroll vers le bas au chargement
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('messages-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    });
</script>
@endsection
