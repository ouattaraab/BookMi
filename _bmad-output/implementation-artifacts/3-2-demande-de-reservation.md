# Story 3.2: Demande de réservation (backend)

Status: done

<!-- Note: Story créée par SM agent depuis epics.md + architecture.md -->

## Story

As a client,
I want envoyer une demande de réservation à un talent avec la date, le lieu et le package choisi,
so that le talent puisse examiner et répondre à ma demande.

**Functional Requirements:** FR18, FR20 (calcul devis)

## Acceptance Criteria (BDD)

**AC1 — Créer une demande de réservation**
**Given** un client authentifié
**When** il envoie `POST /api/v1/booking_requests` avec `{ talent_profile_id, service_package_id, event_date, event_location, message? }`
**Then** la réservation est créée avec status `pending` dans la table `booking_requests`
**And** la réponse 201 contient la réservation avec le devis complet : `cachet_amount`, `commission_amount`, `total_amount` (tous en centimes)
**And** la commission est calculée à 15% du cachet (`config('bookmi.commission_rate')`)
**And** le talent propriétaire du package reçoit un événement `BookingCreated`

**AC2 — Vérification de disponibilité calendrier**
**Given** un client tente de réserver un talent
**When** la date demandée est marquée `blocked`, `rest` ou `confirmed` dans le calendrier du talent
**Then** la réservation est refusée avec l'erreur `BOOKING_DATE_UNAVAILABLE` (422)
**And** si la date n'a pas de créneau explicite (status `available` n'est pas requis), la réservation est autorisée
**And** si la date a un créneau `available`, la réservation est autorisée

**AC3 — Machine à états BookingStatus**
**Given** une réservation existe
**Then** la machine à états suivante est respectée :
- `pending` → `accepted` (talent accepte)
- `pending` → `cancelled` (talent refuse ou client annule avant acceptation)
- `accepted` → `paid` (paiement reçu — story 4.x)
- `paid` → `confirmed` (prestation confirmée par client ou auto 48h)
- `paid` → `cancelled` (annulation avec politique graduée)
- `confirmed` → `completed` (clôture finale)
- `paid|confirmed` → `disputed` (litige)
**And** seules les transitions légitimes sont autorisées (BookingService)

