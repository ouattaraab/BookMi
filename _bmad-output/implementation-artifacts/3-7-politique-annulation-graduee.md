# Story 3.7: Politique d'annulation graduée (backend)

Status: done

<!-- Note: Story créée par Dev agent depuis epics.md + architecture.md -->

## Story

As a client,
I want pouvoir annuler ma réservation avec un remboursement calculé selon le délai avant l'événement,
so that je sois protégé équitablement en cas d'imprévu.

**Functional Requirements:** FR26, FR27

## Acceptance Criteria (BDD)

**AC1 — Remboursement intégral (>= J-14)**
**Given** un client avec une réservation `accepted` ou `confirmed`
**When** il annule à 14 jours ou plus de l'événement
**Then** `refund_amount` = `total_amount`, `cancellation_policy_applied` = `full_refund`

**AC2 — Remboursement partiel (J-7 à J-14)**
**Given** une réservation à entre 7 et 13 jours de l'événement
**When** le client annule
**Then** `refund_amount` = 50% de `total_amount`, `cancellation_policy_applied` = `partial_refund`

**AC3 — Médiation requise (J-2 à J-7)**
**Given** une réservation à entre 2 et 6 jours de l'événement
**When** le client tente d'annuler
**Then** une erreur 422 `BOOKING_CANCELLATION_MEDIATION_REQUIRED` est retournée

**AC4 — Bloqué (< J-2)**
**Given** une réservation à moins de 2 jours de l'événement
**When** le client tente d'annuler
**Then** une erreur 422 `BOOKING_CANCELLATION_NOT_ALLOWED` est retournée

**AC5 — Confirmed → Cancelled autorisé**
**Given** une réservation `confirmed`
**When** le client annule à >= J-14
**Then** la réservation passe en `cancelled` avec remboursement intégral

**AC6 — Seul le client peut annuler**
**Given** un talent ou un tiers
**When** il accède à `POST /booking_requests/{booking}/cancel`
**Then** une erreur 403 est retournée

**AC7 — Statut invalide → 422**
**Given** une réservation déjà `cancelled` ou `completed`
**When** le client tente d'annuler
**Then** une erreur 422 `BOOKING_INVALID_TRANSITION` est retournée

## Tasks / Subtasks

### Phase 1 — État & Migration

- [x] Task 1: Mettre à jour `BookingStatus::allowedTransitions()`
  - [x] 1.1: Ajouter `Cancelled` dans les transitions de `Confirmed`
- [x] Task 2: Migration `add_cancellation_fields_to_booking_requests`
  - [x] 2.1: `refund_amount BIGINT NULLABLE`
  - [x] 2.2: `cancellation_policy_applied VARCHAR(50) NULLABLE`

### Phase 2 — Modèle & Resource

- [x] Task 3: Mettre à jour `BookingRequest` model
  - [x] 3.1: `refund_amount`, `cancellation_policy_applied` dans fillable + casts
- [x] Task 4: Mettre à jour `BookingRequestResource`
  - [x] 4.1: Exposer `refund_amount` et `cancellation_policy_applied`

### Phase 3 — Service, Policy, Controller, Routes

- [x] Task 5: Ajouter exceptions
  - [x] 5.1: `BookingException::cancellationNotAllowed()` (422)
  - [x] 5.2: `BookingException::cancellationRequiresMediation()` (422)
- [x] Task 6: Ajouter `BookingService::cancelBooking()`
  - [x] 6.1: Calcul `daysUntilEvent` via Carbon `diffInDays`
  - [x] 6.2: Application de la politique depuis `config('bookmi.cancellation')`
- [x] Task 7: Mettre à jour `BookingRequestPolicy`
  - [x] 7.1: `cancel()` — client uniquement (`booking->client_id === user->id`)
- [x] Task 8: Mettre à jour `BookingRequestController`
  - [x] 8.1: `cancel(BookingRequest)` — POST
- [x] Task 9: Ajouter la route `POST /booking_requests/{booking}/cancel`

### Phase 4 — Tests

- [x] Task 10: Tests Feature `BookingCancellationTest`
  - [x] AC1 : remboursement intégral >= J-14 (2 cas)
  - [x] AC2 : remboursement partiel J-7 à J-14 (2 cas)
  - [x] AC3 : médiation requise J-2 à J-7
  - [x] AC4 : bloqué < J-2
  - [x] AC5 : Confirmed → Cancelled
  - [x] AC6 : talent et tiers → 403
  - [x] AC7 : already cancelled / completed → 422

## Dev Notes

### Architecture
- **Policy** : `cancel()` restreint aux clients uniquement (différent de `reject()` pour talents)
- **État** : `Confirmed` modifié pour autoriser → `Cancelled` (ajout dans `allowedTransitions`)
- **Config** : `config('bookmi.cancellation')` contient tous les seuils (full_refund_days, partial_refund_days, mediation_only_days, partial_refund_rate)
- **Refund** : `refund_amount` stocké pour Epic 4 (paiement réel). Pour `Accepted`, aucun paiement réel n'a eu lieu encore.
- **Event** : réutilisation de `BookingCancelled` pour toutes les annulations

## Dev Agent Record

### Agent Model Used
Claude Sonnet 4.6

### Completion Notes List
- `diffInDays(absolute: false)` retourne positif pour dates futures, négatif pour passées — comportement correct pour le calcul de la politique
- `BookingStatus::Confirmed` modifié pour inclure `Cancelled` dans `allowedTransitions()`
- Code review: aucun bug critique — comportement Pending → Cancelled (client) acceptable (pas de paiement à rembourser)

### File List
- `database/migrations/2026_02_18_210000_add_cancellation_fields_to_booking_requests_table.php`
- `app/Enums/BookingStatus.php` — Confirmed → Cancelled ajouté
- `app/Models/BookingRequest.php` — refund_amount, cancellation_policy_applied dans fillable + casts
- `app/Http/Resources/BookingRequestResource.php` — refund_amount, cancellation_policy_applied exposés
- `app/Exceptions/BookingException.php` — cancellationNotAllowed(), cancellationRequiresMediation()
- `app/Services/BookingService.php` — cancelBooking() avec politique graduée
- `app/Policies/BookingRequestPolicy.php` — cancel()
- `app/Http/Controllers/Api/V1/BookingRequestController.php` — cancel()
- `routes/api.php` — POST /booking_requests/{booking}/cancel
- `tests/Feature/Booking/BookingCancellationTest.php` — 11 tests
