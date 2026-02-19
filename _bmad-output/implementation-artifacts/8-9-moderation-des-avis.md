# Story 8.9 — Modération des avis

## Status: done

## Story

**As an** admin modérateur,
**I want** to moderate reported reviews,
**So that** the platform's content stays appropriate.

## Acceptance Criteria

1. **AC1** — `POST /api/v1/reviews/{review}/report` lets any authenticated user report a review.
2. **AC2** — `GET /admin/reviews/reported` lists reported reviews with reviewer/reviewee info.
3. **AC3** — `POST /admin/reviews/{review}/approve` clears the report flag (keep review).
4. **AC4** — `DELETE /admin/reviews/{review}` deletes the review (requires reason, logged to audit).
5. **AC5** — `PATCH /admin/reviews/{review}` masks inappropriate content, clears report flag.

## Implementation Notes

### Migration

- `2026_02_19_180300_add_report_fields_to_reviews_table.php` — `is_reported`, `report_reason`, `reported_at`

### Model

- `Review` — added `is_reported`, `report_reason`, `reported_at` to fillable/casts

### Controller

- `AdminReviewModerationController` — `report`, `reported`, `approve`, `destroy`, `update`
- All admin actions logged via `AuditService`

### Tests

- `tests/Feature/Admin/AdminReviewModerationTest.php` — 6 test cases
