# Story 8.7 — Export données financières comptable

## Status: done

## Story

**As an** admin comptable,
**I want** to access financial data and export CSV reports,
**So that** I can produce accounting documents.

## Acceptance Criteria

1. **AC1** — `GET /api/v1/admin/reports/financial` (existing Story 4.9) streams a CSV with transactions, payouts, and refunds.
2. **AC2** — Date filters (`start_date`, `end_date`) are required.
3. **AC3** — CSV includes: date, type, amount, talent, client, status, reference.
4. **AC4** — Restricted to admin users (existing `admin` middleware).

## Implementation Notes

Already implemented in Story 4.9 (`AdminReportController::financial`). No new code needed.
This story documents the existing functionality for the comptable role context.

### Route (existing)

```
GET /api/v1/admin/reports/financial → AdminReportController::financial
```
