<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TalentNotificationRequest extends Model
{
    protected $fillable = [
        'search_query',
        'email',
        'phone',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'notified_at' => 'datetime',
        ];
    }

    public function isPending(): bool
    {
        return $this->notified_at === null;
    }

    public function isNotified(): bool
    {
        return $this->notified_at !== null;
    }
}
