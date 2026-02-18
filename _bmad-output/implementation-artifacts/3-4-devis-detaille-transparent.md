# Story 3.4: Devis détaillé transparent (backend)

Status: done

<!-- Note: Story créée par Dev agent depuis epics.md + architecture.md -->

## Story

As a client,
I want consulter un devis détaillé transparent montrant le cachet artiste et les frais BookMi,
so that je comprenne exactement ce que je paie.

**Functional Requirements:** FR20

## Acceptance Criteria (BDD)

**AC1 — Devis détaillé dans la réponse**
**Given** une réservation accessible par l'utilisateur (toute statut)
**When** il accède à `GET /api/v1/booking_requests/{booking}`
**Then** la section `devis` contient :
  - `cachet_amount` (en centimes, 100% pour l'artiste)
  - `commission_amount` (15% frais BookMi)
  - `total_amount` (cachet + commission)
  - `message` : "Cachet artiste intact — BookMi ajoute 15% de frais de service"

**AC2 — Détail complet du package**
**Given** la même réponse
**Then** la section `service_package` contient :
  - `id`, `name`, `type`
  - `description` (nullable)
  - `inclusions` (array, nullable)
  - `duration_minutes` (nullable)

## Tasks / Subtasks

### Phase 1 — Resource

- [x] Task 1: Mettre à jour `BookingRequestResource`
  - [x] 1.1: Ajouter `message` dans la section `devis`
  - [x] 1.2: Ajouter `description`, `inclusions`, `duration_minutes` dans `service_package`

### Phase 2 — Controller & Service

- [x] Task 2: Mettre à jour les `load()` dans `BookingRequestController`
  - [x] 2.1: `show()` — charger les nouvelles colonnes du package
  - [x] 2.2: `accept()` / `reject()` — idem
  - [x] 2.3: `store()` — idem

- [x] Task 3: Mettre à jour `getBookingsForUser()` dans `BookingService`
  - [x] 3.1: Ajouter les nouvelles colonnes dans le `with()`

### Phase 3 — Tests

- [x] Task 4: Tests Feature `BookingDevisTest`
  - [x] AC1 : devis.message présent et correct
  - [x] AC2 : service_package.description / inclusions / duration_minutes présents

## Dev Notes

### Architecture
- Aucune migration nécessaire : `description`, `inclusions`, `duration_minutes` existent déjà sur `service_packages`
- `inclusions` est un `array` (JSON) côté modèle
- Le `message` du devis est une constante métier — définie dans `BookingRequestResource`

## Dev Agent Record

### Agent Model Used
Claude Sonnet 4.6

### Completion Notes List
- Aucune migration nécessaire : les colonnes `description`, `inclusions`, `duration_minutes` existaient déjà sur `service_packages` (Story 1.8).
- Le `message` de transparence est une constante métier dans `BookingRequestResource` — pas de config, pas de DB.
- Toutes les sélections de colonnes (`:id,name,type`) mises à jour dans le controller et le service pour inclure les nouvelles colonnes — sans cela, les champs auraient été null même si chargés.
- Les tests couvrent aussi le endpoint `store` et la liste `index` pour garantir la cohérence de la réponse sur tous les endpoints exposant `BookingRequestResource`.

### File List
- `app/Http/Resources/BookingRequestResource.php` — enrichi `service_package` + `devis.message`
- `app/Http/Controllers/Api/V1/BookingRequestController.php` — selects mis à jour (×4)
- `app/Services/BookingService.php` — select mis à jour dans `getBookingsForUser`
- `tests/Feature/Booking/BookingDevisTest.php` — 5 tests (124 assertions cumulées)
