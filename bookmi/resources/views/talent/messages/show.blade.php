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
@endphp
<div class="flex flex-col h-full space-y-4" x-data="{ lightboxSrc: null }">

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

        {{-- Form envoi --}}
        <div class="border-t border-gray-100 p-4"
             x-data="{
                 message: '',
                 mediaFile: null,
                 mediaPreviewSrc: null,
                 mediaPreviewType: null,
                 pickMedia() { this.$refs.mediaInput.click(); },
                 onMediaChange(e) {
                     const f = e.target.files[0];
                     if (!f) return;
                     this.mediaFile = f;
                     this.mediaPreviewType = f.type.startsWith('video/') ? 'video' : 'image';
                     const r = new FileReader();
                     r.onload = ev => { this.mediaPreviewSrc = ev.target.result; };
                     r.readAsDataURL(f);
                 },
                 clearMedia() {
                     this.mediaFile = null; this.mediaPreviewSrc = null; this.mediaPreviewType = null;
                     this.$refs.mediaInput.value = '';
                 },
                 canSend() { return this.message.trim() || this.mediaFile; }
             }">

            {{-- Media preview --}}
            <template x-if="mediaPreviewSrc">
                <div class="media-preview mb-2">
                    <template x-if="mediaPreviewType === 'image'">
                        <img :src="mediaPreviewSrc" alt="Aperçu">
                    </template>
                    <template x-if="mediaPreviewType === 'video'">
                        <video :src="mediaPreviewSrc" style="height:54px;width:80px;border-radius:8px;object-fit:cover;"></video>
                    </template>
                    <span class="text-xs text-gray-500 ml-1" x-text="mediaPreviewType === 'video' ? 'Vidéo sélectionnée' : 'Image sélectionnée'"></span>
                    <button type="button" class="media-preview-cancel" @click="clearMedia()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </div>
            </template>

            <form method="POST" action="{{ route('talent.messages.send', $conversation->id) }}"
                  enctype="multipart/form-data" class="flex items-end gap-3"
                  @submit="setTimeout(() => { message = ''; clearMedia(); }, 100)">
                @csrf
                {{-- Bouton upload : input transparent superposé au bouton visuel --}}
                <div style="position:relative;display:inline-flex;flex-shrink:0;" title="Photo ou vidéo">
                    <div class="media-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9l4-4 4 4 4-4 4 4"/><circle cx="8.5" cy="13.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                    </div>
                    <input type="file" name="media" accept="image/*,video/*"
                           x-ref="mediaInput"
                           @change="onMediaChange($event)"
                           style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;">
                </div>

                <textarea
                    name="content"
                    x-model="message"
                    rows="2"
                    placeholder="Écrire un message..."
                    maxlength="2000"
                    class="flex-1 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 resize-none"
                    onkeydown="if(event.ctrlKey && event.key === 'Enter') this.form.submit();"
                ></textarea>
                <button type="submit"
                        class="flex-shrink-0 w-11 h-11 rounded-xl flex items-center justify-center text-white transition-opacity hover:opacity-90"
                        :class="canSend() ? 'opacity-100' : 'opacity-40 cursor-not-allowed'"
                        style="background:#FF6B35">
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
    </div>

    {{-- Lightbox --}}
    <div class="lightbox" x-show="lightboxSrc" x-cloak
         @click="lightboxSrc = null" style="display:none">
        <img :src="lightboxSrc" alt="Agrandir">
    </div>
</div>

@section('scripts')
<script>
    const container = document.getElementById('messages-container');
    if (container) container.scrollTop = container.scrollHeight;
</script>
@endsection
@endsection
