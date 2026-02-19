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
        'refund_amount',
        'refund_reference',
        'refund_reason',
        'refunded_at',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'status'           => TransactionStatus::class,
        'payment_method'   => PaymentMethod::class,
        'initiated_at'     => 'datetime',
        'completed_at'     => 'datetime',
        'refunded_at'      => 'datetime',
        'refund_amount'    => 'integer',
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
