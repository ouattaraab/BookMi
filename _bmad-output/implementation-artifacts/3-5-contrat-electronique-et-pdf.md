# Story 3.5: Contrat électronique et PDF (backend)

Status: done

<!-- Note: Story créée par Dev agent depuis epics.md + architecture.md -->

## Story

As a client,
I want un contrat électronique généré automatiquement et téléchargeable en PDF,
so that la prestation soit formalisée légalement.

**Functional Requirements:** FR21, FR22

## Acceptance Criteria (BDD)

**AC1 — Génération automatique à l'acceptation**
**Given** un talent qui accepte une réservation
**When** le statut passe à `accepted`
**Then** le job `GenerateContractPdf` est dispatchée sur la queue `media`

**AC2 — Contenu du contrat**
**Given** le job exécuté
**Then** le PDF contient : identification des parties (client + talent), description du service (package name, inclusions), date et lieu, prix détaillé (cachet, commission, total), politique d'annulation

**AC3 — Téléchargement du PDF**
**Given** le contrat généré (`contract_path` non null)
**When** le client ou le talent accède à `GET /api/v1/booking_requests/{booking}/contract`
**Then** une réponse `200` avec `Content-Type: application/pdf` est retournée

**AC4 — Contrat non encore prêt**
**Given** la réservation est `accepted` mais le job n'a pas encore tourné
**When** on accède à `GET .../contract`
**Then** une erreur 404 `BOOKING_CONTRACT_NOT_READY` est retournée

**AC5 — Autorisation**
**Given** un utilisateur qui n'est ni le client ni le talent propriétaire
**When** il accède à l'endpoint contract
**Then** une erreur 403 est retournée

**AC6 — `contract_available` dans le resource**
**Given** la réponse JSON de `GET /booking_requests/{booking}`
**Then** le champ `contract_available` est `true` si le contrat est prêt, `false` sinon

## Tasks / Subtasks

### Phase 1 — Dépendances & Migration

- [x] Task 1: Installer `barryvdh/laravel-dompdf ^3.1`
- [x] Task 2: Migration `add_contract_path_to_booking_requests`
  - [x] 2.1: `contract_path TEXT NULLABLE`

### Phase 2 — Model & Resource

- [x] Task 3: Mettre à jour `BookingRequest` model
  - [x] 3.1: `contract_path` dans fillable
- [x] Task 4: Mettre à jour `BookingRequestResource`
  - [x] 4.1: `contract_available` booléen

### Phase 3 — PDF Template & Job

- [x] Task 5: Blade template `resources/views/pdf/booking-contract.blade.php`
- [x] Task 6: Job `app/Jobs/GenerateContractPdf.php`
  - [x] 6.1: Queue `media`
  - [x] 6.2: `handle()` — DomPDF → `contracts/booking-{id}.pdf` → update `contract_path`

### Phase 4 — Service, Policy, Controller, Routes

- [x] Task 7: Mettre à jour `BookingService::acceptBooking()` — dispatch `GenerateContractPdf`
- [x] Task 8: Mettre à jour `BookingRequestPolicy`
  - [x] 8.1: `downloadContract(User, BookingRequest)` — même règle que `view()`
- [x] Task 9: Mettre à jour `BookingRequestController`
  - [x] 9.1: `contract(BookingRequest)` — GET, streame le PDF
- [x] Task 10: Ajouter la route `GET /booking_requests/{booking}/contract`
- [x] Task 11: Ajouter `BookingException::contractNotReady()`

### Phase 5 — Tests

- [x] Task 12: Tests Feature `BookingContractTest`
  - [x] AC1 : job dispatché lors de l'acceptation
  - [x] AC3 : download du PDF réussi
  - [x] AC4 : 404 si contrat pas prêt
  - [x] AC5 : 403 pour tiers
  - [x] AC6 : `contract_available` dans la resource

## Dev Notes

### Architecture
- **Disk** : `local` — `contracts/booking-{id}.pdf`
- **Job** : dispatché dans `BookingService::acceptBooking()` après la transaction et l'event
- **Template** : HTML + styles inline (DomPDF n'utilise pas les classes CSS externes)
- **Streaming** : `Storage::disk('local')->get()` + `response()` avec headers PDF
- **Tests** : `Queue::fake()` pour AC1, `Storage::fake('local')` pour AC3

## Dev Agent Record

### Agent Model Used
Claude Sonnet 4.6

### Completion Notes List
- `public string $queue = 'media'` ne peut pas être déclaré sur la classe car le trait `Queueable` définit déjà `$queue`. Le nom de queue est appliqué à la dispatch via `->onQueue('media')` dans `BookingService`.
- `Storage::fake('local')` utilisé dans les tests de download pour éviter les écritures disque réelles.
- `Queue::assertPushedOn('media', ...)` valide à la fois le nom de queue et la classe du job.
- Template PDF en HTML avec styles inline (DomPDF ne supporte pas les feuilles CSS externes).
- Commission_rate injecté via `config('bookmi.commission_rate')` dans le template (cohérence avec la resource).

### File List
- `database/migrations/2026_02_18_190000_add_contract_path_to_booking_requests_table.php`
- `app/Models/BookingRequest.php` — `contract_path` dans fillable
- `app/Http/Resources/BookingRequestResource.php` — `contract_available` booléen
- `app/Exceptions/BookingException.php` — `contractNotReady()`
- `resources/views/pdf/booking-contract.blade.php` — template HTML/PDF
- `app/Jobs/GenerateContractPdf.php` — queue `media`, DomPDF, Storage
- `app/Services/BookingService.php` — dispatch `GenerateContractPdf`
- `app/Policies/BookingRequestPolicy.php` — `downloadContract()`
- `app/Http/Controllers/Api/V1/BookingRequestController.php` — `contract()`
- `routes/api.php` — GET /booking_requests/{booking}/contract
- `tests/Feature/Booking/BookingContractTest.php` — 7 tests
