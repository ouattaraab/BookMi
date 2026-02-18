<?php

namespace App\Models;

use App\Enums\RescheduleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RescheduleRequest extends Model
{
    protected $fillable = [
        'booking_request_id',
        'requested_by_id',
        'proposed_date',
        'message',
        'status',
        'responded_at',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'proposed_date' => 'date:Y-m-d',
            'status'        => RescheduleStatus::class,
            'responded_at'  => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<BookingRequest, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(BookingRequest::class, 'booking_request_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }
}
