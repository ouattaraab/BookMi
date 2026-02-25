<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingStatusLog extends Model
{
    public $timestamps = false; // Only created_at, set by DB default

    protected $fillable = [
        'booking_request_id',
        'from_status',
        'to_status',
        'performed_by_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }

    /** @return BelongsTo<BookingRequest, $this> */
    public function bookingRequest(): BelongsTo
    {
        return $this->belongsTo(BookingRequest::class);
    }
}
