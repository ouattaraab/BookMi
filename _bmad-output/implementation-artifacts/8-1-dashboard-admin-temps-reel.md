# Story 8.1 — Dashboard admin temps réel

## Status: done

## Story

**As an** admin CEO,
**I want** a real-time dashboard with platform statistics,
**So that** I can monitor the platform at a glance.

## Acceptance Criteria

1. **AC1** — `GET /admin/dashboard` returns overall stats for authenticated admins.
2. **AC2** — Returns: user counts (total, this week, this month), booking counts by status, revenue (total, commission, this month), talent counts, dispute rate.
3. **AC3** — Non-admins receive 403; unauthenticated users receive 401.
4. **AC4** — Dispute rate = disputed / (completed + disputed) × 100.

## Implementation Notes

### Route

```
GET /admin/dashboard → AdminDashboardController::index
```

### Controller

- `app/Http/Controllers/Api/V1/AdminDashboardController.php`
  - Delegates to `AdminService::dashboardStats()`

### Service

- `AdminService::dashboardStats()` — aggregates User, BookingRequest, TalentProfile counts
  - PHP-level aggregation; no MySQL-specific functions

### Tests

- `tests/Feature/Admin/AdminDashboardTest.php` — 4 test cases:
  - admin can view dashboard stats
  - non-admin receives 403
  - unauthenticated receives 401
  - dispute rate calculation is correct
