# Story 7.6 — Messages manager au nom du talent

## Status: done

## Story

**As a** manager,
**I want** to send messages in a talent's conversation on their behalf,
**So that** I can handle client communications while the talent performs.

## Acceptance Criteria

1. **AC1** — `POST /api/v1/manager/conversations/{conversation}/messages` sends a message as the talent.
2. **AC2** — The message is tagged with `sent_by_manager_id` for traceability.
3. **AC3** — Returns 403 if the manager is not assigned to the talent in the conversation.
4. **AC4** — The message appears as sent by the talent (sender_id = talent.user_id).

## Implementation Notes

### Migration

- `2026_02_19_170200_add_sent_by_manager_id_to_messages_table.php`
  - `sent_by_manager_id` (FK→users, nullable, nullOnDelete)

### Route

```
POST /api/v1/manager/conversations/{conversation}/messages → ManagerController::sendMessage
```

### Service

- `ManagerService::sendMessageAsTalent(manager, conversation, body)`
  - Finds talent from conversation
  - Verifies manager assignment
  - Creates message with `sender_id = talent.user_id` and `sent_by_manager_id = manager.id`
  - Wraps in DB transaction, touches conversation
