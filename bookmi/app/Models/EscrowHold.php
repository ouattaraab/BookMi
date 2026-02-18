<?php

namespace App\Models;

use App\Enums\EscrowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EscrowHold extends Model
{
    protected $fillable = [
        'transaction_id',
        'booking_request_id',
        'cachet_amount',
        'commission_amount',
        'total_amount',
        'status',
        'held_at',
        'release_scheduled_at',
        'released_at',
    ];

    protected $casts = [
        'status'               => EscrowStatus::class,
        'held_at'              => 'datetime',
        'release_scheduled_at' => 'datetime',
        'released_at'          => 'datetime',
    ];

    // ─── Relations ──────────────────────────────────────────────────────────

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function bookingRequest(): BelongsTo
    {
        return $this->belongsTo(BookingRequest::class);
    }

    public function payout(): HasOne
    {
        return $this->hasOne(Payout::class);
    }
}
