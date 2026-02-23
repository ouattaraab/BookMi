<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\BookingRequestResource;
use App\Models\BookingRequest;
use App\Models\CalendarSlot;
use App\Models\Conversation;
use App\Models\TalentProfile;
use App\Services\ManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManagerController extends BaseController
{
    public function __construct(
        private readonly ManagerService $managerService,
    ) {
    }

    // ─────────────────────────────────────────────
    // Story 7.1 — Talent assigns/unassigns manager
    // ─────────────────────────────────────────────

    public function assignManager(Request $request): JsonResponse
    {
        $data = $request->validate([
            'manager_email' => ['required', 'email'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $talent = $user->talentProfile;

        if (! $talent) {
            return $this->errorResponse('TALENT_PROFILE_NOT_FOUND', 'Profil talent introuvable.', 404);
        }

        $this->managerService->assignManager($talent, $data['manager_email']);

        return $this->successResponse(['message' => 'Manager assigné avec succès.']);
    }

    public function unassignManager(Request $request): JsonResponse
    {
        $data = $request->validate([
            'manager_email' => ['required', 'email'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $talent = $user->talentProfile;

        if (! $talent) {
            return $this->errorResponse('TALENT_PROFILE_NOT_FOUND', 'Profil talent introuvable.', 404);
        }

        $this->managerService->unassignManager($talent, $data['manager_email']);

        return $this->successResponse(['message' => 'Manager désassigné avec succès.']);
    }

    // ─────────────────────────────────────────────
    // Story 7.2 — Manager interface: list talents
    // ─────────────────────────────────────────────

    public function myTalents(Request $request): JsonResponse
    {
        /** @var \App\Models\User $manager */
        $manager = $request->user();

        $talents = $this->managerService->getMyTalents($manager);

        return $this->successResponse(['talents' => $talents]);
    }

    public function talentStats(Request $request, TalentProfile $talent): JsonResponse
    {
        /** @var \App\Models\User $manager */
        $manager = $request->user();

        $stats = $this->managerService->getTalentStats($talent, $manager);

        return $this->successResponse($stats);
    }

    public function talentBookings(Request $request, TalentProfile $talent): JsonResponse
    {
        /** @var \App\Models\User $manager */
        $manager = $request->user();

        $bookings = $this->managerService->getTalentBookings($talent, $manager);

        $bookings->through(fn ($booking) => new BookingRequestResource($booking));

        return $this->paginatedResponse($bookings);
    }

    // ─────────────────────────────────────────────
    // Story 7.3 — Overload settings
    // ─────────────────────────────────────────────

    public function updateOverloadSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'overload_threshold' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $talent = $user->talentProfile;

        if (! $talent) {
            return $this->errorResponse('TALENT_PROFILE_NOT_FOUND', 'Profil talent introuvable.', 404);
        }

        $this->managerService->updateOverloadSettings($talent, $data['overload_threshold']);

        return $this->successResponse([
            'message' => 'Seuil de surcharge mis à jour.',
            'overload_threshold' => $talent->overload_threshold,
        ]);
    }

    // ─────────────────────────────────────────────
    // Story 7.4 — Calendar management by manager
    // ─────────────────────────────────────────────

    public function storeCalendarSlot(Request $request, TalentProfile $talent): JsonResponse
    {
        $data = $request->validate([
            'date'   => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'status' => ['required', 'string', 'in:available,blocked,rest'],
        ]);

        /** @var \App\Models\User $manager */
        $manager = $request->user();

        $slot = $this->managerService->createCalendarSlot($talent, $manager, $data);

        return $this->successResponse(['slot' => $slot], 201);
    }

    public function updateCalendarSlot(Request $request, TalentProfile $talent, CalendarSlot $slot): JsonResponse
    {
        $data = $request->validate([
            'date'   => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:today'],
            'status' => ['sometimes', 'string', 'in:available,blocked,rest'],
        ]);

        /** @var \App\Models\User $manager */
        $manager = $request->user();

        $updated = $this->managerService->updateCalendarSlot($talent, $manager, $slot, $data);

        return $this->successResponse(['slot' => $updated]);
    }

    public function destroyCalendarSlot(Request $request, TalentProfile $talent, CalendarSlot $slot): JsonResponse
    {
        /** @var \App\Models\User $manager */
        $manager = $request->user();

        $this->managerService->deleteCalendarSlot($talent, $manager, $slot);

        return $this->successResponse(['message' => 'Créneau supprimé.']);
    }

    // ─────────────────────────────────────────────
    // Story 7.5 — Booking validation by manager
    // ─────────────────────────────────────────────

    public function acceptBooking(Request $request, TalentProfile $talent, BookingRequest $booking): JsonResponse
    {
        /** @var \App\Models\User $manager */
        $manager = $request->user();

        $this->managerService->acceptBooking($talent, $manager, $booking);

        return $this->successResponse(['message' => 'Réservation acceptée.']);
    }

    public function rejectBooking(Request $request, TalentProfile $talent, BookingRequest $booking): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        /** @var \App\Models\User $manager */
        $manager = $request->user();

        $this->managerService->rejectBooking($talent, $manager, $booking, $data['reason']);

        return $this->successResponse(['message' => 'Réservation refusée.']);
    }

    // ─────────────────────────────────────────────
    // Story 7.6 — Messages manager au nom du talent
    // ─────────────────────────────────────────────

    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        /** @var \App\Models\User $manager */
        $manager = $request->user();

        $message = $this->managerService->sendMessageAsTalent($manager, $conversation, $data['body']);

        return $this->successResponse(['message' => $message], 201);
    }
}
