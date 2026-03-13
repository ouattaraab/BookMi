@extends('layouts.talent')

@section('title', 'Mes Meet & Greet — BookMi Talent')

@section('content')
<div style="max-width:1100px; margin:0 auto; padding:2rem 1.5rem;">

    {{-- En-tête --}}
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:2rem;">
        <div>
            <h1 style="font-size:1.6rem; font-weight:900; color:#1a2744; margin:0 0 0.25rem;">Mes Meet & Greet</h1>
            <p style="color:#6b7280; font-size:0.9rem; margin:0;">Gérez vos expériences exclusives avec vos fans.</p>
        </div>
        <a href="{{ route('talent.meet-and-greet.create') }}"
           style="display:inline-flex; align-items:center; gap:8px; background:linear-gradient(135deg,#1AB3FF,#0090E8); color:white; font-weight:800; font-size:0.9rem; padding:12px 24px; border-radius:14px; text-decoration:none; box-shadow:0 4px 18px rgba(26,179,255,0.35);">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Créer un Meet & Greet
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div style="background:#f0fdf4; border:1px solid #86efac; color:#166534; padding:14px 18px; border-radius:12px; margin-bottom:1.5rem; font-weight:600; font-size:0.9rem;">
            {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div style="background:#fffbeb; border:1px solid #fcd34d; color:#92400e; padding:14px 18px; border-radius:12px; margin-bottom:1.5rem; font-weight:600; font-size:0.9rem;">
            {{ session('warning') }}
        </div>
    @endif

    @if($experiences->isEmpty())
        <div style="text-align:center; padding:4rem 2rem; background:white; border-radius:20px; border:1px solid #e5e1da;">
            <div style="width:60px; height:60px; background:rgba(26,179,255,0.08); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.25rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" stroke="#1AB3FF" stroke-width="1.8" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            </div>
            <h3 style="font-size:1.1rem; font-weight:800; color:#1a2744; margin:0 0 0.5rem;">Aucun Meet & Greet</h3>
            <p style="color:#6b7280; font-size:0.875rem; margin:0 0 1.5rem;">Créez votre première expérience exclusive pour vos fans.</p>
            <a href="{{ route('talent.meet-and-greet.create') }}"
               style="display:inline-flex; align-items:center; gap:8px; background:#1AB3FF; color:white; font-weight:800; font-size:0.875rem; padding:10px 20px; border-radius:12px; text-decoration:none;">
                Créer mon premier Meet & Greet →
            </a>
        </div>
    @else
        <div style="display:grid; gap:1rem;">
            @foreach($experiences as $exp)
            @php
                $statusColors = [
                    'draft'     => ['bg'=>'#f3f4f6','color'=>'#374151','label'=>'Brouillon'],
                    'published' => ['bg'=>'#f0fdf4','color'=>'#166534','label'=>'Publié'],
                    'full'      => ['bg'=>'#fffbeb','color'=>'#92400e','label'=>'Complet'],
                    'cancelled' => ['bg'=>'#fef2f2','color'=>'#991b1b','label'=>'Annulé'],
                    'completed' => ['bg'=>'#eff6ff','color'=>'#1e40af','label'=>'Terminé'],
                ];
                $sc = $statusColors[$exp->status->value] ?? $statusColors['draft'];
                $isPast = $exp->event_date->isPast();
            @endphp
            <div style="background:white; border:1px solid #e5e1da; border-radius:20px; padding:1.5rem; display:flex; align-items:center; gap:1.25rem; flex-wrap:wrap;">

                {{-- Icône date --}}
                <div style="width:56px; height:56px; border-radius:16px; background:linear-gradient(135deg,#1AB3FF,#0090E8); display:flex; flex-direction:column; align-items:center; justify-content:center; flex-shrink:0; color:white;">
                    <span style="font-size:0.65rem; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; line-height:1;">{{ $exp->event_date->format('M') }}</span>
                    <span style="font-size:1.4rem; font-weight:900; line-height:1.1;">{{ $exp->event_date->format('d') }}</span>
                </div>

                {{-- Infos --}}
                <div style="flex:1; min-width:200px;">
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:4px;">
                        <h3 style="font-size:1rem; font-weight:800; color:#1a2744; margin:0;">{{ $exp->title }}</h3>
                        <span style="background:{{ $sc['bg'] }}; color:{{ $sc['color'] }}; font-size:0.7rem; font-weight:800; padding:3px 10px; border-radius:100px; text-transform:uppercase; letter-spacing:0.05em;">{{ $sc['label'] }}</span>
                    </div>
                    <div style="display:flex; gap:1rem; flex-wrap:wrap; font-size:0.8rem; color:#6b7280; font-weight:600;">
                        <span>📅 {{ $exp->event_date->format('d/m/Y à H\hi') }}</span>
                        <span>👥 {{ $exp->booked_seats }} / {{ $exp->max_seats }} places</span>
                        <span>💰 {{ number_format($exp->price_per_seat, 0, ',', '.') }} FCFA / pers.</span>
                    </div>
                </div>

                {{-- Actions --}}
                <div style="display:flex; gap:8px; flex-shrink:0;">
                    <a href="{{ route('talent.meet-and-greet.show', $exp->id) }}"
                       style="display:inline-flex; align-items:center; gap:6px; padding:9px 16px; border-radius:10px; background:#f3f4f6; color:#374151; font-size:0.82rem; font-weight:700; text-decoration:none; border:1px solid #e5e7eb; transition:all 0.15s;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        Voir détails
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
