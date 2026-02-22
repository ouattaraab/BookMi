@extends('layouts.talent')

@section('title', 'Conversation — BookMi Talent')

@section('head')
<style>
/* ── Media bubble ── */
.bubble-media-img {
    max-width: 18rem;
    border-radius: 0.875rem;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.2s;
    margin-top: 4px;
}
.bubble-media-img:hover { transform: scale(1.02); }
.bubble-media-img img, .bubble-media-img video {
    width: 100%; display: block; max-height: 20rem; object-fit: cover;
}
/* ── Avatar ── */
.msg-avatar {
    width: 1.875rem; height: 1.875rem; border-radius: 50%;
    object-fit: cover; flex-shrink: 0;
}
.msg-avatar-init {
    width: 1.875rem; height: 1.875rem; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.7rem; font-weight: 900; color: white; flex-shrink: 0;
}
/* ── Media upload button ── */
.media-btn {
    width: 2.5rem; height: 2.5rem;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    background: #fff3ee;
    color: #FF6B35;
    border: 1.5px solid #ffd5c2;
    cursor: pointer; flex-shrink: 0;
    transition: background 0.2s, box-shadow 0.2s;
}
.media-btn:hover { background: #ffe8de; box-shadow: 0 2px 8px rgba(255,107,53,0.18); }
/* ── Media preview ── */
.media-preview {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 12px;
    background: #fff8f5;
    border: 1px solid #ffd5c2;
    border-radius: 10px;
    margin-bottom: 8px;
}
.media-preview img, .media-preview video {
    height: 54px; width: 80px; border-radius: 8px; object-fit: cover;
}
.media-preview-cancel {
    margin-left: auto; background: #fee2e2;
    border: none; cursor: pointer;
    width: 24px; height: 24px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: #ef4444; transition: background 0.2s;
}
.media-preview-cancel:hover { background: #fca5a5; }
/* ── Lightbox ── */
.lightbox {
    position: fixed; inset: 0; z-index: 1000;
    background: rgba(0,0,0,0.92);
    display: flex; align-items: center; justify-content: center;
    cursor: zoom-out;
}
.lightbox img { max-width: 90vw; max-height: 90vh; border-radius: 0.75rem; }
</style>
@endsection

@section('content')
@php
    $client          = $conversation->client;
    $clientFirstName = $client->first_name ?? 'Client';
    $clientLastName  = $client->last_name  ?? '';
    $clientFullName  = trim("{$clientFirstName} {$clientLastName}");
    $talentProfile   = auth()->user()->talentProfile;
    $myPhotoUrl      = $talentProfile?->profilePhotoUrl ?? null;
    $myName          = $talentProfile?->stage_name ?? auth()->user()->first_name ?? 'Moi';

    // Booking context
    $booking        = $conversation->bookingRequest;
    $bookingStatus  = null;
    if ($booking) {
        $bookingStatus = $booking->status instanceof \BackedEnum
            ? $booking->status->value
            : (string) $booking->status;
    }
    $canSendMessage = !in_array($bookingStatus, ['completed', 'cancelled', 'disputed']);
@endphp
<div class="flex flex-col h-full space-y-4" x-data="{ lightboxSrc: null }">

    {{-- Back --}}
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('talent.messages') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-orange-600 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Retour aux messages
        </a>
        @if($booking)
            <span style="font-size:0.72rem;font-weight:700;padding:3px 10px;border-radius:9999px;background:#FFF8F5;border:1px solid #FFD5C2;color:#C85A20;">
                Réservation #{{ $booking->id }}
                @if($booking->event_date)
                    · {{ \Carbon\Carbon::parse($booking->event_date)->format('d/m/Y') }}
                @endif
            </span>
        @endif
    </div>

    {{-- Conversation --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm flex flex-col overflow-hidden" style="min-height: 60vh">

        {{-- Header conversation --}}
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3" style="background:#fff8f5">
            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0" style="background:#1A2744">
                {{ strtoupper(substr($clientFirstName, 0, 1)) }}
            </div>
            <div>
                <p class="font-semibold text-gray-900">{{ $clientFullName }}</p>
                <p class="text-xs text-gray-400">Client</p>
            </div>
        </div>

        {{-- Messages --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-4" id="messages-container">
            @forelse($conversation->messages as $message)
                @php $isMe = $message->sender_id === auth()->id(); @endphp
                <div class="flex items-end gap-2 {{ $isMe ? 'justify-end' : 'justify-start' }}">

                    {{-- Client avatar (left) --}}
                    @if(!$isMe)
                        <div class="msg-avatar-init flex-shrink-0" style="background:#1A2744">
                            {{ strtoupper(substr($clientFirstName, 0, 1)) }}
                        </div>
                    @endif

                    <div class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }} max-w-xs md:max-w-md lg:max-w-lg">
                        {{-- Text --}}
                        @if($message->content)
                            <div class="px-4 py-3 rounded-2xl text-sm leading-relaxed
                                {{ $isMe ? 'text-white rounded-br-sm' : 'bg-gray-100 text-gray-800 rounded-bl-sm' }}"
                                 @if($isMe) style="background:#FF6B35" @endif>
                                {{ $message->content }}
                            </div>
                        @endif

                        {{-- Media --}}
                        @if($message->media_path)
                            @php $mediaUrl = Storage::disk('public')->url($message->media_path); @endphp
                            <div class="bubble-media-img"
                                 @if($message->media_type === 'image') @click="lightboxSrc = '{{ $mediaUrl }}'" @endif>
                                @if($message->media_type === 'video')
                                    <video controls preload="none" style="border-radius:0.875rem;max-height:20rem;">
                                        <source src="{{ $mediaUrl }}" type="video/mp4">
                                    </video>
                                @else
                                    <img src="{{ $mediaUrl }}" alt="Image" loading="lazy"
                                         style="border-radius:0.875rem;max-height:20rem;width:auto;max-width:18rem;">
                                @endif
                            </div>
                        @endif

                        <div class="flex items-center gap-1 mt-1 {{ $isMe ? 'justify-end' : 'justify-start' }}">
                            <span class="text-xs text-gray-400">{{ $message->created_at->format('H:i') }}</span>
                            @if($isMe && $message->read_at)
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @endif
                        </div>
                    </div>

                    {{-- Talent avatar (right, me) --}}
                    @if($isMe)
                        @if($myPhotoUrl)
                            <img src="{{ $myPhotoUrl }}" alt="{{ $myName }}" class="msg-avatar"
                                 style="border:2px solid rgba(255,107,53,0.35)">
                        @else
                            <div class="msg-avatar-init flex-shrink-0" style="background:linear-gradient(135deg,#FF6B35,#C85A20)">
                                {{ strtoupper(substr($myName, 0, 1)) }}
                            </div>
                        @endif
                    @endif
                </div>
            @empty
                <div class="text-center py-12 text-gray-400 text-sm">Aucun message pour l'instant. Démarrez la conversation !</div>
            @endforelse
        </div>

        {{-- Error message --}}
        @if($errors->has('content'))
            <div class="mx-4 mb-2 px-4 py-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm flex items-start gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="flex-shrink-0 mt-0.5"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <span>{{ $errors->first('content') }}</span>
            </div>
        @endif

        {{-- Locked state --}}
        @if(!$canSendMessage)
            <div class="mx-4 mb-4 px-4 py-3 rounded-xl flex items-center gap-2"
                 style="background:#F9F8F5;border:1px solid #E5E1DA;color:#8A8278;font-size:0.8rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0;color:#C8C3BC;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Réservation terminée — les échanges via ce fil sont désactivés.
            </div>
        @else
            {{-- Form envoi — vanilla JS only (no Alpine for media) --}}
            <div class="border-t border-gray-100 p-4">

                {{-- Media preview --}}
                <div class="media-preview" id="bm-media-preview" style="display:none;margin-bottom:8px;">
                    <img id="bm-preview-img" alt="Aperçu" style="height:54px;width:80px;border-radius:8px;object-fit:cover;display:none;">
                    <video id="bm-preview-vid" style="height:54px;width:80px;border-radius:8px;object-fit:cover;display:none;"></video>
                    <span class="text-xs text-gray-500 ml-1" id="bm-preview-type"></span>
                    <button type="button" class="media-preview-cancel" onclick="bmClearMedia()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('talent.messages.send', $conversation->id) }}"
                      enctype="multipart/form-data" class="flex items-end gap-3">
                    @csrf
                    {{-- Label wrapping hidden file input — most reliable cross-browser approach --}}
                    <label class="media-btn" title="Photo ou vidéo" style="cursor:pointer;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9l4-4 4 4 4-4 4 4"/><circle cx="8.5" cy="13.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                        <input type="file" name="media" accept="image/*,video/*"
                               id="bm-media-input"
                               style="display:none;"
                               onchange="bmHandleMedia(this)">
                    </label>

                    <textarea
                        name="content"
                        rows="2"
                        placeholder="Écrire un message..."
                        maxlength="2000"
                        id="bm-textarea"
                        class="flex-1 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 resize-none"
                        oninput="bmUpdateSendBtn()"
                        onkeydown="if(event.ctrlKey && event.key === 'Enter' && !document.getElementById('bm-send-btn').disabled) this.form.submit();"
                    >{{ old('content') }}</textarea>

                    <button type="submit" id="bm-send-btn" disabled
                            class="flex-shrink-0 w-11 h-11 rounded-xl flex items-center justify-center text-white transition-opacity hover:opacity-90"
                            style="background:#FF6B35;opacity:0.4;cursor:not-allowed;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </form>
                <p class="text-xs text-gray-400 mt-1.5 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Ctrl+Entrée pour envoyer · Numéros de téléphone masqués pour votre sécurité
                </p>
            </div>
        @endif
    </div>

    {{-- Lightbox --}}
    <div class="lightbox" x-show="lightboxSrc" x-cloak
         @click="lightboxSrc = null" style="display:none">
        <img :src="lightboxSrc" alt="Agrandir">
    </div>
</div>

<script>
// Scroll to bottom
(function() {
    var el = document.getElementById('messages-container');
    if (el) el.scrollTop = el.scrollHeight;
})();

// ── Vanilla JS media handlers ──
function bmHandleMedia(input) {
    var file = input.files[0];
    if (!file) { bmUpdateSendBtn(); return; }
    var previewEl = document.getElementById('bm-media-preview');
    var imgEl     = document.getElementById('bm-preview-img');
    var vidEl     = document.getElementById('bm-preview-vid');
    var typeEl    = document.getElementById('bm-preview-type');
    if (file.type.startsWith('video/')) {
        imgEl.style.display = 'none';
        vidEl.style.display = 'block';
        vidEl.src = URL.createObjectURL(file);
        typeEl.textContent = 'Vidéo sélectionnée';
    } else {
        vidEl.style.display = 'none';
        imgEl.style.display = 'block';
        imgEl.src = URL.createObjectURL(file);
        typeEl.textContent = 'Image sélectionnée';
    }
    previewEl.style.display = 'flex';
    bmUpdateSendBtn();
}

function bmClearMedia() {
    var input = document.getElementById('bm-media-input');
    if (input) input.value = '';
    var previewEl = document.getElementById('bm-media-preview');
    if (previewEl) previewEl.style.display = 'none';
    var imgEl = document.getElementById('bm-preview-img');
    var vidEl = document.getElementById('bm-preview-vid');
    if (imgEl) { imgEl.src = ''; }
    if (vidEl) { vidEl.src = ''; }
    bmUpdateSendBtn();
}

function bmUpdateSendBtn() {
    var input    = document.getElementById('bm-media-input');
    var textarea = document.getElementById('bm-textarea');
    var sendBtn  = document.getElementById('bm-send-btn');
    if (!sendBtn) return;
    var hasMedia = input && input.files && input.files.length > 0;
    var hasText  = textarea && textarea.value.trim().length > 0;
    var canSend  = hasMedia || hasText;
    sendBtn.disabled = !canSend;
    sendBtn.style.opacity    = canSend ? '1'   : '0.4';
    sendBtn.style.cursor     = canSend ? 'pointer' : 'not-allowed';
}

// Enable send button if there's pre-filled content (old input on error)
(function() {
    var ta = document.getElementById('bm-textarea');
    if (ta && ta.value.trim().length > 0) bmUpdateSendBtn();
})();
</script>
@endsection
