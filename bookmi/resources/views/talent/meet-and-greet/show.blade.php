@extends('layouts.talent')

@section('title', $experience->title . ' — BookMi Talent')

@section('content')
<div style="max-width:1000px; margin:0 auto; padding:2rem 1.5rem;">

    {{-- Retour --}}
    <a href="{{ route('talent.meet-and-greet.index') }}"
       style="display:inline-flex; align-items:center; gap:6px; color:#6b7280; font-size:0.85rem; font-weight:600; text-decoration:none; margin-bottom:1.5rem;">
        ← Retour à mes Meet & Greet
    </a>

    {{-- Flash --}}
    @if(session('warning'))
        <div style="background:#fffbeb; border:1px solid #fcd34d; color:#92400e; padding:14px 18px; border-radius:12px; margin-bottom:1.5rem; font-weight:600; font-size:0.9rem;">
            {{ session('warning') }}
        </div>
    @endif

    {{-- En-tête --}}
    @php
        $statusColors = [
            'draft'     => ['bg'=>'#f3f4f6','color'=>'#374151','label'=>'Brouillon'],
            'published' => ['bg'=>'#f0fdf4','color'=>'#166534','label'=>'Publié'],
            'full'      => ['bg'=>'#fffbeb','color'=>'#92400e','label'=>'Complet'],
            'cancelled' => ['bg'=>'#fef2f2','color'=>'#991b1b','label'=>'Annulé'],
            'completed' => ['bg'=>'#eff6ff','color'=>'#1e40af','label'=>'Terminé'],
        ];
        $sc = $statusColors[$experience->status->value] ?? $statusColors['draft'];
        $canCancel = in_array($experience->status->value, ['draft','published','full']);
        $commissionPct = $experience->commission_rate;
    @endphp

    <div style="background:white; border:1px solid #e5e1da; border-radius:24px; padding:2rem; margin-bottom:1.5rem;">
        <div style="display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
            <div>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:0.5rem;">
                    <h1 style="font-size:1.5rem; font-weight:900; color:#1a2744; margin:0;">{{ $experience->title }}</h1>
                    <span style="background:{{ $sc['bg'] }}; color:{{ $sc['color'] }}; font-size:0.7rem; font-weight:800; padding:4px 12px; border-radius:100px; text-transform:uppercase; letter-spacing:0.06em;">{{ $sc['label'] }}</span>
                </div>
                <div style="display:flex; gap:1.25rem; flex-wrap:wrap; font-size:0.85rem; color:#6b7280; font-weight:600;">
                    <span>📅 {{ $experience->event_date->format('l d F Y à H\hi') }}</span>
                    @if($experience->venue_address)
                        <span>📍 {{ $experience->venue_address }}</span>
                    @endif
                </div>
            </div>
            @if($canCancel)
            <button onclick="document.getElementById('cancel-modal').style.display='flex'"
                    style="display:inline-flex; align-items:center; gap:6px; padding:10px 18px; border-radius:12px; background:#fef2f2; color:#991b1b; font-size:0.82rem; font-weight:700; border:1px solid #fca5a5; cursor:pointer; font-family:inherit;">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                Annuler l'événement
            </button>
            @endif
        </div>

        @if($experience->description)
            <p style="color:#4b5563; font-size:0.9rem; line-height:1.75; margin:0;">{{ $experience->description }}</p>
        @endif
    </div>

    {{-- Stats 4 colonnes --}}
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.5rem;">
        @foreach([
            ['label'=>'Places totales',    'value'=> $experience->max_seats,                                        'color'=>'#1AB3FF'],
            ['label'=>'Places réservées',  'value'=> $experience->booked_seats,                                     'color'=>'#8B5CF6'],
            ['label'=>'Places disponibles','value'=> $experience->seats_available,                                  'color'=>'#10B981'],
            ['label'=>'Prix / place',      'value'=> number_format($experience->price_per_seat,0,',','.').' FCFA',   'color'=>'#F59E0B'],
        ] as $s)
        <div style="background:white; border:1px solid #e5e1da; border-radius:16px; padding:1.25rem; text-align:center;">
            <div style="font-size:1.6rem; font-weight:900; color:{{ $s['color'] }}; line-height:1.1; margin-bottom:4px;">{{ $s['value'] }}</div>
            <div style="font-size:0.72rem; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:0.08em;">{{ $s['label'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- Finances --}}
    <div style="background:linear-gradient(135deg,#0B1728,#1A2744); border-radius:20px; padding:1.75rem; margin-bottom:1.5rem; color:white;">
        <h2 style="font-size:1rem; font-weight:800; margin:0 0 1.25rem; display:flex; align-items:center; gap:8px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="#1AB3FF" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            Résumé financier (places confirmées)
        </h2>
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1.25rem;">
            <div>
                <p style="color:rgba(255,255,255,0.45); font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; margin:0 0 4px;">Revenu brut</p>
                <p style="font-size:1.5rem; font-weight:900; color:white; margin:0;">{{ number_format($totalCollected, 0, ',', '.') }} <span style="font-size:0.8rem; opacity:0.6;">FCFA</span></p>
            </div>
            <div>
                <p style="color:rgba(255,255,255,0.45); font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; margin:0 0 4px;">Commission BookMi ({{ $commissionPct }}%)</p>
                <p style="font-size:1.5rem; font-weight:900; color:#F87171; margin:0;">− {{ number_format($totalCommission, 0, ',', '.') }} <span style="font-size:0.8rem; opacity:0.6;">FCFA</span></p>
            </div>
            <div>
                <p style="color:rgba(255,255,255,0.45); font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; margin:0 0 4px;">Votre part nette</p>
                <p style="font-size:1.5rem; font-weight:900; color:#4ADE80; margin:0;">{{ number_format($talentNet, 0, ',', '.') }} <span style="font-size:0.8rem; opacity:0.6;">FCFA</span></p>
            </div>
        </div>
    </div>

    {{-- Liste participants --}}
    <div style="background:white; border:1px solid #e5e1da; border-radius:20px; padding:1.75rem;">
        <h2 style="font-size:1rem; font-weight:800; color:#1a2744; margin:0 0 1.25rem; display:flex; align-items:center; gap:8px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="#1AB3FF" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Participants inscrits ({{ $experience->bookings->count() }})
        </h2>

        @if($experience->bookings->isEmpty())
            <p style="color:#9ca3af; font-size:0.875rem; font-style:italic; text-align:center; padding:2rem 0;">Aucun participant pour le moment.</p>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:0.875rem;">
                    <thead>
                        <tr style="border-bottom:2px solid #f3f4f6;">
                            <th style="text-align:left; padding:8px 12px; font-size:0.72rem; font-weight:800; color:#9ca3af; text-transform:uppercase; letter-spacing:0.06em;">Participant</th>
                            <th style="text-align:center; padding:8px 12px; font-size:0.72rem; font-weight:800; color:#9ca3af; text-transform:uppercase; letter-spacing:0.06em;">Places</th>
                            <th style="text-align:right; padding:8px 12px; font-size:0.72rem; font-weight:800; color:#9ca3af; text-transform:uppercase; letter-spacing:0.06em;">Montant</th>
                            <th style="text-align:center; padding:8px 12px; font-size:0.72rem; font-weight:800; color:#9ca3af; text-transform:uppercase; letter-spacing:0.06em;">Statut</th>
                            <th style="text-align:right; padding:8px 12px; font-size:0.72rem; font-weight:800; color:#9ca3af; text-transform:uppercase; letter-spacing:0.06em;">Inscrit le</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($experience->bookings as $booking)
                        @php
                            $bColors = ['pending'=>['bg'=>'#fffbeb','color'=>'#92400e','label'=>'En attente'],'confirmed'=>['bg'=>'#f0fdf4','color'=>'#166534','label'=>'Confirmé'],'cancelled'=>['bg'=>'#fef2f2','color'=>'#991b1b','label'=>'Annulé']];
                            $bc = $bColors[$booking->status->value] ?? $bColors['pending'];
                        @endphp
                        <tr style="border-bottom:1px solid #f9fafb;">
                            <td style="padding:12px 12px; font-weight:700; color:#1a2744;">
                                {{ $booking->client->first_name }} {{ $booking->client->last_name }}
                                <span style="display:block; font-size:0.75rem; color:#9ca3af; font-weight:500;">{{ $booking->client->email }}</span>
                            </td>
                            <td style="padding:12px; text-align:center; font-weight:700; color:#374151;">{{ $booking->seats_count }}</td>
                            <td style="padding:12px; text-align:right; font-weight:700; color:#1a2744;">{{ number_format($booking->total_amount, 0, ',', '.') }} FCFA</td>
                            <td style="padding:12px; text-align:center;">
                                <span style="background:{{ $bc['bg'] }}; color:{{ $bc['color'] }}; font-size:0.7rem; font-weight:800; padding:3px 10px; border-radius:100px;">{{ $bc['label'] }}</span>
                            </td>
                            <td style="padding:12px; text-align:right; color:#6b7280; font-size:0.82rem;">{{ $booking->created_at->format('d/m/Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Modal annulation --}}
@if($canCancel)
<div id="cancel-modal"
     style="display:none; position:fixed; inset:0; z-index:9999; align-items:center; justify-content:center; background:rgba(0,0,0,0.5); padding:16px;"
     onclick="if(event.target===this)this.style.display='none'">
    <div style="background:white; border-radius:20px; width:100%; max-width:420px; padding:24px; box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <h3 style="font-size:1.1rem; font-weight:800; color:#1a2744; margin:0 0 0.5rem;">Annuler cet événement ?</h3>
        <p style="color:#6b7280; font-size:0.875rem; margin:0 0 1.25rem;">Cette action est irréversible. Les participants seront informés.</p>
        <form action="{{ route('talent.meet-and-greet.cancel', $experience->id) }}" method="POST">
            @csrf
            <textarea name="cancelled_reason" required rows="3"
                      placeholder="Expliquez le motif d'annulation..."
                      style="width:100%; padding:12px; border:1.5px solid #e5e7eb; border-radius:10px; font-size:0.875rem; font-family:inherit; margin-bottom:1rem; resize:none; box-sizing:border-box;"></textarea>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('cancel-modal').style.display='none'"
                        style="padding:10px 18px; border-radius:10px; background:#f3f4f6; color:#374151; font-weight:700; font-size:0.875rem; border:none; cursor:pointer; font-family:inherit;">
                    Garder l'événement
                </button>
                <button type="submit"
                        style="padding:10px 18px; border-radius:10px; background:#dc2626; color:white; font-weight:800; font-size:0.875rem; border:none; cursor:pointer; font-family:inherit;">
                    Confirmer l'annulation
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
