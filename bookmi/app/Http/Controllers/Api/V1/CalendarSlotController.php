<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\StoreCalendarSlotRequest;
use App\Http\Requests\Api\UpdateCalendarSlotRequest;
use App\Http\Resources\CalendarSlotResource;
use App\Exceptions\BookmiException;
use App\Models\CalendarSlot;
use App\Models\TalentProfile;
use App\Services\CalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarSlotController extends BaseController
{
    public function __construct(
        private readonly CalendarService $calendarService,
    ) {
    }

    /**
     * GET /api/v1/talents/{talent}/calendar?month=2026-03
     * Public: any user can consult a talent's calendar.
     */
    public function index(Request $request, TalentProfile $talent): JsonResponse
    {
        $month = $request->query('month', now()->format('Y-m'));

        $calendar = $this->calendarService->getMonthCalendar($talent, (string) $month);

        return $this->successResponse($calendar->values());
    }

    /**
     * POST /api/v1/calendar_slots
     * Talent only: create a new slot.
     */
    public function store(StoreCalendarSlotRequest $request): JsonResponse
    {
        $user   = $request->user();
        $talent = TalentProfile::where('user_id', $user->id)->first();

        if (! $talent) {
            throw new BookmiException(
                'TALENT_PROFILE_NOT_FOUND',
                'Vous devez créer un profil talent avant de gérer votre calendrier.',
                404,
            );
        }

        $slot = $this->calendarService->createSlot($talent, $request->validated());

        return $this->successResponse(new CalendarSlotResource($slot), 201);
    }

    /**
     * PUT /api/v1/calendar_slots/{slot}
     * Talent only: update a slot (owner check via Policy).
     */
    public function update(UpdateCalendarSlotRequest $request, CalendarSlot $slot): JsonResponse
    {
        $this->authorize('update', $slot);

        $slot = $this->calendarService->updateSlot($slot, $request->validated());

        return $this->successResponse(new CalendarSlotResource($slot));
    }

    /**
     * DELETE /api/v1/calendar_slots/{slot}
     * Talent only: delete a slot (owner check via Policy).
     */
    public function destroy(CalendarSlot $slot): JsonResponse
    {
        $this->authorize('delete', $slot);

        $this->calendarService->deleteSlot($slot);

        return response()->json(null, 204);
    }
}
