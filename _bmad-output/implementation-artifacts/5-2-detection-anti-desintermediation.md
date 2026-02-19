# Story 5.2: Détection anti-désintermédiation (backend)

Status: done

## Story

As a BookMi platform,
I want détecter automatiquement les tentatives de partage de coordonnées dans les messages,
So that les échanges restent dans l'application et les transactions passent par BookMi.

**Functional Requirements:** FR-ANTI-DES-1
**Non-Functional Requirements:** NFR-SEC-MSG (regex CPU-safe, aucune requête externe)

## Acceptance Criteria (BDD)

**AC1 — Détection lors de l'envoi**
**Given** un utilisateur qui envoie un message
**When** le contenu contient un numéro de téléphone, email, URL, handle WhatsApp/Telegram ou mot-clé de réseau social
**Then** le champ `is_flagged = true` est enregistré en base
**And** la réponse 201 inclut un bloc `warning.code = CONTACT_SHARING_DETECTED`

**AC2 — Message propre — pas d'avertissement**
**Given** un message sans coordonnées de contact
**When** il est envoyé
**Then** `is_flagged = false`, aucune clé `warning` dans la réponse

**AC3 — Patterns détectés**
- Numéro international/local : `+225 07 12 34 56 78`, `0708000000`
- Email : `user@example.com`
- URL HTTP/HTTPS
- Liens/mots-clés WhatsApp : `wa.me/...`, `WhatsApp`
- Liens/handles Telegram : `t.me/...`, `@monpseudo`
- Mots-clés sociaux : `signal`, `instagram`, `insta`, `dm me`

## Implementation Notes

### Backend (Laravel)

**Nouveau service :**
- `app/Services/ContactDetectionService.php`
  - `PATTERNS` : 6 regex pour phone, email, url, whatsapp, telegram, social
  - `containsContactInfo(string $text): bool`
  - `detect(string $text): array<string>` — retourne les clés matchées (pour diagnostics admin)

**Intégration dans `MessagingService::sendMessage()` :**
```php
$isFlagged = $this->contactDetector->containsContactInfo($content);
Message::create([..., 'is_flagged' => $isFlagged]);
```
`ContactDetectionService` est injecté via le constructeur de `MessagingService`.

**Intégration dans `MessageController` :**
- `send()` et `store()` : si `$message->is_flagged`, la réponse 201 inclut :
```json
{
  "warning": {
    "code": "CONTACT_SHARING_DETECTED",
    "message": "Votre message contient des informations de contact..."
  }
}
```
Le message est **envoyé** (non bloqué) mais marqué pour revue admin future.

### Tests

**Unit :** `tests/Unit/Services/ContactDetectionServiceTest.php` — 14 tests
- Détection phone (international, local)
- Détection email
- Détection URL (http, https)
- Détection wa.me, WhatsApp keyword
- Détection t.me, @handle Telegram
- Détection mot-clé Instagram
- Message propre non flaggé (2 cas)
- `detect()` retourne les clés correctes
- `detect()` vide pour message propre

**Feature (dans MessageControllerTest) :** 2 tests ajoutés
- Message avec numéro → `is_flagged = true` + `warning` dans la réponse
- Message propre → `is_flagged = false` + pas de `warning`
