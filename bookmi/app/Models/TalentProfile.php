<?php

namespace App\Models;

use App\Enums\TalentLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class TalentProfile extends Model
{
    /** @use HasFactory<\Database\Factories\TalentProfileFactory> */
    use HasFactory;
    use HasSlug;
    use SoftDeletes;

    /**
     * Haversine formula SQL — bindings: [$lat, $lat, $lng]
     */
    public const HAVERSINE_SQL = '(2 * 6371 * ASIN(SQRT('
        . 'POWER(SIN(RADIANS(talent_profiles.latitude - ?) / 2), 2) + '
        . 'COS(RADIANS(?)) * COS(RADIANS(talent_profiles.latitude)) * '
        . 'POWER(SIN(RADIANS(talent_profiles.longitude - ?) / 2), 2)'
        . ')))';

    protected $fillable = [
        'user_id',
        'category_id',
        'subcategory_id',
        'stage_name',
        'bio',
        'city',
        'latitude',
        'longitude',
        'cachet_amount',
        'social_links',
        'is_verified',
        'talent_level',
        'average_rating',
        'total_bookings',
        'enable_express_booking',
        'profile_completion_percentage',
        'payout_method',
        'payout_details',
        'auto_reply_message',
        'auto_reply_is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'cachet_amount' => 'integer',
            'is_verified' => 'boolean',
            'enable_express_booking' => 'boolean',
            'talent_level' => TalentLevel::class,
            'average_rating' => 'decimal:2',
            'total_bookings' => 'integer',
            'profile_completion_percentage' => 'integer',
            'social_links'    => 'array',
            'payout_details'       => 'array',
            'auto_reply_is_active' => 'boolean',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('stage_name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate()
            ->usingLanguage('fr');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeVerified(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_verified', true);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeByCategory(\Illuminate\Database\Eloquent\Builder $query, int $categoryId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeByCity(\Illuminate\Database\Eloquent\Builder $query, string $city): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('city', $city);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeWithinRadiusOf(
        \Illuminate\Database\Eloquent\Builder $query,
        float $lat,
        float $lng,
        float $radiusKm,
    ): \Illuminate\Database\Eloquent\Builder {
        $latDelta = $radiusKm / 111.0;
        $lngDelta = $radiusKm / (111.0 * cos(deg2rad($lat)));

        return $query
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta])
            ->whereRaw(
                self::HAVERSINE_SQL . ' <= ?',
                [$lat, $lat, $lng, $radiusKm],
            );
    }

    /**
     * @return HasMany<ServicePackage, $this>
     */
    public function servicePackages(): HasMany
    {
        return $this->hasMany(ServicePackage::class);
    }

    /**
     * @return BelongsToMany<\App\Models\User, $this>
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_favorites')
            ->withTimestamps();
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
