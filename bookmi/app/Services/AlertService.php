<?php

namespace App\Services;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Exceptions\AdminException;
use App\Models\AdminAlert;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AlertService
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function create(
        AlertType $type,
        AlertSeverity $severity,
        string $title,
        string $description,
        ?Model $subject = null,
        array $metadata = [],
    ): AdminAlert {
        return AdminAlert::create([
            'type'         => $type,
            'severity'     => $severity,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id'   => $subject?->getKey(),
            'title'        => $title,
            'description'  => $description,
            'metadata'     => ! empty($metadata) ? $metadata : null,
            'status'       => 'open',
        ]);
    }

    public function openExists(AlertType $type, Model $subject): bool
    {
        return AdminAlert::where('type', $type)
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->where('status', 'open')
            ->exists();
    }

    public function resolve(User $admin, AdminAlert $alert): void
    {
        if (! $alert->isOpen()) {
            throw AdminException::alertAlreadyClosed();
        }

        $alert->update([
            'status'         => 'resolved',
            'resolved_at'    => now(),
            'resolved_by_id' => $admin->id,
        ]);

        $this->audit->log('alert.resolved', $alert, ['admin_id' => $admin->id]);
    }

    public function dismiss(User $admin, AdminAlert $alert): void
    {
        if (! $alert->isOpen()) {
            throw AdminException::alertAlreadyClosed();
        }

        $alert->update([
            'status'         => 'dismissed',
            'resolved_at'    => now(),
            'resolved_by_id' => $admin->id,
        ]);

        $this->audit->log('alert.dismissed', $alert, ['admin_id' => $admin->id]);
    }
}
