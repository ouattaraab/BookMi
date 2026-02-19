<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioItem extends Model
{
    /** @use HasFactory<\Database\Factories\PortfolioItemFactory> */
    use HasFactory;

    protected $fillable = [
        'talent_profile_id',
        'booking_request_id',
        'media_type',
        'original_path',
        'compressed_path',
        'caption',
        'is_compressed',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_compressed' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<TalentProfile, $this>
     */
    public function talentProfile(): BelongsTo
    {
        return $this->belongsTo(TalentProfile::class);
    }

    /**
     * @return BelongsTo<BookingRequest, $this>
     */
    public function bookingRequest(): BelongsTo
    {
        return $this->belongsTo(BookingRequest::class);
    }

    public function displayPath(): string
    {
        return $this->compressed_path ?? $this->original_path;
    }
}
