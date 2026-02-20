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
        'link_url',
        'link_platform',
        'submitted_by_client',
        'submitted_by_user_id',
        'is_approved',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_compressed'       => 'boolean',
            'submitted_by_client' => 'boolean',
            'is_approved'         => 'boolean',
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

    /**
     * Returns the YouTube embed URL if this is a YouTube link, null otherwise.
     */
    public function youtubeEmbedUrl(): ?string
    {
        if ($this->media_type !== 'link' || $this->link_platform !== 'youtube') {
            return null;
        }

        preg_match(
            '/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/',
            $this->link_url ?? '',
            $matches,
        );

        return isset($matches[1])
            ? 'https://www.youtube.com/embed/' . $matches[1] . '?rel=0'
            : null;
    }

    /**
     * Returns the public URL for file-based items (images, videos).
     */
    public function publicUrl(): ?string
    {
        $path = $this->displayPath();
        return $path ? \Illuminate\Support\Facades\Storage::disk('public')->url($path) : null;
    }
}
