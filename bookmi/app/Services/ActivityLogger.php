<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Write an entry to the activity log.
     *
     * @param  string       $action    Free-form action key, e.g. "verification.approved"
     * @param  object|null  $subject   Eloquent model that is the subject of the action
     * @param  array<string, mixed>  $metadata  Any extra data to store as JSON
     * @param  int|null     $causerId  Override the authenticated user id
     */
    public static function log(
        string $action,
        ?object $subject = null,
        array $metadata = [],
        ?int $causerId = null
    ): void {
        try {
            ActivityLog::create([
                'causer_id'    => $causerId ?? Auth::id(),
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id'   => $subject ? $subject->getKey() : null,
                'action'       => $action,
                'metadata'     => $metadata,
                'ip_address'   => Request::ip(),
            ]);
        } catch (\Throwable) {
            // Never break the main flow because of a logging failure
        }
    }
}
