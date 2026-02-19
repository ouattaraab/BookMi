# Story 5.7: Écran messagerie Flutter (mobile)

Status: done

## Story

As a client ou talent,
I want accéder à mes conversations et envoyer des messages depuis l'app mobile,
So that je puisse communiquer sans quitter BookMi.

**Functional Requirements:** FR-MSG-MOBILE-1, FR-MSG-MOBILE-2
**Non-Functional Requirements:** UX-BUBBLE-1 (is_flagged badge orange), UX-BUBBLE-2 (is_auto_reply badge bot)

## Acceptance Criteria (BDD)

**AC1 — Liste des conversations**
**Given** un utilisateur connecté
**When** il navigue vers `ConversationListPage`
**Then** ses conversations s'affichent avec le nom de l'interlocuteur et le dernier message
**And** une icône d'avertissement est affichée si le dernier message est flaggé

**AC2 — Chat page**
**Given** un utilisateur qui ouvre une conversation
**When** `ChatPage` se charge
**Then** les messages s'affichent en bulles
**And** les messages flaggés ont un fond orange + label "Coordonnées détectées"
**And** les messages auto-reply ont un badge "Réponse automatique" + icône robot

**AC3 — Envoi de message**
**Given** un utilisateur dans une conversation
**When** il tape un message et appuie sur Envoyer
**Then** le message apparaît immédiatement dans la liste
**And** la liste défile automatiquement vers le bas

**AC4 — États BLoC**
- `MessagingInitial` → pas encore chargé
- `MessagingLoading` → spinner
- `ConversationsLoaded` → liste
- `MessagesLoaded` → bulles de chat
- `MessageSending` → bouton désactivé, spinner
- `MessagingError` → message d'erreur

## Implementation Notes

### Flutter — Nouveaux fichiers

**Data layer :**
- `features/messaging/data/models/message_model.dart` — `MessageModel` avec `isFlagged`, `isAutoReply`, `isRead`
- `features/messaging/data/models/conversation_model.dart` — `ConversationModel` avec `latestMessage`
- `features/messaging/data/repositories/messaging_repository.dart` — `MessagingRepository`
  - `getConversations()` → `GET /conversations`
  - `startConversation(talentProfileId, message)` → `POST /conversations`
  - `getMessages(conversationId)` → `GET /conversations/{id}/messages`
  - `sendMessage(conversationId, content)` → `POST /conversations/{id}/messages`
  - `markAsRead(conversationId)` → `POST /conversations/{id}/read`

**BLoC layer :**
- `features/messaging/bloc/messaging_state.dart` — sealed states : `Initial`, `Loading`, `ConversationsLoaded`, `MessagesLoaded`, `MessageSending`, `Error`
- `features/messaging/bloc/messaging_cubit.dart` — `MessagingCubit`
  - `loadConversations()` — liste des conversations
  - `loadMessages(conversationId)` — messages inversés (oldest-first) + `markAsRead` fire-and-forget
  - `sendMessage(conversationId, content)` — optimistic UI via `MessageSending`

**Presentation layer :**
- `features/messaging/presentation/pages/conversation_list_page.dart` — `ConversationListPage` + `_ConversationTile`
  - Icône warning si `latestMessage.isFlagged`
- `features/messaging/presentation/pages/chat_page.dart` — `ChatPage` + `_ChatBubble` + `_InputBar`
  - `_ChatBubble` : fond orange + label si `isFlagged`, badge bot si `isAutoReply`
  - `_InputBar` : TextField + bouton Envoyer avec `AnimatedSwitcher`

**Barrel :** `features/messaging/messaging.dart`

**`ApiEndpoints` — Nouveaux endpoints ajoutés :**
- `conversations`, `conversationMessages(id)`, `conversationRead(id)`
- `meUpdateFcmToken`, `notifications`, `notificationsReadAll`, `notificationRead(id)`

### Tests

**Flutter :** `test/features/messaging/bloc/messaging_cubit_test.dart` — 6 tests
- State initial `MessagingInitial`
- `loadConversations` success + error
- `loadMessages` success (messages reversed oldest-first)
- `sendMessage` : `[Loading, Loaded, Sending, Loaded]`, nouveau message ajouté
- `isFlagged = true` préservé dans `MessagesLoaded`
