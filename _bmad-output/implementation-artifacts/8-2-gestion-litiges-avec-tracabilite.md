# Story 8.2 — Gestion des litiges avec traçabilité

## Status: done

## Story

**As an** admin,
**I want** to manage disputes with full traceability,
**So that** I can resolve conflicts fairly and document every action.

## Acceptance Criteria

1. **AC1** — `GET /admin/disputes` lists all disputed bookings.
2. **AC2** — `GET /admin/disputes/{booking}` shows full dispute detail (parties, escrow, tracking events).
3. **AC3** — `POST /admin/disputes/{booking}/notes` adds an internal note (logged to `activity_logs`).
4. **AC4** — `POST /admin/disputes/{booking}/resolve` resolves the dispute with `refund_client | pay_talent | compromise`.
5. **AC5** — Resolution is logged in `activity_logs` with resolution type and note.

## Implementation Notes

### Routes (admin.php)

```
GET  /admin/disputes
GET  /admin/disputes/{booking}
GET  /admin/disputes/{booking}/messages  (existing, Story 5.6)
POST /admin/disputes/{booking}/notes
POST /admin/disputes/{booking}/resolve
```

### Controller

- `AdminDisputeController` extended with: `index`, `show`, `addNote`, `resolve`
- `resolve()` validates `resolution` ∈ {refund_client, pay_talent, compromise}
- Delegates to `AdminService::resolveDispute()`

### Service

- `AdminService::resolveDispute()` — DB transaction: update booking status, log audit

### Tests

- `tests/Feature/Admin/AdminDisputeResolveTest.php` — 5 test cases
