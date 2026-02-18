<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\RescheduleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'is_express',
        'reject_reason',
        'contract_path',
        'status',
        'cachet_amount',
        'commission_amount',
        'total_amount',
        'refund_amount',
        'cancellation_policy_applied',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'event_date'        => 'date:Y-m-d',
            'is_express'        => 'boolean',
            'status'            => BookingStatus::class,
            'reject_reason'     => 'string',
            'cachet_amount'     => 'integer',
            'commission_amount' => 'integer',
            'total_amount'      => 'integer',
            'refund_amount'     => 'integer',
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
     * @return HasMany<RescheduleRequest, $this>
     */
    public function rescheduleRequests(): HasMany
    {
        return $this->hasMany(RescheduleRequest::class);
    }

    public function hasPendingReschedule(): bool
    {
        return $this->rescheduleRequests()
            ->where('status', RescheduleStatus::Pending)
            ->exists();
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
