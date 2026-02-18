<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRequest extends Model
{
    /** @use HasFactory<\Database\Factories\BookingRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'client_id',
        'talent_profile_id',
        'service_package_id',
        'event_date',
        'event_location',
        'message',
        'reject_reason',
        'status',
        'cachet_amount',
        'commission_amount',
        'total_amount',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'event_date'        => 'date:Y-m-d',
            'status'            => BookingStatus::class,
            'reject_reason'     => 'string',
            'cachet_amount'     => 'integer',
            'commission_amount' => 'integer',
            'total_amount'      => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * @return BelongsTo<TalentProfile, $this>
     */
    public function talentProfile(): BelongsTo
    {
        return $this->belongsTo(TalentProfile::class);
    }

    /**
     * @return BelongsTo<ServicePackage, $this>
     */
    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class);
    }

    /**
     * Returns true if the user is the client or the talent owner of this booking.
     */
    public function isOwnedByUser(User $user): bool
    {
        if ($this->client_id === $user->id) {
            return true;
        }

        return TalentProfile::where('id', $this->talent_profile_id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
