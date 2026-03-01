<?php

namespace App\Models;

use App\Enums\ReviewType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    /** @use HasFactory<\Database\Factories\ReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_request_id',
        'reviewer_id',
        'reviewee_id',
        'type',
        'rating',
        'punctuality_score',
        'quality_score',
        'professionalism_score',
        'contract_respect_score',
        'comment',
        'reply',
        'reply_at',
        'is_reported',
        'report_reason',
        'reported_at',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'type'                   => ReviewType::class,
            'rating'                 => 'integer',
            'punctuality_score'      => 'integer',
            'quality_score'          => 'integer',
            'professionalism_score'  => 'integer',
            'contract_respect_score' => 'integer',
            'reply_at'    => 'datetime',
            'is_reported' => 'boolean',
            'reported_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<BookingRequest, $this>
     */
    public function bookingRequest(): BelongsTo
    {
        return $this->belongsTo(BookingRequest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }
}
