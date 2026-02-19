# Story 7.4 — Gestion calendrier par le manager

## Status: done

## Story

**As a** manager,
**I want** to create, update, and delete calendar slots for my assigned talents,
**So that** I can manage their availability without them needing to do it themselves.

## Acceptance Criteria

1. **AC1** — `POST /api/v1/manager/talents/{talent}/calendar_slots` creates a slot.
2. **AC2** — `PUT /api/v1/manager/talents/{talent}/calendar_slots/{slot}` updates a slot.
3. **AC3** — `DELETE /api/v1/manager/talents/{talent}/calendar_slots/{slot}` deletes a slot.
4. **AC4** — Returns 403 if the manager is not assigned to the talent.
5. **AC5** — Returns 403 if the slot does not belong to the talent.

## Implementation Notes

### Routes

```
POST   /api/v1/manager/talents/{talent}/calendar_slots           → storeCalendarSlot
PUT    /api/v1/manager/talents/{talent}/calendar_slots/{slot}    → updateCalendarSlot
DELETE /api/v1/manager/talents/{talent}/calendar_slots/{slot}    → destroyCalendarSlot
```

### Service

- `ManagerService::createCalendarSlot(talent, manager, data)` — verifies assignment, creates slot with `{date, status}`
- `ManagerService::updateCalendarSlot(talent, manager, slot, data)` — verifies assignment and slot ownership, updates
- `ManagerService::deleteCalendarSlot(talent, manager, slot)` — verifies assignment and slot ownership, deletes

### CalendarSlot Schema

- `date` (date Y-m-d) — required on create
- `status` (available|blocked|rest) — required on create

### Tests

- `tests/Feature/Api/V1/ManagerBookingTest.php` — create and delete slot tests
