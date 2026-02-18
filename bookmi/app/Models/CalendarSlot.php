<?php

namespace App\Models;

use App\Enums\CalendarSlotStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarSlot extends Model
{
    /** @use HasFactory<\Database\Factories\CalendarSlotFactory> */
    use HasFactory;

    protected $fillable = [
        'talent_profile_id',
        'date',
        'status',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'date'   => 'date:Y-m-d',
            'status' => CalendarSlotStatus::class,
        ];
    }

    /**
     * @return BelongsTo<TalentProfile, $this>
     */
    public function talentProfile(): BelongsTo
    {
        return $this->belongsTo(TalentProfile::class);
    }
}
