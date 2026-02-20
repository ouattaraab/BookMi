@extends('layouts.client')

@section('title', 'Mes messages — BookMi Client')

@section('head')
<style>
/* ── Conversation row ── */
.conv-row {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 0.875rem 1.25rem;
    text-decoration: none;
    border-bottom: 1px solid var(--glass-border);
    transition: background 0.15s;
    position: relative;
}
.conv-row:last-child { border-bottom: none; }
.conv-row:hover { background: rgba(255,255,255,0.04); }
/* ── Avatar ── */
.conv-avatar {
    width: 2.75rem; height: 2.75rem;
    border-radius: 0.875rem;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 1rem; color: white;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--navy), var(--blue));
    box-shadow: 0 0 12px rgba(33,150,243,0.25);
}
/* ── Unread dot ── */
.unread-dot {
    width: 0.5rem; height: 0.5rem;
    border-radius: 50%;
    background: var(--orange);
    flex-shrink: 0;
    box-shadow: 0 0 6px rgba(255,107,53,0.6);
}
/* ── Reveal ── */
.reveal-item { opacity:0; transform:translateY(14px); transition:opacity 0.42s cubic-bezier(0.16,1,0.3,1), transform 0.42s cubic-bezier(0.16,1,0.3,1); }
.reveal-item.visible { opacity:1; transform:none; }
</style>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Flash --}}
    @if(session('success'))
    <div class="p-3 rounded-xl text-sm font-medium" style="background:rgba(76,175,80,0.12);border:1px solid rgba(76,175,80,0.25);color:rgba(134,239,172,0.95)">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="p-3 rounded-xl text-sm font-medium" style="background:rgba(244,67,54,0.12);border:1px solid rgba(244,67,54,0.25);color:rgba(252,165,165,0.95)">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="reveal-item">
        <h1 class="section-title">Messages</h1>
        <p class="section-sub">Vos conversations avec les talents</p>
    </div>

    @if($conversations->isEmpty())
        {{-- Empty state --}}
        <div class="glass-card flex flex-col items-center justify-center py-20 text-center reveal-item" style="transition-delay:0.06s">
            <div class="w-20 h-20 rounded-3xl mb-5 flex items-center justify-center"
                 style="background:rgba(33,150,243,0.07);border:1px solid rgba(33,150,243,0.15);box-shadow:0 0 30px rgba(33,150,243,0.08)">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="none" viewBox="0 0 24 24" stroke="#64B5F6" stroke-width="1.5">
                    <path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <p class="font-black text-base mb-2" style="color:var(--text)">Aucune conversation</p>
            <p class="text-sm" style="color:var(--text-muted)">Vos échanges avec les talents apparaîtront ici.</p>
        </div>
    @else
        <div class="glass-card overflow-hidden reveal-item" style="transition-delay:0.06s">
            @foreach($conversations as $i => $conversation)
            @php
                $talentUser = $conversation->talentProfile->user ?? null;
                $talentName = $conversation->talentProfile->stage_name
                    ?? trim(($talentUser->first_name ?? '') . ' ' . ($talentUser->last_name ?? ''))
                    ?: 'Talent';
                $talentInitial = strtoupper(substr($talentName, 0, 1));
                $lastMsg = $conversation->latestMessage;
                $lastMsgPreview = $lastMsg ? Str::limit($lastMsg->content, 60) : 'Aucun message';
                $lastMsgDate = $conversation->last_message_at
                    ? \Carbon\Carbon::parse($conversation->last_message_at)->diffForHumans()
                    : '';
                $catName = $conversation->talentProfile->category->name ?? null;
            @endphp
            <a href="{{ route('client.messages.show', $conversation->id) }}" class="conv-row">
                {{-- Avatar --}}
                <div class="conv-avatar">{{ $talentInitial }}</div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-0.5">
                        <span class="font-bold text-sm truncate" style="color:var(--text)">{{ $talentName }}</span>
                        <span class="text-xs flex-shrink-0 ml-2" style="color:var(--text-faint)">{{ $lastMsgDate }}</span>
                    </div>
                    <p class="text-xs truncate" style="color:var(--text-muted)">{{ $lastMsgPreview }}</p>
                    @if($catName)
                        <p class="text-xs mt-0.5" style="color:var(--text-faint)">{{ $catName }}</p>
                    @endif
                </div>

                {{-- Unread / Arrow --}}
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:var(--text-faint);flex-shrink:0"><path d="M9 5l7 7-7 7"/></svg>
            </a>
            @endforeach
        </div>

        @if($conversations->hasPages())
        <div class="mt-4">{{ $conversations->links() }}</div>
        @endif
    @endif

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const io = new IntersectionObserver(entries => {
        entries.forEach(e => { if(e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); } });
    }, { threshold: 0.04 });
    document.querySelectorAll('.reveal-item').forEach(el => io.observe(el));
});
</script>
@endsection
