# Story 8.8 — Suivi check-ins contrôleur opérationnel

## Status: done

## Story

**As an** admin contrôleur,
**I want** to see today's services with their real-time tracking status,
**So that** I can detect missing check-ins and act immediately.

## Acceptance Criteria

1. **AC1** — `GET /admin/operations` returns today's Confirmed/Completed bookings with tracking events.
2. **AC2** — Each booking has a `tracking_status`: upcoming, in_progress, completed, late.
3. **AC3** — Results sorted: late → in_progress → upcoming → completed.
4. **AC4** — `late` status when no check-in 30+ minutes after event_date.

## Implementation Notes

### Route (admin.php)

```
GET /admin/operations → AdminOperationsController::index
```

### Controller

- `AdminOperationsController::index()`
  - Filters `BookingRequest` by today's date and Confirmed/Completed status
  - Loads tracking events, derives `tracking_status` via PHP logic
  - Sorts by tracking status priority

### Tests

No dedicated feature test (covered by the dashboard integration).
