# Story 8.4 — Signalement automatique talents note basse

## Status: done

## Story

**As a** platform,
**I want** to automatically flag talents with low average ratings,
**So that** admins can take corrective action.

## Acceptance Criteria

1. **AC1** — Command `bookmi:flag-low-rating-talents` flags talents with `average_rating < low_rating_threshold` (default 3.0).
2. **AC2** — Skips talents with 0 bookings or 0 rating (new talents).
3. **AC3** — Skips if an open `LowRating` alert already exists for the talent.
4. **AC4** — `--dry-run` logs without creating alerts.
5. **AC5** — Command runs daily at 03:00.

## Implementation Notes

### Migration

- `2026_02_19_180100_create_admin_alerts_table.php` — `type`, `severity`, `subject_type/id`, `title`, `description`, `metadata`, `status`, `resolved_at`, `resolved_by_id`

### Models

- `AdminAlert` — `AlertType` enum (low_rating, suspicious_activity, pending_action), `AlertSeverity` enum (info, warning, critical)

### Service

- `AlertService::create()` — creates `AdminAlert`
- `AlertService::openExists(AlertType, Model)` — deduplication check

### Command

- `FlagLowRatingTalents` — chunks talents 100 at a time, skips duplicates, creates `AlertType::LowRating` alerts

### Schedule

```php
Schedule::command(FlagLowRatingTalents::class)->dailyAt('03:00');
```

### Tests

- `tests/Unit/Commands/FlagLowRatingTalentsTest.php` — 5 test cases
