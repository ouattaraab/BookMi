@extends('layouts.talent')

@section('title', 'Mes abonnés — BookMi Talent')

@section('head')
<style>
@keyframes fadeUp {
    from { opacity:0; transform:translateY(18px); }
    to   { opacity:1; transform:translateY(0); }
}
.dash-fade { opacity:0; animation:fadeUp 0.5s cubic-bezier(0.16,1,0.3,1) forwards; }
</style>
@endsection

@section('content')
<div style="max-width:1000px; margin:0 auto; padding:2rem 1.5rem;">

    {{-- Header --}}
    <div class="dash-fade" style="margin-bottom:2rem;">
        <h1 style="font-size:1.6rem; font-weight:900; color:white; margin:0 0 0.25rem;">Mes abonnés</h1>
        <p style="color:rgba(255,255,255,0.5); font-size:0.9rem; margin:0;">
            {{ $subscribers->count() }} client{{ $subscribers->count() !== 1 ? 's' : '' }} vous suivent
        </p>
    </div>

    @if($subscribers->isEmpty())
    <div class="dash-fade" style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:16px; padding:4rem 2rem; text-align:center;">
        <div style="width:56px; height:56px; background:rgba(26,179,255,0.12); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.25rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" stroke="#1AB3FF" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <p style="color:rgba(255,255,255,0.6); font-size:0.875rem; margin:0;">Aucun abonné pour l'instant.</p>
    </div>
    @else

    {{-- Stats globales --}}
    <div class="dash-fade" style="display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-bottom:2rem; animation-delay:0.05s;">
        @php
            $totalBookingsFromFollowers = $bookingStats->sum('bookings_count');
            $totalRevenueFromFollowers  = $bookingStats->sum('total_spent');
        @endphp
        <div style="background:rgba(26,179,255,0.08); border:1px solid rgba(26,179,255,0.2); border-radius:14px; padding:1.25rem; text-align:center;">
            <p style="font-size:1.75rem; font-weight:900; color:#1AB3FF; margin:0 0 2px;">{{ $subscribers->count() }}</p>
            <p style="font-size:0.75rem; font-weight:700; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:0.06em; margin:0;">Abonnés</p>
        </div>
        <div style="background:rgba(16,185,129,0.08); border:1px solid rgba(16,185,129,0.2); border-radius:14px; padding:1.25rem; text-align:center;">
            <p style="font-size:1.75rem; font-weight:900; color:#10B981; margin:0 0 2px;">{{ $totalBookingsFromFollowers }}</p>
            <p style="font-size:0.75rem; font-weight:700; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:0.06em; margin:0;">Réservations</p>
        </div>
        <div style="background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.2); border-radius:14px; padding:1.25rem; text-align:center;">
            <p style="font-size:1.4rem; font-weight:900; color:#F59E0B; margin:0 0 2px;">{{ number_format($totalRevenueFromFollowers, 0, ',', '.') }}</p>
            <p style="font-size:0.75rem; font-weight:700; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:0.06em; margin:0;">CA abonnés (XOF)</p>
        </div>
    </div>

    {{-- Tableau --}}
    <div class="dash-fade" style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:16px; overflow:hidden; animation-delay:0.1s;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.08);">
                    <th style="padding:14px 20px; text-align:left; font-size:0.7rem; font-weight:800; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:0.08em;">Client</th>
                    <th style="padding:14px 20px; text-align:left; font-size:0.7rem; font-weight:800; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:0.08em;">Contact</th>
                    <th style="padding:14px 20px; text-align:center; font-size:0.7rem; font-weight:800; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:0.08em;">Réservations</th>
                    <th style="padding:14px 20px; text-align:right; font-size:0.7rem; font-weight:800; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:0.08em;">Total dépensé</th>
                    <th style="padding:14px 20px; text-align:left; font-size:0.7rem; font-weight:800; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:0.08em;">Abonné depuis</th>
                </tr>
            </thead>
            <tbody>
                @foreach($subscribers as $i => $follow)
                @php
                    $client = $follow->user;
                    $stats  = $bookingStats->get($follow->user_id);
                    $bookingsCount = $stats?->bookings_count ?? 0;
                    $totalSpent    = $stats?->total_spent ?? 0;
                    $init = strtoupper(substr(($client->first_name ?? 'C'), 0, 1));
                @endphp
                <tr style="border-bottom:1px solid rgba(255,255,255,0.05); {{ $i % 2 === 0 ? '' : 'background:rgba(255,255,255,0.02);' }}">
                    {{-- Nom --}}
                    <td style="padding:14px 20px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,#374151,#1F2937); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.85rem; color:rgba(255,255,255,0.8); flex-shrink:0;">
                                {{ $init }}
                            </div>
                            <div>
                                <p style="font-weight:700; font-size:0.875rem; color:white; margin:0;">
                                    {{ $client->first_name }} {{ $client->last_name }}
                                </p>
                                @if($bookingsCount > 0)
                                    <span style="display:inline-block; background:rgba(16,185,129,0.15); color:#10B981; font-size:0.65rem; font-weight:800; padding:2px 6px; border-radius:6px; margin-top:2px;">
                                        Client actif
                                    </span>
                                @endif
                            </div>
                        </div>
                    </td>
                    {{-- Contact --}}
                    <td style="padding:14px 20px;">
                        <p style="color:rgba(255,255,255,0.55); font-size:0.8rem; margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:180px;">
                            {{ $client->email }}
                        </p>
                        @if($client->phone)
                        <p style="color:rgba(255,255,255,0.35); font-size:0.75rem; margin:2px 0 0;">{{ $client->phone }}</p>
                        @endif
                    </td>
                    {{-- Réservations --}}
                    <td style="padding:14px 20px; text-align:center;">
                        <span style="display:inline-block; background:{{ $bookingsCount > 0 ? 'rgba(26,179,255,0.12)' : 'rgba(255,255,255,0.04)' }}; color:{{ $bookingsCount > 0 ? '#1AB3FF' : 'rgba(255,255,255,0.3)' }}; font-weight:800; font-size:0.9rem; padding:4px 12px; border-radius:100px; border:1px solid {{ $bookingsCount > 0 ? 'rgba(26,179,255,0.25)' : 'rgba(255,255,255,0.08)' }};">
                            {{ $bookingsCount }}
                        </span>
                    </td>
                    {{-- Total dépensé --}}
                    <td style="padding:14px 20px; text-align:right;">
                        @if($totalSpent > 0)
                            <span style="color:#F59E0B; font-weight:800; font-size:0.875rem;">
                                {{ number_format($totalSpent, 0, ',', '.') }} XOF
                            </span>
                        @else
                            <span style="color:rgba(255,255,255,0.25); font-size:0.8rem;">—</span>
                        @endif
                    </td>
                    {{-- Depuis --}}
                    <td style="padding:14px 20px;">
                        <span style="color:rgba(255,255,255,0.4); font-size:0.78rem; font-weight:600;">
                            {{ $follow->created_at->translatedFormat('d M Y') }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>
@endsection
