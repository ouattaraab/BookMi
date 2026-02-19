# Story 7.1 — Assignation manager à un talent

## Status: done

## Story

**As a** talent,
**I want** to assign a manager to my profile by email,
**So that** they can manage my calendar, bookings, and messages on my behalf.

## Acceptance Criteria

1. **AC1** — `POST /api/v1/talent_profiles/me/manager` with `{"manager_email": "..."}` assigns the manager.
2. **AC2** — Returns 404 if the manager email is not found.
3. **AC3** — Returns 422 if the found user does not have the `manager` role.
4. **AC4** — Returns 422 if the manager is already assigned to this talent.
5. **AC5** — `DELETE /api/v1/talent_profiles/me/manager` with `{"manager_email": "..."}` removes the assignment.
6. **AC6** — Returns 403 if the manager is not currently assigned.

## Implementation Notes

### Database

- `2026_02_19_170000_create_talent_manager_table.php`
  - Columns: `id`, `talent_profile_id` (FK), `manager_id` (FK→users), `assigned_at`, timestamps
  - Unique constraint: `(talent_profile_id, manager_id)`

### Models

- `User.php` — added `managedTalents(): BelongsToMany`
- `TalentProfile.php` — added `managers(): BelongsToMany`

### Exception

- `app/Exceptions/ManagerException.php` — `managerNotFound`, `notAManager`, `alreadyAssigned`, `notAssigned`, `noManagerAssigned`, `unauthorized`, `cannotManageOwnConversation`

### Service

- `app/Services/ManagerService.php` — `assignManager()`, `unassignManager()`

### Middleware

- `app/Http/Middleware/EnsureUserIsManager.php` — checks `hasRole('manager', 'api')`
- Registered in `bootstrap/app.php` as `'manager'` alias

### Controller

- `app/Http/Controllers/Api/V1/ManagerController.php` — `assignManager()`, `unassignManager()`

### Routes

```
POST   /api/v1/talent_profiles/me/manager   → assignManager
DELETE /api/v1/talent_profiles/me/manager   → unassignManager
```

### Tests

- `tests/Feature/Api/V1/ManagerAssignTest.php` — 9 test cases covering all AC
