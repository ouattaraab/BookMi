# Story 7.5 — Validation réservation par le manager

## Status: done

## Story

**As a** manager,
**I want** to accept or reject booking requests on behalf of my assigned talents,
**So that** the talent can focus on their art while I handle the business side.

## Acceptance Criteria

1. **AC1** — `POST /api/v1/manager/talents/{talent}/bookings/{booking}/accept` accepts a pending booking.
2. **AC2** — `POST /api/v1/manager/talents/{talent}/bookings/{booking}/reject` rejects a pending booking (with reason).
3. **AC3** — Returns 403 if the manager is not assigned to the talent.
4. **AC4** — Returns 422 if the booking is not in `pending` status.
5. **AC5** — Rejection sets status to `cancelled` with the provided `reject_reason`.

## Implementation Notes

### Routes

```
POST /api/v1/manager/talents/{talent}/bookings/{booking}/accept  → acceptBooking
POST /api/v1/manager/talents/{talent}/bookings/{booking}/reject  → rejectBooking
```

### Service

- `ManagerService::acceptBooking(talent, manager, booking)` — sets status to `accepted`
- `ManagerService::rejectBooking(talent, manager, booking, reason)` — sets status to `cancelled`, stores `reject_reason`

### Tests

- `tests/Feature/Api/V1/ManagerBookingTest.php` — accept, reject, unauthorized tests
