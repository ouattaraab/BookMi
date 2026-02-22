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
/* ── Media bubble ── */
.bubble-media-img {
    max-width: 18rem;
    border-radius: 0.875rem;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.2s;
}
.bubble-media-img:hover { transform: scale(1.02); }
.bubble-media-img img, .bubble-media-img video {
    width: 100%; display: block; max-height: 20rem; object-fit: cover;
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
/* ── Media upload button ── */
.media-btn {
    width: 2.375rem; height: 2.375rem;
    border-radius: 0.75rem;
    display: flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,0.07);
    color: rgba(255,255,255,0.55);
    border: 1px solid rgba(255,255,255,0.10);
    cursor: pointer; flex-shrink: 0;
    transition: background 0.2s, color 0.2s;
}
.media-btn:hover { background: rgba(255,255,255,0.12); color: rgba(255,255,255,0.85); }
/* ── Media preview strip ── */
.media-preview {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 12px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 0.75rem;
    margin-bottom: 8px;
}
.media-preview img, .media-preview video {
    height: 54px; width: 80px; border-radius: 0.5rem; object-fit: cover;
}
.media-preview-cancel {
    margin-left: auto;
    background: rgba(255,255,255,0.08);
    border: none; cursor: pointer;
    width: 24px; height: 24px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: rgba(255,255,255,0.55);
    transition: background 0.2s;
}
.media-preview-cancel:hover { background: rgba(252,100,100,0.25); color: rgba(252,165,165,0.9); }
/* ── Avatar ── */
.msg-avatar {
    width: 2rem; height: 2rem; border-radius: 50%;
    object-fit: cover; flex-shrink: 0;
}
.msg-avatar-init {
    width: 2rem; height: 2rem; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem; font-weight: 900; color: white; flex-shrink: 0;
}
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
    $talentProfile  = $conversation->talentProfile;
    $talentUser     = $talentProfile->user ?? null;
    $talentName     = $talentProfile->stage_name
        ?? trim(($talentUser->first_name ?? '') . ' ' . ($talentUser->last_name ?? ''))
        ?: 'Talent';
    $talentPhotoUrl = $talentProfile->profilePhotoUrl ?? null;
    $clientName     = auth()->user()->first_name ?? 'Moi';
