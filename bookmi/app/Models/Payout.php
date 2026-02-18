<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PayoutStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    protected $fillable = [
        'talent_profile_id',
        'escrow_hold_id',
        'amount',
        'payout_method',
        'payout_details',
        'gateway',
        'gateway_reference',
        'status',
        'processed_at',
    ];

    protected $casts = [
        'payout_details' => 'array',
        'status'         => PayoutStatus::class,
        'payout_method'  => PaymentMethod::class,
        'processed_at'   => 'datetime',
    ];

    // ─── Relations ──────────────────────────────────────────────────────────

    public function talentProfile(): BelongsTo
    {
        return $this->belongsTo(TalentProfile::class);
    }

    public function escrowHold(): BelongsTo
    {
        return $this->belongsTo(EscrowHold::class);
    }
}
