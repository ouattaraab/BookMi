# Story 2.5: Déconnexion et gestion token

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a utilisateur connecté,
I want me déconnecter et révoquer mon token, et consulter mes informations de profil,
so that mon compte reste sécurisé après utilisation et que je puisse vérifier mon identité. (FR9)

## Acceptance Criteria (AC)

1. **Given** un utilisateur authentifié avec un token Sanctum valide **When** il envoie `POST /api/v1/auth/logout` **Then** le token courant est révoqué **And** la réponse retourne `200` avec un message de confirmation
2. **Given** un utilisateur authentifié **When** il envoie `GET /api/v1/me` **Then** la réponse retourne `200` avec les informations utilisateur (profil, rôles, permissions)
3. **Given** un token révoqué ou expiré **When** l'utilisateur tente d'accéder à une route protégée (`/auth/logout`, `/me`) **Then** la réponse retourne `401` avec le code `UNAUTHENTICATED`
4. **Given** les tokens Sanctum **Then** ils expirent automatiquement après 24h (ARCH-AUTH-1, configuré via `config('bookmi.auth.token_expiration_hours')`)
5. **Given** une déconnexion réussie **Then** un événement `UserLoggedOut` est dispatché (cohérence avec `UserLoggedIn` de Story 2.3)
6. **Given** les endpoints `/auth/logout` et `/me` **Then** ils sont protégés par le middleware `auth:sanctum` et `throttle:auth`

## Tasks / Subtasks

- [x] 1. Créer l'événement `UserLoggedOut` (AC: #5)
  - [x] 1.1 Créer `app/Events/UserLoggedOut.php` avec propriété `User $user` et traits `Dispatchable`, `SerializesModels`
  - [x] 1.2 Suivre le pattern établi par `UserLoggedIn` et `PasswordReset` (Story 2.3 / 2.4)

