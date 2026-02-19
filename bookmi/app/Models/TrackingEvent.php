<?php

namespace App\Models;

use App\Enums\TrackingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingEvent extends Model
{
    /** @use HasFactory<\Database\Factories\TrackingEventFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_request_id',
        'updated_by',
        'status',
        'latitude',
        'longitude',
        'occurred_at',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status'      => TrackingStatus::class,
            'latitude'    => 'float',
            'longitude'   => 'float',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<BookingRequest, $this>
     */
    public function bookingRequest(): BelongsTo
    {
        return $this->belongsTo(BookingRequest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
