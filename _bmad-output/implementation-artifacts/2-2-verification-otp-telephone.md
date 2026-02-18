# Story 2.2: Vérification OTP téléphone

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a utilisateur nouvellement inscrit,
I want vérifier mon numéro de téléphone via un code OTP,
so that mon compte soit activé et sécurisé.

## Acceptance Criteria (AC)

1. **Given** un utilisateur inscrit avec un OTP envoyé **When** il envoie `POST /api/v1/auth/verify-otp` avec `phone` et `code` OTP valide **Then** le champ `phone_verified_at` est mis à jour et un token Sanctum 24h est retourné avec les données utilisateur `{ "data": { "token": "...", "user": {...}, "roles": [...] } }`
2. **Given** un code OTP expiré (> 10 min) **When** l'utilisateur tente de vérifier **Then** la réponse retourne `422` avec le code `AUTH_OTP_EXPIRED` et le message en français
3. **Given** un code OTP invalide **When** l'utilisateur tente de vérifier **Then** la réponse retourne `422` avec le code `AUTH_OTP_INVALID` et un compteur de tentatives échouées est incrémenté
4. **Given** 5 tentatives OTP échouées consécutives **When** l'utilisateur tente à nouveau **Then** le compte est temporairement bloqué 15 min (NFR20) avec le code `AUTH_ACCOUNT_LOCKED` et `locked_until` dans les détails
5. **Given** un utilisateur avec un OTP expiré ou non reçu **When** il envoie `POST /api/v1/auth/resend-otp` avec `phone` **Then** un nouvel OTP est généré et envoyé (max 3 envois par heure)
6. **Given** un utilisateur ayant dépassé 3 renvois par heure **When** il demande un renvoi **Then** la réponse retourne `429` avec le code `AUTH_OTP_RESEND_LIMIT`
7. **Given** les endpoints verify-otp et resend-otp **Then** le rate limiting est de 10/min (rate limiter `auth` existant)

## Tasks / Subtasks

