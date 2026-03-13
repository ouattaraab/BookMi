<?php

namespace App\Models;

use App\Enums\ExperienceBookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExperienceBooking extends Model
{
    /** @use HasFactory<\Database\Factories\ExperienceBookingFactory> */
    use HasFactory;
    protected $fillable = [
        'private_experience_id',
        'client_id',
        'seats_count',
        'price_per_seat',
        'total_amount',
        'commission_amount',
        'status',
        'cancelled_reason',
        'cancelled_at',
        'premium_options_selected',
    ];

    protected $casts = [
        'status'                   => ExperienceBookingStatus::class,
        'cancelled_at'             => 'datetime',
        'premium_options_selected' => 'array',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function experience(): BelongsTo
    {
        return $this->belongsTo(PrivateExperience::class, 'private_experience_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    // ── Accessors ──────────────────────────────────────────────────────────

    public function getTalentAmountAttribute(): int
    {
        return $this->total_amount - $this->commission_amount;
    }
}