**AC4 — Validation des champs**
**Given** un client envoie une demande avec des données invalides
**Then** une erreur 422 avec les détails est retournée :
- `talent_profile_id` : requis, talent existant et vérifié
- `service_package_id` : requis, package appartenant au talent spécifié
- `event_date` : requis, format `Y-m-d`, date future (strictement après aujourd'hui)
- `event_location` : requis, string max 255 caractères
- `message` : optionnel, string max 1000 caractères

**AC5 — Accès à sa propre réservation**
**Given** un client ou un talent authentifié
**When** il accède à `GET /api/v1/booking_requests/{booking}`
**Then** le client peut voir ses propres réservations
**And** le talent peut voir les réservations le concernant
**And** un utilisateur non concerné reçoit 403

**AC6 — Lister les réservations**
**Given** un utilisateur authentifié
**When** il accède à `GET /api/v1/booking_requests`
**Then** un client voit ses réservations en tant que demandeur
**And** un talent voit les réservations reçues pour son profil
**And** la liste est paginée (cursor-based, 20 par page)
**And** un filtre `?status=pending` est disponible

## Tasks / Subtasks

### Phase 1 — Enum & Migration

- [x] Task 1: Créer l'enum BookingStatus
  - [x] 1.1: `app/Enums/BookingStatus.php` — cases : pending, accepted, paid, confirmed, completed, cancelled, disputed

- [x] Task 2: Créer la migration booking_requests
  - [x] 2.1: Table `booking_requests` : id, client_id (FK→users), talent_profile_id (FK→talent_profiles), service_package_id (FK→service_packages), event_date (date), event_location (string 255), message (text nullable), status (enum, default=pending), cachet_amount (int), commission_amount (int), total_amount (int), timestamps
  - [x] 2.2: Index : `booking_requests_talent_profile_id_status_index`, `booking_requests_client_id_status_index`
  - [x] 2.3: FK avec cascadeOnDelete sur talent_profiles et client

### Phase 2 — Model, Resource & Factory

- [x] Task 3: Créer le Model BookingRequest
  - [x] 3.1: Fillable, casts (event_date→date, status→enum, montants→int), relations
  - [x] 3.2: Relations : `client()` BelongsTo User, `talentProfile()` BelongsTo TalentProfile, `servicePackage()` BelongsTo ServicePackage
  - [x] 3.3: Méthode `isOwnedByUser(User)` — client ou talent propriétaire

- [x] Task 4: Créer BookingRequestResource
  - [x] 4.1: Sérialisation complète : id, status, client (id, name), talent_profile (id, stage_name), service_package (id, name, type), event_date, event_location, message, devis { cachet_amount, commission_amount, total_amount }

- [x] Task 5: Créer BookingRequestFactory
  - [x] 5.1: States : pending, accepted, paid, confirmed, completed, cancelled, disputed

### Phase 3 — Request Validation

- [x] Task 6: StoreBookingRequestRequest
  - [x] 6.1: Règles : talent_profile_id (exists:talent_profiles,id + is_verified), service_package_id (exists:service_packages,id + belongs to talent), event_date (after:today), event_location (max:255), message (nullable, max:1000)
  - [x] 6.2: Messages d'erreur en français

### Phase 4 — Service & Exception

- [x] Task 7: Créer BookingException
  - [x] 7.1: `dateUnavailable()` → `BOOKING_DATE_UNAVAILABLE` (422)
  - [x] 7.2: `talentNotFound()` → `BOOKING_TALENT_NOT_FOUND` (404)
  - [x] 7.3: `packageNotBelongToTalent()` → `BOOKING_PACKAGE_MISMATCH` (422)
  - [x] 7.4: `invalidStatusTransition()` → `BOOKING_INVALID_TRANSITION` (422)

- [x] Task 8: Créer BookingService
  - [x] 8.1: Méthode `createBookingRequest(User $client, array $data) → BookingRequest`
    - Valider package appartient au talent
    - Vérifier disponibilité via CalendarService::isDateAvailable() (slots blocked/rest + confirmed bookings)
    - Calculer cachet_amount (depuis package), commission_amount (15%), total_amount
    - Créer la réservation
    - Dispatcher event `BookingCreated`
  - [x] 8.2: Méthode `getBookingsForUser(User, array $filters) → CursorPaginator` — client ou talent selon profil
  - [x] 8.3: Méthode `getBookingForUser(User, BookingRequest) → BookingRequest` — avec vérification ownership
  - [x] CalendarService::isDateAvailable() ajoutée (vérifie blocked/rest/confirmed)

### Phase 5 — Events

- [x] Task 9: Créer l'événement BookingCreated
  - [x] 9.1: `app/Events/BookingCreated.php` — payload : `BookingRequest $booking`
  - [x] 9.2: Auto-découverte Laravel 12 — pas de listener pour l'instant (notification push = story 5.4)

### Phase 6 — Policy & Controller & Routes

- [x] Task 10: Créer BookingRequestPolicy
  - [x] 10.1: `view(User, BookingRequest)` — client propriétaire OU talent concerné

- [x] Task 11: Créer BookingRequestController
  - [x] 11.1: `index(Request)` — GET /booking_requests (avec filtre status, cursor pagination)
  - [x] 11.2: `store(StoreBookingRequestRequest)` — POST /booking_requests
  - [x] 11.3: `show(BookingRequest)` — GET /booking_requests/{booking}

- [x] Task 12: Ajouter les routes dans api.php (auth:sanctum)
  - [x] 12.1: GET /booking_requests
  - [x] 12.2: POST /booking_requests
  - [x] 12.3: GET /booking_requests/{booking}

### Phase 7 — Tests Feature

- [x] Task 13: Tests Feature (17 tests, 60 assertions — tous verts)
  - [x] 13.1: `tests/Feature/Booking/BookingRequestTest.php`
  - [x] 13.2: AC1 : créer réservation (succès), calcul commission correct, event dispatché
  - [x] 13.3: AC2 : date blocked → 422, date rest → 422, date sans slot → 200, date available → 200
  - [x] 13.4: AC4 : talent non vérifié → 422, package mauvais talent → 422, date passée → 422, non authentifié → 401
  - [x] 13.5: AC5 : client voit sa réservation, talent voit la sienne, tiers → 403
  - [x] 13.6: AC6 : liste paginée client / talent, filtre status

## Dev Notes

### Architecture
- **Pattern** : BookingService + BookingRequestController (pas de Repository — logique métier trop riche)
- **Montants** : toujours en centimes (int). `commission_amount = (cachet_amount * commission_rate) / 100` arrondi
- **commission_rate** : `config('bookmi.commission_rate')` = 15 (pourcent)
- **Disponibilité** : un jour sans créneau explicite est disponible ; seuls `blocked`, `rest`, `confirmed` bloquent
- **event BookingCreated** : listener vide pour l'instant (notification push = story 5.4)

### Composants existants à réutiliser
| Composant | Chemin | Usage |
|---|---|---|
| `CalendarService` | `app/Services/CalendarService.php` | Vérification disponibilité |
| `config('bookmi.commission_rate')` | `config/bookmi.php` | Taux commission (15%) |
| `BookmiException` | `app/Exceptions/BookmiException.php` | Base exceptions |
| `ApiResponseTrait` | `app/Http/Traits/ApiResponseTrait.php` | successResponse, paginatedResponse |
| `TalentProfile` model | `app/Models/TalentProfile.php` | Relation servicePackages |
| `ServicePackage` model | `app/Models/ServicePackage.php` | cachet_amount |

### Table booking_requests (schéma cible)
```sql
id               BIGINT PK
client_id        FK → users.id
talent_profile_id FK → talent_profiles.id
service_package_id FK → service_packages.id
event_date       DATE
event_location   VARCHAR(255)
message          TEXT NULL
status           ENUM('pending','accepted','paid','confirmed','completed','cancelled','disputed') DEFAULT 'pending'
cachet_amount    INT (centimes)
commission_amount INT (centimes)
total_amount     INT (centimes)
created_at       TIMESTAMP
updated_at       TIMESTAMP
```

### Références
- [Source: _bmad-output/planning-artifacts/epics.md#Story 3.2]
- [Source: _bmad-output/planning-artifacts/architecture.md#BookingService]
- [Source: _bmad-output/implementation-artifacts/3-1-calendrier-disponibilites-du-talent.md] — CalendarService existant
- [Source: bookmi/config/bookmi.php] — commission_rate = 15

## Dev Agent Record

### Agent Model Used
Claude Sonnet 4.6

### Completion Notes List
- `CalendarService::isDateAvailable()` ajoutée pour vérifier blocked/rest/confirmed
- Le format de réponse des erreurs de validation est `error.details.errors.field` (custom handler app) — tests adaptés
- `BookingCreated` event auto-découvert par Laravel 12, pas d'enregistrement explicite nécessaire
- Suite complète : 328 tests, 1105 assertions — 0 failure

### File List
- `app/Enums/BookingStatus.php`
- `database/migrations/2026_02_18_171915_create_booking_requests_table.php`
- `app/Models/BookingRequest.php`
- `app/Http/Resources/BookingRequestResource.php`
- `database/factories/BookingRequestFactory.php`
- `app/Http/Requests/Api/StoreBookingRequestRequest.php`
- `app/Exceptions/BookingException.php`
- `app/Services/CalendarService.php` (méthode `isDateAvailable` ajoutée)
- `app/Services/BookingService.php`
- `app/Events/BookingCreated.php`
- `app/Policies/BookingRequestPolicy.php`
- `app/Http/Controllers/Api/V1/BookingRequestController.php`
- `routes/api.php` (3 routes booking_requests ajoutées)
- `tests/Feature/Booking/BookingRequestTest.php`
