# Story 8.5 — Détection comportements suspects

## Status: done

## Story

**As a** platform,
**I want** to automatically detect suspicious patterns,
**So that** admins can investigate and prevent fraud.

## Acceptance Criteria

1. **AC1** — Command `bookmi:detect-suspicious-activity` runs daily.
2. **AC2** — Detects accounts sharing the same phone prefix (10 chars) → severity: critical.
3. **AC3** — Detects > 3 registrations within the last hour → severity: warning.
4. **AC4** — `--dry-run` logs without creating alerts.
5. **AC5** — Uses the shared `AlertService::create()` with `AlertType::SuspiciousActivity`.

## Implementation Notes

### Command

- `DetectSuspiciousActivity` — `bookmi:detect-suspicious-activity {--dry-run}`
  - `detectDuplicatePhones()` — raw SQL `SUBSTR(phone, 1, 10)` group by + having count > 1
  - `detectMultipleRegistrationsSameDay()` — count recent users in last hour

### Schedule

```php
Schedule::command(DetectSuspiciousActivity::class)->dailyAt('04:00');
```

### Tests

- `tests/Unit/Commands/DetectSuspiciousActivityTest.php` — 3 test cases
