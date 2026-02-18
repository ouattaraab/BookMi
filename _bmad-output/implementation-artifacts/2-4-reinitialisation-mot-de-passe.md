# Story 2.4: Réinitialisation mot de passe

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a utilisateur,
I want réinitialiser mon mot de passe via email,
so that je puisse récupérer l'accès à mon compte si j'ai oublié mon mot de passe. (FR10)

## Acceptance Criteria (AC)

1. **Given** un utilisateur a oublié son mot de passe **When** il envoie `POST /api/v1/auth/forgot-password` avec son email **Then** un email avec un lien de réinitialisation est envoyé (via Mailgun) **And** la réponse retourne `200` avec un message de confirmation (même si l'email n'existe pas — anti-énumération)
2. **Given** un lien de réinitialisation envoyé **Then** le lien (token) expire après 60 minutes (configuration password broker)
3. **Given** un token valide **When** l'utilisateur envoie `POST /api/v1/auth/reset-password` avec token, email, et nouveau password **Then** le mot de passe est mis à jour (bcrypt 12 rounds via cast `hashed`)
4. **Given** une réinitialisation réussie **Then** tous les tokens Sanctum existants de l'utilisateur sont révoqués
5. **Given** une réinitialisation réussie **Then** un événement `PasswordReset` est émis
6. **Given** l'endpoint forgot-password **Then** le rate limiting est de 5/min (rate limiter dédié `forgot-password`)
7. **Given** le password broker **Then** un throttle de 60 secondes empêche les demandes répétées pour le même email (configuration Laravel native)
8. **Given** un token invalide ou expiré **When** l'utilisateur envoie `POST /api/v1/auth/reset-password` **Then** la réponse retourne `422` avec le code d'erreur approprié

## Tasks / Subtasks

