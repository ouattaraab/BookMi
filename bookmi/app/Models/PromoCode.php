<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $expires_at
 */
class PromoCode extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'max_uses',
        'used_count',
        'min_booking_amount',
        'expires_at',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value'              => 'integer',
            'max_uses'           => 'integer',
            'used_count'         => 'integer',
            'min_booking_amount' => 'integer',
            'is_active'          => 'boolean',
            'expires_at'         => 'datetime',
        ];
    }

    /**
     * @return HasMany<BookingRequest, $this>
     */
    public function bookingRequests(): HasMany
    {
        return $this->hasMany(BookingRequest::class, 'promo_code_id');
    }

    /**
     * Check whether this promo code can be applied to the given booking amount.
     */
    public function isValidFor(int $bookingAmount): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        if ($this->min_booking_amount !== null && $bookingAmount < $this->min_booking_amount) {
            return false;
        }

        return true;
    }

    /**
     * Calculate the discount in XOF for the given booking amount.
     */
    public function calculateDiscount(int $bookingAmount): int
    {
        if ($this->type === 'percentage') {
            return (int) round($bookingAmount * $this->value / 100);
        }

        // Fixed — cap at booking amount so total is never negative
        return min($this->value, $bookingAmount);
    }
}