- [x] 1. Créer VerifyOtpRequest (AC: #1, #2, #3)
  - [x] 1.1 Créer `app/Http/Requests/Api/VerifyOtpRequest.php` avec règles : phone (required|regex E.164 +225), code (required|string|size:6)
  - [x] 1.2 Messages de validation en français

- [x] 2. Créer ResendOtpRequest (AC: #5)
  - [x] 2.1 Créer `app/Http/Requests/Api/ResendOtpRequest.php` avec règle : phone (required|regex E.164 +225|exists:users,phone)
  - [x] 2.2 Messages de validation en français

- [x] 3. Étendre AuthService — méthode verifyOtp (AC: #1, #2, #3, #4)
  - [x] 3.1 Ajouter `verifyOtp(string $phone, string $code): array` dans `AuthService`
  - [x] 3.2 Vérifier le lockout : si `Cache::has("otp_lockout:{$phone}")` → throw `AUTH_ACCOUNT_LOCKED` avec `locked_until`
  - [x] 3.3 Récupérer l'OTP en cache : `Cache::get("otp:{$phone}")` — si null → `AUTH_OTP_EXPIRED`
  - [x] 3.4 Comparer le code : si mismatch → incrémenter `Cache::increment("otp_attempts:{$phone}")`, vérifier si >= 5 → mettre en lockout (`Cache::put("otp_lockout:{$phone}", true, now()->addMinutes(config('bookmi.auth.lockout_minutes')))`) → throw `AUTH_OTP_INVALID`
  - [x] 3.5 Si code valide → supprimer l'OTP et les compteurs du cache, mettre à jour `phone_verified_at`, créer token Sanctum, retourner `['token' => $token, 'user' => $user, 'roles' => $roles]`

- [x] 4. Étendre AuthService — méthode resendOtp (AC: #5, #6)
  - [x] 4.1 Ajouter `resendOtp(string $phone): void` dans `AuthService`
  - [x] 4.2 Vérifier le rate limit de renvoi : `Cache::get("otp_resend_count:{$phone}")` — si >= `config('bookmi.auth.otp_max_resend_per_hour')` → throw erreur `AUTH_OTP_RESEND_LIMIT`
  - [x] 4.3 Incrémenter le compteur de renvois avec TTL 1 heure
  - [x] 4.4 Appeler `sendOtp($phone)` pour générer et envoyer un nouveau code

- [x] 5. Étendre AuthController (AC: #1, #5)
  - [x] 5.1 Ajouter `verifyOtp(VerifyOtpRequest $request): JsonResponse` — appelle `AuthService::verifyOtp()`, retourne 200 avec token et user
  - [x] 5.2 Ajouter `resendOtp(ResendOtpRequest $request): JsonResponse` — appelle `AuthService::resendOtp()`, retourne 200 avec message

- [x] 6. Routes (AC: #7)
  - [x] 6.1 Ajouter `Route::post('/auth/verify-otp', ...)` avec `throttle:auth` middleware, hors du groupe `auth:sanctum`
  - [x] 6.2 Ajouter `Route::post('/auth/resend-otp', ...)` avec `throttle:auth` middleware, hors du groupe `auth:sanctum`

- [x] 7. Créer AuthException pour les erreurs métier auth (AC: #2, #3, #4, #6)
  - [x] 7.1 Créer `app/Exceptions/AuthException.php` extends `BookmiException` — avec codes `AUTH_OTP_EXPIRED`, `AUTH_OTP_INVALID`, `AUTH_ACCOUNT_LOCKED`, `AUTH_OTP_RESEND_LIMIT`
  - [x] 7.2 Vérifier que `BookmiException` est déjà rendu correctement dans `bootstrap/app.php` — si oui, aucune modification nécessaire

- [x] 8. Tests (tous les AC)
  - [x] 8.1 Tests Feature : `VerifyOtpTest.php` — 13 tests couvrant OTP valide (200), OTP expiré (422), OTP invalide (422), lockout après 5 tentatives (422), vérification après lockout expiré (200), données manquantes (422), phone_verified_at mis à jour, cache nettoyé, route a throttle middleware
  - [x] 8.2 Tests Feature : `ResendOtpTest.php` — 8 tests couvrant renvoi réussi (200), limite 3/heure (429), phone non inscrit (422), données manquantes (422), incrémentation compteur renvois, route a throttle middleware
  - [x] 8.3 Tests Unit : `AuthServiceVerifyOtpTest.php` — 8 tests couvrant verifyOtp() retourne token+user+roles, lockout incrémente compteur, verifyOtp() supprime cache après succès, resendOtp() incrémente compteur renvoi
  - [x] 8.4 Les 192 tests existants passent toujours — total: 221 tests (789 assertions)

## Dev Notes

### Architecture & Patterns obligatoires (hérités de Story 2.1)

- **BaseController** : `AuthController extends BaseController` — déjà en place (Story 2.1)
- **JSON envelope** : `successResponse($data, $statusCode)` et `errorResponse($code, $message, $statusCode, $details)` — via `ApiResponseTrait`
- **FormRequest** : validation dans `VerifyOtpRequest` et `ResendOtpRequest`, pas dans le controller
- **Service Layer** : logique métier dans `AuthService`, le controller appelle le service
- **Rate limiting** : réutiliser le rate limiter `auth` existant (10/min par IP) — `->middleware('throttle:auth')`

### État actuel du code auth (après Story 2.1)

**AuthService.php** — méthodes existantes :
```php
register(array $data): User    // Crée user, assigne rôle, envoie OTP
sendOtp(string $phone): string // Génère 6 chiffres, cache otp:{$phone}, appelle SmsService
```

**AuthController.php** — méthode existante :
```php
register(RegisterRequest $request): JsonResponse // POST /auth/register → 201
```

**Cache keys utilisées (Story 2.1) :**
- `otp:{$phone}` → code OTP 6 chiffres (TTL: 10 min)

**Nouvelles cache keys pour Story 2.2 :**
- `otp_attempts:{$phone}` → compteur tentatives échouées (TTL: 15 min — s'aligne avec lockout)
- `otp_lockout:{$phone}` → flag lockout actif (TTL: `config('bookmi.auth.lockout_minutes')` = 15 min)
- `otp_resend_count:{$phone}` → compteur renvois par heure (TTL: 60 min)

### Config déjà en place (config/bookmi.php)

```php
'auth' => [
    'token_expiration_hours' => 24,
    'otp_expiration_minutes' => 10,
    'max_login_attempts' => 5,      // Utilisé pour OTP failed attempts aussi
    'lockout_minutes' => 15,
    'otp_max_resend_per_hour' => 3,
],
```

Toutes ces valeurs sont déjà configurées — utiliser `config('bookmi.auth.xxx')` dans le service.

### Token Sanctum — format de réponse

Après vérification OTP réussie, créer un token Sanctum et retourner :
```php
$token = $user->createToken('auth-token')->plainTextToken;

return $this->successResponse([
    'token' => $token,
    'user' => [
        'id' => $user->id,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'phone' => $user->phone,
        'phone_verified_at' => $user->phone_verified_at,
        'is_active' => $user->is_active,
    ],
    'roles' => $user->getRoleNames()->toArray(),
]);
```

**Note :** Ne PAS utiliser une Resource class pour cette réponse — la structure est simple et spécifique à l'auth. Retourner directement un array avec les champs nécessaires.

### BookmiException — pattern existant

`app/Exceptions/BookmiException.php` existe déjà et est rendu dans `bootstrap/app.php` via `$e->toArray()`. Créer `AuthException extends BookmiException` pour les erreurs auth métier.

Pattern existant :
```php
class BookmiException extends Exception {
    public function __construct(
        string $message,
        public readonly string $code,
        public readonly int $statusCode,
        public readonly ?array $details = null,
    ) { parent::__construct($message); }
}
```

### Codes d'erreur pour Story 2.2

| Code | Status HTTP | Message FR | Détails |
|------|------------|------------|---------|
| `AUTH_OTP_EXPIRED` | 422 | Le code OTP a expiré. Demandez un nouveau code. | — |
| `AUTH_OTP_INVALID` | 422 | Le code OTP est invalide. | `{ "remaining_attempts": N }` |
| `AUTH_ACCOUNT_LOCKED` | 422 | Compte temporairement bloqué après trop de tentatives échouées. | `{ "locked_until": "ISO8601", "remaining_seconds": N }` |
| `AUTH_OTP_RESEND_LIMIT` | 429 | Limite de renvoi OTP atteinte. Réessayez dans une heure. | — |
| `AUTH_PHONE_NOT_FOUND` | 422 | Aucun compte associé à ce numéro de téléphone. | — |

### Leçons de Story 2.1 — à suivre impérativement

1. **Tests : désactiver les deux classes throttle** dans setUp() :
   ```php
   $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
   ```
2. **Tests : format d'erreur BookMi** — utiliser `assertJsonPath('error.code', 'AUTH_OTP_INVALID')` et `assertJsonPath('error.details.errors.field.0', 'message')` — NE PAS utiliser `assertJsonValidationErrors()`
3. **Tests : seeder obligatoire** — `$this->seed(RoleAndPermissionSeeder::class)` dans setUp() pour tout test qui crée/utilise des users avec rôles
4. **SQLite defaults** — toujours passer les valeurs explicitement dans `create()`, ne pas se fier aux defaults de migration
5. **SmsService** — stub qui log uniquement (sans le code OTP !) : `Log::info("OTP sent to {$phone}")`
6. **Phone regex** — `/^\+225[0-9]{10}$/` — toujours valider côté FormRequest
7. **Pint + PHPStan** — lancer à la fin pour vérifier le style et l'analyse statique

### Logique de lockout — flow détaillé

```
Tentative OTP :
  1. Vérifier lockout : Cache::has("otp_lockout:{$phone}")
     → Si oui : throw AUTH_ACCOUNT_LOCKED (avec locked_until)
  2. Vérifier OTP en cache : Cache::get("otp:{$phone}")
     → Si null : throw AUTH_OTP_EXPIRED
  3. Comparer codes :
     → Si mismatch :
        a. Incrémenter : attempts = Cache::increment("otp_attempts:{$phone}")
        b. Si première incrémentation, set TTL 15 min
        c. Si attempts >= 5 :
           - Cache::put("otp_lockout:{$phone}", true, 15 min)
           - Cache::forget("otp_attempts:{$phone}")
           - Cache::forget("otp:{$phone}")
           - throw AUTH_ACCOUNT_LOCKED
        d. Sinon : throw AUTH_OTP_INVALID (remaining_attempts: 5 - attempts)
     → Si match :
        a. Cache::forget("otp:{$phone}")
        b. Cache::forget("otp_attempts:{$phone}")
        c. Cache::forget("otp_resend_count:{$phone}")
        d. $user->update(['phone_verified_at' => now()])
        e. $token = $user->createToken('auth-token')->plainTextToken
        f. Return token + user + roles
```

### Logique de resend — flow détaillé

```
Demande de renvoi OTP :
  1. Trouver l'utilisateur par phone
     → Si non trouvé : throw AUTH_PHONE_NOT_FOUND
  2. Vérifier limite renvois : Cache::get("otp_resend_count:{$phone}")
     → Si >= 3 : throw AUTH_OTP_RESEND_LIMIT
  3. Incrémenter compteur avec TTL 1h :
     - Si première incrémentation : Cache::put("otp_resend_count:{$phone}", 1, 60 min)
     - Sinon : Cache::increment("otp_resend_count:{$phone}")
  4. Appeler sendOtp($phone) — génère nouveau code, stocke en cache, appelle SmsService
  5. Retourner succès
```

### Project Structure Notes

**Fichiers à créer :**
```
bookmi/app/Http/Requests/Api/VerifyOtpRequest.php
bookmi/app/Http/Requests/Api/ResendOtpRequest.php
bookmi/app/Exceptions/AuthException.php
bookmi/tests/Feature/Auth/VerifyOtpTest.php
bookmi/tests/Feature/Auth/ResendOtpTest.php
bookmi/tests/Unit/Services/AuthServiceVerifyOtpTest.php
```

**Fichiers à modifier :**
```
bookmi/app/Services/AuthService.php — ajouter verifyOtp() et resendOtp()
bookmi/app/Http/Controllers/Api/V1/AuthController.php — ajouter verifyOtp() et resendOtp()
bookmi/routes/api.php — ajouter 2 routes auth
```

### References

- [Source: _bmad-output/planning-artifacts/epics.md — Epic 2, Story 2.2]
- [Source: _bmad-output/planning-artifacts/architecture.md — Auth flow, Error codes, Rate limiting, NFR20]
- [Source: _bmad-output/implementation-artifacts/2-1-inscription-client-et-talent.md — Dev Notes, Debug Log, Code Review]
- [Source: bookmi/config/bookmi.php — auth config (otp_expiration_minutes, max_login_attempts, lockout_minutes, otp_max_resend_per_hour)]
- [Source: bookmi/app/Services/AuthService.php — existing register() and sendOtp() methods]
- [Source: bookmi/app/Exceptions/BookmiException.php — base exception pattern]
- [Source: bookmi/bootstrap/app.php — exception rendering for BookmiException]
- [Source: bookmi/app/Http/Traits/ApiResponseTrait.php — successResponse/errorResponse pattern]

## Dev Agent Record

### Agent Model Used

Claude Opus 4.6

### Debug Log References

- `phone_verified_at` not in User `$fillable` — `$user->update(['phone_verified_at' => now()])` silently ignored. Fixed by direct assignment: `$user->phone_verified_at = $verifiedAt; $user->save();`
- PHPStan error on `$user->phone_verified_at->toIso8601String()` — Larastan sees attribute as string. Fixed by storing Carbon value in local variable: `$verifiedAt = now()` then using `$verifiedAt->toIso8601String()`
- Pint fixed 1 unused import (`SmsService`) in `AuthServiceVerifyOtpTest.php`

### Completion Notes List

- All 7 ACs covered by 29 new tests (13 Feature VerifyOtp + 8 Feature ResendOtp + 8 Unit AuthService)
- AuthException uses static factory methods for clean error creation (otpExpired, otpInvalid, accountLocked, otpResendLimit)
- Lockout stores ISO 8601 string in cache (not boolean `true` as suggested in story notes, nor Carbon object) to provide accurate `locked_until` and `remaining_seconds` while avoiding serialization fragility
- Token expiration uses `expiresAt` named parameter on `createToken()` per Sanctum API
- ResendOtp validation uses `exists:users,phone` rule in FormRequest — AUTH_PHONE_NOT_FOUND handled via VALIDATION_FAILED (consistent with framework pattern)
- All config values read from `config('bookmi.auth.xxx')` — no hardcoded values

### Change Log

| File | Action | Description |
|------|--------|-------------|
| `app/Http/Requests/Api/VerifyOtpRequest.php` | Created | FormRequest: phone (E.164 +225), code (regex 6 digits), French messages |
| `app/Http/Requests/Api/ResendOtpRequest.php` | Created | FormRequest: phone (E.164 +225, exists:users), French messages |
| `app/Exceptions/AuthException.php` | Created | Extends BookmiException, 4 static factories for auth error codes |
| `app/Services/AuthService.php` | Modified | Added `verifyOtp()` and `resendOtp()` methods |
| `app/Http/Controllers/Api/V1/AuthController.php` | Modified | Added `verifyOtp()` and `resendOtp()` actions |
| `routes/api.php` | Modified | Added POST `/auth/verify-otp` and `/auth/resend-otp` with throttle:auth |
| `tests/Feature/Auth/VerifyOtpTest.php` | Created | 14 tests: success, expiry, invalid, lockout, validation (incl. non-digit), middleware |
| `tests/Feature/Auth/ResendOtpTest.php` | Created | 8 tests: success, limit, validation, middleware |
| `tests/Unit/Services/AuthServiceVerifyOtpTest.php` | Created | 8 tests: verifyOtp + resendOtp service logic |

### File List

- bookmi/app/Http/Requests/Api/VerifyOtpRequest.php
- bookmi/app/Http/Requests/Api/ResendOtpRequest.php
- bookmi/app/Exceptions/AuthException.php
- bookmi/app/Services/AuthService.php
- bookmi/app/Http/Controllers/Api/V1/AuthController.php
- bookmi/routes/api.php
- bookmi/app/Http/Requests/Api/VerifyOtpRequest.php
- bookmi/app/Http/Requests/Api/ResendOtpRequest.php
- bookmi/app/Exceptions/AuthException.php
- bookmi/app/Services/AuthService.php
- bookmi/app/Http/Controllers/Api/V1/AuthController.php
- bookmi/routes/api.php
- bookmi/tests/Feature/Auth/VerifyOtpTest.php
- bookmi/tests/Feature/Auth/ResendOtpTest.php
- bookmi/tests/Unit/Services/AuthServiceVerifyOtpTest.php

## Code Review (AI) — 2026-02-18

**Reviewer:** Claude Opus 4.6 (adversarial)
**Issues Found:** 2 High, 2 Medium, 1 Low
**Issues Fixed:** 4 (all HIGH + MEDIUM)
**Action Items Created:** 1 (LOW)

### Findings & Fixes Applied

| # | Severity | Issue | File:Line | Fix |
|---|----------|-------|-----------|-----|
| H1 | HIGH | TOCTOU race: `Cache::has()` + `Cache::get()` sur lockout — crash si la clé expire entre les deux appels | `AuthService.php:58-59` | Remplacé par `Cache::get()` + null check |
| H2 | HIGH | Comparaison OTP `!==` vulnérable aux timing attacks | `AuthService.php:72` | Remplacé par `hash_equals()` |
| M1 | MEDIUM | `size:6` accepte des caractères non-numériques ("abcdef") comme code OTP | `VerifyOtpRequest.php:21` | Remplacé par `regex:/^[0-9]{6}$/`, ajouté test `non_digit_code_returns_422` |
| M2 | MEDIUM | Objet Carbon stocké en cache pour lockout — sérialisation fragile | `AuthService.php:85-86` | Stocke ISO 8601 string, parse avec `Carbon::parse()` à la lecture |

### Review Follow-ups (AI)

- [ ] [AI-Review][LOW] `AUTH_PHONE_NOT_FOUND` listé dans les codes d'erreur story mais non implémenté — `ResendOtpRequest` utilise `exists:users,phone` → retourne `VALIDATION_FAILED` au lieu de `AUTH_PHONE_NOT_FOUND`. Évaluer si un code d'erreur dédié est nécessaire pour la cohérence API. [AuthException.php]

### Post-Review Test Results

- 222 tests passed (792 assertions)
- Pint: pass
- PHPStan: 0 errors
