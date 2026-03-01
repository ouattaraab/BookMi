<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Notifications\ResetPasswordNotification;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected string $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'is_admin',
        'is_active',
        'is_client_verified',
        'client_verified_at',
        'is_suspended',
        'suspended_at',
        'suspended_until',
        'suspension_reason',
        'fcm_token',
        'two_factor_enabled',
        'two_factor_method',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'phone_verified_at',
        'avatar',
        'notification_preferences',
        'referred_by_code',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin'                => 'boolean',
            'is_active'               => 'boolean',
            'is_client_verified'      => 'boolean',
            'client_verified_at'      => 'datetime',
            'is_suspended'            => 'boolean',
            'suspended_at'            => 'datetime',
            'suspended_until'         => 'datetime',
            'two_factor_enabled'      => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'notification_preferences' => 'array',
        ];
    }

    /**
     * @return HasOne<\App\Models\TalentProfile, $this>
     */
    public function talentProfile(): HasOne
    {
        return $this->hasOne(TalentProfile::class);
    }

    /**
     * @return HasMany<\App\Models\IdentityVerification, $this>
     */
    public function identityVerifications(): HasMany
    {
        return $this->hasMany(IdentityVerification::class);
    }

    /**
     * @return HasOne<\App\Models\IdentityVerification, $this>
     */
    public function identityVerification(): HasOne
    {
        return $this->hasOne(IdentityVerification::class)->latestOfMany();
    }

    /**
     * @return HasMany<\App\Models\ActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'causer_id');
    }

    /**
     * @return BelongsToMany<\App\Models\TalentProfile, $this>
     */
    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(TalentProfile::class, 'user_favorites')
            ->withPivot('id')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<\App\Models\TalentProfile, $this>
     */
    public function managedTalents(): BelongsToMany
    {
        return $this->belongsToMany(TalentProfile::class, 'talent_manager', 'manager_id', 'talent_profile_id')
            ->withPivot('assigned_at')
            ->withTimestamps();
    }

    /**
     * @return HasMany<\App\Models\TalentFollow, $this>
     */
    public function talentFollows(): HasMany
    {
        return $this->hasMany(TalentFollow::class);
    }

    /**
     * @return HasMany<\App\Models\AdminWarning, $this>
     */
    public function warnings(): HasMany
    {
        return $this->hasMany(AdminWarning::class);
    }

    /**
     * @return HasOne<ReferralCode, $this>
     */
    public function referralCode(): HasOne
    {
        return $this->hasOne(ReferralCode::class);
    }

    /**
     * Referrals where this user is the referrer.
     *
     * @return HasMany<Referral, $this>
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin === true
            && $this->is_active === true
            && $this->hasAnyRole([
                \App\Enums\UserRole::ADMIN_CEO->value,
                \App\Enums\UserRole::ADMIN_COMPTABLE->value,
                \App\Enums\UserRole::ADMIN_CONTROLEUR->value,
                \App\Enums\UserRole::ADMIN_MODERATEUR->value,
            ]);
    }

    public function getFilamentName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? Storage::disk('public')->url($this->avatar) : null;
    }

    public static function defaultNotificationPreferences(): array
    {
        return [
            'new_message'      => true,
            'booking_updates'  => true,
            'new_review'       => true,
            'follow_update'    => true,
            'admin_broadcast'  => true,
        ];
    }

    public function getNotificationPreference(string $type): bool
    {
        $prefs = $this->notification_preferences ?? self::defaultNotificationPreferences();
        return (bool) ($prefs[$type] ?? true);
    }
}
