# Story 2.3: Connexion email / mot de passe

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a utilisateur enregistré et vérifié,
I want me connecter avec mon email et mot de passe,
so that je puisse accéder à mon compte depuis n'importe quel appareil.

## Acceptance Criteria (AC)

1. **Given** un utilisateur avec un compte vérifié (`phone_verified_at` non null et `is_active` true) **When** il envoie `POST /api/v1/auth/login` avec `email` et `password` valides **Then** un token Sanctum 24h est retourné avec les données utilisateur `{ "data": { "token": "...", "user": {...}, "roles": [...] } }`
2. **Given** un email ou mot de passe incorrect **When** l'utilisateur tente de se connecter **Then** la réponse retourne `422` avec le code `AUTH_INVALID_CREDENTIALS` et un compteur de tentatives échouées est incrémenté
3. **Given** 5 tentatives de connexion échouées consécutives **When** l'utilisateur tente à nouveau **Then** le compte est temporairement bloqué 15 min (NFR20) avec le code `AUTH_ACCOUNT_LOCKED` et `locked_until` dans les détails
4. **Given** un utilisateur dont le téléphone n'est pas vérifié (`phone_verified_at` null) **When** il tente de se connecter **Then** la réponse retourne `422` avec le code `AUTH_PHONE_NOT_VERIFIED`
5. **Given** un utilisateur dont le compte est désactivé (`is_active` false) **When** il tente de se connecter **Then** la réponse retourne `422` avec le code `AUTH_ACCOUNT_DISABLED`
6. **Given** l'endpoint login **Then** le rate limiting est de 10/min (rate limiter `auth` existant)
7. **Given** une connexion réussie **Then** un événement `UserLoggedIn` est dispatché pour le logging

## Tasks / Subtasks

