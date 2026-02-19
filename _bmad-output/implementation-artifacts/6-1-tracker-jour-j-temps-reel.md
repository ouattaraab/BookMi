# Story 6.1 : Tracker Jour J Temps Réel

Status: done

## Story

As a talent,
I want envoyer des mises à jour de statut en temps réel le jour de la prestation,
So that le client puisse suivre ma progression (en route, arrivé, en prestation, terminé).

**Functional Requirements:** FR-TRACK-1, FR-TRACK-2, FR-TRACK-3

## Acceptance Criteria (BDD)

**AC1 — Séquence de statuts obligatoire**
**Given** un talent dont la réservation est en statut Paid ou Confirmed
**When** il poste `POST /booking_requests/{booking}/tracking` avec `status: "preparing"`
**Then** un TrackingEvent est créé et un événement broadcast est émis
**And** la séquence doit être : preparing → en_route → arrived → performing → completed

**AC2 — Transition invalide rejetée**
**Given** un talent dont le dernier statut est `arrived`
**When** il tente de poster `en_route`
**Then** une erreur 422 est retournée
**And** aucun TrackingEvent n'est créé

**AC3 — Seul le talent peut poster**
**Given** un client authentifié
**When** il tente de poster une mise à jour de suivi
**Then** il reçoit une erreur 403

**AC4 — Réservation inactive non trackable**
**Given** une réservation en statut Pending ou Accepted
**When** le talent tente de poster un tracking
**Then** il reçoit une erreur 422

**AC5 — Historique visible par les deux parties**
**Given** un client ou un talent authentifié propriétaire de la réservation
**When** il appelle `GET /booking_requests/{booking}/tracking`
**Then** il reçoit la liste des TrackingEvents dans l'ordre chronologique

## Implementation Notes

### Backend — Fichiers créés

**Migration :**
- `database/migrations/2026_02_19_130000_create_tracking_events_table.php`
  - `booking_request_id`, `updated_by`, `status VARCHAR(30)`, `latitude DECIMAL(10,7)`, `longitude DECIMAL(10,7)`, `occurred_at`
  - Index composite `(booking_request_id, occurred_at)`

**Enum :**
- `app/Enums/TrackingStatus.php` — `Preparing | EnRoute | Arrived | Performing | Completed`
  - `allowedTransitions()` / `canTransitionTo()` — transitions forward-only
  - `label()` — labels en français

**Model :**
- `app/Models/TrackingEvent.php` — casts: `status → TrackingStatus`, `latitude/longitude → float`, `occurred_at → datetime`
- `app/Models/BookingRequest.php` — ajout de `trackingEvents(): HasMany`

**Event :**
- `app/Events/TrackingStatusChanged.php` — `ShouldBroadcast` sur `PresenceChannel('tracking.{bookingId}')`
  - `broadcastAs() = 'tracking.updated'`
  - payload: `id, booking_request_id, status, status_label, latitude, longitude, occurred_at, updated_by`

**Service :**
- `app/Services/TrackingService.php`
  - `sendUpdate(BookingRequest, User, TrackingStatus, ?float, ?float): TrackingEvent`
  - `assertBookingIsActive()` — Paid ou Confirmed requis
  - `assertValidTransition()` — premier statut = preparing, transitions forward-only
  - `broadcast()` wrappé dans try/catch + Log::error en cas d'échec

**Request :**
- `app/Http/Requests/Api/UpdateTrackingRequest.php` — `status` (Rule::enum), `latitude` (between:-90,90), `longitude` (between:-180,180)

**Controller :**
- `app/Http/Controllers/Api/V1/TrackingController.php`
  - `update()` — POST, talent uniquement, délègue au service
  - `index()` — GET, client + talent, utilise `$booking->trackingEvents()`

**Factory :**
- `database/factories/TrackingEventFactory.php`

**Routes :**
```
GET  /api/v1/booking_requests/{booking}/tracking  → TrackingController@index
POST /api/v1/booking_requests/{booking}/tracking  → TrackingController@update
```

### Tests

**Feature :** `tests/Feature/Api/V1/TrackingControllerTest.php` — 15 tests
- séquence complète (5 statuts)
- premier statut non-preparing → 422
- transition backward → 422
- statut terminal completed → 422
- client → 403, unrelated user → 403, unauthenticated → 401
- lat/lon invalides → 422
- booking Pending → 422, booking Paid → 201

**Unit :** `tests/Unit/Enums/TrackingStatusTest.php` — 4 tests
- transitions valides / invalides
- état terminal / labels

### Issues résolues lors de la code review

- **H1** : `broadcast()` entouré de try/catch + Log::error pour éviter incohérence DB/réponse
- **H2** : FQCNs `\App\Models\...` dans le contrôleur → use statements propres
- **M2** : Tests de validation lat/lon ajoutés
- **M3** : `index()` utilise désormais `$booking->trackingEvents()` au lieu d'une query inline
- **L1** : Messages ValidationException en anglais (cohérence codebase)
- **M1** : ⚠️ Action item — Race condition sur la première mise à jour `preparing` (deux requêtes concurrentes). À corriger dans une story d'amélioration avec DB lock ou contrainte unique.