- [x] 1. Créer le fichier de traduction `lang/fr/passwords.php` (AC: #1, #8)
  - [x] 1.1 Créer `lang/fr/passwords.php` avec les messages français pour le password broker Laravel
  - [x] 1.2 Messages à couvrir : `reset`, `sent`, `throttled`, `token`, `user`

- [x] 2. Créer la Notification `ResetPasswordNotification` (AC: #1, #2)
  - [x] 2.1 Créer `app/Notifications/ResetPasswordNotification.php` via canal `mail`
  - [x] 2.2 Email en français avec sujet "Réinitialisation de votre mot de passe BookMi"
  - [x] 2.3 Le lien doit pointer vers un deep link ou URL frontend (configurable via `config('bookmi.auth.password_reset_url')`)
  - [x] 2.4 Le token et l'email doivent être inclus dans le lien
  - [x] 2.5 Mentionner l'expiration de 60 minutes dans le corps de l'email

- [x] 3. Configurer l'envoi de la notification sur le modèle User (AC: #1)
  - [x] 3.1 Override `sendPasswordResetNotification($token)` dans `User.php` pour utiliser `ResetPasswordNotification`

- [x] 4. Créer les FormRequests (AC: #1, #3)
  - [x] 4.1 Créer `app/Http/Requests/Api/ForgotPasswordRequest.php` avec règle : email (required|email)
  - [x] 4.2 Créer `app/Http/Requests/Api/ResetPasswordRequest.php` avec règles : token (required|string), email (required|email), password (required|string|min:8|confirmed)
  - [x] 4.3 Messages de validation en français

- [x] 5. Étendre AuthException — nouveaux codes d'erreur (AC: #8)
  - [x] 5.1 Ajouter `resetTokenInvalid()` : `AUTH_RESET_TOKEN_INVALID` (422)
  - [x] 5.2 Ajouter `resetThrottled()` : `AUTH_RESET_THROTTLED` (429)

- [x] 6. Étendre AuthService — méthodes forgot/reset password (AC: #1, #2, #3, #4, #5, #7, #8)
  - [x] 6.1 Ajouter `forgotPassword(string $email): void` — utilise le Password Broker Laravel pour envoyer le lien
  - [x] 6.2 Ajouter `resetPassword(string $email, string $token, string $password): void` — utilise le Password Broker pour valider le token et mettre à jour le mot de passe
  - [x] 6.3 Dans resetPassword : révoquer tous les tokens Sanctum `$user->tokens()->delete()`
  - [x] 6.4 Dans resetPassword : dispatcher l'événement `PasswordReset`

- [x] 7. Créer l'événement PasswordReset (AC: #5)
  - [x] 7.1 Créer `app/Events/PasswordReset.php` avec propriété `User $user`

- [x] 8. Ajouter le rate limiter `forgot-password` (AC: #6)
  - [x] 8.1 Ajouter un rate limiter `forgot-password` (5/min par IP) dans `AppServiceProvider`

- [x] 9. Étendre AuthController (AC: #1, #3)
  - [x] 9.1 Ajouter `forgotPassword(ForgotPasswordRequest $request): JsonResponse` — appelle `AuthService::forgotPassword()`, retourne 200
  - [x] 9.2 Ajouter `resetPassword(ResetPasswordRequest $request): JsonResponse` — appelle `AuthService::resetPassword()`, retourne 200

- [x] 10. Routes (AC: #6)
  - [x] 10.1 Ajouter `Route::post('/auth/forgot-password', ...)` avec `throttle:forgot-password` middleware
  - [x] 10.2 Ajouter `Route::post('/auth/reset-password', ...)` avec `throttle:auth` middleware
  - [x] 10.3 Les deux routes hors du groupe `auth:sanctum`

- [x] 11. Config — ajouter password_reset_url (AC: #2)
  - [x] 11.1 Ajouter `password_reset_url` dans `config/bookmi.php` section `auth`

- [x] 12. Tests (tous les AC)
  - [x] 12.1 Tests Feature : `ForgotPasswordTest.php` — 6 tests (200 email valide, 200 anti-énumération, case insensitive, email manquant 422, email invalide 422, throttle middleware)
  - [x] 12.2 Tests Feature : `ResetPasswordTest.php` — 12 tests (reset réussi 200, tokens révoqués, PasswordReset event, token invalide 422, token expiré 422, email inconnu 422, password mismatch 422, token manquant 422, email manquant 422, password manquant 422, password trop court 422, throttle middleware)
  - [x] 12.3 Tests Unit : `AuthServicePasswordResetTest.php` — 9 tests (notification envoyée, email inconnu silencieux, email lowercase, throttle broker, reset password, révocation tokens, event dispatché, token invalide, email inconnu)
  - [x] 12.4 Vérifier que les 243 tests existants passent toujours (270 tests au total, 925 assertions)

## Dev Notes

### Architecture & Patterns obligatoires (hérités de Stories 2.1 / 2.2 / 2.3)

- **BaseController** : `AuthController extends BaseController` — déjà en place
- **JSON envelope** : `successResponse($data, $statusCode)` et `errorResponse($code, $message, $statusCode, $details)` — via `ApiResponseTrait`
- **FormRequest** : validation dans les FormRequests, pas dans le controller
- **Service Layer** : logique métier dans `AuthService`, le controller appelle le service
- **Rate limiting** : créer un nouveau rate limiter `forgot-password` (5/min par IP)
- **Anti-énumération** : toujours retourner 200 sur forgot-password, même si l'email n'existe pas

### Password Broker Laravel — infrastructure existante

**config/auth.php** — broker déjà configuré :
```php
'passwords' => [
    'users' => [
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60,       // Token expire après 60 minutes
        'throttle' => 60,     // 60 secondes entre deux demandes
    ],
],
```

**Migration** — table `password_reset_tokens` déjà créée :
```php
Schema::create('password_reset_tokens', function (Blueprint $table) {
    $table->string('email')->primary();
    $table->string('token');
    $table->timestamp('created_at')->nullable();
});
```

**User model** — hérite de `Illuminate\Foundation\Auth\User as Authenticatable` qui implémente déjà `CanResetPassword` et fournit `sendPasswordResetNotification($token)`. Le trait `Notifiable` est aussi en place.

### Password Broker — utilisation recommandée

Le Password Broker Laravel (`Password::broker()`) gère :
- Génération du token sécurisé (hashed dans la table)
- Throttle natif (60 sec entre deux envois)
- Validation du token (expiration + correspondance)
- Suppression du token après utilisation

**Usage pour forgotPassword :**
```php
use Illuminate\Support\Facades\Password;

$status = Password::sendResetLink(['email' => $email]);

// $status retourne :
// Password::RESET_LINK_SENT — lien envoyé
// Password::RESET_THROTTLED — trop de demandes
// Password::INVALID_USER — utilisateur non trouvé
```

**Usage pour resetPassword :**
```php
$status = Password::reset(
    ['email' => $email, 'password' => $password, 'password_confirmation' => $password, 'token' => $token],
    function (User $user, string $password) {
        $user->password = $password;
        $user->save();
        $user->tokens()->delete(); // Révoquer tous les tokens Sanctum
        PasswordReset::dispatch($user);
    }
);

// $status retourne :
// Password::PASSWORD_RESET — succès
// Password::INVALID_TOKEN — token invalide/expiré
// Password::INVALID_USER — utilisateur non trouvé
```

### État actuel du code auth (après Stories 2.1 + 2.2 + 2.3)

**AuthService.php** — méthodes existantes :
```php
register(array $data): User
verifyOtp(string $phone, string $code): array
resendOtp(string $phone): void
login(string $email, string $password): array
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
```

### Nouveaux codes d'erreur pour Story 2.4

| Code | Status HTTP | Message FR | Détails |
|------|------------|------------|---------|
| `AUTH_RESET_TOKEN_INVALID` | 422 | Le lien de réinitialisation est invalide ou a expiré. | — |
| `AUTH_RESET_THROTTLED` | 429 | Veuillez patienter avant de demander un nouveau lien. | — |

### Mail — configuration actuelle

**config/mail.php** :
- Default mailer : `log` (via `env('MAIL_MAILER', 'log')`)
- En dev/test, les emails sont loggés — pas envoyés
- En production : Mailgun (per architecture)
- `MAIL_FROM_ADDRESS` : `env('MAIL_FROM_ADDRESS', 'hello@example.com')`

**Note :** Les tests utilisent `Notification::fake()` — pas besoin de configurer un vrai mailer pour les tests.

### Notification — pattern recommandé

Utiliser une Notification Laravel plutôt qu'un Mailable direct :
- Le User a déjà le trait `Notifiable`
- Override `sendPasswordResetNotification()` dans User.php
- Le Password Broker appelle automatiquement cette méthode

```php
// app/Notifications/ResetPasswordNotification.php
class ResetPasswordNotification extends Notification
{
    public function __construct(public readonly string $token) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = config('bookmi.auth.password_reset_url') . '?' . http_build_query([
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe BookMi')
            ->greeting('Bonjour ' . $notifiable->first_name . ',')
            ->line('Vous recevez cet email car une demande de réinitialisation de mot de passe a été effectuée pour votre compte.')
            ->action('Réinitialiser mon mot de passe', $url)
            ->line('Ce lien expirera dans 60 minutes.')
            ->line('Si vous n\'avez pas demandé cette réinitialisation, aucune action n\'est requise.')
            ->salutation('L\'équipe BookMi');
    }
}

// User.php — override
public function sendPasswordResetNotification($token): void
{
    $this->notify(new ResetPasswordNotification($token));
}
```

### Rate Limiter — nouveau `forgot-password`

L'architecture spécifie 5/min pour forgot-password. Créer le rate limiter dans `bootstrap/app.php` ou `AppServiceProvider` :

```php
// Vérifier où les rate limiters existants sont définis dans le projet
RateLimiter::for('forgot-password', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});
```

**Note :** Le rate limiter `auth` existant est 10/min par IP. Le `forgot-password` est plus restrictif (5/min).

### lang/fr/passwords.php — à créer

Le Password Broker utilise les clés de traduction `passwords.*` :
```php
return [
    'reset' => 'Votre mot de passe a été réinitialisé.',
    'sent' => 'Un lien de réinitialisation a été envoyé à votre adresse email.',
    'throttled' => 'Veuillez patienter avant de réessayer.',
    'token' => 'Ce lien de réinitialisation est invalide ou a expiré.',
    'user' => 'Aucun utilisateur trouvé avec cette adresse email.',
];
```

**Note :** Ces messages ne seront PAS retournés directement dans l'API (on utilise nos propres AuthException). Mais ils sont utilisés en interne par le broker pour identifier le statut.

### Config bookmi.php — ajout nécessaire

Ajouter dans la section `auth` :
```php
'auth' => [
    // ... existant
    'password_reset_url' => env('PASSWORD_RESET_URL', 'http://localhost:3000/reset-password'),
],
```

### Flow détaillé — forgot-password

```
POST /api/v1/auth/forgot-password { email }
  1. Normaliser email : strtolower($email)
  2. Password::sendResetLink(['email' => $email])
  3. Switch sur le statut :
     - RESET_LINK_SENT → retourner 200 avec message de confirmation
     - RESET_THROTTLED → throw AUTH_RESET_THROTTLED (429)
     - INVALID_USER → retourner 200 quand même (anti-énumération)
```

### Flow détaillé — reset-password

```
POST /api/v1/auth/reset-password { token, email, password, password_confirmation }
  1. Normaliser email : strtolower($email)
  2. Password::reset(credentials, callback)
     callback :
       a. $user->password = $password (sera haché par le cast 'hashed')
       b. $user->save()
       c. $user->tokens()->delete() (révoquer Sanctum tokens)
       d. PasswordReset::dispatch($user)
  3. Switch sur le statut :
     - PASSWORD_RESET → retourner 200 avec message de confirmation
     - INVALID_TOKEN → throw AUTH_RESET_TOKEN_INVALID (422)
     - INVALID_USER → throw AUTH_RESET_TOKEN_INVALID (422) (même message — anti-énumération)
```

### Leçons de Stories 2.1 / 2.2 / 2.3 — à suivre impérativement

1. **Tests : désactiver les deux classes throttle** dans setUp() :
   ```php
   $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
   ```
2. **Tests : format d'erreur BookMi** — utiliser `assertJsonPath('error.code', ...)` — NE PAS utiliser `assertJsonValidationErrors()`
3. **Tests : seeder obligatoire** — `$this->seed(RoleAndPermissionSeeder::class)` dans setUp()
4. **SQLite defaults** — toujours passer les valeurs explicitement dans `create()`, ne pas se fier aux defaults de migration
5. **Cache : atomic operations** — toujours utiliser `Cache::get()` + null check, JAMAIS `Cache::has()`
6. **Normalisation email** — `strtolower($email)` avant toute opération (fix H1 Story 2.3)
7. **Notification::fake()** — utiliser dans les tests pour vérifier l'envoi sans réellement envoyer
8. **Pint + PHPStan** — lancer à la fin pour vérifier le style et l'analyse statique (phpstan avec `-d memory_limit=512M`)
9. **Event testing** — utiliser `Event::fake()` et `Event::assertDispatched(PasswordReset::class)` dans les tests
10. **Anti-énumération** — ne jamais révéler si un email existe ou non dans les réponses forgot-password

### User model — champs pertinents

```php
// Extends Authenticatable (implements CanResetPassword)
// Traits: HasApiTokens, HasFactory, HasRoles, Notifiable

protected $fillable = ['first_name', 'last_name', 'email', 'phone', 'password', 'is_admin', 'is_active'];

// $casts
'password' => 'hashed',  // Auto-hashing à l'assignation
```

### Project Structure Notes

**Fichiers à créer :**
```
bookmi/lang/fr/passwords.php
bookmi/app/Notifications/ResetPasswordNotification.php
bookmi/app/Events/PasswordReset.php
bookmi/app/Http/Requests/Api/ForgotPasswordRequest.php
bookmi/app/Http/Requests/Api/ResetPasswordRequest.php
bookmi/tests/Feature/Auth/ForgotPasswordTest.php
bookmi/tests/Feature/Auth/ResetPasswordTest.php
bookmi/tests/Unit/Services/AuthServicePasswordResetTest.php
```

**Fichiers à modifier :**
```
bookmi/app/Models/User.php — override sendPasswordResetNotification()
bookmi/app/Services/AuthService.php — ajouter forgotPassword(), resetPassword()
bookmi/app/Http/Controllers/Api/V1/AuthController.php — ajouter forgotPassword(), resetPassword()
bookmi/app/Exceptions/AuthException.php — ajouter resetTokenInvalid(), resetThrottled()
bookmi/routes/api.php — ajouter 2 routes POST /auth/forgot-password et /auth/reset-password
bookmi/config/bookmi.php — ajouter password_reset_url
bookmi/bootstrap/app.php (ou AppServiceProvider) — ajouter rate limiter forgot-password
```

### References

- [Source: _bmad-output/planning-artifacts/epics.md — Epic 2, Story 2.4, FR10]
- [Source: _bmad-output/planning-artifacts/architecture.md — Auth flow, Mailgun, Rate limiting, NFR13 bcrypt, Password Broker config]
- [Source: _bmad-output/implementation-artifacts/2-3-connexion-email-mot-de-passe.md — Dev Notes, Debug Log, Code Review, Patterns hérités]
- [Source: _bmad-output/implementation-artifacts/2-2-verification-otp-telephone.md — OTP patterns, Cache patterns, Test patterns]
- [Source: bookmi/config/auth.php — Password broker config (expire: 60, throttle: 60)]
- [Source: bookmi/config/mail.php — Default mailer 'log', Mailgun pour production]
- [Source: bookmi/config/bookmi.php — auth config existante]
- [Source: bookmi/app/Models/User.php — Authenticatable, Notifiable, HasApiTokens, CanResetPassword via héritage]
- [Source: bookmi/app/Services/AuthService.php — existing methods, buildAuthResponse(), handleFailedLoginAttempt()]
- [Source: bookmi/app/Exceptions/AuthException.php — existing static factories]
- [Source: bookmi/database/migrations/0001_01_01_000000_create_users_table.php — password_reset_tokens table]

## Dev Agent Record

### Agent Model Used

Claude Opus 4.6

### Debug Log References

- Pint a corrigé `ResetPasswordNotification.php` : `new_with_parentheses` — `new MailMessage` → `new MailMessage()`
- PHPStan flag correct : `--memory-limit=512M` (pas `-d memory_limit`)

### Completion Notes List

- Implémentation complète de `POST /api/v1/auth/forgot-password` et `POST /api/v1/auth/reset-password`
- Utilisation du Password Broker Laravel natif (`Password::sendResetLink()`, `Password::reset()`) avec la table `password_reset_tokens` existante
- Token expire après 60 minutes (config/auth.php `expire: 60`), throttle broker natif 60 secondes entre deux demandes
- Anti-énumération : forgot-password retourne toujours 200, même si l'email n'existe pas
- Normalisation email `strtolower()` dans les deux méthodes (pattern hérité Story 2.3)
- Notification `ResetPasswordNotification` via canal `mail` avec sujet français, lien configurable via `config('bookmi.auth.password_reset_url')`
- Override `sendPasswordResetNotification()` dans User.php pour utiliser la notification custom
- Tokens Sanctum révoqués après reset réussi (`$user->tokens()->delete()`)
- Événement `PasswordReset` dispatché après reset réussi
- Rate limiter `forgot-password` (5/min par IP) dans AppServiceProvider
- 2 nouveaux codes d'erreur : `AUTH_RESET_TOKEN_INVALID` (422), `AUTH_RESET_THROTTLED` (429)
- Fichier de traduction `lang/fr/passwords.php` créé (5 clés)
- 28 nouveaux tests (7 Feature forgot + 12 Feature reset + 9 Unit), 271 tests au total, 930 assertions — tous passent
- Pint PASS, PHPStan 0 erreurs

### Change Log

- 2026-02-18 : Implémentation Story 2.4 — Réinitialisation mot de passe (forgot-password, reset-password, notification, event, tests)
- 2026-02-18 : Code Review — 5 issues trouvées (3 MEDIUM, 2 LOW), 3 MEDIUM corrigées automatiquement

### Senior Developer Review

**Review Date:** 2026-02-18
**Reviewer:** Claude Opus 4.6 (Adversarial Code Review)
**Result:** PASS (all MEDIUM issues fixed, LOW items documented)

#### Issues Found & Fixed (MEDIUM)

**M1 — `\DB` global alias in test** (`ResetPasswordTest.php:137`)
- Utilisait `\DB::table(...)` au lieu du facade importé `DB::table(...)`
- Fix: Ajout `use Illuminate\Support\Facades\DB;` import + remplacement de `\DB` par `DB`

**M2 — Test Feature manquant pour throttle broker 429** (`ForgotPasswordTest.php`)
- AC#7 (throttle broker 60s) n'était testé qu'au niveau service, pas au niveau HTTP
- Fix: Ajout du test `broker_throttle_returns_429_on_rapid_requests` qui envoie deux requêtes rapides et vérifie le 429 + code `AUTH_RESET_THROTTLED`

**M3 — Deux tests Feature sans assertion HTTP 200** (`ResetPasswordTest.php`)
- `reset_revokes_all_sanctum_tokens` et `reset_dispatches_password_reset_event` ne vérifiaient pas le code HTTP
- Fix: Ajout `$response->assertStatus(200)` aux deux tests

#### Action Items (LOW — non bloquants)

**L1 — Type hint `mixed $notifiable` dans ResetPasswordNotification**
- Pourrait être typé `User` au lieu de `mixed` pour plus de sécurité. Pattern Laravel standard, non bloquant.

**L2 — Collision de nom `App\Events\PasswordReset` avec `Illuminate\Auth\Events\PasswordReset`**
- Risque de confusion lors d'imports. Actuellement pas de conflit car on n'utilise pas l'événement Illuminate. Non bloquant.

#### Post-Review Verification

- 271 tests, 930 assertions — TOUS PASSENT
- Pint PASS
- PHPStan 0 erreurs

### File List

**Fichiers créés :**
- `bookmi/lang/fr/passwords.php`
- `bookmi/app/Notifications/ResetPasswordNotification.php`
- `bookmi/app/Events/PasswordReset.php`
- `bookmi/app/Http/Requests/Api/ForgotPasswordRequest.php`
- `bookmi/app/Http/Requests/Api/ResetPasswordRequest.php`
- `bookmi/tests/Feature/Auth/ForgotPasswordTest.php`
- `bookmi/tests/Feature/Auth/ResetPasswordTest.php`
- `bookmi/tests/Unit/Services/AuthServicePasswordResetTest.php`

**Fichiers modifiés :**
- `bookmi/app/Models/User.php` — ajout sendPasswordResetNotification(), import ResetPasswordNotification
- `bookmi/app/Services/AuthService.php` — ajout forgotPassword(), resetPassword(), imports Password/PasswordReset
- `bookmi/app/Http/Controllers/Api/V1/AuthController.php` — ajout forgotPassword(), resetPassword(), imports
- `bookmi/app/Exceptions/AuthException.php` — ajout resetTokenInvalid(), resetThrottled()
- `bookmi/app/Providers/AppServiceProvider.php` — ajout rate limiter forgot-password
- `bookmi/routes/api.php` — ajout 2 routes POST /auth/forgot-password et /auth/reset-password
- `bookmi/config/bookmi.php` — ajout password_reset_url
