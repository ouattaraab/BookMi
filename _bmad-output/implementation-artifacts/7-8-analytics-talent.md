# Story 7.8 — Analytics talent

## Status: done

## Story

**As a** talent,
**I want** an analytics dashboard showing my booking stats, revenue trends, and rating history,
**So that** I can understand my performance and make better decisions.

## Acceptance Criteria

1. **AC1** — `GET /api/v1/me/analytics` returns stats for the authenticated talent.
2. **AC2** — Returns 404 if the user has no talent profile.
3. **AC3** — Includes bookings by status, monthly revenue (last 12 months), and rating history (last 20 reviews).
4. **AC4** — Works correctly with 0 bookings/reviews.

## Analytics Response

```json
{
  "talent_profile_id": 1,
  "stage_name": "DJ Kerozen",
  "talent_level": "confirme",
  "average_rating": 4.2,
  "total_bookings": 12,
  "pending_bookings": 2,
  "current_month_revenue_xof": 150000,
  "bookings_by_status": {"pending": 2, "completed": 8},
  "monthly_revenue": [
    {"month": "2026-01", "revenue_xof": 50000, "bookings_count": 1}
  ],
  "rating_history": [
    {"month": "2026-01", "rating": 5}
  ]
}
```

## Implementation Notes

### Route

```
GET /api/v1/me/analytics → AnalyticsController::dashboard
```

### Controller

- `app/Http/Controllers/Api/V1/AnalyticsController.php`
  - Uses PHP-level grouping (not MySQL `DATE_FORMAT`) for DB portability
  - Aggregates bookings by month over last 12 months

### Tests

- `tests/Feature/Api/V1/AnalyticsControllerTest.php` — 3 test cases
