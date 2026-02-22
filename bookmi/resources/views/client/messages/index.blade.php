@extends('layouts.client')

@section('title', 'Mes messages — BookMi Client')

@section('head')
<style>
main.page-content { background: #F2EFE9 !important; }

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(22px); }
    to   { opacity: 1; transform: translateY(0); }
}
.dash-fade { opacity: 0; animation: fadeUp 0.6s cubic-bezier(0.16,1,0.3,1) forwards; }

/* ── Conversation row ── */
.conv-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 24px;
    text-decoration: none;
    border-bottom: 1px solid #EAE7E0;
    transition: background 0.18s, padding-left 0.22s;
    position: relative;
}
.conv-row:last-child { border-bottom: none; }
.conv-row:hover {
    background: #FFF8F5;
    padding-left: 28px;
}

/* ── Avatar ── */
.conv-avatar {
    width: 48px; height: 48px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-weight: 900; font-size: 1.1rem; color: white;
    flex-shrink: 0;
    background: linear-gradient(135deg, #1A2744 0%, #2563EB 100%);
    box-shadow: 0 3px 10px rgba(26,39,68,0.18);
}

/* ── CTA Button ── */
.btn-cta {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 22px;
    background: linear-gradient(135deg, #FF6B35 0%, #f0520f 100%);
    color: #fff; font-weight: 800; font-size: 0.85rem;
    border-radius: 12px; text-decoration: none;
    box-shadow: 0 4px 18px rgba(255,107,53,0.32);
    transition: transform 0.2s, box-shadow 0.2s;
}
.btn-cta:hover { transform: translateY(-2px); box-shadow: 0 8px 26px rgba(255,107,53,0.42); }
</style>
@endsection

@section('content')
<div style="font-family:'Nunito',sans-serif;color:#1A2744;max-width:1100px;">

    {{-- Flash --}}
    @if(session('success'))
    <div class="dash-fade" style="animation-delay:0ms;margin-bottom:16px;padding:14px 18px;border-radius:12px;font-size:0.875rem;font-weight:600;background:#F0FDF4;border:1px solid #86EFAC;color:#15803D;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="dash-fade" style="animation-delay:0ms;margin-bottom:16px;padding:14px 18px;border-radius:12px;font-size:0.875rem;font-weight:600;background:#FEF2F2;border:1px solid #FCA5A5;color:#991B1B;">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="dash-fade" style="animation-delay:0ms;margin-bottom:28px;">
        <h1 style="font-size:1.8rem;font-weight:900;color:#1A2744;letter-spacing:-0.025em;margin:0 0 5px 0;line-height:1.15;">Messages</h1>
        <p style="font-size:0.875rem;color:#8A8278;font-weight:500;margin:0;">Vos conversations par réservation — chaque fil est lié à une prestation distincte</p>
    </div>

    @if($conversations->isEmpty())
        {{-- Empty state --}}
        <div class="dash-fade" style="animation-delay:80ms;background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:72px 24px;text-align:center;">
            <div style="width:76px;height:76px;border-radius:22px;background:#EFF6FF;border:2px solid #93C5FD;display:flex;align-items:center;justify-content:center;margin-bottom:22px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <p style="font-size:1.05rem;font-weight:800;color:#1A2744;margin:0 0 8px 0;">Aucune conversation</p>
            <p style="font-size:0.875rem;color:#8A8278;margin:0 0 28px 0;max-width:280px;line-height:1.6;">Vos échanges avec les talents apparaîtront ici après votre première réservation.</p>
            <a href="{{ route('talents.index') }}" class="btn-cta">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                Découvrir les talents
            </a>
        </div>
    @else
        <div class="dash-fade" style="animation-delay:80ms;background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);overflow:hidden;">

            {{-- Header de la card --}}
            <div style="padding:18px 24px 16px;border-bottom:1px solid #EAE7E0;display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <h2 style="font-size:1rem;font-weight:900;color:#1A2744;margin:0 0 2px 0;">Conversations</h2>
                    <p style="font-size:0.75rem;color:#8A8278;margin:0;font-weight:500;">{{ $conversations->total() ?? $conversations->count() }} conversation{{ ($conversations->total() ?? $conversations->count()) > 1 ? 's' : '' }}</p>
                </div>
            </div>

            @foreach($conversations as $i => $conversation)
            @php
                $talentUser  = $conversation->talentProfile->user ?? null;
                $talentName  = $conversation->talentProfile->stage_name
                    ?? trim(($talentUser->first_name ?? '') . ' ' . ($talentUser->last_name ?? ''))
                    ?: 'Talent';
                $talentInit  = strtoupper(substr($talentName, 0, 1));
                $lastMsg     = $conversation->latestMessage;
                $lastPreview = $lastMsg
                    ? ($lastMsg->content ? Str::limit($lastMsg->content, 60) : '📷 Média')
                    : 'Aucun message';
                $lastDate    = $conversation->last_message_at
                    ? \Carbon\Carbon::parse($conversation->last_message_at)->diffForHumans()
                    : '';
                $catName     = $conversation->talentProfile->category->name ?? null;
                $booking     = $conversation->bookingRequest;
                $bookingStatus = null;
                if ($booking) {
                    $bookingStatus = $booking->status instanceof \BackedEnum
                        ? $booking->status->value
                        : (string) $booking->status;
                }
                $isLocked = in_array($bookingStatus, ['completed', 'cancelled', 'disputed']);
            @endphp
            <a href="{{ route('client.messages.show', $conversation->id) }}" class="conv-row">
                <div class="conv-avatar">{{ $talentInit }}</div>

                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px;">
                        <span style="font-weight:800;font-size:0.9rem;color:#1A2744;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $talentName }}</span>
                        <span style="font-size:0.72rem;color:#B0A89E;flex-shrink:0;margin-left:8px;font-weight:500;">{{ $lastDate }}</span>
                    </div>
                    <p style="font-size:0.78rem;color:#8A8278;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:500;">{{ $lastPreview }}</p>
                    <div style="display:flex;align-items:center;gap:6px;margin-top:3px;flex-wrap:wrap;">
                        @if($booking)
                            <span style="font-size:0.68rem;font-weight:700;color:#B0A89E;">Réservation #{{ $booking->id }}
                                @if($booking->event_date)
                                    · {{ \Carbon\Carbon::parse($booking->event_date)->format('d/m/Y') }}
                                @endif
                            </span>
                        @elseif($catName)
                            <span style="font-size:0.72rem;color:#B0A89E;font-weight:500;">{{ $catName }}</span>
                        @endif
                        @if($isLocked)
                            <span style="font-size:0.65rem;font-weight:700;padding:1px 7px;border-radius:9999px;background:#F9F8F5;border:1px solid #E5E1DA;color:#B0A89E;">
                                🔒 Terminée
                            </span>
                        @endif
                    </div>
                </div>

                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#C8C3BC" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M9 5l7 7-7 7"/></svg>
            </a>
            @endforeach

        </div>

        @if($conversations->hasPages())
        <div style="margin-top:20px;">{{ $conversations->links() }}</div>
        @endif
    @endif

</div>
@endsection