@endphp
<div class="flex flex-col" style="height:calc(100vh - 10rem); max-width: 48rem"
     x-data="{ lightboxSrc: null, lightboxType: null }">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-4 flex-shrink-0">
        <a href="{{ route('client.messages') }}" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M15 19l-7-7 7-7"/></svg>
        </a>

        {{-- Talent avatar with photo --}}
        @if($talentPhotoUrl)
            <img src="{{ $talentPhotoUrl }}" alt="{{ $talentName }}"
                 class="w-10 h-10 rounded-xl object-cover flex-shrink-0"
                 style="box-shadow:0 0 12px rgba(255,107,53,0.25);border:2px solid rgba(255,107,53,0.35)">
        @else
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-black text-sm flex-shrink-0"
                 style="background:linear-gradient(135deg,#FF6B35,#C85A20);box-shadow:0 0 12px rgba(255,107,53,0.28)">
                {{ strtoupper(substr($talentName, 0, 1)) }}
            </div>
        @endif
        <div>
            <p class="font-black text-sm" style="color:var(--text)">{{ $talentName }}</p>
            @if($talentProfile->category)
                <p class="text-xs" style="color:var(--text-muted)">{{ $talentProfile->category->name }}</p>
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
                    @if($talentPhotoUrl)
                        <img src="{{ $talentPhotoUrl }}" alt="{{ $talentName }}" class="msg-avatar"
                             style="border:1.5px solid rgba(255,107,53,0.35)">
                    @else
                        <div class="msg-avatar-init" style="background:linear-gradient(135deg,#FF6B35,#C85A20)">
                            {{ strtoupper(substr($talentName, 0, 1)) }}
                        </div>
                    @endif
                @endif

                <div class="flex flex-col {{ $isClient ? 'items-end' : 'items-start' }}">
                    {{-- Text content --}}
                    @if($message->content)
                        <div class="{{ $isClient ? 'bubble-client' : 'bubble-talent' }}">{{ $message->content }}</div>
                    @endif

                    {{-- Media content --}}
                    @if($message->media_path)
                        @php $mediaUrl = Storage::disk('public')->url($message->media_path); @endphp
                        <div class="bubble-media-img mt-1"
                             @if($message->media_type === 'image')
                             @click="lightboxSrc = '{{ $mediaUrl }}'; lightboxType = 'image'"
                             @endif>
                            @if($message->media_type === 'video')
                                <video controls preload="none" style="max-height:20rem;border-radius:0.875rem;">
                                    <source src="{{ $mediaUrl }}" type="video/mp4">
                                    Votre navigateur ne supporte pas la vidéo.
                                </video>
                            @else
                                <img src="{{ $mediaUrl }}" alt="Image partagée" loading="lazy"
                                     style="border-radius:0.875rem;max-height:20rem;width:auto;max-width:18rem;">
                            @endif
                        </div>
                    @endif

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
                    <div class="msg-avatar-init" style="background:linear-gradient(135deg,var(--navy),var(--blue))">
                        {{ strtoupper(substr($clientName, 0, 1)) }}
                    </div>
                @endif
            </div>
            @endforeach
        @endif
    </div>

    {{-- Send form --}}
    <div class="flex-shrink-0"
         x-data="{
             message: '',
             mediaFile: null,
             mediaPreviewSrc: null,
             mediaPreviewType: null,
             pickMedia() {
                 this.$refs.mediaInput.click();
             },
             onMediaChange(event) {
                 const file = event.target.files[0];
                 if (!file) return;
                 this.mediaFile = file;
                 this.mediaPreviewType = file.type.startsWith('video/') ? 'video' : 'image';
                 const reader = new FileReader();
                 reader.onload = e => { this.mediaPreviewSrc = e.target.result; };
                 reader.readAsDataURL(file);
             },
             clearMedia() {
                 this.mediaFile = null;
                 this.mediaPreviewSrc = null;
                 this.mediaPreviewType = null;
                 this.$refs.mediaInput.value = '';
             },
             canSend() {
                 return this.message.trim() || this.mediaFile;
             }
         }">

        {{-- Error --}}
        @if($errors->has('content'))
            <p class="text-xs mb-2" style="color:rgba(252,165,165,0.9)">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="display:inline;vertical-align:-1px;margin-right:4px"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                {{ $errors->first('content') }}
            </p>
        @endif

        {{-- Media preview --}}
        <template x-if="mediaPreviewSrc">
            <div class="media-preview">
                <template x-if="mediaPreviewType === 'video'">
                    <video :src="mediaPreviewSrc" style="height:54px;width:80px;border-radius:0.5rem;object-fit:cover;"></video>
                </template>
                <template x-if="mediaPreviewType === 'image'">
                    <img :src="mediaPreviewSrc" alt="Aperçu" style="height:54px;width:80px;border-radius:0.5rem;object-fit:cover;">
                </template>
                <span class="text-xs ml-2" style="color:rgba(255,255,255,0.55)" x-text="mediaPreviewType === 'video' ? 'Vidéo sélectionnée' : 'Image sélectionnée'"></span>
                <button type="button" class="media-preview-cancel" @click="clearMedia()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
        </template>

        <form action="{{ route('client.messages.send', $conversation->id) }}" method="POST"
              enctype="multipart/form-data"
              @submit="setTimeout(() => { message = ''; clearMedia(); }, 100)">
            @csrf
            <div class="msg-input-wrap">
                {{-- Bouton upload : input transparent superposé au bouton visuel --}}
                <div style="position:relative;display:inline-flex;flex-shrink:0;" title="Envoyer une photo ou vidéo">
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
                    rows="1"
                    placeholder="Écrivez un message..."
                    x-ref="textarea"
                    @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                    @keydown.enter.prevent.exact="if(canSend()) $el.closest('form').submit()"
                    @keydown.shift.enter="$event.stopPropagation()"
                ></textarea>
                <button type="submit" class="send-btn" :disabled="!canSend()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </div>
        </form>
        <p class="text-xs text-center mt-2" style="color:var(--text-faint)">
            Entrée pour envoyer · Maj+Entrée pour saut de ligne ·
            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="display:inline;vertical-align:-1px"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Numéros de téléphone masqués pour votre sécurité
        </p>
    </div>

    {{-- Lightbox --}}
    <div class="lightbox" x-show="lightboxSrc && lightboxType === 'image'" x-cloak
         @click="lightboxSrc = null; lightboxType = null" style="display:none">
        <img :src="lightboxSrc" alt="Agrandir">
    </div>

</div>
@endsection
