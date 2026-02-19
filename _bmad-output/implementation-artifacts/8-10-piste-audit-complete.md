# Story 8.10 — Piste d'audit complète

## Status: done

## Story

**As an** admin,
**I want** a complete audit trail of all admin actions,
**So that** every decision is traceable and compliant.

## Acceptance Criteria

1. **AC1** — `GET /admin/audit` lists all `activity_logs` with filters.
2. **AC2** — Filters: `causer_id`, `action` (substring), `model` (subject_type), `from`/`to` dates.
3. **AC3** — `ActivityLog` is append-only (`UPDATED_AT = null`), never modifiable.
4. **AC4** — All admin actions in `AdminService`, `AlertService`, and controllers log via `AuditService::log()`.

## Implementation Notes

### Existing infrastructure

- `ActivityLog` model — `causer_id`, `subject_type`, `subject_id`, `action`, `metadata`, `ip_address`; `UPDATED_AT = null`
- `AuditService::log()` — creates `ActivityLog` record with current auth user, request IP

### Controller

- `AdminAuditController::index()` — paginated 50/page, filters applied via Eloquent `when()`

### Route (admin.php)

```
GET /admin/audit → AdminAuditController::index
```

### Tests

- `tests/Feature/Admin/AdminAuditTest.php` — 4 test cases
