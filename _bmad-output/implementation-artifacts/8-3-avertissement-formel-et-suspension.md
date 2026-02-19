# Story 8.3 — Avertissement formel et suspension

## Status: done

## Story

**As an** admin,
**I want** to issue formal warnings and suspend user accounts,
**So that** I can enforce platform rules and protect other users.

## Acceptance Criteria

1. **AC1** — `POST /admin/users/{user}/warnings` creates a formal warning, logged to `activity_logs`.
2. **AC2** — `POST /admin/users/{user}/suspend` suspends the account: tokens revoked, talent profile hidden.
3. **AC3** — `POST /admin/users/{user}/unsuspend` lifts suspension.
4. **AC4** — Cannot suspend an admin account (403).
5. **AC5** — Cannot suspend an already-suspended account (422).

## Implementation Notes

### Migration

- `2026_02_19_180000_create_admin_warnings_table.php` — warnings issued to users
- `2026_02_19_180200_add_suspended_fields_to_users_table.php` — `is_suspended`, `suspended_at`, `suspended_until`, `suspension_reason`

### Models

- `AdminWarning` — `user_id`, `issued_by_id`, `reason`, `details`, `status` (WarningStatus enum)
- `User` — added `is_suspended`, `suspended_at`, `suspended_until`, `suspension_reason` to fillable/casts
- `User::warnings()` — HasMany → AdminWarning

### Enums

- `WarningStatus` — Active, Resolved

### Exceptions

- `AdminException::cannotSuspendAdmin()` — 403
- `AdminException::alreadySuspended()` — 422
- `AdminException::notSuspended()` — 422

### Service

- `AdminService::createWarning()` — creates `AdminWarning`, logs audit
- `AdminService::suspendUser()` — DB transaction: sets `is_suspended=true`, revokes all tokens, hides talent profile
- `AdminService::unsuspendUser()` — lifts suspension, reactivates talent profile

### Tests

- `tests/Feature/Admin/AdminWarningTest.php` — 7 test cases
