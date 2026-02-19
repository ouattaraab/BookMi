# Story 7.2 — Interface unifiée manager multi-talents

## Status: done

## Story

**As a** manager,
**I want** a unified interface listing all my assigned talents with their stats and bookings,
**So that** I can oversee multiple talents from a single view.

## Acceptance Criteria

1. **AC1** — `GET /api/v1/manager/talents` returns the list of all talents the manager manages.
2. **AC2** — `GET /api/v1/manager/talents/{talent}` returns a talent's stats dashboard.
3. **AC3** — `GET /api/v1/manager/talents/{talent}/bookings` returns the talent's paginated bookings.
4. **AC4** — Routes are protected by the `manager` middleware — non-managers get 403.
5. **AC5** — Returns 403 if the manager is not assigned to the requested talent.

## Implementation Notes

### Routes

```
GET /api/v1/manager/talents                          → ManagerController::myTalents
GET /api/v1/manager/talents/{talent}                 → ManagerController::talentStats
GET /api/v1/manager/talents/{talent}/bookings        → ManagerController::talentBookings
```

All routes under `middleware('manager')->prefix('manager')` group.

### Service

- `ManagerService::getMyTalents(User $manager)` — loads managed talents with user and category
- `ManagerService::getTalentStats(TalentProfile $talent, User $manager)` — pending/confirmed bookings, month revenue, overload status
- `ManagerService::getTalentBookings(TalentProfile $talent, User $manager)` — paginated bookings

### Stats Response

```json
{
  "talent_profile_id": 1,
  "stage_name": "DJ Kerozen",
  "talent_level": "confirme",
  "average_rating": 4.2,
  "total_bookings": 12,
  "pending_bookings": 2,
  "confirmed_bookings": 3,
  "month_revenue_xof": 150000,
  "overload_threshold": 10,
  "is_overloaded": false
}
```

### Tests

- `tests/Feature/Api/V1/ManagerAssignTest.php` — tests for myTalents, talentStats, access control
