<?php

namespace App\Models;

use App\Enums\ExperienceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrivateExperience extends Model
{
    /** @use HasFactory<\Database\Factories\PrivateExperienceFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'talent_profile_id',
        'title',
        'description',
        'event_date',
        'venue_address',
        'venue_revealed',
        'total_price',
        'max_seats',
        'booked_seats',
        'status',
        'premium_options',
        'cover_image',
        'cancelled_reason',
        'commission_rate',
    ];

    protected $casts = [
        'event_date'       => 'datetime',
        'venue_revealed'   => 'boolean',
        'premium_options'  => 'array',
        'status'           => ExperienceStatus::class,
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function talentProfile(): BelongsTo
    {
        return $this->belongsTo(TalentProfile::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(ExperienceBooking::class);
    }

    // ── Accessors ──────────────────────────────────────────────────────────

    /**
     * Prix par place en XOF (arrondi).
     */
    public function getPricePerSeatAttribute(): int
    {
        if ($this->max_seats <= 0) {
            return 0;
        }

        return (int) round($this->total_price / $this->max_seats);
    }

    public function getSeatsAvailableAttribute(): int
    {
        return max(0, $this->max_seats - $this->booked_seats);
    }

    public function getIsFullAttribute(): bool
    {
        return $this->booked_seats >= $this->max_seats;
    }

    /**
     * Total perçu (bookings confirmed).
     */
    public function getTotalCollectedAttribute(): int
    {
        return (int) $this->bookings()->where('status', 'confirmed')->sum('total_amount');
    }

    /**
     * Part nette talent (total perçu − commission BookMi) — 1 seule query.
     */
    public function getTalentNetAttribute(): int
    {
        $net = $this->bookings()
            ->where('status', 'confirmed')
            ->selectRaw('SUM(total_amount) - SUM(commission_amount) as net')
            ->value('net');

        return (int) ($net ?? 0);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereIn('status', ExperienceStatus::visibleOnPublic());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('event_date', '>=', now());
    }

    /**
     * URL publique du média de couverture (image ou vidéo).
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->cover_image);
    }
}
