# Story 2.1: Inscription client et talent

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a visiteur,
I want créer un compte client ou talent avec email et numéro de téléphone,
so that je puisse accéder aux fonctionnalités de BookMi.

## Acceptance Criteria (AC)

1. **Given** un visiteur non inscrit **When** il envoie `POST /api/v1/auth/register` avec `email`, `phone` (+225 format E.164), `password`, `first_name`, `last_name`, `role` (client ou talent), et optionnellement `category_id`/`subcategory_id` (pour talent) **Then** un utilisateur est créé avec le mot de passe haché (bcrypt 12 rounds)
2. **Given** l'inscription réussie **When** le rôle est assigné **Then** il est assigné via Spatie Laravel Permission (client ou talent)
3. **Given** l'inscription réussie **When** le compte est créé **Then** un OTP est envoyé par SMS au numéro de téléphone (OTP stub pour cette story — l'envoi réel est Story 2.2)
4. **Given** l'inscription réussie **Then** la réponse retourne `201 Created` avec `{ "data": { "message": "Compte créé. Vérifiez votre téléphone." } }`
5. **Given** des données invalides **Then** les validations incluent : email unique, phone unique, password min 8 caractères, role in [client,talent], messages en français
6. **Given** un endpoint de registration **Then** le rate limiting est de 10/min sur cet endpoint (rate limiter `auth` existant)
7. **Given** un schema de base de données **Then** la migration ajoute à la table `users` : `first_name`, `last_name`, `phone` (unique, E.164), `phone_verified_at` (nullable timestamp), `is_active` (boolean default true), et supprime `name`

## Tasks / Subtasks

