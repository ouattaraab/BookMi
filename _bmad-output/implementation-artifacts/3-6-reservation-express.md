# Story 3.6: Réservation express (backend)

Status: done

<!-- Note: Story créée par Dev agent depuis epics.md + architecture.md -->

## Story

As a client,
I want pouvoir réserver directement un talent en mode express sans attendre sa validation,
so that je puisse confirmer rapidement une prestation urgente.

**Functional Requirements:** FR25

## Acceptance Criteria (BDD)

**AC1 — Auto-acceptation**
**Given** un talent avec `enable_express_booking = true`
**When** le client soumet `POST /api/v1/booking_requests` avec `is_express: true`
**Then** la réservation est créée en statut `accepted` (auto-acceptée)
**And** le job `GenerateContractPdf` est dispatché sur la queue `media`

**AC2 — Talent sans express → 422**
**Given** un talent avec `enable_express_booking = false`
**When** le client soumet `is_express: true`
**Then** une erreur 422 `BOOKING_EXPRESS_NOT_AVAILABLE` est retournée

**AC3 — Réservation normale non affectée**
**Given** un talent avec `enable_express_booking = true`
**When** le client soumet `is_express: false` (ou absent)
**Then** la réservation reste en statut `pending`

**AC4 — `is_express` dans le resource**
**Given** une réservation express acceptée
**When** on consulte `GET /booking_requests/{booking}`
**Then** le champ `is_express` est `true` dans la réponse

## Tasks / Subtasks

### Phase 1 — Migrations

- [x] Task 1: Migration `add_enable_express_booking_to_talent_profiles`
  - [x] 1.1: `enable_express_booking BOOLEAN DEFAULT false`
- [x] Task 2: Migration `add_is_express_to_booking_requests`
  - [x] 2.1: `is_express BOOLEAN DEFAULT false`

### Phase 2 — Modèles & Resources

- [x] Task 3: Mettre à jour `TalentProfile` model
  - [x] 3.1: `enable_express_booking` dans fillable + cast boolean
- [x] Task 4: Mettre à jour `BookingRequest` model
  - [x] 4.1: `is_express` dans fillable + cast boolean
- [x] Task 5: Mettre à jour `BookingRequestResource`
  - [x] 5.1: Exposer `is_express`
- [x] Task 6: Ajouter état `withExpressBooking()` à `TalentProfileFactory`

### Phase 3 — Validation & Service

- [x] Task 7: Mettre à jour `StoreBookingRequestRequest`
  - [x] 7.1: Champ `is_express` nullable boolean
- [x] Task 8: Ajouter `BookingException::expressBookingNotAvailable()`
- [x] Task 9: Mettre à jour `BookingService::createBookingRequest()`
  - [x] 9.1: Si `is_express && !talent.enable_express_booking` → exception
  - [x] 9.2: Si `is_express` → appeler `acceptBooking()` après création

### Phase 4 — Tests

- [x] Task 10: Tests Feature `BookingExpressTest`
  - [x] AC1 : express booking auto-acceptée + ContractPdf dispatché
  - [x] AC2 : 422 si talent sans express
  - [x] AC3 : booking normale reste pending
  - [x] AC4 : `is_express` dans la resource

## Dev Notes

### Architecture
- **Aucune route supplémentaire** — réutilise `POST /booking_requests`
- **Auto-accept flow** : create (Pending) → `acceptBooking()` → retourne Accepted
- **Double event** : `BookingCreated` + `BookingAccepted` sont tous deux dispatched
- **Validation `is_express`** : nullable boolean dans FormRequest ; vérification métier dans le service

## Dev Agent Record

### Agent Model Used
Claude Sonnet 4.6

### Completion Notes List
- `is_express` déclaré `['nullable', 'boolean']` dans FormRequest (code review: sans nullable, `null` échoue la validation)
- La vérification `enable_express_booking` est dans le service, pas dans la validation FormRequest (concern séparé)
- `BookingCreated` est dispatché avant `acceptBooking()` pour respecter l'ordre chronologique des events

### File List
- `database/migrations/2026_02_18_200000_add_enable_express_booking_to_talent_profiles_table.php`
- `database/migrations/2026_02_18_200100_add_is_express_to_booking_requests_table.php`
- `app/Models/TalentProfile.php` — `enable_express_booking` dans fillable + cast
- `app/Models/BookingRequest.php` — `is_express` dans fillable + cast
- `app/Http/Resources/BookingRequestResource.php` — `is_express` exposé
- `app/Http/Requests/Api/StoreBookingRequestRequest.php` — `is_express` nullable boolean
- `app/Exceptions/BookingException.php` — `expressBookingNotAvailable()`
- `app/Services/BookingService.php` — logique express dans `createBookingRequest()`
- `database/factories/TalentProfileFactory.php` — état `withExpressBooking()`
- `tests/Feature/Booking/BookingExpressTest.php` — 5 tests
