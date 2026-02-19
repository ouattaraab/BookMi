# Story 7.3 — Alertes surcharge talent

## Status: done

## Story

**As a** manager,
**I want** to be notified when a managed talent has too many active bookings,
**So that** I can proactively manage their capacity.

## Acceptance Criteria

1. **AC1** — Talent can set their overload threshold: `PUT /api/v1/talent_profiles/me/overload_settings` with `{"overload_threshold": 10}`.
2. **AC2** — The `bookmi:detect-talent-overload` command detects talents whose active (confirmed+paid) bookings ≥ threshold.
3. **AC3** — On detection, sends a push notification to each assigned manager.
4. **AC4** — Notifications are sent at most once per day per talent (guarded by `overload_notified_at`).
5. **AC5** — Command runs daily at 09:00 via the scheduler.

## Implementation Notes

### Migration

- `2026_02_19_170100_add_overload_settings_to_talent_profiles_table.php`
  - `overload_threshold` (tinyint, default 10)
  - `overload_notified_at` (timestamp, nullable)

### TalentProfile Model

- Added `overload_threshold` and `overload_notified_at` to `$fillable` and `casts()`

### Route

```
PUT /api/v1/talent_profiles/me/overload_settings → ManagerController::updateOverloadSettings
```

### Command

- `app/Console/Commands/DetectTalentOverload.php`
  - Chunks talents with managers
  - Counts active bookings per talent
  - Sends `SendPushNotification` job to each manager
  - Updates `overload_notified_at` on notification
  - `--dry-run` flag for testing

### Schedule

```php
Schedule::command(DetectTalentOverload::class)->dailyAt('09:00');
```
