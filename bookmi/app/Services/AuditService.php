<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function log(string $action, ?Model $subject = null, array $metadata = []): ActivityLog
    {
        return ActivityLog::create([
            'causer_id' => auth()->id(),
            'subject_type' => $subject !== null ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'action' => $action,
            'metadata' => ! empty($metadata) ? $metadata : null,
            'ip_address' => request()->ip(),
        ]);
    }
}
