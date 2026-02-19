# Story 5.4: Notifications push FCM (backend)

Status: done

## Story

As a user (client ou talent),
I want recevoir des notifications push sur mon téléphone lors de nouveaux messages ou événements,
So that je reste informé sans avoir l'app ouverte.

**Functional Requirements:** FR-PUSH-1, FR-PUSH-2
**Non-Functional Requirements:** NFR-QUEUE-1 (ShouldQueue, retry 3×), NFR-FAIL-SAFE (FCM non configuré = log uniquement)

## Acceptance Criteria (BDD)

**AC1 — Enregistrement du FCM token**
**Given** un utilisateur authentifié
**When** il envoie `PUT /api/v1/me/fcm_token` avec son token FCM
**Then** le token est persisté sur l'utilisateur
**And** la réponse 200 contient le token confirmé

**AC2 — Notification push lors d'un nouveau message**
**Given** un participant à une conversation avec un FCM token
**When** l'autre participant envoie un message
**Then** un job `SendPushNotification` est dispatché dans la queue pour le destinataire
**And** le job crée une entrée `push_notifications` et tente la livraison FCM

**AC3 — Liste des notifications**
**Given** un utilisateur authentifié
**When** il envoie `GET /api/v1/notifications`
**Then** ses notifications sont retournées paginées par 20, du plus récent au plus ancien
**And** les notifications des autres utilisateurs ne sont pas incluses

**AC4 — Marquer comme lue**
**Given** une notification appartenant à l'utilisateur
**When** il envoie `POST /api/v1/notifications/{id}/read`
**Then** `read_at` est défini, la réponse 200 inclut `is_read: true`
**And** un non-propriétaire reçoit 403

**AC5 — Marquer toutes comme lues**
**Given** des notifications non lues
**When** il envoie `POST /api/v1/notifications/read-all`
**Then** toutes les non-lues sont marquées, la réponse inclut le `marked_read` count

**AC6 — FCM non configuré = graceful degradation**
**Given** aucune clé `FCM_SERVER_KEY` définie
**When** le job tente la livraison
**Then** un log info est écrit, pas d'exception

## Implementation Notes

### Backend (Laravel)

**Nouvelles migrations :**
- `2026_02_19_120000_add_fcm_token_to_users_table.php` — `fcm_token STRING NULLABLE`
- `2026_02_19_120100_create_push_notifications_table.php` — `user_id`, `title`, `body`, `data JSON`, `read_at`, `sent_at`, index `(user_id, read_at)`

**Nouveau modèle :**
- `app/Models/PushNotification.php` — `isRead()`, cast `data → array`, `read_at/sent_at → datetime`

**Nouveau service :**
- `app/Services/FcmService.php`
  - `send(token, title, body, data): bool`
  - Utilise `Http::withToken()` vers `fcm.googleapis.com/v1/projects/{projectId}/messages:send`
  - Config : `services.fcm.project_id` + `services.fcm.server_key`
  - Graceful degradation si non configuré (log + return false)

**Nouveau job :**
- `app/Jobs/SendPushNotification.php` — `ShouldQueue`, `tries = 3`, `backoff = 10`
  - Crée `PushNotification` en DB d'abord
  - Tente `FcmService::send()` si `user->fcm_token` présent
  - Met à jour `sent_at` en cas de succès

**Nouveau controller :**
- `app/Http/Controllers/Api/V1/NotificationController.php`
  - `index` — liste paginée (20) propre à l'utilisateur
  - `markRead` — 403 si non-propriétaire
  - `markAllRead` — bulk update
  - `updateFcmToken` — PUT /me/fcm_token (validation min:10, max:512)

**Intégration `MessagingService::notifyRecipients()` (private) :**
- Appelé après broadcast dans `sendMessage()`
- Dispatche `SendPushNotification::dispatch(userId, title, body, data)` pour chaque participant autre que le sender

**Routes ajoutées :**
```
GET  /api/v1/notifications                         → index
POST /api/v1/notifications/read-all                → markAllRead
POST /api/v1/notifications/{notification}/read     → markRead
PUT  /api/v1/me/fcm_token                          → updateFcmToken
```

### Tests

**Feature :** `tests/Feature/Api/V1/NotificationControllerTest.php` — 9 tests
- Enregistrement FCM token (200), validation trop court (422), 401 sans auth
- Liste notifications propres uniquement, 401 sans auth
- Marquer 1 notification comme lue, 403 non-propriétaire
- markAllRead : 4 marquées (1 déjà lue ignorée)
- `Queue::fake()` : `SendPushNotification` dispatché quand message envoyé
