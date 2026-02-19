<?php

namespace App\Events;

use App\Models\TrackingEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrackingStatusChanged implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly TrackingEvent $trackingEvent,
    ) {
    }

    /**
     * @return Channel|array<Channel>
     */
    public function broadcastOn(): Channel|array
    {
        return new PresenceChannel('tracking.' . $this->trackingEvent->booking_request_id);
    }

    public function broadcastAs(): string
    {
        return 'tracking.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id'                  => $this->trackingEvent->id,
            'booking_request_id'  => $this->trackingEvent->booking_request_id,
            'status'              => $this->trackingEvent->status->value,
            'status_label'        => $this->trackingEvent->status->label(),
            'latitude'            => $this->trackingEvent->latitude,
            'longitude'           => $this->trackingEvent->longitude,
            'occurred_at'         => $this->trackingEvent->occurred_at?->toISOString(),
            'updated_by'          => $this->trackingEvent->updated_by,
        ];
    }
}