- [x] 1. Installer Spatie Laravel Permission et configurer les rôles (AC: #2)
  - [x] 1.1 `composer require spatie/laravel-permission` — v7.1 installé (PHP 8.4 / Laravel 12 compatible)
  - [x] 1.2 `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"` — config/permission.php + migration créés
  - [x] 1.3 Migration Spatie publiée (tables roles, permissions, model_has_roles, etc.)
  - [x] 1.4 Créé `RoleAndPermissionSeeder` — seed les 7 rôles sur guard `api`
  - [x] 1.5 Créé `app/Enums/UserRole.php` (backed string enum) avec 7 valeurs + `registrableRoles()` + `labels()`
  - [x] 1.6 Ajouté `HasRoles` trait au modèle User + `$guard_name = 'api'`
  - [x] 1.7 Spatie v7 : guard spécifié par rôle dans le seeder (pas de config globale guard_name)

- [x] 2. Migration et modèle User (AC: #1, #7)
  - [x] 2.1 Créé migration `2026_02_18_120000_add_auth_fields_to_users_table` : ajoute `first_name`, `last_name`, `phone` (unique), `phone_verified_at`, `is_active` ; supprime `name`
  - [x] 2.2 Mis à jour `User.php` : `$fillable` (first_name, last_name, phone, is_active, is_admin), `$casts` (phone_verified_at, is_active, is_admin)
  - [x] 2.3 Mis à jour `UserFactory` : first_name/last_name, phone +225 format, is_active

- [x] 3. Créer AuthService (AC: #1, #2, #3)
  - [x] 3.1 Créé `app/Services/AuthService.php` avec `register(array $data): User` et `sendOtp(string $phone): string`
  - [x] 3.2 Logique : DB::transaction, User::create, assignRole, sendOtp (cache + SmsService)
  - [x] 3.3 Créé `app/Services/SmsService.php` — stub qui log seulement
  - [x] 3.4 AuthService injecté via constructor injection (pas de bind explicite nécessaire)

- [x] 4. Créer RegisterRequest (AC: #5)
  - [x] 4.1 Créé `app/Http/Requests/Api/RegisterRequest.php` avec toutes les règles de validation
  - [x] 4.2 Messages de validation en français

- [x] 5. Créer AuthController (AC: #1, #4)
  - [x] 5.1 Créé `app/Http/Controllers/Api/V1/AuthController.php` extends `BaseController`
  - [x] 5.2 Méthode `register(RegisterRequest $request)` → appelle `AuthService::register()`, retourne 201

- [x] 6. Routes et rate limiting (AC: #6)
  - [x] 6.1 Route `POST /api/v1/auth/register` avec `throttle:auth` middleware, hors du groupe `auth:sanctum`
  - [x] 6.2 Rate limiter `auth` existant réutilisé

- [x] 7. Configurer auth guard api pour Sanctum (AC: #2)
  - [x] 7.1 Ajouté guard `api` dans `config/auth.php` (driver sanctum, provider users)
  - [x] 7.2 `config/sanctum.php` : expiration → 1440 (24h)

- [x] 8. Tests (tous les AC)
  - [x] 8.1 Tests Feature : `RegisterTest.php` — 15 tests couvrant tous les scénarios
  - [x] 8.2 Tests Unit : `AuthServiceTest.php` — 5 tests (create user, assign role, OTP cache, SMS call, TTL)
  - [x] 8.3 Tests Unit : `UserRoleEnumTest.php` — 4 tests (count, values, registrable, labels)
  - [x] 8.4 Tous les 168 tests existants passent + 24 nouveaux = 192 tests, 695 assertions

### Review Follow-ups (AI)

- [ ] [AI-Review][MEDIUM] `is_admin` dans `$fillable` de User.php:37 — risque de mass-assignment. Avec Spatie Permission gérant les rôles admin, évaluer si `is_admin` doit rester mass-assignable ou être retiré de `$fillable`. Nécessite analyse de l'impact sur le système admin existant (VerificationController, middleware admin).
- [ ] [AI-Review][LOW] `composer.lock` absent de la File List — incohérence documentaire mineure (présent dans le Change Log mais pas dans la File List).

## Dev Notes

### Architecture & Patterns obligatoires

- **BaseController** : `AuthController extends BaseController` qui utilise `ApiResponseTrait` + `AuthorizesRequests`
- **JSON envelope** : `successResponse($data, $statusCode)` et `errorResponse($code, $message, $statusCode, $details)` — pattern existant dans `ApiResponseTrait.php`
- **FormRequest** : validation dans `RegisterRequest`, pas dans le controller. Pattern identique à `SearchTalentRequest.php` (rules + messages en français + after validators si besoin)
- **Service Layer** : logique métier dans `AuthService`, pas dans le controller
- **Enum backed string** : `UserRole` enum avec valeurs string (`'client'`, `'talent'`, etc.) — même pattern que `TalentLevel.php`, `VerificationStatus.php`, `PackageType.php` déjà dans `app/Enums/`
- **Rate limiting** : le rate limiter `auth` existe déjà dans `AppServiceProvider::configureRateLimiting()` — 10/min par IP. Il suffit d'ajouter `->middleware('throttle:auth')` à la route

### Modèle User existant — modifications nécessaires

Le modèle `User.php` actuel a :
- `$fillable = ['name', 'email', 'password', 'is_admin']`
- Relations : `talentProfile()`, `identityVerifications()`, `identityVerification()`, `activityLogs()`, `favorites()`
- Traits : `HasApiTokens`, `HasFactory`, `Notifiable`

**Modifications :**
1. Ajouter trait `HasRoles` (Spatie)
2. `$fillable` : remplacer `name` par `first_name`, `last_name`, `phone`, `is_active`
3. `$casts` : ajouter `phone_verified_at => datetime`, `is_active => boolean`
4. Toutes les relations existantes doivent être préservées

### Phone E.164 format

- Format stockage : `+2250700000000` (E.164 complet, Côte d'Ivoire)
- Regex validation : `'/^\+225[0-9]{10}$/'` (préfixe +225 + 10 chiffres)
- Le formattage d'affichage (XX XX XX XX XX) est côté Flutter uniquement

### OTP stub pour cette story

- L'envoi réel SMS est dans Story 2.2
- Pour cette story : générer un code 6 chiffres, le stocker dans `Cache::put("otp:{$phone}", $code, now()->addMinutes(config('bookmi.auth.otp_expiration_minutes')))`
- `SmsService::sendOtp()` doit juste `Log::info("OTP $code sent to $phone")` — pas d'intégration réelle
- Config déjà en place : `config('bookmi.auth.otp_expiration_minutes')` = 10

### Spatie Permission v7 — configuration critique

- Guard : `api` (pas `web`). Configurer dans `config/permission.php` → `'guard_name' => 'api'`
- Le User model doit utiliser `HasRoles` trait
- Migration publiée par `vendor:publish` crée les tables `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`
- Seeder : créer les 7 rôles sur le guard `api`
- Registration : `$user->assignRole($validatedData['role'])` — doit correspondre au nom exact dans la table roles

### Config auth.php — ajout guard api

Actuellement `config/auth.php` n'a que le guard `web`. Ajouter :
```php
'guards' => [
    'web' => ['driver' => 'session', 'provider' => 'users'],
    'api' => ['driver' => 'sanctum', 'provider' => 'users'],
],
```

### Config sanctum.php — expiration token

Actuellement `'expiration' => null`. Changer à `'expiration' => 1440` (24h).

### Migration backward-compatibility

- La colonne `name` est utilisée dans le code existant (`UserFactory`, possiblement dans des tests)
- La migration doit : ajouter `first_name`, `last_name`, `phone`, `phone_verified_at`, `is_active` PUIS supprimer `name`
- Mettre à jour `UserFactory` pour utiliser `first_name` / `last_name` au lieu de `name`
- Vérifier TOUS les tests existants qui référencent `name` — les adapter

### Error codes auth

Utiliser le préfixe `AUTH_` pour les codes d'erreur métier. Codes prédéfinis dans l'architecture :
- `AUTH_INVALID_CREDENTIALS` (pour login, Story 2.3)
- `AUTH_TOKEN_EXPIRED` (pour token expiré, Story 2.5)
- `AUTH_PHONE_NOT_VERIFIED` (pour phone non vérifié)
- Validation : utiliser le code standard `VALIDATION_FAILED` via Laravel FormRequest

### Project Structure Notes

**Fichiers à créer :**
```
bookmi/app/Enums/UserRole.php
bookmi/app/Services/AuthService.php
bookmi/app/Services/SmsService.php
bookmi/app/Http/Controllers/Api/V1/AuthController.php
bookmi/app/Http/Requests/Api/RegisterRequest.php
bookmi/database/migrations/xxxx_add_auth_fields_to_users_table.php
bookmi/database/migrations/xxxx_setup_roles_and_permissions.php (via vendor:publish)
bookmi/database/seeders/RoleAndPermissionSeeder.php
bookmi/tests/Feature/Auth/RegisterTest.php
bookmi/tests/Unit/Services/AuthServiceTest.php
bookmi/tests/Unit/Enums/UserRoleEnumTest.php
```

**Fichiers à modifier :**
```
bookmi/app/Models/User.php — ajouter HasRoles, modifier $fillable/$casts
bookmi/database/factories/UserFactory.php — first_name/last_name au lieu de name
bookmi/routes/api.php — ajouter route register
bookmi/config/auth.php — ajouter guard api
bookmi/config/sanctum.php — expiration 1440
bookmi/config/permission.php — guard_name api (fichier créé par vendor:publish)
bookmi/app/Providers/AppServiceProvider.php — bind AuthService (optionnel si pas d'interface)
bookmi/composer.json — spatie/laravel-permission ajouté
```

### References

- [Source: _bmad-output/planning-artifacts/epics.md — Epic 2, Story 2.1]
- [Source: _bmad-output/planning-artifacts/architecture.md — sections Auth, API patterns, Database conventions, Error codes]
- [Source: _bmad-output/planning-artifacts/ux-design-specification.md — Onboarding flows Client/Talent]
- [Source: bookmi/config/bookmi.php — auth config section]
- [Source: bookmi/app/Http/Traits/ApiResponseTrait.php — JSON envelope pattern]
- [Source: bookmi/app/Http/Requests/Api/SearchTalentRequest.php — FormRequest pattern avec messages FR]
- [Source: bookmi/app/Enums/TalentLevel.php — enum backed string pattern]
- [Source: bookmi/app/Models/User.php — modèle actuel à modifier]
- [Source: bookmi/app/Providers/AppServiceProvider.php — rate limiting déjà configuré]

## Dev Agent Record

### Agent Model Used

Claude Opus 4.6 (claude-opus-4-6)

### Debug Log References

- Redis class not found: `ThrottleRequestsWithRedis` middleware requires phpredis — disabled both `ThrottleRequests` and `ThrottleRequestsWithRedis` in tests
- Spatie guard mismatch: Added `$guard_name = 'api'` to User model (Spatie v7 defaults to model guard)
- Validation response format: BookMi custom error envelope uses `error.details.errors.field` not `errors.field`
- SQLite `is_active` default: Added explicit `'is_active' => true` in `AuthService::register()` for test reliability
- AdminVerificationResource: Updated `$this->user->name` → `$this->user->first_name` / `$this->user->last_name` after removing `name` column

### Completion Notes List

- All 7 ACs satisfied
- 192 tests passing (695 assertions) — 24 new tests added, all 168 existing tests still pass
- Pint: 0 violations
- PHPStan: 0 errors
- Spatie v7 installed and configured with 7 roles on guard `api`
- OTP stub implemented (cache-based, SmsService masked log — real SMS in Story 2.2)
- Migration is backward-compatible: data migration for name→first_name/last_name handled

### Code Review (AI) — 2026-02-18

**Reviewer:** Claude Opus 4.6 (adversarial code review)
**Outcome:** APPROVED with fixes applied

**Issues Found:** 2 Critical, 2 High, 3 Medium, 1 Low
**Issues Fixed:** 6 (2C + 2H + 2M)
**Action Items Created:** 2 (1M + 1L)

**Fixes Applied:**
1. **[CRITICAL]** DatabaseSeeder: `'name' => 'Test User'` → `'first_name'/'last_name'` (colonne `name` supprimée)
2. **[CRITICAL]** DatabaseSeeder: Ajout de `RoleAndPermissionSeeder` (sinon rôles absents en production)
3. **[HIGH]** SmsService: OTP code masqué dans les logs (`Log::info("OTP sent to {$phone}")`)
4. **[HIGH]** AuthService: Ajout commentaire PHPDoc expliquant que category_id est validé à l'inscription mais stocké lors de la création du TalentProfile
5. **[MEDIUM]** RegisterTest: Renommé `talent_can_register_with_category` → `talent_can_register_successfully` + assertions renforcées
6. **[MEDIUM]** Migration down(): `CONCAT()` → `||` pour compatibilité SQLite

### Change Log

| Action | File | Description |
|--------|------|-------------|
| CREATE | `app/Enums/UserRole.php` | Backed string enum with 7 roles, registrableRoles(), labels() |
| CREATE | `app/Services/AuthService.php` | register() + sendOtp() with DB transaction |
| CREATE | `app/Services/SmsService.php` | Stub: logs OTP only |
| CREATE | `app/Http/Controllers/Api/V1/AuthController.php` | register() endpoint, extends BaseController |
| CREATE | `app/Http/Requests/Api/RegisterRequest.php` | Validation rules + French messages |
| CREATE | `database/migrations/2026_02_18_110902_create_permission_tables.php` | Spatie permission tables (vendor:publish) |
| CREATE | `database/migrations/2026_02_18_120000_add_auth_fields_to_users_table.php` | first_name, last_name, phone, phone_verified_at, is_active; drops name |
| CREATE | `database/seeders/RoleAndPermissionSeeder.php` | Seeds 7 roles on guard api |
| CREATE | `config/permission.php` | Spatie config (vendor:publish) |
| CREATE | `tests/Feature/Auth/RegisterTest.php` | 15 feature tests |
| CREATE | `tests/Unit/Services/AuthServiceTest.php` | 5 unit tests |
| CREATE | `tests/Unit/Enums/UserRoleEnumTest.php` | 4 unit tests |
| MODIFY | `app/Models/User.php` | Added HasRoles, $guard_name, updated $fillable/$casts |
| MODIFY | `database/factories/UserFactory.php` | first_name/last_name, phone, is_active |
| MODIFY | `routes/api.php` | Added POST /auth/register route |
| MODIFY | `config/auth.php` | Added api guard (sanctum driver) |
| MODIFY | `config/sanctum.php` | expiration: 1440 (24h) |
| MODIFY | `app/Http/Resources/AdminVerificationResource.php` | name → first_name/last_name |
| MODIFY | `composer.json` | Added spatie/laravel-permission ^7.1 |
| MODIFY | `composer.lock` | Updated lockfile |
| REVIEW-FIX | `database/seeders/DatabaseSeeder.php` | Fixed broken name→first_name/last_name + added RoleAndPermissionSeeder call |
| REVIEW-FIX | `app/Services/SmsService.php` | Masked OTP code in log output |
| REVIEW-FIX | `app/Services/AuthService.php` | Added PHPDoc explaining category_id intent |
| REVIEW-FIX | `tests/Feature/Auth/RegisterTest.php` | Renamed misleading test + added assertions |
| REVIEW-FIX | `database/migrations/2026_02_18_120000_...` | CONCAT→\|\| for SQLite compat in down() |

### File List

**Created (12 files):**
- `bookmi/app/Enums/UserRole.php`
- `bookmi/app/Services/AuthService.php`
- `bookmi/app/Services/SmsService.php`
- `bookmi/app/Http/Controllers/Api/V1/AuthController.php`
- `bookmi/app/Http/Requests/Api/RegisterRequest.php`
- `bookmi/database/migrations/2026_02_18_110902_create_permission_tables.php`
- `bookmi/database/migrations/2026_02_18_120000_add_auth_fields_to_users_table.php`
- `bookmi/database/seeders/RoleAndPermissionSeeder.php`
- `bookmi/config/permission.php`
- `bookmi/tests/Feature/Auth/RegisterTest.php`
- `bookmi/tests/Unit/Services/AuthServiceTest.php`
- `bookmi/tests/Unit/Enums/UserRoleEnumTest.php`

**Modified (9 files):**
- `bookmi/app/Models/User.php`
- `bookmi/database/factories/UserFactory.php`
- `bookmi/database/seeders/DatabaseSeeder.php`
- `bookmi/routes/api.php`
- `bookmi/config/auth.php`
- `bookmi/config/sanctum.php`
- `bookmi/app/Http/Resources/AdminVerificationResource.php`
- `bookmi/composer.json`
- `bookmi/composer.lock`