- [x] 1. Créer LoginRequest (AC: #1, #2)
  - [x] 1.1 Créer `app/Http/Requests/Api/LoginRequest.php` avec règles : email (required|email), password (required|string)
  - [x] 1.2 Messages de validation en français

- [x] 2. Créer l'événement UserLoggedIn (AC: #7)
  - [x] 2.1 Créer `app/Events/UserLoggedIn.php` avec propriété `User $user`
  - [x] 2.2 L'événement doit implémenter `ShouldBroadcast` : NON — juste `Dispatchable` pour le logging

- [x] 3. Étendre AuthException — nouveaux codes d'erreur (AC: #2, #4, #5)
  - [x] 3.1 Ajouter `invalidCredentials()` : `AUTH_INVALID_CREDENTIALS` (422) avec `remaining_attempts` dans les détails
  - [x] 3.2 Ajouter `phoneNotVerified()` : `AUTH_PHONE_NOT_VERIFIED` (422)
  - [x] 3.3 Ajouter `accountDisabled()` : `AUTH_ACCOUNT_DISABLED` (422)

- [x] 4. Étendre AuthService — méthode login (AC: #1, #2, #3, #4, #5, #7)
  - [x] 4.1 Ajouter `login(string $email, string $password): array` dans `AuthService`
  - [x] 4.2 Vérifier le lockout : `Cache::get("login_lockout:{$email}")` — si non null → throw `AUTH_ACCOUNT_LOCKED`
  - [x] 4.3 Chercher l'utilisateur par email — si non trouvé → incrémenter tentatives, throw `AUTH_INVALID_CREDENTIALS`
  - [x] 4.4 Vérifier le mot de passe avec `Hash::check()` — si invalide → incrémenter tentatives, throw `AUTH_INVALID_CREDENTIALS`
  - [x] 4.5 Vérifier `phone_verified_at` non null — sinon throw `AUTH_PHONE_NOT_VERIFIED`
  - [x] 4.6 Vérifier `is_active` true — sinon throw `AUTH_ACCOUNT_DISABLED`
  - [x] 4.7 Réinitialiser les compteurs de tentatives, créer token Sanctum 24h, dispatcher `UserLoggedIn`, retourner token + user + roles

- [x] 5. Étendre AuthController (AC: #1)
  - [x] 5.1 Ajouter `login(LoginRequest $request): JsonResponse` — appelle `AuthService::login()`, retourne 200 avec token et user

- [x] 6. Route (AC: #6)
  - [x] 6.1 Ajouter `Route::post('/auth/login', ...)` avec `throttle:auth` middleware, hors du groupe `auth:sanctum`

- [x] 7. Tests (tous les AC)
  - [x] 7.1 Tests Feature : `LoginTest.php` — connexion réussie (200), credentials invalides (422), lockout après 5 tentatives (422), phone non vérifié (422), compte désactivé (422), lockout expiré (200), données manquantes (422), email invalide (422), route a throttle middleware, UserLoggedIn dispatché
  - [x] 7.2 Tests Unit : `AuthServiceLoginTest.php` — login() retourne token+user+roles, lockout incrémente compteur, login() supprime compteurs après succès, phone non vérifié throw exception, compte désactivé throw exception
  - [x] 7.3 Vérifier que les 222 tests existants passent toujours (243 tests au total, 863 assertions)

## Dev Notes

### Architecture & Patterns obligatoires (hérités de Stories 2.1 / 2.2)

- **BaseController** : `AuthController extends BaseController` — déjà en place
- **JSON envelope** : `successResponse($data, $statusCode)` et `errorResponse($code, $message, $statusCode, $details)` — via `ApiResponseTrait`
- **FormRequest** : validation dans `LoginRequest`, pas dans le controller
- **Service Layer** : logique métier dans `AuthService`, le controller appelle le service
- **Rate limiting** : réutiliser le rate limiter `auth` existant (10/min par IP) — `->middleware('throttle:auth')`

### État actuel du code auth (après Stories 2.1 + 2.2)

**AuthService.php** — méthodes existantes :
```php
register(array $data): User         // Crée user, assigne rôle, envoie OTP
verifyOtp(string $phone, string $code): array  // Vérifie OTP, retourne token+user+roles
resendOtp(string $phone): void       // Renvoie OTP (max 3/heure)
sendOtp(string $phone): string       // Génère 6 chiffres, cache, appelle SmsService
```

**AuthController.php** — méthodes existantes :
```php
register(RegisterRequest $request): JsonResponse      // POST /auth/register → 201
verifyOtp(VerifyOtpRequest $request): JsonResponse     // POST /auth/verify-otp → 200
resendOtp(ResendOtpRequest $request): JsonResponse     // POST /auth/resend-otp → 200
```

**AuthException.php** — codes existants :
```php
AuthException::otpExpired()          // AUTH_OTP_EXPIRED (422)
AuthException::otpInvalid($remaining)  // AUTH_OTP_INVALID (422)
AuthException::accountLocked($until, $seconds)  // AUTH_ACCOUNT_LOCKED (422)
AuthException::otpResendLimit()      // AUTH_OTP_RESEND_LIMIT (429)
```

### Cache keys utilisées (Stories 2.1 + 2.2) :
- `otp:{$phone}` → code OTP (TTL: 10 min)
- `otp_attempts:{$phone}` → compteur tentatives OTP (TTL: 15 min)
- `otp_lockout:{$phone}` → ISO8601 string lockout OTP (TTL: 15 min)
- `otp_resend_count:{$phone}` → compteur renvois (TTL: 60 min)

### Nouvelles cache keys pour Story 2.3 :
- `login_attempts:{$email}` → compteur tentatives login échouées (TTL: 15 min — s'aligne avec lockout)
- `login_lockout:{$email}` → ISO8601 string lockout actif (TTL: `config('bookmi.auth.lockout_minutes')` = 15 min)

**Important :** Utiliser l'email comme identifiant de cache (pas le phone) car le login se fait par email.

### Config déjà en place (config/bookmi.php)

```php
'auth' => [
    'token_expiration_hours' => 24,
    'otp_expiration_minutes' => 10,
    'max_login_attempts' => 5,      // Réutiliser pour les tentatives de login
    'lockout_minutes' => 15,
    'otp_max_resend_per_hour' => 3,
],
```

Toutes ces valeurs sont déjà configurées — utiliser `config('bookmi.auth.xxx')` dans le service.

### Token Sanctum — format de réponse (identique à verifyOtp)

```php
$verifiedAt = now(); // ou $user->phone_verified_at
$expirationHours = (int) config('bookmi.auth.token_expiration_hours', 24);
$token = $user->createToken('auth-token', expiresAt: now()->addHours($expirationHours))->plainTextToken;

return $this->successResponse([
    'token' => $token,
    'user' => [
        'id' => $user->id,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'phone' => $user->phone,
        'phone_verified_at' => $user->phone_verified_at->toIso8601String(),
        'is_active' => $user->is_active,
    ],
    'roles' => $user->getRoleNames()->toArray(),
]);
```

**Note :** `phone_verified_at` est garanti non null car on vérifie en amont. Utiliser `->toIso8601String()` directement après vérification.

### Codes d'erreur pour Story 2.3

| Code | Status HTTP | Message FR | Détails |
|------|------------|------------|---------|
| `AUTH_INVALID_CREDENTIALS` | 422 | Identifiants invalides. | `{ "remaining_attempts": N }` |
| `AUTH_ACCOUNT_LOCKED` | 422 | Compte temporairement bloqué après trop de tentatives échouées. | `{ "locked_until": "ISO8601", "remaining_seconds": N }` |
| `AUTH_PHONE_NOT_VERIFIED` | 422 | Veuillez vérifier votre numéro de téléphone avant de vous connecter. | — |
| `AUTH_ACCOUNT_DISABLED` | 422 | Ce compte a été désactivé. | — |

**Note :** `AUTH_ACCOUNT_LOCKED` est déjà implémenté dans `AuthException` — le réutiliser tel quel.

### Logique de login — flow détaillé

```
Tentative de connexion :
  1. Vérifier lockout : Cache::get("login_lockout:{$email}")
     → Si non null : parse ISO8601, calculer remaining_seconds, throw AUTH_ACCOUNT_LOCKED

  2. Chercher l'utilisateur par email : User::where('email', $email)->first()
     → Si non trouvé : incrémenter login_attempts, vérifier lockout, throw AUTH_INVALID_CREDENTIALS
       (NE PAS révéler que l'email n'existe pas — même message que mot de passe incorrect)

  3. Vérifier mot de passe : Hash::check($password, $user->password)
     → Si invalide : incrémenter login_attempts, vérifier lockout, throw AUTH_INVALID_CREDENTIALS

  4. Vérifier phone_verified_at non null
     → Si null : throw AUTH_PHONE_NOT_VERIFIED (ne pas incrémenter les tentatives)

  5. Vérifier is_active true
     → Si false : throw AUTH_ACCOUNT_DISABLED (ne pas incrémenter les tentatives)

  6. Connexion réussie :
     a. Cache::forget("login_attempts:{$email}")
     b. Cache::forget("login_lockout:{$email}")
     c. Créer token Sanctum 24h
     d. Dispatcher UserLoggedIn event
     e. Retourner token + user + roles
```

**Note sécurité :** L'ordre des vérifications est important. Le lockout check vient EN PREMIER pour empêcher toute interaction. Les vérifications email/password utilisent le même message d'erreur pour éviter l'énumération d'utilisateurs.

### Logique de lockout login — helper method

```
Méthode privée handleFailedLoginAttempt(string $email):
  1. attempts = Cache::increment("login_attempts:{$email}")
  2. Si attempts === 1 : Cache::put("login_attempts:{$email}", attempts, 15 min)
  3. maxAttempts = config('bookmi.auth.max_login_attempts')
  4. Si attempts >= maxAttempts :
     a. lockedUntil = now()->addMinutes(config('bookmi.auth.lockout_minutes'))
     b. Cache::put("login_lockout:{$email}", lockedUntil->toIso8601String(), lockedUntil)
     c. Cache::forget("login_attempts:{$email}")
     d. throw AUTH_ACCOUNT_LOCKED
  5. Sinon : throw AUTH_INVALID_CREDENTIALS (remaining_attempts: maxAttempts - attempts)
```

### Événement UserLoggedIn

```php
// app/Events/UserLoggedIn.php
class UserLoggedIn
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
    ) {}
}
```

Pas de listener pour le moment — l'événement est dispatché et peut être écouté plus tard pour audit logging, analytics, etc.

### Leçons de Stories 2.1 / 2.2 — à suivre impérativement

1. **Tests : désactiver les deux classes throttle** dans setUp() :
   ```php
   $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
   ```
2. **Tests : format d'erreur BookMi** — utiliser `assertJsonPath('error.code', 'AUTH_INVALID_CREDENTIALS')` — NE PAS utiliser `assertJsonValidationErrors()`
3. **Tests : seeder obligatoire** — `$this->seed(RoleAndPermissionSeeder::class)` dans setUp()
4. **SQLite defaults** — toujours passer les valeurs explicitement dans `create()`, ne pas se fier aux defaults de migration
5. **Hash::check() pour mots de passe** — déjà constant-time (bcrypt), pas besoin de `hash_equals()`
6. **Cache : atomic operations** — toujours utiliser `Cache::get()` + null check, JAMAIS `Cache::has()` + `Cache::get()` (TOCTOU race)
7. **Cache : ISO8601 strings** — stocker des strings ISO8601 pour les timestamps, PAS des objets Carbon (sérialisation fragile)
8. **phone_verified_at non fillable** — utiliser direct assignment `$user->phone_verified_at = $value` si besoin de modifier
9. **Pint + PHPStan** — lancer à la fin pour vérifier le style et l'analyse statique (phpstan avec `-d memory_limit=512M`)
10. **Event testing** — utiliser `Event::fake()` et `Event::assertDispatched(UserLoggedIn::class)` dans les tests

### User model — champs pertinents pour le login

```php
// $fillable (PAS phone_verified_at !)
protected $fillable = ['first_name', 'last_name', 'email', 'phone', 'password', 'is_admin', 'is_active'];

// $casts
'email_verified_at' => 'datetime',
'phone_verified_at' => 'datetime',
'password' => 'hashed',
'is_admin' => 'boolean',
'is_active' => 'boolean',
```

### Project Structure Notes

**Fichiers à créer :**
```
bookmi/app/Http/Requests/Api/LoginRequest.php
bookmi/app/Events/UserLoggedIn.php
bookmi/tests/Feature/Auth/LoginTest.php
bookmi/tests/Unit/Services/AuthServiceLoginTest.php
```

**Fichiers à modifier :**
```
bookmi/app/Services/AuthService.php — ajouter login()
bookmi/app/Http/Controllers/Api/V1/AuthController.php — ajouter login()
bookmi/app/Exceptions/AuthException.php — ajouter invalidCredentials(), phoneNotVerified(), accountDisabled()
bookmi/routes/api.php — ajouter route POST /auth/login
```

### References

- [Source: _bmad-output/planning-artifacts/epics.md — Epic 2, Story 2.3]
- [Source: _bmad-output/planning-artifacts/architecture.md — Auth flow, Login endpoint, Error codes AUTH_INVALID_CREDENTIALS / AUTH_TOKEN_EXPIRED / AUTH_PHONE_NOT_VERIFIED, Rate limiting, NFR20]
- [Source: _bmad-output/implementation-artifacts/2-2-verification-otp-telephone.md — Dev Notes, Debug Log, Code Review, Lockout pattern, Cache key patterns]
- [Source: _bmad-output/implementation-artifacts/2-1-inscription-client-et-talent.md — Dev Notes, Test patterns, SmsService stub]
- [Source: bookmi/config/bookmi.php — auth config (max_login_attempts, lockout_minutes, token_expiration_hours)]
- [Source: bookmi/app/Services/AuthService.php — existing register(), verifyOtp(), resendOtp(), sendOtp() methods]
- [Source: bookmi/app/Exceptions/AuthException.php — existing static factories, accountLocked() reusable]
- [Source: bookmi/app/Models/User.php — $fillable, $casts, phone_verified_at datetime cast]
- [Source: bookmi/bootstrap/app.php — exception rendering for BookmiException]

## Dev Agent Record

### Agent Model Used

Claude Opus 4.6

### Debug Log References

- PHPStan erreur `Cannot call method toIso8601String() on string` sur `$user->phone_verified_at->toIso8601String()` — corrigé avec PHPDoc `@var \Carbon\Carbon` et variable locale (même pattern que Story 2.2)
- Pint a corrigé `UserLoggedIn.php` : `single_trait_insert_per_statement`, `braces_position`

### Completion Notes List

- Implémentation complète de `POST /api/v1/auth/login` avec token Sanctum 24h
- Login flow sécurisé : lockout check → email lookup → password verify → phone verified → is_active → success
- Lockout : 5 tentatives max → blocage 15 min, cache keys `login_attempts:{email}` et `login_lockout:{email}` (ISO8601)
- Sécurité : même message d'erreur pour email inconnu et mot de passe incorrect (anti-énumération), Cache::get atomique (pas de TOCTOU), Hash::check constant-time
- Événement `UserLoggedIn` dispatché à chaque connexion réussie
- 3 nouveaux codes d'erreur : `AUTH_INVALID_CREDENTIALS`, `AUTH_PHONE_NOT_VERIFIED`, `AUTH_ACCOUNT_DISABLED`
- 21 nouveaux tests (13 Feature + 8 Unit), 243 tests au total, 867 assertions — tous passent
- Pint PASS, PHPStan 0 erreurs

**Code Review Fixes (2026-02-18) :**
- [H1] Normalisation email lowercase dans `login()` pour empêcher le contournement du lockout par variation de casse
- [M1] Ajout assertions de non-incrémentation des tentatives dans les tests `phone_not_verified` et `account_disabled` (Feature + Unit)
- [M2] Extraction `buildAuthResponse()` pour éliminer la duplication entre `login()` et `verifyOtp()`

**Action Items (LOW — à considérer plus tard) :**
- [L1] Test Feature `login_dispatches_user_logged_in_event` ne vérifie pas le statut HTTP 200
- [L2] `remaining_attempts` dans la réponse peut révéler si un email a déjà été tenté (fuite d'information mineure)

### Change Log

- 2026-02-18 : Implémentation Story 2.3 — Connexion email/mot de passe (login endpoint, lockout, event, tests)
- 2026-02-18 : Code Review — 3 fixes appliquées (H1 sécurité, M1 tests, M2 DRY), 2 action items LOW créés

### File List

**Fichiers créés :**
- `bookmi/app/Http/Requests/Api/LoginRequest.php`
- `bookmi/app/Events/UserLoggedIn.php`
- `bookmi/tests/Feature/Auth/LoginTest.php`
- `bookmi/tests/Unit/Services/AuthServiceLoginTest.php`

**Fichiers modifiés :**
- `bookmi/app/Services/AuthService.php` — ajout login(), handleFailedLoginAttempt(), buildAuthResponse() + strtolower email
- `bookmi/app/Http/Controllers/Api/V1/AuthController.php` — ajout login()
- `bookmi/app/Exceptions/AuthException.php` — ajout invalidCredentials(), phoneNotVerified(), accountDisabled()
- `bookmi/routes/api.php` — ajout route POST /auth/login avec throttle:auth
