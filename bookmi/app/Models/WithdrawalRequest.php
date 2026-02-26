<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalRequest extends Model
{
    protected $fillable = [
        'talent_profile_id',
        'amount',
        'status',
        'payout_method',
        'payout_details',
        'note',
        'processed_at',
        'processed_by',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'payout_details' => 'array',
            'status'         => WithdrawalStatus::class,
            'payout_method'  => PaymentMethod::class,
            'processed_at'   => 'datetime',
        ];
    }

    // ─── Relations ──────────────────────────────────────────────────────────

    public function talentProfile(): BelongsTo
    {
        return $this->belongsTo(TalentProfile::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
