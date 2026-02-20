@extends('layouts.client')

@section('title', 'Conversation — BookMi Client')

@section('content')
<div class="flex flex-col h-[calc(100vh-8rem)] max-w-3xl">

    {{-- Header conversation --}}
    <div class="flex items-center gap-3 mb-4 flex-shrink-0">
        <a href="{{ route('client.messages') }}" class="p-2 rounded-xl hover:bg-gray-100 transition-colors text-gray-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        @php
            $talentName = $conversation->talentProfile->stage_name
                ?? trim(($conversation->talentProfile->user->first_name ?? '') . ' ' . ($conversation->talentProfile->user->last_name ?? ''))
                ?: 'Talent';
        @endphp
        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold flex-shrink-0" style="background:#2196F3">
            {{ strtoupper(substr($talentName, 0, 1)) }}
        </div>
        <div>
            <p class="font-bold text-gray-900">{{ $talentName }}</p>
            @if($conversation->talentProfile->category)
                <p class="text-xs text-gray-400">{{ $conversation->talentProfile->category->name }}</p>
            @endif
        </div>
    </div>

    {{-- Zone messages scrollable --}}
    <div class="flex-1 overflow-y-auto bg-white rounded-2xl border border-gray-100 p-4 space-y-3 mb-4"
         id="messages-container"
         x-data x-init="$el.scrollTop = $el.scrollHeight">

        @if($conversation->messages->isEmpty())
            <div class="flex items-center justify-center h-full text-gray-400 text-sm">
                Aucun message. Commencez la conversation !
            </div>
        @else
            @foreach($conversation->messages as $message)
                @php
                    $isClient = $message->sender_id === auth()->id();
                @endphp
                <div class="flex {{ $isClient ? 'justify-end' : 'justify-start' }}">
                    @if(!$isClient)
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0 mr-2 self-end" style="background:#6b7280">
                        {{ strtoupper(substr($message->sender->name ?? 'T', 0, 1)) }}
                    </div>
                    @endif
                    <div class="max-w-xs lg:max-w-md">
                        <div class="px-4 py-2.5 rounded-2xl text-sm leading-relaxed
                            {{ $isClient
                                ? 'text-white rounded-br-sm'
                                : 'bg-gray-100 text-gray-800 rounded-bl-sm' }}"
                            @if($isClient) style="background:#2196F3" @endif>
                            {{ $message->content }}
                        </div>
                        <p class="text-xs text-gray-400 mt-1 {{ $isClient ? 'text-right' : 'text-left' }}">
                            {{ $message->created_at->format('H:i') }}
                            @if($isClient && $message->read_at)
                                <span class="ml-1" title="Lu">✓✓</span>
                            @elseif($isClient)
                                <span class="ml-1 text-gray-300" title="Envoyé">✓</span>
                            @endif
                        </p>
                    </div>
                    @if($isClient)
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0 ml-2 self-end" style="background:#2196F3">
                        {{ strtoupper(substr(auth()->user()->name ?? 'M', 0, 1)) }}
                    </div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>

    {{-- Form envoi message --}}
    <div class="flex-shrink-0">
        <form action="{{ route('client.messages.send', $conversation->id) }}" method="POST"
              x-data="{ message: '' }"
              @submit="setTimeout(() => { message = '' }, 100)">
            @csrf
            @if($errors->has('content'))
                <p class="text-red-500 text-xs mb-2">{{ $errors->first('content') }}</p>
            @endif
            <div class="flex gap-3 items-end bg-white rounded-2xl border border-gray-200 p-3">
                <textarea
                    name="content"
                    x-model="message"
                    rows="1"
                    placeholder="Écrivez un message..."
                    class="flex-1 resize-none border-none outline-none text-sm text-gray-800 placeholder-gray-400 bg-transparent max-h-32 overflow-y-auto"
                    x-ref="textarea"
                    @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                    @keydown.enter.prevent.exact="if(message.trim()) $el.closest('form').submit()"
                    @keydown.shift.enter="$event.stopPropagation()"
                ></textarea>
                <button type="submit"
                    :disabled="!message.trim()"
                    :class="message.trim() ? 'opacity-100' : 'opacity-40 cursor-not-allowed'"
                    class="w-10 h-10 rounded-xl flex items-center justify-center text-white transition-opacity flex-shrink-0"
                    style="background:#2196F3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-1 text-center">Entrée pour envoyer · Maj+Entrée pour sauter une ligne</p>
        </form>
    </div>

</div>
@endsection
