# Story 5.5: Rappels automatiques J-7 et J-2 (backend)

Status: done

## Story

As a client,
I want recevoir un rappel push 7 jours et 2 jours avant ma prestation,
So that je n'oublie pas la date et puisse me préparer.

**Functional Requirements:** FR-REMINDER-1
**Non-Functional Requirements:** NFR-SCHED-1 (Laravel Scheduler dailyAt 08:00), NFR-IDEMPOTENT (1 notif / fenêtre)

## Acceptance Criteria (BDD)

**AC1 — J-7 dispatché**
**Given** une réservation `paid` ou `confirmed` dont `event_date = today + 7 jours`
**When** la commande `bookmi:send-reminders` est exécutée
**Then** un job `SendPushNotification` est dispatché pour le client de cette réservation
**And** le payload `data.booking_id` est correct

**AC2 — J-2 dispatché**
**Given** une réservation `paid` ou `confirmed` dont `event_date = today + 2 jours`
**When** la commande s'exécute
**Then** un job `SendPushNotification` est dispatché

**AC3 — Pas de notification hors fenêtre**
**Given** une réservation à J-1 (demain) ou J-3
**When** la commande s'exécute
**Then** aucun job n'est dispatché

**AC4 — Réservations annulées ignorées**
**Given** une réservation `cancelled` à J-7
**When** la commande s'exécute
**Then** aucun job n'est dispatché

**AC5 — Mode --dry-run**
**Given** des réservations éligibles
**When** la commande est lancée avec `--dry-run`
**Then** aucun job n'est dispatché mais les bookings sont logués

## Implementation Notes

### Backend (Laravel)

**Nouvelle commande :**
- `app/Console/Commands/SendReminderNotifications.php`
  - Signature : `bookmi:send-reminders {--dry-run}`
  - Pour chaque fenêtre (J-7, J-2) : filtre `event_date = today + N`, status `paid|confirmed`, `chunkById(100)`
  - Dispatche `SendPushNotification::dispatch(userId, title, body, data)`
  - `--dry-run` : affiche seulement, ne dispatche pas

**Scheduler (routes/console.php) :**
```php
Schedule::command(SendReminderNotifications::class)->dailyAt('08:00');
```

### Tests

**Feature :** `tests/Feature/Commands/SendReminderNotificationsTest.php` — 6 tests
- Dispatch J-7, dispatch J-2
- Pas de dispatch pour J-1 (hors fenêtre)
- Pas de dispatch pour `cancelled`
- `--dry-run` ne dispatche rien
- Payload `booking_id` correct
