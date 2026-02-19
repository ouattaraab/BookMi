# Story 5.3: Réponses automatiques talent (backend)

Status: done

## Story

As a talent,
I want configurer un message de réponse automatique,
So that les clients reçoivent immédiatement un accusé de réception quand je suis indisponible.

**Functional Requirements:** FR-AUTO-REPLY-1
**Non-Functional Requirements:** NFR-ONCE (envoyée une seule fois par conversation)

## Acceptance Criteria (BDD)

**AC1 — Configurer la réponse automatique**
**Given** un talent authentifié
**When** il envoie `PUT /api/v1/talent_profiles/me/auto_reply` avec `auto_reply_message` et `auto_reply_is_active = true`
**Then** la réponse 200 retourne le message et le statut actif
**And** les champs sont persistés sur `talent_profiles`

**AC2 — Désactiver la réponse automatique**
**Given** un talent avec auto-reply actif
**When** il envoie `PUT /me/auto_reply` avec `auto_reply_is_active = false`
**Then** plus aucun auto-reply n'est déclenché

**AC3 — Déclenchement au 1er message du client**
**Given** un talent avec auto-reply actif
**When** un client envoie le premier message d'une conversation
**Then** un message automatique (`is_auto_reply = true`) est créé, envoyé par le talent
**And** il est broadcasté sur le même channel

**AC4 — Une seule auto-reply par conversation**
**Given** un client qui envoie plusieurs messages dans la même conversation
**When** l'auto-reply a déjà été envoyée
**Then** aucune deuxième auto-reply n'est générée

**AC5 — Validation**
**Given** une requête sans `auto_reply_message`
**When** le endpoint est appelé
**Then** la réponse est 422

## Implementation Notes

### Backend (Laravel)

**Nouvelle migration :**
- `2026_02_19_110000_add_auto_reply_to_talent_profiles_table.php`
  - `auto_reply_message TEXT NULLABLE`
  - `auto_reply_is_active BOOLEAN DEFAULT FALSE`

**`TalentProfile` model :**
- Ajout de `auto_reply_message`, `auto_reply_is_active` dans `$fillable`
- Cast `auto_reply_is_active → boolean`

**Nouvelle request :**
- `app/Http/Requests/Api/UpdateAutoReplyRequest.php` — `auto_reply_message` (required, max 2000) + `auto_reply_is_active` (required, boolean)

**`TalentProfileController::updateAutoReply()` :**
- Route `PUT /api/v1/talent_profiles/me/auto_reply`
- Récupère le profil via `TalentProfileService::getByUserId()`
- 404 si profil introuvable

**`MessagingService::maybeAutoReply()` (private) :**
- Appelé dans `sendMessage()` uniquement si `!$isAutoReply`
- Conditions : `auto_reply_is_active && auto_reply_message && !already_replied`
- Envoie `sendMessage(conversation, talentUser, autoReplyMessage, isAutoReply: true)`

### Tests

**Feature :** `tests/Feature/Api/V1/AutoReplyTest.php` — 7 tests
- Talent active/désactive l'auto-reply (PUT endpoint)
- 422 sans message, 401 sans auth
- Auto-reply envoyée quand client envoie 1er message
- Pas d'auto-reply si désactivée
- Auto-reply envoyée une seule fois par conversation (3 messages au total)
