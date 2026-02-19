# Story 5.6: Accès admin aux messages en cas de litige (backend)

Status: done

## Story

As an admin,
I want accéder aux messages échangés dans une conversation liée à une réservation en litige,
So that je puisse instruire le dossier et prendre une décision équitable.

**Functional Requirements:** FR-ADMIN-DISPUTE-MSG-1
**Non-Functional Requirements:** NFR-SEC-ADMIN (is_admin guard), NFR-AUDIT-1 (AdminAccessedMessages event)

## Acceptance Criteria (BDD)

**AC1 — Admin accède aux messages**
**Given** un admin authentifié
**When** il envoie `GET /api/v1/admin/disputes/{booking}/messages` pour une réservation `disputed`
**Then** la réponse 200 contient tous les messages de la conversation liée
**And** un événement `AdminAccessedMessages` est dispatché (traçabilité)

**AC2 — Réservation non en litige → 422**
**Given** une réservation `paid` (pas en litige)
**When** l'admin tente d'accéder aux messages
**Then** la réponse est 422 avec `error.code = BOOKING_NOT_DISPUTED`

**AC3 — Pas de conversation → 404**
**Given** une réservation en litige sans conversation associée
**When** l'admin tente d'accéder aux messages
**Then** la réponse est 404

**AC4 — Non-admin → 403**
**Given** un utilisateur non-admin
**When** il tente d'accéder au endpoint admin
**Then** la réponse est 403

**AC5 — Non authentifié → 401**

## Implementation Notes

### Backend (Laravel)

**Nouvel événement :**
- `app/Events/AdminAccessedMessages.php` — `Dispatchable`, `SerializesModels`, `admin: User`, `booking: BookingRequest`

**Nouveau controller :**
- `app/Http/Controllers/Api/V1/AdminDisputeController.php`
  - `messages(Request, BookingRequest)` :
    1. Vérifie `$booking->status === BookingStatus::Disputed` → 422 sinon
    2. Cherche `Conversation::where('booking_request_id', $booking->id)` → 404 si absent
    3. `event(new AdminAccessedMessages($admin, $booking))` — audit synchrone
    4. Retourne `MessageResource::collection($conversation->messages()->with('sender')->get())`

**Route ajoutée (sous middleware `admin`) :**
```
GET /api/v1/admin/disputes/{booking}/messages → AdminDisputeController@messages
```

### Tests

**Feature :** `tests/Feature/Api/V1/AdminDisputeControllerTest.php` — 5 tests
- Admin accède aux messages + `Event::assertDispatched(AdminAccessedMessages)`
- Réservation non disputed → 422
- Pas de conversation → 404
- Non-admin → 403
- Non authentifié → 401
