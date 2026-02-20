@extends('layouts.client')

@section('title', 'Conversation — BookMi Client')

@section('head')
<style>
/* ── Chat bubble client (right) ── */
.bubble-client {
    background: linear-gradient(135deg, #1565C0, #2196F3);
    color: white;
    border-radius: 1rem 1rem 0.25rem 1rem;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    line-height: 1.55;
    max-width: 22rem;
    word-break: break-word;
    box-shadow: 0 2px 12px rgba(33,150,243,0.30);
}
/* ── Chat bubble talent (left) ── */
.bubble-talent {
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.10);
    color: rgba(255,255,255,0.90);
    border-radius: 1rem 1rem 1rem 0.25rem;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    line-height: 1.55;
    max-width: 22rem;
    word-break: break-word;
    backdrop-filter: blur(8px);
}
/* ── Messages scroll container ── */
#msg-scroll {
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.10) transparent;
}
#msg-scroll::-webkit-scrollbar { width: 4px; }
#msg-scroll::-webkit-scrollbar-track { background: transparent; }
#msg-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.12); border-radius: 2px; }
/* ── Input area ── */
.msg-input-wrap {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.09);
    border-radius: 1rem;
    padding: 0.625rem 0.75rem;
    display: flex; align-items: flex-end; gap: 0.75rem;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}
.msg-input-wrap textarea {
    flex: 1; resize: none; border: none; outline: none;
    background: transparent;
    color: rgba(255,255,255,0.90);
    font-size: 0.875rem;
    font-family: 'Nunito', sans-serif;
    max-height: 8rem; overflow-y: auto;
    line-height: 1.5;
}
.msg-input-wrap textarea::placeholder { color: rgba(255,255,255,0.30); }
.send-btn {
    width: 2.375rem; height: 2.375rem;
    border-radius: 0.75rem;
    display: flex; align-items: center; justify-content: center;
    background: var(--blue);
    color: white; border: none; cursor: pointer;
    flex-shrink: 0;
    transition: background 0.2s, transform 0.15s, opacity 0.2s;
    box-shadow: 0 2px 10px rgba(33,150,243,0.35);
}
.send-btn:hover:not(:disabled) { background: #1565C0; transform: scale(1.05); }
.send-btn:disabled { opacity: 0.35; cursor: not-allowed; }
/* ── Back button ── */
.back-btn {
    display: inline-flex; align-items: center; gap: 0.5rem;
    padding: 0.4rem 0.75rem;
    border-radius: 0.625rem;
    font-size: 0.8rem; font-weight: 700;
    background: var(--glass-bg); border: 1px solid var(--glass-border);
    color: var(--text-muted);
    text-decoration: none;
    transition: all 0.2s;
    flex-shrink: 0;
}
.back-btn:hover { color: var(--text); border-color: rgba(255,255,255,0.18); }
</style>
@endsection

@section('content')
@php
    $talentName = $conversation->talentProfile->stage_name
        ?? trim(($conversation->talentProfile->user->first_name ?? '') . ' ' . ($conversation->talentProfile->user->last_name ?? ''))
        ?: 'Talent';
@endphp
<div class="flex flex-col" style="height:calc(100vh - 10rem); max-width: 48rem">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-4 flex-shrink-0">
        <a href="{{ route('client.messages') }}" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M15 19l-7-7 7-7"/></svg>
        </a>

        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-black text-sm flex-shrink-0"
             style="background:linear-gradient(135deg,var(--navy),var(--blue));box-shadow:0 0 12px rgba(33,150,243,0.28)">
            {{ strtoupper(substr($talentName, 0, 1)) }}
        </div>
        <div>
            <p class="font-black text-sm" style="color:var(--text)">{{ $talentName }}</p>
            @if($conversation->talentProfile->category)
                <p class="text-xs" style="color:var(--text-muted)">{{ $conversation->talentProfile->category->name }}</p>
            @endif
        </div>
    </div>

    {{-- Messages scroll --}}
    <div class="flex-1 overflow-y-auto rounded-2xl p-4 space-y-4 mb-4"
         id="msg-scroll"
         style="background:rgba(255,255,255,0.025);border:1px solid var(--glass-border)"
         x-data x-init="$el.scrollTop = $el.scrollHeight">

        @if($conversation->messages->isEmpty())
            <div class="flex items-center justify-center h-full">
                <p class="text-sm" style="color:var(--text-muted)">Aucun message. Commencez la conversation !</p>
            </div>
        @else
            @foreach($conversation->messages as $message)
            @php $isClient = $message->sender_id === auth()->id(); @endphp
            <div class="flex {{ $isClient ? 'justify-end' : 'justify-start' }} items-end gap-2">

                {{-- Talent avatar (left) --}}
                @if(!$isClient)
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-black flex-shrink-0"
                     style="background:rgba(255,255,255,0.12)">
                    {{ strtoupper(substr($message->sender->first_name ?? $message->sender->name ?? 'T', 0, 1)) }}
                </div>
                @endif

                <div class="flex flex-col {{ $isClient ? 'items-end' : 'items-start' }}">
                    <div class="{{ $isClient ? 'bubble-client' : 'bubble-talent' }}">{{ $message->content }}</div>
                    <p class="text-xs mt-1" style="color:var(--text-faint)">
                        {{ $message->created_at->format('H:i') }}
                        @if($isClient)
                            @if($message->read_at)
                                <span class="ml-1" style="color:var(--blue-light)" title="Lu">✓✓</span>
                            @else
                                <span class="ml-1" style="color:var(--text-faint)" title="Envoyé">✓</span>
                            @endif
                        @endif
                    </p>
                </div>

                {{-- Client avatar (right) --}}
                @if($isClient)
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-black flex-shrink-0"
                     style="background:linear-gradient(135deg,var(--navy),var(--blue))">
                    {{ strtoupper(substr(auth()->user()->first_name ?? 'M', 0, 1)) }}
                </div>
                @endif
            </div>
            @endforeach
        @endif
    </div>

    {{-- Send form --}}
    <div class="flex-shrink-0">
        @if($errors->has('content'))
            <p class="text-xs mb-2" style="color:rgba(252,165,165,0.9)">{{ $errors->first('content') }}</p>
        @endif
        <form action="{{ route('client.messages.send', $conversation->id) }}" method="POST"
              x-data="{ message: '' }"
              @submit="setTimeout(() => { message = '' }, 100)">
            @csrf
            <div class="msg-input-wrap">
                <textarea
                    name="content"
                    x-model="message"
                    rows="1"
                    placeholder="Écrivez un message..."
                    x-ref="textarea"
                    @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                    @keydown.enter.prevent.exact="if(message.trim()) $el.closest('form').submit()"
                    @keydown.shift.enter="$event.stopPropagation()"
                ></textarea>
                <button type="submit" class="send-btn" :disabled="!message.trim()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </div>
        </form>
        <p class="text-xs text-center mt-2" style="color:var(--text-faint)">Entrée pour envoyer · Maj+Entrée pour saut de ligne</p>
    </div>

</div>
@endsection
