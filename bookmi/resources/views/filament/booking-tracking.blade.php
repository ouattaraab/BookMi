<div style="font-family:sans-serif;padding:8px 0;">
    <h2 style="font-size:1rem;font-weight:700;margin-bottom:16px;color:#1e293b;">
        Chronologie Jour-J — Réservation #{{ $booking->id }}
    </h2>

    @if($trackingEvents->isEmpty())
        <p style="color:#94a3b8;font-size:0.875rem;">Aucun événement de suivi enregistré.</p>
    @else
        <div style="position:relative;padding-left:20px;">
            @foreach($trackingEvents as $event)
            @php
                $isLast = $loop->last;
                $statusLabels = [
                    'preparing'  => 'En préparation 🎒',
                    'en_route'   => 'En route 🚗',
                    'arrived'    => 'Arrivé sur place ✅',
                    'performing' => 'En prestation 🎤',
                    'completed'  => 'Prestation terminée ⭐',
                ];
                $statusLabel = $statusLabels[$event->status->value ?? $event->status] ?? ($event->status->value ?? $event->status);
            @endphp
            <div style="position:relative;padding-bottom:{{ $isLast ? '0' : '16px' }};">
                <div style="position:absolute;left:-20px;top:4px;width:10px;height:10px;border-radius:50%;background:{{ $isLast ? '#16a34a' : '#f97316' }};"></div>
                @if(!$isLast)
                <div style="position:absolute;left:-16px;top:14px;width:2px;background:#e2e8f0;bottom:0;"></div>
                @endif
                <p style="font-size:0.9rem;font-weight:700;color:#1e293b;margin:0 0 2px;">{{ $statusLabel }}</p>
                <p style="font-size:0.78rem;color:#64748b;margin:0;">
                    {{ $event->occurred_at?->format('d/m/Y H:i') ?? '—' }}
                </p>
                @if($event->client_notified_at)
                <p style="font-size:0.75rem;color:#7c3aed;margin:2px 0 0;">
                    📱 Client notifié à {{ $event->client_notified_at->format('H:i') }}
                </p>
                @endif
            </div>
            @endforeach
        </div>
    @endif

    {{-- Confirmation client --}}
    <div style="margin-top:20px;padding-top:16px;border-top:1px solid #e2e8f0;">
        <p style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#94a3b8;margin:0 0 8px;">Confirmation de présence (client)</p>
        @if($booking->client_confirmed_arrival_at)
            <p style="font-size:0.9rem;font-weight:700;color:#16a34a;margin:0;">
                ✅ Confirmée le {{ $booking->client_confirmed_arrival_at->format('d/m/Y à H:i') }}
            </p>
        @else
            <p style="font-size:0.875rem;color:#94a3b8;margin:0;">En attente de confirmation du client</p>
        @endif
    </div>
</div>
