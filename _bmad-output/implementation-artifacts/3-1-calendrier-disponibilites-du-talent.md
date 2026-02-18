# Story 3.1: Calendrier de disponibilités du talent (backend)

Status: done

## Story

As a talent,
I want gérer mon calendrier de disponibilités (bloquer des jours, marquer les jours de repos),
so that les clients ne puissent réserver que les jours où je suis disponible.

**Functional Requirements:** FR52

## Acceptance Criteria (BDD)

**AC1 — Créer un créneau**
**Given** un talent authentifié
**When** il envoie `POST /api/v1/calendar_slots` avec `{ date, status }`
**Then** le créneau est créé dans la table `calendar_slots`
**And** la réponse 201 contient le créneau avec `{ id, talent_profile_id, date, status }`
**And** status peut être `available`, `blocked` ou `rest`
**And** un même talent ne peut pas avoir deux créneaux pour la même date (unique constraint)

**AC2 — Lister les disponibilités du mois**
**Given** un utilisateur (authentifié ou non)
**When** il envoie `GET /api/v1/talents/{talent}/calendar?month=2026-03`
**Then** la réponse contient tous les créneaux du talent pour le mois spécifié
**And** les jours avec une réservation confirmée (`booking_requests.status = confirmed`) apparaissent avec status `confirmed`
**And** si aucun créneau n'est défini pour un jour, il n'apparaît pas dans la réponse

**AC3 — Modifier un créneau**
**Given** un talent authentifié propriétaire du créneau
**When** il envoie `PUT /api/v1/calendar_slots/{slot}` avec `{ status }`
**Then** le statut du créneau est mis à jour
**And** la réponse 200 contient le créneau mis à jour
**And** seul le talent propriétaire peut modifier ses créneaux (Policy)

**AC4 — Supprimer un créneau**
**Given** un talent authentifié propriétaire du créneau
**When** il envoie `DELETE /api/v1/calendar_slots/{slot}`
**Then** le créneau est supprimé
**And** la réponse 204 est retournée
**And** seul le talent propriétaire peut supprimer ses créneaux (Policy)

**AC5 — Conflits de dates**
**Given** un créneau existe déjà pour une date donnée
**When** le talent tente de créer un second créneau pour la même date
**Then** une erreur `CALENDAR_SLOT_CONFLICT` (409) est retournée

**AC6 — Validation**
**Given** un talent authentifié
**When** il envoie une requête avec des données invalides
**Then** une erreur 422 avec les détails de validation est retournée
**And** `date` doit être une date valide au format `Y-m-d`, non passée
**And** `status` doit être l'un de : `available`, `blocked`, `rest`

## Tasks / Subtasks

### Phase 1 — Migration & Enum

- [x] Task 1: Créer l'enum CalendarSlotStatus
  - [x] 1.1: `app/Enums/CalendarSlotStatus.php` avec cases : available, blocked, rest

- [x] Task 2: Créer la migration calendar_slots
  - [x] 2.1: Table `calendar_slots` : id, talent_profile_id (FK), date (date), status (enum), timestamps
  - [x] 2.2: Index unique `(talent_profile_id, date)`
  - [x] 2.3: FK vers `talent_profiles.id` avec cascade delete

### Phase 2 — Model & Resource

- [x] Task 3: Créer le Model CalendarSlot
  - [x] 3.1: Fillable, casts (date→date, status→enum), relations
  - [x] 3.2: Relation `talentProfile()` BelongsTo

- [x] Task 4: Créer CalendarSlotResource
  - [x] 4.1: Sérialisation : id, talent_profile_id, date (Y-m-d), status

### Phase 3 — Request Validation

- [x] Task 5: StoreCalendarSlotRequest
  - [x] 5.1: Règles : date (required, date, after_or_equal:today), status (required, enum)

- [x] Task 6: UpdateCalendarSlotRequest
  - [x] 6.1: Règles : status (required, enum)

