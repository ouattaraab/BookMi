# Story 5.1: Messagerie interne temps réel (backend)

Status: done

## Story

As a client ou talent,
I want envoyer et recevoir des messages en temps réel dans l'app,
So that je puisse communiquer avec mon interlocuteur sans quitter BookMi.

**Functional Requirements:** FR-MSG-1, FR-MSG-2
**Non-Functional Requirements:** NFR-RT-1 (ShouldBroadcast, PrivateChannel), NFR-SEC-1 (participant guard)

## Acceptance Criteria (BDD)

**AC1 — Lister les conversations**
**Given** un utilisateur authentifié
**When** il envoie `GET /api/v1/conversations`
**Then** seules ses conversations (en tant que client ou talent) sont retournées
**And** chaque conversation inclut le `latest_message`

**AC2 — Démarrer une conversation**
**Given** un client authentifié
**When** il envoie `POST /api/v1/conversations` avec `talent_profile_id` et `message`
**Then** la réponse 201 contient `conversation` + `message`
**And** un événement `MessageSent` est broadcasté sur `private-conversation.{id}`
**And** si la conversation existe déjà, elle est réutilisée (pas de doublon)

**AC3 — Seuls les clients peuvent initier**
**Given** un talent authentifié
**When** il tente `POST /api/v1/conversations`
**Then** la réponse est 403

**AC4 — Historique paginé**
**Given** un participant à une conversation
**When** il envoie `GET /api/v1/conversations/{id}/messages`
**Then** les messages sont retournés paginés par 30, du plus récent au plus ancien
**And** un non-participant reçoit 403

**AC5 — Envoyer un message**
**Given** un participant à une conversation existante
**When** il envoie `POST /api/v1/conversations/{id}/messages` avec `content`
**Then** le message est créé et broadcasté
**And** `last_message_at` de la conversation est mis à jour

**AC6 — Marquer comme lu**
**Given** un participant à une conversation
**When** il envoie `POST /api/v1/conversations/{id}/read`
**Then** les messages de l'autre participant non lus sont marqués `read_at = now()`
**And** ses propres messages ne sont pas affectés

## Implementation Notes

### Backend (Laravel)

**Fichiers de configuration :**
- `config/broadcasting.php` — `log` driver par défaut, `reverb` configuré via env pour la prod

**Nouvelles migrations :**
- `2026_02_19_100000_create_conversations_table.php` — `client_id`, `talent_profile_id`, `booking_request_id` (nullable), `last_message_at`, unique `(client_id, talent_profile_id)`
- `2026_02_19_100100_create_messages_table.php` — `conversation_id`, `sender_id`, `content`, `type` (text/image/audio), `read_at`, `is_flagged`, `is_auto_reply`, index `(conversation_id, created_at)`

**Nouveaux modèles :**
- `app/Models/Conversation.php` — `isParticipant(User)`, `latestMessage()` (hasOne), `messages()` (hasMany)
- `app/Models/Message.php` — `isRead()`, cast `type → MessageType`, cast `read_at → datetime`

**Nouvel enum :**
- `app/Enums/MessageType.php` — `Text | Image | Audio`

**Nouvel événement :**
- `app/Events/MessageSent.php` — `ShouldBroadcast`, `PrivateChannel('conversation.{id}')`, `broadcastAs()` = `'message.sent'`, `broadcastWith()` inclut sender_id, content, type

**Nouveau service :**
- `app/Services/MessagingService.php`
  - `listConversations(User)` — eager load `client`, `talentProfile.user`, `latestMessage`
  - `getOrCreateConversation(client, talentProfileId, bookingRequestId)` — `firstOrCreate` avec unique constraint DB
  - `getMessages(Conversation, perPage=30)` — paginated DESC
  - `sendMessage(conversation, sender, content, type, isAutoReply)` — crée message, met à jour `last_message_at`, broadcast
  - `markAsRead(Conversation, reader)` — bulk update `read_at` des messages de l'autre

**Nouveau controller :**
- `app/Http/Controllers/Api/V1/MessageController.php`
  - `index` — liste conversations de l'utilisateur
  - `store` — démarre conversation (client only) + premier message, wrappé `DB::transaction`
  - `messages` — historique paginé avec guard participant
  - `send` — envoie message dans conversation existante
  - `read` — marque messages comme lus

**Nouvelles requêtes :**
- `app/Http/Requests/Api/StartConversationRequest.php`
- `app/Http/Requests/Api/SendMessageRequest.php`

**Nouvelles resources :**
- `app/Http/Resources/ConversationResource.php`
- `app/Http/Resources/MessageResource.php`

**Factories :**
- `ConversationFactory.php`, `MessageFactory.php` (avec états `read`, `flagged`, `autoReply`)

**Routes ajoutées :**
```
GET  /api/v1/conversations                          → index
POST /api/v1/conversations                          → store
GET  /api/v1/conversations/{conversation}/messages  → messages
POST /api/v1/conversations/{conversation}/messages  → send
POST /api/v1/conversations/{conversation}/read      → read
```

### Code review fixes

- **H1 (transaction manquante) :** `store()` wrappé dans `DB::transaction` — si `sendMessage()` échoue après `getOrCreateConversation()`, la conversation est rollbackée.
- **M1 (`toOthers()` sans socket ID) :** `broadcast()->toOthers()` retiré ; le client filtre les messages par `sender_id` pour éviter les duplicatas d'affichage.

### Tests

**Backend :** `tests/Feature/Api/V1/MessageControllerTest.php` — 14 tests (31 assertions)
- list conversations (propres uniquement, 401)
- talent voit ses conversations
- client démarre conversation + 1er message + Event::assertDispatched
- conversation réutilisée (firstOrCreate)
- talent forbidden à initier
- talent_profile_id invalide → 422
- historique paginé + meta, 403 non-participant
- envoi message par talent + event dispatché avec conversation_id correct
- content vide → 422
- non-participant forbidden
- markAsRead (3 messages marqués, 0 propres messages)
