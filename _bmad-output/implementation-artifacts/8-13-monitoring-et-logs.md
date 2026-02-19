# Story 8.13 — Monitoring et logs

## Status: done

## Story

**As a** platform operator,
**I want** structured monitoring and error capture,
**So that** production incidents are detected and resolved quickly.

## Acceptance Criteria

1. **AC1** — Sentry captures all unhandled exceptions in Laravel.
2. **AC2** — Structured JSON logs (Monolog) with daily rotation.
3. **AC3** — `activity_logs` table provides complete admin audit trail.
4. **AC4** — Laravel Telescope available in dev environment.

## Implementation Notes

### Already in place

- `config/sentry.php` — Sentry DSN configured via `SENTRY_LARAVEL_DSN` env variable
- `config/logging.php` — Daily rotation channel (`LOG_CHANNEL=daily`)
- `ActivityLog` model + `AuditService` — complete admin audit trail (Story 8.10)
- `bootstrap/app.php` — Global exception handler renders structured JSON for all API/admin errors

### Recommended configuration

```env
SENTRY_LARAVEL_DSN=https://...@sentry.io/...
LOG_CHANNEL=daily
LOG_LEVEL=warning
```

### Notes

- Laravel Telescope: `composer require laravel/telescope --dev` (dev only)
- Laravel Horizon: `composer require laravel/horizon` (prod queue monitoring)
- Both packages are optional infrastructure — not included in core migrations
