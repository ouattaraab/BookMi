# Story 7.7 — Niveaux automatiques talent

## Status: done

## Story

**As a** platform,
**I want** talent levels to be automatically recalculated daily based on completed bookings and average rating,
**So that** talent badges always reflect their actual activity.

## Acceptance Criteria

1. **AC1** — Command `bookmi:recalculate-talent-levels` recalculates levels for all talents.
2. **AC2** — Level thresholds follow `config/bookmi.php → talent.levels`.
3. **AC3** — Level is assigned based on the highest tier the talent qualifies for (both bookings AND rating threshold must be met).
4. **AC4** — Command runs daily at 02:00 via the scheduler.
5. **AC5** — `--dry-run` flag logs changes without saving.

## Level Thresholds

| Level     | Min bookings | Min rating |
|-----------|-------------|------------|
| nouveau   | 0           | 0.0        |
| confirmé  | 6           | 3.5        |
| populaire | 21          | 4.0        |
| élite     | 51          | 4.5        |

## Implementation Notes

### Command

- `app/Console/Commands/RecalculateTalentLevels.php`
  - Chunks all `TalentProfile` records (100 at a time)
  - Iterates levels from highest to lowest, assigns first match
  - Only updates if level changed
  - Logs every change

### Schedule

```php
Schedule::command(RecalculateTalentLevels::class)->dailyAt('02:00');
```

### Tests

- `tests/Unit/Commands/RecalculateTalentLevelsTest.php` — 5 test cases: upgrade to confirmé/populaire/élite, no upgrade on low rating, dry-run
