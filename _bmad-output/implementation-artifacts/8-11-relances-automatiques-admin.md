# Story 8.11 — Relances automatiques admin

## Status: done

## Story

**As a** platform,
**I want** to automatically create alerts for overdue admin tasks,
**So that** nothing falls through the cracks.

## Acceptance Criteria

1. **AC1** — Command `bookmi:send-admin-reminders` checks pending tasks older than `admin.pending_action_reminder_hours` (default 48h).
2. **AC2** — Creates `PendingAction` alerts for: pending identity verifications, unresolved disputes, reported reviews.
3. **AC3** — Skips if a `PendingAction` open alert already exists for the subject.
4. **AC4** — `--dry-run` logs without creating alerts.
5. **AC5** — Runs daily at 07:00.

## Implementation Notes

### Command

- `SendAdminReminders` — `bookmi:send-admin-reminders {--dry-run}`
  - Reads `config('bookmi.admin.pending_action_reminder_hours', 48)`
  - Checks: pending `IdentityVerification` (on `verification_status`), disputed `BookingRequest` (on `updated_at`), reported `Review` (on `reported_at`)
  - Uses `AlertService::openExists()` for deduplication

### Schedule

```php
Schedule::command(SendAdminReminders::class)->dailyAt('07:00');
```

### Tests

- `tests/Unit/Commands/SendAdminRemindersTest.php` — 4 test cases
