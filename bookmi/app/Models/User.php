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
        'is_suspended',
        'suspended_at',
        'suspended_until',
        'suspension_reason',
        'fcm_token',
        'two_factor_enabled',
        'two_factor_method',
        'two_factor_secret',
        'two_factor_confirmed_at',
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
            'is_suspended'            => 'boolean',
            'suspended_at'            => 'datetime',
            'suspended_until'         => 'datetime',
            'two_factor_enabled'      => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
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
     * @return HasMany<\App\Models\AdminWarning, $this>
     */
    public function warnings(): HasMany
    {
        return $this->hasMany(AdminWarning::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin === true && $this->is_active === true;
    }

    public function getFilamentName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
