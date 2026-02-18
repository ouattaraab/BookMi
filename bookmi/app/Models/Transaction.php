<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    protected $fillable = [
        'booking_request_id',
        'payment_method',
        'amount',
        'currency',
        'gateway',
        'gateway_reference',
        'gateway_response',
        'status',
        'idempotency_key',
        'initiated_at',
        'completed_at',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'status'           => TransactionStatus::class,
        'payment_method'   => PaymentMethod::class,
        'initiated_at'     => 'datetime',
        'completed_at'     => 'datetime',
    ];

    // ─── Relations ──────────────────────────────────────────────────────────

    public function bookingRequest(): BelongsTo
    {
        return $this->belongsTo(BookingRequest::class);
    }

    public function escrowHold(): HasOne
    {
        return $this->hasOne(EscrowHold::class);
    }
}
