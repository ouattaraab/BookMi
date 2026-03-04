<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\ManagerInvitationStatus;
use App\Exceptions\ManagerException;
use App\Jobs\SendPushNotification;
use App\Models\BookingRequest;
use App\Models\CalendarSlot;
use App\Models\Conversation;
use App\Models\ManagerInvitation;
use App\Models\Message;
use App\Models\TalentProfile;
use App\Models\User;
use App\Notifications\ManagerInvitedNotification;
use App\Notifications\ManagerInvitationResponseNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class ManagerService
{
    // ─────────────────────────────────────────────
    // Story 7.1 — Assignation manager ↔ talent
    // ─────────────────────────────────────────────

    public function assignManager(TalentProfile $talent, string $managerEmail): void
    {
        $manager = User::where('email', strtolower($managerEmail))->first();

        if (! $manager) {
            throw ManagerException::managerNotFound();
        }

        if (! $manager->hasRole('manager', 'api')) {
            throw ManagerException::notAManager();
        }

        $alreadyAssigned = $talent->managers()->where('manager_id', $manager->id)->exists();
        if ($alreadyAssigned) {
            throw ManagerException::alreadyAssigned();
        }

        $talent->managers()->attach($manager->id, ['assigned_at' => now()]);
    }

    public function unassignManager(TalentProfile $talent, string $managerEmail): void
    {
        $manager = User::where('email', strtolower($managerEmail))->first();

        if (! $manager) {
            throw ManagerException::managerNotFound();
        }

        $isAssigned = $talent->managers()->where('manager_id', $manager->id)->exists();
        if (! $isAssigned) {
            throw ManagerException::notAssigned();
        }

        $talent->managers()->detach($manager->id);
    }

    // ─────────────────────────────────────────────
    // Story 7.2 — Interface manager multi-talents
    // ─────────────────────────────────────────────

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, TalentProfile>
     */
    public function getMyTalents(User $manager): \Illuminate\Database\Eloquent\Collection
    {
        return $manager->managedTalents()
            ->with(['user:id,first_name,last_name,email', 'category:id,name'])
            ->withCount('managers')
            ->get();
    }

    public function getTalentStats(TalentProfile $talent, User $manager): array
    {
        $this->assertManages($talent, $manager);

        $now = now();

        $pendingCount = BookingRequest::where('talent_profile_id', $talent->id)
            ->where('status', BookingStatus::Pending->value)
            ->count();

        $confirmedCount = BookingRequest::where('talent_profile_id', $talent->id)
            ->whereIn('status', [BookingStatus::Confirmed->value, BookingStatus::Paid->value])
            ->count();

        $monthRevenue = BookingRequest::where('talent_profile_id', $talent->id)
            ->where('status', BookingStatus::Completed->value)
            ->whereMonth('event_date', $now->month)
            ->whereYear('event_date', $now->year)
            ->sum('total_amount');

        return [
            'talent_profile_id' => $talent->id,
            'stage_name' => $talent->stage_name,
            'talent_level' => $talent->talent_level?->value,
            'average_rating' => (float) $talent->average_rating,
            'total_bookings' => $talent->total_bookings,
            'pending_bookings' => $pendingCount,
            'confirmed_bookings' => $confirmedCount,
            'month_revenue_xof' => (int) $monthRevenue,
            'overload_threshold' => $talent->overload_threshold,
            'is_overloaded' => $confirmedCount >= $talent->overload_threshold,
        ];
    }

    public function getTalentBookings(TalentProfile $talent, User $manager): LengthAwarePaginator
    {
        $this->assertManages($talent, $manager);

        return BookingRequest::where('talent_profile_id', $talent->id)
            ->with(['client:id,first_name,last_name,email', 'servicePackage:id,name'])
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    // ─────────────────────────────────────────────
    // Story 7.3 — Surcharge settings
    // ─────────────────────────────────────────────

    public function updateOverloadSettings(TalentProfile $talent, int $threshold): void
    {
        $talent->update(['overload_threshold' => $threshold]);
    }

    // ─────────────────────────────────────────────
    // Story 7.4 — Gestion calendrier par le manager
    // ─────────────────────────────────────────────

    public function createCalendarSlot(TalentProfile $talent, User $manager, array $data): CalendarSlot
    {
        $this->assertManages($talent, $manager);

        return CalendarSlot::create([
            'talent_profile_id' => $talent->id,
            'date' => $data['date'],
            'status' => $data['status'],
        ]);
    }

    public function updateCalendarSlot(TalentProfile $talent, User $manager, CalendarSlot $slot, array $data): CalendarSlot
    {
        $this->assertManages($talent, $manager);

        if ($slot->talent_profile_id !== $talent->id) {
            throw ManagerException::unauthorized();
        }

        $slot->update($data);

        return $slot->fresh();
    }

    public function deleteCalendarSlot(TalentProfile $talent, User $manager, CalendarSlot $slot): void
    {
        $this->assertManages($talent, $manager);

        if ($slot->talent_profile_id !== $talent->id) {
            throw ManagerException::unauthorized();
        }

        $slot->delete();
    }

    // ─────────────────────────────────────────────
    // Story 7.5 — Validation réservation manager
    // ─────────────────────────────────────────────

    public function acceptBooking(TalentProfile $talent, User $manager, BookingRequest $booking): void
    {
        $this->assertManages($talent, $manager);
        $this->assertBookingBelongsToTalent($booking, $talent);

        if ($booking->status !== BookingStatus::Pending) {
            throw new \App\Exceptions\BookmiException(
                'BOOKING_NOT_PENDING',
                'Cette réservation ne peut pas être acceptée dans son état actuel.',
                422,
            );
        }

        $booking->update(['status' => BookingStatus::Accepted]);
    }

    public function rejectBooking(TalentProfile $talent, User $manager, BookingRequest $booking, string $reason): void
    {
        $this->assertManages($talent, $manager);
        $this->assertBookingBelongsToTalent($booking, $talent);

        if ($booking->status !== BookingStatus::Pending) {
            throw new \App\Exceptions\BookmiException(
                'BOOKING_NOT_PENDING',
                'Cette réservation ne peut pas être refusée dans son état actuel.',
                422,
            );
        }

        $booking->update([
            'status' => BookingStatus::Cancelled,
            'reject_reason' => $reason,
        ]);
    }

    // ─────────────────────────────────────────────
    // Story 7.6 — Messages manager au nom du talent
    // ─────────────────────────────────────────────

    public function sendMessageAsTalent(User $manager, Conversation $conversation, string $body): Message
    {
        $talent = $conversation->talentProfile;

        if (! $talent) {
            throw ManagerException::cannotManageOwnConversation();
        }

        $this->assertManages($talent, $manager);

        return DB::transaction(function () use ($conversation, $talent, $manager, $body) {
            $message = $conversation->messages()->create([
                'sender_id' => $talent->user_id,
                'body' => $body,
                'sent_by_manager_id' => $manager->id,
            ]);

            $conversation->touch();

            return $message;
        });
    }

    // ─────────────────────────────────────────────
    // Invitation system
    // ─────────────────────────────────────────────

    public function inviteManager(TalentProfile $profile, string $email): ManagerInvitation
    {
        $email = strtolower($email);

        $existing = ManagerInvitation::where('talent_profile_id', $profile->id)
            ->where('manager_email', $email)
            ->where('status', ManagerInvitationStatus::Pending->value)
            ->first();

        if ($existing) {
            throw new \App\Exceptions\BookmiException(
                'INVITATION_ALREADY_PENDING',
                'Une invitation est déjà en attente pour cet email.',
                409,
            );
        }

        $invitation = ManagerInvitation::create([
            'talent_profile_id' => $profile->id,
            'manager_email'     => $email,
            'status'            => ManagerInvitationStatus::Pending,
            'token'             => Str::uuid()->toString(),
            'invited_at'        => now(),
        ]);

        // If a manager account already exists, link it and push FCM
        $managerUser = User::where('email', $email)->first();
        if ($managerUser) {
            $invitation->update(['manager_id' => $managerUser->id]);

            $talentName = $profile->stage_name ?? trim($profile->user?->first_name . ' ' . $profile->user?->last_name);
            SendPushNotification::dispatch(
                $managerUser->id,
                'Invitation manager',
                "{$talentName} vous invite à gérer son profil BookMi.",
                ['type' => 'manager_invitation', 'talent_id' => (string) $profile->id],
            );
        }

        // Send email notification via on-demand (invitation is not a notifiable model)
        Notification::route('mail', $invitation->manager_email)
            ->notify(new ManagerInvitedNotification($profile, $invitation));

        return $invitation;
    }

    public function acceptInvitation(ManagerInvitation $invitation, ?string $comment): void
    {
        $invitation->update([
            'status'          => ManagerInvitationStatus::Accepted,
            'manager_comment' => $comment,
            'responded_at'    => now(),
        ]);

        // Attach manager to talent
        $profile = $invitation->talentProfile;
        if ($profile && $invitation->manager_id) {
            $alreadyAssigned = $profile->managers()
                ->where('manager_id', $invitation->manager_id)
                ->exists();
            if (! $alreadyAssigned) {
                $profile->managers()->attach($invitation->manager_id, ['assigned_at' => now()]);
            }
        }

        // Notify talent
        if ($profile) {
            $this->notifyTalentOfResponse($invitation, $profile);
        }
    }

    public function rejectInvitation(ManagerInvitation $invitation, ?string $comment): void
    {
        $invitation->update([
            'status'          => ManagerInvitationStatus::Rejected,
            'manager_comment' => $comment,
            'responded_at'    => now(),
        ]);

        $rejectProfile = $invitation->talentProfile;
        if ($rejectProfile) {
            $this->notifyTalentOfResponse($invitation, $rejectProfile);
        }
    }

    /** @return Collection<int, ManagerInvitation> */
    public function getMyInvitations(User $manager): Collection
    {
        return ManagerInvitation::with(['talentProfile.user'])
            ->where('status', ManagerInvitationStatus::Pending->value)
            ->where(function ($q) use ($manager) {
                $q->where('manager_email', strtolower($manager->email))
                    ->orWhere('manager_id', $manager->id);
            })
            ->orderByDesc('invited_at')
            ->get();
    }

    private function notifyTalentOfResponse(ManagerInvitation $invitation, TalentProfile $profile): void
    {
        $talentUser = $profile->user;
        if (! $talentUser) {
            return;
        }

        // Email
        $talentUser->notify(new ManagerInvitationResponseNotification($invitation));

        // FCM push
        $statusLabel = $invitation->status === ManagerInvitationStatus::Accepted ? 'accepté' : 'refusé';
        $managerUser = $invitation->manager;
        $managerName = $managerUser !== null
            ? $managerUser->first_name
            : explode('@', $invitation->manager_email)[0];

        SendPushNotification::dispatch(
            $talentUser->id,
            'Invitation manager',
            "Le manager {$managerName} a {$statusLabel} votre invitation.",
            [
                'type'      => 'manager_invitation_response',
                'status'    => $invitation->status->value,
                'talent_id' => (string) $profile->id,
            ],
        );
    }

    // ─────────────────────────────────────────────
    // Internal helpers
    // ─────────────────────────────────────────────

    private function assertManages(TalentProfile $talent, User $manager): void
    {
        $isAssigned = $talent->managers()->where('manager_id', $manager->id)->exists();
        if (! $isAssigned) {
            throw ManagerException::unauthorized();
        }
    }

    private function assertBookingBelongsToTalent(BookingRequest $booking, TalentProfile $talent): void
    {
        if ($booking->talent_profile_id !== $talent->id) {
            throw ManagerException::unauthorized();
        }
    }
}