### Phase 4 — Service & Exception

- [x] Task 7: Créer CalendarService
  - [x] 7.1: `createSlot(TalentProfile, array)` — vérifie unicité, crée le créneau
  - [x] 7.2: `updateSlot(CalendarSlot, array)` — met à jour le statut
  - [x] 7.3: `deleteSlot(CalendarSlot)` — supprime le créneau
  - [x] 7.4: `getMonthCalendar(TalentProfile, string month)` — agrège créneaux + réservations confirmées

- [x] Task 8: Créer CalendarException
  - [x] 8.1: `slotConflict()` → `CALENDAR_SLOT_CONFLICT` (409)
  - [x] 8.2: `slotNotFound()` → `CALENDAR_SLOT_NOT_FOUND` (404)

### Phase 5 — Controller & Policy & Routes

- [x] Task 9: Créer CalendarSlotPolicy
  - [x] 9.1: `modify(User, CalendarSlot)` — vérifie que l'user est le talent propriétaire

- [x] Task 10: Créer CalendarSlotController
  - [x] 10.1: `index(TalentProfile, Request)` — GET /talents/{talent}/calendar
  - [x] 10.2: `store(StoreCalendarSlotRequest)` — POST /calendar_slots
  - [x] 10.3: `update(UpdateCalendarSlotRequest, CalendarSlot)` — PUT /calendar_slots/{slot}
  - [x] 10.4: `destroy(CalendarSlot)` — DELETE /calendar_slots/{slot}

- [x] Task 11: Ajouter les routes dans api.php
  - [x] 11.1: Route publique : GET /talents/{talent}/calendar
  - [x] 11.2: Routes auth:sanctum + role:talent : POST/PUT/DELETE /calendar_slots

### Phase 6 — Tests

- [x] Task 12: Tests Feature
  - [x] 12.1: `tests/Feature/Calendar/CalendarSlotTest.php`
  - [x] 12.2: Tests : créer créneau (succès), conflit date, validation, lister par mois,
    modifier (succès + forbidden), supprimer (succès + forbidden), créneaux confirmés auto

## Dev Notes

- Pattern : CalendarService + CalendarSlotController (pas de Repository pour cette feature simple)
- Les montants restent en centimes côté backend (pas de montants dans cette story)
- `month` param format : `Y-m` (ex: `2026-03`)
- Les jours avec réservation confirmée (`booking_requests` table — story 3.2) : pour l'instant
  on anticipe la structure mais on ne bloque pas la migration sur la table booking_requests inexistante

## Dev Agent Record

### Agent Model Used
Claude Sonnet 4.6

### Completion Notes List
- All 12 tasks completed across 6 phases
- 309 tests passing (294 existing + 15 new calendar tests, 1042 assertions)
- CalendarService handles graceful skip of booking_requests table (story 3.2 not yet implemented)
- CalendarSlotStatus.Confirmed is virtual (not stored in DB, injected by service at query time)
- Policy enforces talent ownership for update/delete

### File List

**Created:**
- `app/Enums/CalendarSlotStatus.php`
- `app/Models/CalendarSlot.php`
- `app/Http/Resources/CalendarSlotResource.php`
- `app/Http/Requests/Api/StoreCalendarSlotRequest.php`
- `app/Http/Requests/Api/UpdateCalendarSlotRequest.php`
- `app/Exceptions/CalendarException.php`
- `app/Services/CalendarService.php`
- `app/Policies/CalendarSlotPolicy.php`
- `app/Http/Controllers/Api/V1/CalendarSlotController.php`
- `database/migrations/2026_02_18_165945_create_calendar_slots_table.php`
- `database/factories/CalendarSlotFactory.php`
- `tests/Feature/Calendar/CalendarSlotTest.php`

**Modified:**
- `routes/api.php` (3 routes ajoutées : GET calendar public + POST/PUT/DELETE auth)
