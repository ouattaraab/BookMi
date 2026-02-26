@extends('layouts.client')

@section('title', 'Notifications — BookMi Client')

@section('head')
<style>
main.page-content { background: #F2EFE9 !important; }

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(22px); }
    to   { opacity: 1; transform: translateY(0); }
}
.dash-fade { opacity: 0; animation: fadeUp 0.6s cubic-bezier(0.16,1,0.3,1) forwards; }

.notif-row {
    padding: 16px 24px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    border-bottom: 1px solid #F5F3EF;
    transition: background 0.15s;
    text-decoration: none;
    color: inherit;
}
.notif-row:last-child { border-bottom: none; }
.notif-row:hover { background: #FAFAF8; }
.notif-row.unread { background: #FFFBF8; }
</style>
@endsection

@section('content')
<div style="font-family:'Nunito',sans-serif;color:#1A2744;max-width:780px;">

    {{-- Flash --}}
    @if(session('success'))
    <div class="dash-fade" style="animation-delay:0ms;margin-bottom:16px;padding:14px 18px;border-radius:12px;font-size:0.875rem;font-weight:600;background:#F0FDF4;border:1px solid #86EFAC;color:#15803D;">{{ session('success') }}</div>
    @endif

    {{-- Header --}}
    <div class="dash-fade" style="animation-delay:0ms;display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;">
        <div>
            <h1 style="font-size:1.8rem;font-weight:900;color:#1A2744;letter-spacing:-0.025em;margin:0 0 4px;line-height:1.15;">
                Notifications
            </h1>
            <p style="font-size:0.875rem;color:#8A8278;font-weight:500;margin:0;">
                @if($unreadCount > 0)
                    <span style="color:#FF6B35;font-weight:700;">{{ $unreadCount }}</span> non lue{{ $unreadCount > 1 ? 's' : '' }}
                @else
                    Tout est lu
                @endif
            </p>
        </div>
        @if($unreadCount > 0)
        <form action="{{ route('client.notifications.read-all') }}" method="POST">
            @csrf
            <button type="submit"
                    style="padding:10px 20px;border-radius:12px;font-size:0.8rem;font-weight:700;color:#FF6B35;background:#FFF4EF;border:1.5px solid rgba(255,107,53,0.25);cursor:pointer;font-family:'Nunito',sans-serif;transition:background 0.2s;"
                    onmouseover="this.style.background='#FFE8DC'"
                    onmouseout="this.style.background='#FFF4EF'">
                Tout marquer lu
            </button>
        </form>
        @endif
    </div>

    {{-- Notifications list --}}
    <div class="dash-fade" style="animation-delay:80ms;background:#FFFFFF;border-radius:18px;border:1px solid #E5E1DA;box-shadow:0 2px 12px rgba(26,39,68,0.06);overflow:hidden;">

        @if($notifications->isEmpty())
        <div style="padding:64px 24px;text-align:center;">
            <div style="width:56px;height:56px;border-radius:16px;background:#FFF4EF;border:1.5px solid rgba(255,107,53,0.18);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#FF6B35" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
            </div>
            <p style="font-size:0.9rem;font-weight:700;color:#8A8278;margin:0 0 4px;">Aucune notification</p>
            <p style="font-size:0.78rem;font-weight:500;color:#B0A89E;margin:0;">Vos notifications apparaîtront ici.</p>
        </div>
        @else
        @foreach($notifications as $notif)
        @php $isUnread = $notif->read_at === null; @endphp
        <div class="notif-row {{ $isUnread ? 'unread' : '' }}">
            {{-- Dot --}}
            <div style="flex-shrink:0;padding-top:4px;">
                @if($isUnread)
                <div style="width:8px;height:8px;border-radius:50%;background:#FF6B35;"></div>
                @else
                <div style="width:8px;height:8px;border-radius:50%;background:#E5E1DA;"></div>
                @endif
            </div>
            {{-- Content --}}
            <div style="flex:1;min-width:0;">
                <p style="font-size:0.875rem;font-weight:{{ $isUnread ? '800' : '600' }};color:#1A2744;margin:0 0 3px;line-height:1.4;">
                    {{ $notif->title }}
                </p>
                @if($notif->body)
                <p style="font-size:0.82rem;color:#6B7280;font-weight:500;margin:0 0 6px;line-height:1.5;">{{ $notif->body }}</p>
                @endif
                <p style="font-size:0.72rem;color:#B0A89E;font-weight:500;margin:0;">
                    {{ $notif->created_at->diffForHumans() }}
                </p>
            </div>
            {{-- Mark read --}}
            @if($isUnread)
            <form action="{{ route('client.notifications.read', $notif->id) }}" method="POST" style="flex-shrink:0;">
                @csrf
                <button type="submit"
                        style="padding:6px 12px;border-radius:9px;font-size:0.72rem;font-weight:700;color:#8A8278;background:#F5F3EF;border:none;cursor:pointer;font-family:'Nunito',sans-serif;transition:background 0.15s;"
                        title="Marquer comme lu"
                        onmouseover="this.style.background='#EAE7E0'"
                        onmouseout="this.style.background='#F5F3EF'">
                    Lu
                </button>
            </form>
            @endif
        </div>
        @endforeach

        {{-- Pagination --}}
        @if($notifications->hasPages())
        <div style="padding:14px 24px;border-top:1px solid #EAE7E0;display:flex;justify-content:center;">
            {{ $notifications->links() }}
        </div>
        @endif
        @endif

    </div>

</div>
@endsection
