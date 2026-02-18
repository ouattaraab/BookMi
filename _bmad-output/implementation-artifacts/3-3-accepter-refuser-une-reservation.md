# Story 3.3: Accepter/Refuser une réservation (backend)

Status: done

<!-- Note: Story créée par Dev agent depuis epics.md + architecture.md -->

## Story

As a talent,
I want accepter ou refuser une demande de réservation,
so that je puisse contrôler mes engagements.

**Functional Requirements:** FR19

## Acceptance Criteria (BDD)

**AC1 — Accepter une réservation**
**Given** un talent authentifié avec une réservation en status `pending`
**When** il envoie `POST /api/v1/booking_requests/{booking}/accept`
**Then** le status passe à `accepted`
**And** la réponse 200 contient la réservation mise à jour
**And** l'événement `BookingAccepted` est dispatché
**And** un créneau calendrier `blocked` est créé (ou mis à jour) pour la date de l'événement

**AC2 — Refuser une réservation**
**Given** un talent authentifié avec une réservation en status `pending`
**When** il envoie `POST /api/v1/booking_requests/{booking}/reject` avec `{ reason? }`
**Then** le status passe à `cancelled`
**And** `reject_reason` est enregistré (null si non fourni)
**And** l'événement `BookingCancelled` est dispatché

**AC3 — Machine à états**
**Given** une réservation en status != `pending`
**When** le talent tente d'accepter ou refuser
**Then** une erreur 422 `BOOKING_INVALID_TRANSITION` est retournée

**AC4 — Autorisation**
**Given** un utilisateur qui n'est PAS le talent propriétaire du profil
**When** il tente d'accepter ou refuser
**Then** une erreur 403 est retournée
**And** seul le talent propriétaire du `talent_profile_id` peut accepter/refuser

**AC5 — Validation**
**Given** un appel `reject` avec une raison
**Then** `reason` est optionnel, string max 500 caractères

## Tasks / Subtasks

### Phase 1 — Migration

- [x] Task 1: Ajouter `reject_reason` à la table `booking_requests`
  - [x] 1.1: `TEXT NULLABLE` après `message`

### Phase 2 — Model & Resource

- [x] Task 2: Mettre à jour `BookingRequest` model
  - [x] 2.1: Ajouter `reject_reason` dans fillable
  - [x] 2.2: Cast `reject_reason` → string nullable

- [x] Task 3: Mettre à jour `BookingRequestResource`
  - [x] 3.1: Exposer `reject_reason` dans la réponse

### Phase 3 — Events

- [x] Task 4: Créer `BookingAccepted` event
- [x] Task 5: Créer `BookingCancelled` event

### Phase 4 — Service

- [x] Task 6: Mettre à jour `BookingService`
  - [x] 6.1: `acceptBooking(User, BookingRequest)` — valide état, → accepted, bloque calendrier, dispatch BookingAccepted
  - [x] 6.2: `rejectBooking(User, BookingRequest, ?string)` — valide état, → cancelled, raison, dispatch BookingCancelled

### Phase 5 — Policy & Controller & Routes

- [x] Task 7: Mettre à jour `BookingRequestPolicy`
  - [x] 7.1: `accept(User, BookingRequest)` — talent propriétaire uniquement
  - [x] 7.2: `reject(User, BookingRequest)` — talent propriétaire uniquement

- [x] Task 8: Créer `RejectBookingRequestRequest`
  - [x] 8.1: Règle : `reason` nullable, string, max:500

- [x] Task 9: Mettre à jour `BookingRequestController`
  - [x] 9.1: `accept(BookingRequest)` — POST /booking_requests/{booking}/accept
  - [x] 9.2: `reject(RejectBookingRequestRequest, BookingRequest)` — POST /booking_requests/{booking}/reject

- [x] Task 10: Ajouter les routes dans `api.php`

### Phase 6 — Tests

- [x] Task 11: Tests Feature `BookingStatusTransitionTest`
  - [x] AC1 : accept → status=accepted, calendrier bloqué, event dispatché
  - [x] AC2 : reject avec/sans raison → status=cancelled, reason stored
  - [x] AC3 : transition invalide → 422 BOOKING_INVALID_TRANSITION
  - [x] AC4 : client/tiers → 403
  - [x] AC5 : reason trop longue → 422

## Dev Notes

### Architecture
- **Blocage calendrier** : `CalendarSlot::updateOrCreate(['talent_profile_id', 'date'], ['status' => Blocked])`
- **State machine** : utiliser `BookingStatus::canTransitionTo()` déjà défini
- **Events** : `BookingAccepted($booking)` et `BookingCancelled($booking)` — listeners vides pour l'instant (notifications story 5.x)

## Dev Agent Record

### Agent Model Used
Claude Sonnet 4.6

### Completion Notes List
- Policy `accept`/`reject` factored via private `isTalentOwner()` helper to avoid duplication.
- `BookingService::acceptBooking()` uses `CalendarSlot::updateOrCreate()` so an existing slot is updated rather than duplicated.
- `BookingService::rejectBooking()` stores `null` when `reason` is absent (AC5: optional).
- Authorization is handled entirely by the policy (`$this->authorize()`), keeping the service layer stateless w.r.t. auth.
- Test `accepting_updates_existing_calendar_slot` covers the `updateOrCreate` branch (pre-existing slot is overwritten rather than creating a duplicate).

### File List
- `database/migrations/2026_02_18_180000_add_reject_reason_to_booking_requests_table.php`
- `app/Models/BookingRequest.php` — added `reject_reason` to fillable + casts
- `app/Http/Resources/BookingRequestResource.php` — exposed `reject_reason`
- `app/Events/BookingAccepted.php`
- `app/Events/BookingCancelled.php`
- `app/Services/BookingService.php` — added `acceptBooking()`, `rejectBooking()`
- `app/Policies/BookingRequestPolicy.php` — added `accept()`, `reject()`
- `app/Http/Requests/Api/RejectBookingRequestRequest.php`
- `app/Http/Controllers/Api/V1/BookingRequestController.php` — added `accept()`, `reject()`
- `routes/api.php` — 2 new routes
- `tests/Feature/Booking/BookingStatusTransitionTest.php` — 11 tests (29 assertions)
