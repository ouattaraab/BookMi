<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Enums\WarningStatus;
use App\Exceptions\AdminException;
use App\Models\AdminWarning;
use App\Models\BookingRequest;
use App\Models\TalentProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminService
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    // ─── Dashboard (8.1) ────────────────────────────────────────────────────

    public function dashboardStats(): array
    {
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $monthStart = Carbon::now()->startOfMonth();

        $bookings = BookingRequest::query();

        return [
            'users' => [
                'total'      => User::count(),
                'this_week'  => User::where('created_at', '>=', $weekStart)->count(),
                'this_month' => User::where('created_at', '>=', $monthStart)->count(),
            ],
            'bookings' => [
                'total'        => (clone $bookings)->count(),
                'pending'      => (clone $bookings)->where('status', BookingStatus::Pending)->count(),
                'completed'    => (clone $bookings)->where('status', BookingStatus::Completed)->count(),
                'disputed'     => (clone $bookings)->where('status', BookingStatus::Disputed)->count(),
                'today'        => (clone $bookings)->whereDate('created_at', $today)->count(),
            ],
            'revenue' => [
                'total_xof'   => BookingRequest::where('status', BookingStatus::Completed)->sum('cachet_amount'),
                'commission_xof' => BookingRequest::where('status', BookingStatus::Completed)->sum('commission_amount'),
                'this_month_xof' => BookingRequest::where('status', BookingStatus::Completed)
                    ->where('created_at', '>=', $monthStart)->sum('commission_amount'),
            ],
            'talents' => [
                'total'    => TalentProfile::count(),
                'verified' => TalentProfile::where('is_verified', true)->count(),
            ],
            'dispute_rate' => $this->disputeRate(),
        ];
    }

    private function disputeRate(): float
    {
        $total = BookingRequest::whereIn('status', [
            BookingStatus::Completed, BookingStatus::Disputed,
        ])->count();

        if ($total === 0) {
            return 0.0;
        }

        $disputed = BookingRequest::where('status', BookingStatus::Disputed)->count();

        return round(($disputed / $total) * 100, 2);
    }

    // ─── Dispute resolve (8.2) ──────────────────────────────────────────────

    public function resolveDispute(User $admin, BookingRequest $booking, string $resolution, ?string $note): void
    {
        if ($booking->status !== BookingStatus::Disputed) {
            throw new \InvalidArgumentException('La réservation n\'est pas en litige.');
        }

        DB::transaction(function () use ($admin, $booking, $resolution, $note) {
            $newStatus = match ($resolution) {
                'refund_client' => BookingStatus::Cancelled,
                'pay_talent'    => BookingStatus::Completed,
                'compromise'    => BookingStatus::Completed,
                default         => throw new \InvalidArgumentException('Résolution invalide.'),
            };

            $booking->update(['status' => $newStatus]);

            $this->audit->log('dispute.resolved', $booking, [
                'resolution' => $resolution,
                'note'       => $note,
                'new_status' => $newStatus->value,
            ]);
        });
    }

    // ─── Warnings & Suspension (8.3) ────────────────────────────────────────

    public function createWarning(User $admin, User $target, string $reason, ?string $details): AdminWarning
    {
        $warning = AdminWarning::create([
            'user_id'      => $target->id,
            'issued_by_id' => $admin->id,
            'reason'       => $reason,
            'details'      => $details,
            'status'       => WarningStatus::Active,
        ]);

        $this->audit->log('warning.issued', $target, [
            'warning_id' => $warning->id,
            'reason'     => $reason,
        ]);

        return $warning;
    }

    public function suspendUser(User $admin, User $target, string $reason, ?string $until): void
    {
        if ($target->is_admin) {
            throw AdminException::cannotSuspendAdmin();
        }

        if ($target->is_suspended) {
            throw AdminException::alreadySuspended();
        }

        $suspendedUntil = $until ? Carbon::parse($until) : null;

        DB::transaction(function () use ($admin, $target, $reason, $suspendedUntil) {
            $target->update([
                'is_suspended'     => true,
                'suspended_at'     => now(),
                'suspended_until'  => $suspendedUntil,
                'suspension_reason' => $reason,
            ]);

            // Revoke all API tokens
            $target->tokens()->delete();

            // Hide talent profile from discovery
            if ($target->talentProfile) {
                $target->talentProfile->update(['is_active' => false]);
            }

            $this->audit->log('user.suspended', $target, [
                'reason'   => $reason,
                'until'    => $suspendedUntil?->toDateString(),
            ]);
        });
    }

    public function unsuspendUser(User $admin, User $target): void
    {
        if (! $target->is_suspended) {
            throw AdminException::notSuspended();
        }

        DB::transaction(function () use ($admin, $target) {
            $target->update([
                'is_suspended'      => false,
                'suspended_at'      => null,
                'suspended_until'   => null,
                'suspension_reason' => null,
            ]);

            if ($target->talentProfile) {
                $target->talentProfile->update(['is_active' => true]);
            }

            $this->audit->log('user.unsuspended', $target);
        });
    }

    // ─── Team delegation (8.6) ──────────────────────────────────────────────

    public function listAdminTeam(): \Illuminate\Support\Collection
    {
        $adminRoles = [
            UserRole::ADMIN_CEO->value,
            UserRole::ADMIN_COMPTABLE->value,
            UserRole::ADMIN_CONTROLEUR->value,
            UserRole::ADMIN_MODERATEUR->value,
        ];

        return User::whereHas('roles', fn ($q) => $q->whereIn('name', $adminRoles))
            ->with('roles')
            ->get()
            ->map(fn (User $u) => [
                'id'         => $u->id,
                'name'       => $u->first_name . ' ' . $u->last_name,
                'email'      => $u->email,
                'role'       => $u->roles->first()?->name,
                'created_at' => $u->created_at,
            ]);
    }

    public function createAdminCollaborator(User $ceo, array $data): User
    {
        $allowedRoles = [
            UserRole::ADMIN_COMPTABLE->value,
            UserRole::ADMIN_CONTROLEUR->value,
            UserRole::ADMIN_MODERATEUR->value,
        ];

        if (! in_array($data['role'], $allowedRoles, true)) {
            throw AdminException::insufficientAdminRole();
        }

        return DB::transaction(function () use ($ceo, $data) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'email'      => $data['email'],
                'phone'      => $data['phone'],
                'password'   => bcrypt($data['password']),
                'is_admin'   => true,
                'is_active'  => true,
            ]);

            $user->assignRole($data['role']);

            $this->audit->log('team.collaborator_created', $user, [
                'role'       => $data['role'],
                'created_by' => $ceo->id,
            ]);

            return $user;
        });
    }

    public function updateCollaboratorRole(User $ceo, User $collaborator, string $newRole): void
    {
        if ($collaborator->id === $ceo->id) {
            throw AdminException::cannotModifyOwnRole();
        }

        $allowedRoles = [
            UserRole::ADMIN_COMPTABLE->value,
            UserRole::ADMIN_CONTROLEUR->value,
            UserRole::ADMIN_MODERATEUR->value,
        ];

        if (! in_array($newRole, $allowedRoles, true)) {
            throw AdminException::insufficientAdminRole();
        }

        DB::transaction(function () use ($ceo, $collaborator, $newRole) {
            $collaborator->syncRoles([$newRole]);
            $this->audit->log('team.role_updated', $collaborator, [
                'new_role'   => $newRole,
                'updated_by' => $ceo->id,
            ]);
        });
    }

    public function revokeCollaboratorAccess(User $ceo, User $collaborator): void
    {
        if ($collaborator->id === $ceo->id) {
            throw AdminException::cannotModifyOwnRole();
        }

        DB::transaction(function () use ($ceo, $collaborator) {
            $collaborator->syncRoles([]);
            $collaborator->update(['is_admin' => false, 'is_active' => false]);
            $collaborator->tokens()->delete();
            $this->audit->log('team.access_revoked', $collaborator, ['revoked_by' => $ceo->id]);
        });
    }
}