- [x] 2. Ajouter `logout()` dans AuthService (AC: #1, #5)
  - [x] 2.1 Méthode `logout(User $user): void` — révoquer le token courant via `$user->currentAccessToken()->delete()`
  - [x] 2.2 Dispatcher l'événement `UserLoggedOut` après révocation
  - [x] 2.3 NE PAS révoquer TOUS les tokens — uniquement le token courant (différent de resetPassword qui révoque tout)

- [x] 3. Ajouter `getProfile()` dans AuthService (AC: #2)
  - [x] 3.1 Méthode `getProfile(User $user): array` — retourner les informations utilisateur
  - [x] 3.2 Inclure : id, first_name, last_name, email, phone, phone_verified_at, is_active
  - [x] 3.3 Inclure les rôles via `$user->getRoleNames()->toArray()`
  - [x] 3.4 Inclure les permissions via `$user->getAllPermissions()->pluck('name')->toArray()`

- [x] 4. Étendre AuthController (AC: #1, #2)
  - [x] 4.1 Ajouter `logout(Request $request): JsonResponse` — appeler `AuthService::logout($request->user())`, retourner 200 avec message
  - [x] 4.2 Ajouter `me(Request $request): JsonResponse` — appeler `AuthService::getProfile($request->user())`, retourner 200 avec données

- [x] 5. Ajouter les routes dans le groupe `auth:sanctum` (AC: #1, #2, #6)
  - [x] 5.1 Route `POST /api/v1/auth/logout` avec middleware `auth:sanctum` et `throttle:auth` — nommée `auth.logout`
  - [x] 5.2 Route `GET /api/v1/me` avec middleware `auth:sanctum` et `throttle:auth` — nommée `me`
  - [x] 5.3 Les deux routes DANS le groupe `auth:sanctum` (routes protégées existantes lignes 45-65 de `routes/api.php`)

- [x] 6. Vérifier l'expiration automatique des tokens 24h (AC: #3, #4)
  - [x] 6.1 Vérifier que `config/sanctum.php` a `'expiration' => 1440` (24h en minutes)
  - [x] 6.2 Vérifier que `config/bookmi.php` a `'token_expiration_hours' => 24`
  - [x] 6.3 Vérifier que `buildAuthResponse()` dans AuthService utilise `expiresAt: now()->addHours($expirationHours)`
  - [x] 6.4 Vérifier que `bootstrap/app.php` gère `AuthenticationException` → JSON 401 avec code `UNAUTHENTICATED`
  - [x] 6.5 S'assurer que le middleware Sanctum `EnsureFrontendRequestsAreStateful` ou la vérification de token expiry fonctionne en test

- [x] 7. Tests (tous les AC)
  - [x] 7.1 Tests Feature : `LogoutTest.php` — 6 tests (logout réussi 200, token révoqué, UserLoggedOut event, token invalide 401, sans token 401, throttle middleware)
  - [x] 7.2 Tests Feature : `MeTest.php` — 7 tests (profil retourné 200, rôles inclus, permissions incluses, format réponse correct, sans token 401, token expiré 401, throttle middleware)
  - [x] 7.3 Tests Unit : `AuthServiceLogoutMeTest.php` — 6 tests (logout révoque token courant, logout ne révoque PAS les autres tokens, logout dispatche event, getProfile retourne données correctes, getProfile inclut rôles, getProfile inclut permissions)
  - [x] 7.4 Vérifier que les 271 tests existants passent toujours
  - [x] 7.5 Total attendu après Story 2.5 : ~290 tests (271 existants + 19 nouveaux)

## Dev Notes

### Architecture & Patterns obligatoires (hérités de Stories 2.1 / 2.2 / 2.3 / 2.4)

- **BaseController** : `AuthController extends BaseController` — déjà en place
- **JSON envelope** : `successResponse($data, $statusCode)` et `errorResponse($code, $message, $statusCode, $details)` — via `ApiResponseTrait`
- **FormRequest** : pas nécessaire pour logout (pas de body) ni me (GET sans params) — utiliser `Request $request` directement
- **Service Layer** : logique métier dans `AuthService`, le controller appelle le service
- **Anti-énumération** : pas applicable ici (routes protégées, utilisateur identifié)
- **Email normalization** : pas applicable ici (pas d'email en input)

### Token Sanctum — infrastructure existante

**config/sanctum.php** — expiration configurée :
```php
'expiration' => 1440, // 24 heures en minutes
```

**config/bookmi.php** — token_expiration_hours :
```php
'auth' => [
    'token_expiration_hours' => 24,
    // ...
],
```

**AuthService.php** — buildAuthResponse() existant :
```php
private function buildAuthResponse(User $user, ?Carbon $phoneVerifiedAtOverride = null): array
{
    $expirationHours = (int) config('bookmi.auth.token_expiration_hours', 24);
    $token = $user->createToken('auth-token', expiresAt: now()->addHours($expirationHours))->plainTextToken;

    return [
        'token' => $token,
        'user' => [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'phone_verified_at' => $phoneVerifiedAt->toIso8601String(),
            'is_active' => $user->is_active,
        ],
        'roles' => $user->getRoleNames()->toArray(),
    ];
}
```

**bootstrap/app.php** — gestion AuthenticationException déjà en place :
```php
$exceptions->render(function (AuthenticationException $e, Request $request) {
    if ($request->expectsJson() || $request->is('api/*') || $request->is('admin/*')) {
        return response()->json([
            'error' => [
                'code' => 'UNAUTHENTICATED',
                'message' => 'Non authentifié.',
                'status' => 401,
                'details' => new \stdClass(),
            ],
        ], 401);
    }
});
```

### État actuel du code auth (après Stories 2.1 + 2.2 + 2.3 + 2.4)

**AuthService.php** — méthodes existantes :
```php
register(array $data): User
verifyOtp(string $phone, string $code): array
resendOtp(string $phone): void
login(string $email, string $password): array
forgotPassword(string $email): void
resetPassword(string $email, string $token, string $password): void
buildAuthResponse(User $user, ?Carbon $phoneVerifiedAtOverride = null): array  // private
handleFailedLoginAttempt(string $email): never  // private
sendOtp(string $phone): string
```

**AuthController.php** — méthodes existantes :
```php
register(RegisterRequest $request): JsonResponse      // POST /auth/register → 201
verifyOtp(VerifyOtpRequest $request): JsonResponse     // POST /auth/verify-otp → 200
resendOtp(ResendOtpRequest $request): JsonResponse     // POST /auth/resend-otp → 200
login(LoginRequest $request): JsonResponse             // POST /auth/login → 200
forgotPassword(ForgotPasswordRequest $request): JsonResponse  // POST /auth/forgot-password → 200
resetPassword(ResetPasswordRequest $request): JsonResponse    // POST /auth/reset-password → 200
```

**AuthException.php** — codes existants :
```php
AuthException::otpExpired()                  // AUTH_OTP_EXPIRED (422)
AuthException::otpInvalid($remaining)        // AUTH_OTP_INVALID (422)
AuthException::accountLocked($until, $secs)  // AUTH_ACCOUNT_LOCKED (422)
AuthException::otpResendLimit()              // AUTH_OTP_RESEND_LIMIT (429)
AuthException::invalidCredentials($remaining)// AUTH_INVALID_CREDENTIALS (422)
AuthException::phoneNotVerified()            // AUTH_PHONE_NOT_VERIFIED (422)
AuthException::accountDisabled()             // AUTH_ACCOUNT_DISABLED (422)
AuthException::resetTokenInvalid()           // AUTH_RESET_TOKEN_INVALID (422)
AuthException::resetThrottled()              // AUTH_RESET_THROTTLED (429)
```

**Events existants** :
```php
App\Events\UserLoggedIn   // dispatché dans login() (Story 2.3)
App\Events\PasswordReset  // dispatché dans resetPassword() (Story 2.4)
```

### Routes existantes (routes/api.php)

```php
// Routes publiques (sans auth)
Route::post('/auth/register', ...)->middleware('throttle:auth');
Route::post('/auth/verify-otp', ...)->middleware('throttle:auth');
Route::post('/auth/resend-otp', ...)->middleware('throttle:auth');
Route::post('/auth/login', ...)->middleware('throttle:auth');
Route::post('/auth/forgot-password', ...)->middleware('throttle:forgot-password');
Route::post('/auth/reset-password', ...)->middleware('throttle:auth');

// Routes protégées (auth:sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // endpoints existants pour talents, favoris, etc.
});
```

### Clarification : Error code 401 — UNAUTHENTICATED vs AUTH_TOKEN_EXPIRED

L'architecture mentionne `AUTH_TOKEN_EXPIRED` dans le diagramme de séquence auth, mais l'implémentation actuelle dans `bootstrap/app.php` retourne `UNAUTHENTICATED` pour TOUTES les `AuthenticationException` (token manquant, révoqué, OU expiré). Sanctum ne distingue pas ces cas — il lève la même `AuthenticationException`.

**Décision Story 2.5 :** Tester pour le code `UNAUTHENTICATED` (comportement réel actuel). Les tests doivent asserter `$response->assertJsonPath('error.code', 'UNAUTHENTICATED')` pour les cas 401.

### Format réponse `/me` — structure JSON finale

Après wrapping par `ApiResponseTrait::successResponse()`, la réponse JSON sera :
```json
{
  "data": {
    "user": {
      "id": 42,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "phone": "+2250700000001",
      "phone_verified_at": "2026-02-18T10:00:00+00:00",
      "is_active": true
    },
    "roles": ["client"],
    "permissions": ["view-bookings", "create-bookings"]
  }
}
```

**Tests doivent asserter :**
- `$response->assertJsonPath('data.user.id', $user->id)`
- `$response->assertJsonPath('data.roles', ['client'])`
- `$response->assertJsonPath('data.permissions', [...])`

### Implémentation recommandée — logout

```php
// AuthService.php — AJOUTER cet import en haut du fichier
use App\Events\UserLoggedOut;

// Méthode à ajouter
public function logout(User $user): void
{
    $user->currentAccessToken()->delete();
    UserLoggedOut::dispatch($user);
}
```

**IMPORTANT** : Utiliser `currentAccessToken()->delete()` (pas `tokens()->delete()`) pour révoquer UNIQUEMENT le token utilisé pour cette requête. Les autres sessions de l'utilisateur restent actives. `tokens()->delete()` est réservé à `resetPassword()` qui révoque TOUT par sécurité.

### Implémentation recommandée — getProfile / me

```php
// AuthService.php
public function getProfile(User $user): array
{
    return [
        'user' => [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'phone_verified_at' => $user->phone_verified_at?->toIso8601String(),
            'is_active' => $user->is_active,
        ],
        'roles' => $user->getRoleNames()->toArray(),
        'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
    ];
}
```

**Note** : Le endpoint `/me` ne retourne PAS de token (différent de `buildAuthResponse`). Il retourne les infos du profil + rôles + permissions. Les permissions Spatie sont incluses car le frontend en aura besoin pour le contrôle d'accès UI (Story 2.6).

### Implémentation recommandée — controller

```php
// AuthController.php — AJOUTER cet import en haut du fichier
use Illuminate\Http\Request;  // NOUVEAU — nécessaire pour logout() et me()

// Méthodes à ajouter
public function logout(Request $request): JsonResponse
{
    $this->authService->logout($request->user());

    return $this->successResponse([
        'message' => 'Déconnexion réussie.',
    ]);
}

public function me(Request $request): JsonResponse
{
    $profile = $this->authService->getProfile($request->user());

    return $this->successResponse($profile);
}
```

**Note** : Pas de FormRequest nécessaire — `logout` n'a pas de body, `me` est un GET sans paramètres. L'injection `$request->user()` est fournie par le middleware `auth:sanctum`.

### Implémentation recommandée — routes

**Ajouter AU DÉBUT du groupe `auth:sanctum` existant** (ligne 45 de `routes/api.php`, avant les routes talent_profiles) :
```php
// routes/api.php — DANS le groupe prefix('v1')->name('api.v1.') existant
// ET DANS le sous-groupe middleware('auth:sanctum') existant (ligne 45)
Route::middleware('auth:sanctum')->group(function () {
    // ═══ Auth routes protégées (Story 2.5) ═══
    Route::post('/auth/logout', [AuthController::class, 'logout'])
        ->middleware('throttle:auth')
        ->name('auth.logout');  // → nom complet: api.v1.auth.logout

    Route::get('/me', [AuthController::class, 'me'])
        ->middleware('throttle:auth')
        ->name('me');  // → nom complet: api.v1.me

    // ═══ Routes existantes ═══
    // Route::post('/talent_profiles', ...  // existant
    // ...
});
```

**NE PAS créer un second `Route::middleware('auth:sanctum')->group()`.** Ajouter les routes dans le groupe existant.

**ATTENTION noms de routes dans les tests** — le préfixe `api.v1.` est ajouté automatiquement par le groupe parent `Route::prefix('v1')->name('api.v1.')`. Les tests doivent utiliser le nom COMPLET :
- `api.v1.auth.logout` (pas `auth.logout`)
- `api.v1.me` (pas `me`)

### Token expiration — vérification en test

Sanctum vérifie l'expiration via DEUX mécanismes :
1. **`expires_at` column** sur `personal_access_tokens` — défini par `createToken(..., expiresAt: ...)`
2. **`config('sanctum.expiration')` + `last_used_at`** — expiration globale (1440 min = 24h)

Pour le test, créer un token avec `expiresAt: now()->subMinute()` suffit car Sanctum vérifie `expires_at` en priorité. Le token sera rejeté même si `last_used_at` est récent.

```php
// Test token expiré — fonctionne car expires_at est dans le passé
$token = $user->createToken('test', expiresAt: now()->subMinute())->plainTextToken;
$response = $this->withHeader('Authorization', 'Bearer ' . $token)
    ->getJson('/api/v1/me');
$response->assertStatus(401)
    ->assertJsonPath('error.code', 'UNAUTHENTICATED');
```

### Tests Feature — patterns à suivre

```php
// LogoutTest.php — setUp
protected function setUp(): void
{
    parent::setUp();
    $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
    $this->seed(RoleAndPermissionSeeder::class);
}

// Helper pour créer un utilisateur authentifié avec token
private function createAuthenticatedUser(): array
{
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
        'phone' => '+2250700000001',
        'phone_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole('client');

    $expirationHours = (int) config('bookmi.auth.token_expiration_hours', 24);
    $token = $user->createToken('auth-token', expiresAt: now()->addHours($expirationHours))->plainTextToken;

    return ['user' => $user, 'token' => $token];
}

// Helper pour requête authentifiée
private function authenticatedPost(string $url, string $token, array $data = [])
{
    return $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson($url, $data);
}
```

**IMPORTANT** : Utiliser `$this->withHeader('Authorization', 'Bearer ' . $token)` pour simuler un utilisateur authentifié dans les tests Feature, OU utiliser `$this->actingAs($user, 'sanctum')` (plus simple avec Sanctum).

**Pattern Sanctum recommandé pour tests** :
```php
// actingAs simplifie l'authentification en test
$this->actingAs($user, 'sanctum')
    ->getJson('/api/v1/me')
    ->assertStatus(200);
```

Cependant, pour tester le logout qui doit révoquer le TOKEN COURANT, il faut utiliser un vrai token :
```php
// Logout test — besoin d'un vrai token pour vérifier la révocation
$token = $user->createToken('auth-token', expiresAt: now()->addHours(24))->plainTextToken;
$this->withHeader('Authorization', 'Bearer ' . $token)
    ->postJson('/api/v1/auth/logout')
    ->assertStatus(200);

// Vérifier que ce token est révoqué
$this->withHeader('Authorization', 'Bearer ' . $token)
    ->getJson('/api/v1/me')
    ->assertStatus(401);
```

### Unit test logout — mock du token courant

`$user->currentAccessToken()` retourne `null` hors contexte HTTP. Pour tester en unit, utiliser `withAccessToken()` de Sanctum :

```php
// Créer un vrai token en DB
$newToken = $user->createToken('auth-token', expiresAt: now()->addHours(24));
$accessToken = $newToken->accessToken; // PersonalAccessToken model

// Simuler le token courant
$user->withAccessToken($accessToken);

// Maintenant $user->currentAccessToken() retourne $accessToken
$this->authService->logout($user);

// Vérifier que CE token est supprimé
$this->assertNull($accessToken->fresh());
```

Pour tester que les AUTRES tokens ne sont PAS supprimés :
```php
$token1 = $user->createToken('session-1', expiresAt: now()->addHours(24));
$token2 = $user->createToken('session-2', expiresAt: now()->addHours(24));

$user->withAccessToken($token1->accessToken);
$this->authService->logout($user);

// Token 1 supprimé, token 2 intact
$this->assertNull($token1->accessToken->fresh());
$this->assertNotNull($token2->accessToken->fresh());
```

### Leçons de Stories 2.1 / 2.2 / 2.3 / 2.4 — à suivre impérativement

1. **Tests : désactiver les deux classes throttle** dans setUp() :
   ```php
   $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
   ```
2. **Tests : format d'erreur BookMi** — utiliser `assertJsonPath('error.code', ...)` — NE PAS utiliser `assertJsonValidationErrors()`
3. **Tests : seeder obligatoire** — `$this->seed(RoleAndPermissionSeeder::class)` dans setUp()
4. **Tests : toujours asserter le status HTTP** même quand on teste des side-effects (fix M3 Story 2.4)
5. **Imports** : toujours utiliser les facades (`use Illuminate\Support\Facades\DB;`), jamais les alias globaux (`\DB`) (fix M1 Story 2.4)
6. **Feature tests HTTP** : tester TOUS les AC au niveau HTTP, pas seulement au niveau service (fix M2 Story 2.4)
7. **Pint + PHPStan** — lancer à la fin (`--memory-limit=512M` pour PHPStan, pas `-d memory_limit`)
8. **Event testing** — `Event::fake()` + `Event::assertDispatched(EventClass::class)` dans les tests
9. **SQLite defaults** — toujours passer les valeurs explicitement dans `create()`, ne pas se fier aux defaults

### User model — traits et relations pertinents

```php
// Extends Authenticatable (implements CanResetPassword)
// Traits: HasApiTokens, HasFactory, HasRoles, Notifiable

// HasApiTokens fournit :
// - createToken(string $name, array $abilities, ?CarbonInterface $expiresAt)
// - tokens() → HasMany<PersonalAccessToken>
// - currentAccessToken() → Token courant de la requête

// HasRoles (Spatie) fournit :
// - getRoleNames() → Collection
// - getAllPermissions() → Collection<Permission>
// - hasRole(string $role) → bool

protected $fillable = ['first_name', 'last_name', 'email', 'phone', 'password', 'is_admin', 'is_active'];
protected string $guard_name = 'api';

// $casts
'password' => 'hashed',
```

### Différence logout vs resetPassword pour la révocation de tokens

| Opération | Méthode de révocation | Raison |
|-----------|----------------------|--------|
| `logout()` | `$user->currentAccessToken()->delete()` | Révoquer uniquement la session courante, les autres appareils restent connectés |
| `resetPassword()` | `$user->tokens()->delete()` | Révoquer TOUTES les sessions — le mot de passe a changé, sécurité maximale |

### Scope backend uniquement

**Note** : L'AC du epic mentionne "un Dio interceptor côté Flutter intercepte tout 401, vide le secure storage, et redirige vers login". Ce comportement Flutter est **hors scope** de cette Story 2.5 (backend) et sera implémenté dans la **Story 2.6 — Écrans authentification Flutter**.

### Project Structure Notes

**Fichiers à créer :**
```
bookmi/app/Events/UserLoggedOut.php
bookmi/tests/Feature/Auth/LogoutTest.php
bookmi/tests/Feature/Auth/MeTest.php
bookmi/tests/Unit/Services/AuthServiceLogoutMeTest.php
```

**Fichiers à modifier :**
```
bookmi/app/Services/AuthService.php — ajouter logout(), getProfile()
bookmi/app/Http/Controllers/Api/V1/AuthController.php — ajouter logout(), me()
bookmi/routes/api.php — ajouter 2 routes dans le groupe auth:sanctum
```

### References

- [Source: _bmad-output/planning-artifacts/epics.md — Epic 2, Story 2.5]
- [Source: _bmad-output/planning-artifacts/architecture.md — ARCH-AUTH-1 (token 24h), Auth flow, Sanctum config, API endpoints]
- [Source: _bmad-output/implementation-artifacts/2-4-reinitialisation-mot-de-passe.md — Dev Notes, Code Review, Patterns hérités]
- [Source: _bmad-output/implementation-artifacts/2-3-connexion-email-mot-de-passe.md — Login patterns, UserLoggedIn event, buildAuthResponse()]
- [Source: bookmi/config/sanctum.php — Token expiration 1440 minutes (24h)]
- [Source: bookmi/config/bookmi.php — auth.token_expiration_hours: 24]
- [Source: bookmi/config/auth.php — Guards: api (sanctum), web (session)]
- [Source: bookmi/app/Services/AuthService.php — buildAuthResponse(), resetPassword() token revocation pattern]
- [Source: bookmi/bootstrap/app.php — AuthenticationException → 401 JSON handler]
- [Source: bookmi/routes/api.php — auth:sanctum middleware group]

## Dev Agent Record

### Agent Model Used

Claude Opus 4.6

### Debug Log References

- PHPStan found `phone_verified_at?->toIso8601String()` type error — fixed with `@var` annotation
- LogoutTest `logout_revokes_current_token` failed initially — Sanctum caches auth guard between requests in same test; fixed with `$this->app['auth']->forgetGuards()` + `assertDatabaseCount`

### Completion Notes List

- 294 tests, 1003 assertions — ALL PASS (271 existing + 23 new)
- Pint: PASS
- PHPStan: 0 errors
- Task 6 verification: `config/sanctum.php` expiration=1440, `config/bookmi.php` token_expiration_hours=24, `buildAuthResponse()` uses `expiresAt:`, `bootstrap/app.php` handles AuthenticationException → 401 UNAUTHENTICATED

### Change Log

- Created `app/Events/UserLoggedOut.php` — event following UserLoggedIn pattern
- Modified `app/Services/AuthService.php` — added `logout()`, `getProfile()` methods + `UserLoggedOut` import
- Modified `app/Http/Controllers/Api/V1/AuthController.php` — added `logout()`, `me()` methods + `Request` import
- Modified `routes/api.php` — added `POST /auth/logout` and `GET /me` routes in `auth:sanctum` group with `throttle:auth`
- Created `tests/Feature/Auth/LogoutTest.php` — 8 tests
- Created `tests/Feature/Auth/MeTest.php` — 9 tests
- Created `tests/Unit/Services/AuthServiceLogoutMeTest.php` — 6 tests

### File List

- `bookmi/app/Events/UserLoggedOut.php` (CREATED)
- `bookmi/app/Services/AuthService.php` (MODIFIED)
- `bookmi/app/Http/Controllers/Api/V1/AuthController.php` (MODIFIED)
- `bookmi/routes/api.php` (MODIFIED)
- `bookmi/tests/Feature/Auth/LogoutTest.php` (CREATED)
- `bookmi/tests/Feature/Auth/MeTest.php` (CREATED)
- `bookmi/tests/Unit/Services/AuthServiceLogoutMeTest.php` (CREATED)

## Senior Developer Review

**Reviewer:** Claude Opus 4.6 | **Date:** 2026-02-18

### Findings Summary

| # | Severity | Description | Status |
|---|----------|-------------|--------|
| M1 | MEDIUM | `LogoutTest:67` — `assertDatabaseCount` fragile, remplacé par `assertDatabaseMissing` avec ID spécifique | FIXED |
| M2 | MEDIUM | `MeTest` — pas de test multi-rôle ni permissions réelles, ajouté `me_includes_multiple_roles` et `me_includes_real_permissions_via_role` | FIXED |
| M3 | MEDIUM | `LogoutTest` — test manquant pour token expiré sur `/auth/logout`, ajouté `logout_with_expired_token_returns_401` | FIXED |
| M4 | MEDIUM | `MeTest::createAuthenticatedUser` créait un token inutilisé, refactoré en `createVerifiedUser()` | FIXED |
| L1 | LOW | `AuthService::logout()` — pas de null-safety sur `currentAccessToken()`, sûr derrière middleware mais risqué si appelé directement | ACTION ITEM |
| L2 | LOW | `MeTest::me_returns_200_with_user_profile` — assertions `first_name`/`last_name` manquantes | FIXED (intégré dans M2) |

### Action Items (LOW — non-bloquants)

- [ ] [AI-Review][LOW] `AuthService.php:292` — Ajouter null-safety sur `currentAccessToken()` : `$user->currentAccessToken()?->delete()` ou guard clause. Sûr derrière `auth:sanctum` mais vulnérable si appelé en dehors du contexte HTTP (CLI, queue).
