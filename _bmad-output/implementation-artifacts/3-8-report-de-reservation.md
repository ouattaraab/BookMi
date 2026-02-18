# Story 3.8: Report de réservation (backend)

Status: done

<!-- Note: Story créée par Dev agent depuis epics.md + architecture.md -->

## Story

As a client or talent,
I want pouvoir demander un report de la date d'une réservation acceptée,
so that les imprévus puissent être gérés sans annulation.

**Functional Requirements:** FR28

## Acceptance Criteria (BDD)

**AC1 — Client peut créer un reschedule**
**Given** une réservation `accepted`, `paid` ou `confirmed`
**When** le client soumet `POST /booking_requests/{booking}/reschedule` avec une date future différente
**Then** une `reschedule_request` est créée en statut `pending`

**AC2 — Talent peut créer un reschedule**
**Given** une réservation `accepted`
**When** le talent soumet le même endpoint
**Then** une `reschedule_request` est créée en statut `pending`

**AC3 — Contrepartie peut accepter**
**When** la contrepartie (non-requester) appelle `POST /reschedule_requests/{reschedule}/accept`
**Then** `reschedule_request.status` = `accepted`, `booking_request.event_date` mis à jour, calendrier mis à jour

**AC4 — Contrepartie peut rejeter**
**When** la contrepartie appelle `POST /reschedule_requests/{reschedule}/reject`
**Then** `reschedule_request.status` = `rejected`, booking inchangé

**AC5 — Un seul reschedule pending à la fois**
**Given** une `reschedule_request` déjà `pending` pour cette réservation
**When** une deuxième est soumise
**Then** une erreur 422 `BOOKING_RESCHEDULE_ALREADY_PENDING` est retournée

**AC6 — Le requester ne peut pas répondre à sa propre demande**
**Given** le requester tente d'accepter/rejeter sa propre demande
**Then** une erreur 403 est retournée

**AC7 — Tiers exclu**
**Given** un tiers non lié à la réservation
**Then** 403 pour create et respond

**AC8 — Statut de réservation invalide → 422**
**Given** réservation `cancelled`, `completed`, ou `pending`
**When** reschedule tenté
**Then** 422 `BOOKING_INVALID_TRANSITION`

**AC9 — Même date → 422**
**Given** la date proposée = date actuelle de l'événement
**Then** 422 `BOOKING_RESCHEDULE_SAME_DATE`

**AC10 — Reschedule non-pending → 422**
**Given** un reschedule déjà `accepted` ou `rejected`
**When** on tente de l'accepter/rejeter
**Then** 422 `BOOKING_RESCHEDULE_NOT_PENDING`

## Tasks / Subtasks

### Phase 1 — Migration & Enum

- [x] Task 1: Migration `create_reschedule_requests_table`
- [x] Task 2: Enum `RescheduleStatus` (pending|accepted|rejected)

### Phase 2 — Modèle

- [x] Task 3: Modèle `RescheduleRequest`
  - [x] 3.1: `booking_request_id`, `requested_by_id`, `proposed_date`, `message`, `status`, `responded_at`
  - [x] 3.2: Relations `booking()`, `requestedBy()`
- [x] Task 4: Mettre à jour `BookingRequest` model
  - [x] 4.1: Relation `rescheduleRequests()` HasMany
  - [x] 4.2: Méthode `hasPendingReschedule()`

### Phase 3 — Service, Policy, Resource

- [x] Task 5: `RescheduleService`
  - [x] 5.1: `createReschedule()` — validations + création
  - [x] 5.2: `acceptReschedule()` — DB::transaction + mise à jour booking + slots calendrier
  - [x] 5.3: `rejectReschedule()`
- [x] Task 6: Exceptions `BookingException`
  - [x] 6.1: `rescheduleAlreadyPending()`
  - [x] 6.2: `rescheduleNotPending()`
  - [x] 6.3: `rescheduleSameDate()`
- [x] Task 7: `RescheduleRequestPolicy`
  - [x] 7.1: `create(User, BookingRequest)` — client ou talent owner
  - [x] 7.2: `respond(User, RescheduleRequest)` — contrepartie uniquement
- [x] Task 8: `RescheduleRequestResource`
- [x] Task 9: `StoreRescheduleRequestRequest` (proposed_date, message)

### Phase 4 — Controller & Routes

- [x] Task 10: `RescheduleController`
  - [x] 10.1: `store(StoreRescheduleRequestRequest, BookingRequest)` — POST
  - [x] 10.2: `accept(RescheduleRequest)` — POST
  - [x] 10.3: `reject(RescheduleRequest)` — POST
- [x] Task 11: Routes
  - [x] `POST /booking_requests/{booking}/reschedule`
  - [x] `POST /reschedule_requests/{reschedule}/accept`
  - [x] `POST /reschedule_requests/{reschedule}/reject`

### Phase 5 — Tests

- [x] Task 12: Tests Feature `BookingRescheduleTest` — 11 tests

## Dev Notes

### Architecture
- **Bidirectionnel** : client et talent peuvent créer un reschedule, la contrepartie répond
- **Atomicité** : `acceptReschedule()` wrappé dans `DB::transaction()` (libère ancien slot, bloque nouveau, met à jour booking, marque accepted)
- **Policy** : `authorize('create', [RescheduleRequest::class, $booking])` — syntaxe array pour passer `$booking` à `RescheduleRequestPolicy::create()`
- **Un seul pending** : `hasPendingReschedule()` vérifie via `rescheduleRequests()->where('status', Pending)->exists()`
- **HasFactory supprimé** : pas de factory créée pour ce sprint, trait inutile évité

## Dev Agent Record

### Agent Model Used
Claude Sonnet 4.6

### Completion Notes List
- `HasFactory` supprimé du modèle `RescheduleRequest` (code review) : pas de factory dans ce sprint
- `authorize('create', [RescheduleRequest::class, $booking])` : syntaxe correcte pour auto-discovery + passer `$booking` comme argument supplémentaire
- Calendar slots mis à jour dans transaction : old slot → Available, new slot → Blocked

### File List
- `database/migrations/2026_02_18_220000_create_reschedule_requests_table.php`
- `app/Enums/RescheduleStatus.php`
- `app/Models/RescheduleRequest.php` — sans HasFactory
- `app/Models/BookingRequest.php` — rescheduleRequests(), hasPendingReschedule()
- `app/Exceptions/BookingException.php` — 3 nouvelles exceptions
- `app/Services/RescheduleService.php` — createReschedule, acceptReschedule, rejectReschedule
- `app/Policies/RescheduleRequestPolicy.php`
- `app/Http/Resources/RescheduleRequestResource.php`
- `app/Http/Requests/Api/StoreRescheduleRequestRequest.php`
- `app/Http/Controllers/Api/V1/RescheduleController.php`
- `routes/api.php` — 3 nouvelles routes
- `tests/Feature/Booking/BookingRescheduleTest.php` — 11 tests
