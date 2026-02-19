# Story 8.12 — KPIs plateforme

## Status: done

## Story

**As an** admin CEO,
**I want** a KPI dashboard with platform performance metrics and trends,
**So that** I can make data-driven decisions.

## Acceptance Criteria

1. **AC1** — `GET /admin/kpis` returns platform KPIs for authenticated admins.
2. **AC2** — KPIs: user registrations (total, this month, prev month, trend %), booking conversion rate (Paid+/total), dispute rate, revenue (total, this month, prev month, trend %), average rating, talent count.
3. **AC3** — Monthly revenue trend for the last 12 months.
4. **AC4** — Trends are percentage change vs previous month (null if no prev month data).

## Implementation Notes

### Route (admin.php)

```
GET /admin/kpis → AdminKpiController::index
```

### Controller

- `AdminKpiController::index()`
  - PHP-level grouping for monthly revenue trend (no `DATE_FORMAT`)
  - Returns trend as `round(delta/prev * 100, 1)` or `null` when prev = 0

### Tests

No dedicated feature test (covered as part of dashboard integration).
