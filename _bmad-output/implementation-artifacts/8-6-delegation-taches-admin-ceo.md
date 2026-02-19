# Story 8.6 — Délégation tâches admin CEO

## Status: done

## Story

**As an** admin CEO,
**I want** to create collaborator accounts with specific admin roles,
**So that** I can delegate responsibilities without sharing my credentials.

## Acceptance Criteria

1. **AC1** — `GET /admin/team` lists all admin team members with their roles.
2. **AC2** — `POST /admin/team` creates a collaborator with role: `admin_comptable | admin_controleur | admin_moderateur`.
3. **AC3** — `PUT /admin/team/{user}` updates collaborator role.
4. **AC4** — `DELETE /admin/team/{user}` revokes access (roles cleared, `is_admin=false`, tokens deleted).
5. **AC5** — CEO cannot modify their own role (403).

## Implementation Notes

### Routes (admin.php)

```
GET    /admin/team
POST   /admin/team
PUT    /admin/team/{user}
DELETE /admin/team/{user}
```

### Service

- `AdminService::listAdminTeam()` — users with any admin_* role via `whereHas('roles')`
- `AdminService::createAdminCollaborator()` — creates User + `assignRole()` in DB transaction
- `AdminService::updateCollaboratorRole()` — `syncRoles([newRole])`, logs audit
- `AdminService::revokeCollaboratorAccess()` — clears roles, sets `is_admin=false`, deletes tokens

### Tests

- `tests/Feature/Admin/AdminTeamTest.php` — 7 test cases
