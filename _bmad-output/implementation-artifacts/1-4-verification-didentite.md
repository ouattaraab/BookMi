# Story 1.4: Vérification d'identité

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a utilisateur,
I want soumettre ma pièce d'identité pour vérification,
so that je puisse obtenir le badge "Vérifié" et inspirer confiance. (FR3, FR4, FR5)

## Acceptance Criteria (AC)

1. **Given** un utilisateur authentifié avec un profil talent
   **When** il envoie `POST /api/v1/verifications` avec une photo de CNI ou passeport
   **Then** la pièce est stockée dans un espace chiffré séparé (AES-256-CBC via `Crypt::encrypt()`)
   **And** un enregistrement `identity_verifications` est créé avec statut `pending`
   **And** la réponse est `201 Created` avec le JSON envelope `{ "data": { "id", "type": "identity_verification", "attributes": {...} } }`

2. **Given** un utilisateur authentifié
   **When** il envoie `GET /api/v1/verifications/me`
   **Then** son statut de vérification est retourné (ou `404` s'il n'a jamais soumis)

3. **Given** un administrateur authentifié (`is_admin = true`)
   **When** il accède à `GET /admin/verifications`
   **Then** la liste des demandes de vérification en attente est retournée (JSON)

4. **Given** un administrateur authentifié
   **When** il accède à `GET /admin/verifications/{verification}/document`
   **Then** le document chiffré est déchiffré en mémoire et streamé (jamais écrit sur disque en clair)
   **And** les headers empêchent le cache navigateur (`Cache-Control: no-store`)

5. **Given** un administrateur authentifié
   **When** il envoie `POST /admin/verifications/{verification}/review` avec `decision: approved`
   **Then** `verification_status` passe à `approved`, `is_verified` passe à `true` sur le `talent_profile`
   **And** `profile_completion_percentage` est recalculé (+20% pour la vérification)
   **And** la pièce d'identité chiffrée est **supprimée du disque** (seul le statut et la date sont conservés) (NFR18)

6. **Given** un administrateur authentifié
   **When** il envoie `POST /admin/verifications/{verification}/review` avec `decision: rejected`
   **Then** `verification_status` passe à `rejected` avec `rejection_reason`
   **And** la pièce d'identité chiffrée est **supprimée du disque**
   **And** l'utilisateur peut soumettre une nouvelle demande

7. **Given** toute action admin sur les vérifications
   **Then** l'action est journalisée dans `activity_logs` avec : `causer_id`, `subject`, `action`, `metadata`, `ip_address`

8. **Given** un utilisateur non-admin
   **When** il tente d'accéder aux routes `/admin/*`
   **Then** une réponse `403 Forbidden` est retournée

## Tasks / Subtasks

- [x] Task 1 — Créer l'enum VerificationStatus (AC: #1, #5, #6)
  - [x] 1.1 Créer `app/Enums/VerificationStatus.php` — backed string enum avec 3 états : `pending`, `approved`, `rejected`
  - [x] 1.2 Ajouter méthodes helper `label(): string` (en français) et `isTerminal(): bool`

- [x] Task 2 — Créer les migrations (AC: #1, #3, #7, #8)
  - [x] 2.1 Créer migration `add_is_admin_to_users_table` — ajouter `is_admin` boolean default false après `password`
  - [x] 2.2 Créer migration `create_identity_verifications_table` — voir schéma complet dans Dev Notes
  - [x] 2.3 Créer migration `create_activity_logs_table` — voir schéma complet dans Dev Notes

- [x] Task 3 — Créer les modèles (AC: #1, #7)
  - [x] 3.1 Créer `app/Models/IdentityVerification.php` — relations (user, reviewer), casts (VerificationStatus enum), scopes (pending, reviewed)
  - [x] 3.2 Créer `app/Models/ActivityLog.php` — relations (causer BelongsTo User, subject MorphTo), cast (metadata → array)
  - [x] 3.3 Modifier `app/Models/User.php` — ajouter `is_admin` dans `$fillable` et `casts()`, ajouter relations `identityVerification()` HasOne et `activityLogs()` HasMany
  - [x] 3.4 Créer `database/factories/IdentityVerificationFactory.php` avec états : `pending()`, `approved()`, `rejected()`

- [x] Task 4 — Configurer le stockage chiffré (AC: #1, #4)
  - [x] 4.1 Ajouter le disque `identity_documents` dans `config/filesystems.php` — root: `storage_path('app/identity_documents')`, throw: true, PAS de `url` (pas d'accès public)
  - [x] 4.2 Ajouter la section `verification` dans `config/bookmi.php` — `allowed_mimes`, `max_file_size_kb`, `disk`

- [x] Task 5 — Créer le Repository pattern (AC: #1, #3)
  - [x] 5.1 Créer `app/Repositories/Contracts/VerificationRepositoryInterface.php` — méthodes : `find`, `findByUserId`, `findPending`, `create`, `update`
  - [x] 5.2 Créer `app/Repositories/Eloquent/VerificationRepository.php`
  - [x] 5.3 Ajouter binding dans `AppServiceProvider::register()`

- [x] Task 6 — Créer les services (AC: #1, #5, #6, #7)
  - [x] 6.1 Créer `app/Services/IdentityVerificationService.php` — méthodes : `submit()`, `getByUserId()`, `review()`, `getDocumentContent()`, `deleteDocument()`
  - [x] 6.2 Créer `app/Services/AuditService.php` — méthode `log(action, subject, metadata)` — utilise `auth()->id()` et `request()->ip()`
  - [x] 6.3 Modifier `app/Services/TalentProfileService.php` — ajouter `recalculateCompletion(TalentProfile): TalentProfile` qui vérifie bio + is_verified

- [x] Task 7 — Créer le middleware admin (AC: #8)
  - [x] 7.1 Créer `app/Http/Middleware/EnsureUserIsAdmin.php` — vérifie `$request->user()?->is_admin`, sinon `abort(403)`
  - [x] 7.2 Enregistrer l'alias `admin` dans `bootstrap/app.php` → `withMiddleware`

- [x] Task 8 — Créer les Form Requests (AC: #1, #5, #6)
  - [x] 8.1 Créer `app/Http/Requests/Api/StoreVerificationRequest.php` — authorize: true (auth middleware), rules: `document` required|file|mimes:jpeg,jpg,png,pdf|max:5120, `document_type` required|in:cni,passport
  - [x] 8.2 Créer `app/Http/Requests/Admin/ReviewVerificationRequest.php` — authorize: true (admin middleware), rules: `decision` required|in:approved,rejected, `rejection_reason` required_if:decision,rejected|nullable|string|max:500

- [x] Task 9 — Créer les API Resources (AC: #1, #2, #3)
  - [x] 9.1 Créer `app/Http/Resources/VerificationResource.php` — JSON envelope avec `id`, `type: "identity_verification"`, `attributes: { document_type, verification_status, rejection_reason, submitted_at, reviewed_at, verified_at }`
  - [x] 9.2 Créer `app/Http/Resources/AdminVerificationResource.php` — même chose + `user` (nom, email), `reviewer` (nom), `has_document` (bool)

- [x] Task 10 — Créer la Policy (AC: #2)
  - [x] 10.1 Créer `app/Policies/IdentityVerificationPolicy.php` — `view(User, IdentityVerification)` : seul le propriétaire ou admin, `create(User)` : pas de vérification pending existante

- [x] Task 11 — Créer les Controllers et Routes (AC: #1, #2, #3, #4, #5, #6)
  - [x] 11.1 Créer `app/Http/Controllers/Api/V1/VerificationController.php` extends BaseController — `store()` et `showOwn()`
  - [x] 11.2 Créer `app/Http/Controllers/Admin/VerificationController.php` — `index()`, `show()`, `document()` (stream), `review()`
  - [x] 11.3 Ajouter routes API dans `routes/api.php` sous middleware `auth:sanctum`
  - [x] 11.4 Ajouter routes admin dans `routes/admin.php` sous middleware `auth` + `admin`

- [x] Task 12 — Tests (AC: tous)
  - [x] 12.1 Créer `tests/Feature/Api/V1/VerificationControllerTest.php` — 9 tests : soumission, statut, validation, doublon, auth, resoumission après rejet
  - [x] 12.2 Créer `tests/Feature/Admin/VerificationControllerTest.php` — 11 tests : listing, review approve/reject, document stream, auth admin, audit log, completion percentage
  - [x] 12.3 Créer `tests/Unit/Services/IdentityVerificationServiceTest.php` — 8 tests : submit, review, delete document, recalculate completion, decrypt
  - [x] 12.4 Vérifier : `php artisan test` passe (67 tests, 230 assertions), `pint --test` passe, `phpstan analyse --memory-limit=512M` passe (0 erreurs)

### Review Follow-ups (AI)

- [ ] [AI-Review][LOW] Policy `create()` est du code mort (pas appelé dans le controller) — nettoyer ou documenter l'intention [app/Policies/IdentityVerificationPolicy.php:16-22]
- [ ] [AI-Review][LOW] Aucun test pour `document_type: 'passport'` — ajouter un test avec passport en complément de 'cni' [tests/Feature/Api/V1/VerificationControllerTest.php]

## Dev Notes

### Architecture Patterns et Contraintes — CRITIQUE

**Pattern Controller → Service → Repository → Model (ARCH-PATTERN-2) :**
- **Controllers** : JAMAIS de logique métier. Valide (FormRequest), délègue (Service), répond (Resource).
- **Services** : Logique métier pure. Injection du Repository via constructeur.
- **Repositories** : Interface dans `Contracts/`, implémentation dans `Eloquent/`.
- **Models** : Relations, scopes, casts, accessors UNIQUEMENT.

**Format réponse API — JSON Envelope (ARCH-API-1 à ARCH-API-5) :**
```json
// Succès
{ "data": { "id": 1, "type": "identity_verification", "attributes": { ... } } }

// Erreur
{ "error": { "code": "VERIFICATION_ALREADY_PENDING", "message": "Une demande de vérification est déjà en cours.", "status": 422, "details": {} } }
```

**Codes d'erreur — Préfixe `VERIFICATION_` :**
- `VERIFICATION_ALREADY_PENDING` — l'utilisateur a déjà une demande en attente
- `VERIFICATION_NOT_FOUND` — aucune vérification trouvée
- `VERIFICATION_ALREADY_REVIEWED` — la vérification a déjà été traitée
- `VERIFICATION_NO_TALENT_PROFILE` — l'utilisateur n'a pas de profil talent

### Schéma Base de Données Complet

**Migration `add_is_admin_to_users_table` :**

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| `is_admin` | boolean | default false, after 'password' | Flag admin temporaire (remplacé par spatie/laravel-permission Story 2.1) |

**Table `identity_verifications` :**

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| `id` | bigint | PK auto-increment | |
| `user_id` | bigint | FK → users.id, cascadeOnDelete | Utilisateur qui soumet |
| `document_type` | string(20) | required | 'cni' ou 'passport' |
| `stored_path` | string(255) | nullable | Chemin vers le fichier chiffré (null après review) |
| `original_mime` | string(50) | required | image/jpeg, image/png, application/pdf |
| `verification_status` | string(20) | default 'pending' | pending, approved, rejected — cast VerificationStatus enum |
| `reviewer_id` | bigint | nullable, FK → users.id, nullOnDelete | Admin qui a review |
| `reviewed_at` | timestamp | nullable | Date de la décision |
| `rejection_reason` | text | nullable | Motif de rejet (si rejected) |
| `verified_at` | timestamp | nullable | Date d'approbation (si approved) |
| `created_at` | timestamp | | Date de soumission |
| `updated_at` | timestamp | | |

**Index :**
- `identity_verifications_user_id_index`
- `identity_verifications_verification_status_index`
- `identity_verifications_reviewer_id_index`

**Table `activity_logs` :**

| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| `id` | bigint | PK auto-increment | |
| `causer_id` | bigint | FK → users.id, cascadeOnDelete | Admin qui a agi |
| `subject_type` | string(255) | nullable | Classe du modèle (polymorphique) |
| `subject_id` | bigint | nullable | ID du modèle |
| `action` | string(100) | required | Ex: 'identity_verification.approved' |
| `metadata` | json | nullable | Contexte additionnel |
| `ip_address` | string(45) | nullable | IP de l'admin |
| `created_at` | timestamp | | |

**Note :** `activity_logs` n'a PAS de `updated_at` — les logs sont en écriture seule (append-only). Utiliser `public const UPDATED_AT = null;` dans le modèle, et ne PAS appeler `$table->timestamps()`. Utiliser `$table->timestamp('created_at')->useCurrent()` uniquement.

**Index :**
- `activity_logs_causer_id_created_at_index` (composite)
- `activity_logs_subject_type_subject_id_index` (composite)

### Stockage Chiffré des Documents — CRITIQUE

**Disque dédié dans `config/filesystems.php` :**
```php
'identity_documents' => [
    'driver' => 'local',
    'root'   => storage_path('app/identity_documents'),
    'throw'  => true,  // Exception si erreur d'écriture
    'report' => true,
    // PAS de 'url' — empêche Storage::url() de générer un lien public
    // PAS de 'visibility' — private par défaut
],
```

**Flow de chiffrement :**
```php
// Upload
$plaintext = file_get_contents($uploadedFile->getRealPath());
$encrypted = Crypt::encrypt($plaintext, serialize: false);
$storedName = Str::uuid() . '.enc';
Storage::disk('identity_documents')->put("identity/{$storedName}", $encrypted);

// Lecture admin (streaming, jamais sur disque)
$encrypted = Storage::disk('identity_documents')->get($storedPath);
$plaintext = Crypt::decrypt($encrypted, unserialize: false);
return response()->stream(fn () => echo $plaintext, 200, [
    'Content-Type' => $originalMime,
    'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
]);

// Suppression définitive (après review)
Storage::disk('identity_documents')->delete($storedPath);
// + mettre stored_path à null en DB
```

**CRITIQUE — Sécurité :**
- Le fichier déchiffré ne DOIT JAMAIS être écrit sur disque — streaming en mémoire uniquement
- Le `stored_path` est mis à `null` en DB après suppression
- Le nom de fichier utilise `Str::uuid()` — jamais le nom original
- Le dossier `storage/app/identity_documents/` n'est PAS lié par `storage:link`

### Configuration `config/bookmi.php` — Section à ajouter

```php
'verification' => [
    'allowed_mimes' => ['image/jpeg', 'image/png', 'application/pdf'],
    'max_file_size_kb' => 5120, // 5 Mo
    'disk' => 'identity_documents',
    'document_types' => ['cni', 'passport'],
],
```

### Middleware Admin — Approche Temporaire (CRITIQUE)

**`is_admin` est un flag TEMPORAIRE** qui sera remplacé par `spatie/laravel-permission` dans la Story 2.1. Ne PAS construire un système de rôles — juste un boolean simple.

```php
// app/Http/Middleware/EnsureUserIsAdmin.php
public function handle(Request $request, Closure $next): Response
{
    if (! $request->user()?->is_admin) {
        abort(403, 'Accès réservé aux administrateurs.');
    }
    return $next($request);
}
```

**Enregistrement dans `bootstrap/app.php` :**
```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->throttleWithRedis();
    $middleware->alias([
        'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
    ]);
})
```

### Routes Admin — Pattern Crucial

Les routes admin sont dans `routes/admin.php`, déjà configurées dans `bootstrap/app.php` avec le middleware `web` et le préfixe `/admin`. Elles utilisent l'authentification par **session** (guard `web`), PAS Sanctum.

```php
// routes/admin.php
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/verifications', [AdminVerificationController::class, 'index'])
        ->name('verifications.index');
    Route::get('/verifications/{verification}', [AdminVerificationController::class, 'show'])
        ->name('verifications.show');
    Route::get('/verifications/{verification}/document', [AdminVerificationController::class, 'document'])
        ->name('verifications.document');
    Route::post('/verifications/{verification}/review', [AdminVerificationController::class, 'review'])
        ->name('verifications.review');
});
```

**Note Testing :** Pour les tests Feature des routes admin, utiliser `$this->actingAs($admin)` SANS le guard `sanctum` (utilise le guard `web` par défaut). Utiliser `getJson()` et `postJson()` — Laravel gère automatiquement le CSRF en mode test.

### Routes API — User-facing

```php
// routes/api.php — à ajouter dans le groupe auth:sanctum existant
Route::middleware('auth:sanctum')->group(function () {
    // ... routes existantes talent_profiles ...

    Route::post('/verifications', [VerificationController::class, 'store'])
        ->name('verifications.store');
    Route::get('/verifications/me', [VerificationController::class, 'showOwn'])
        ->name('verifications.me');
});
```

### Recalcul `profile_completion_percentage` — Modification de TalentProfileService

Ajouter une méthode dans `TalentProfileService` pour recalculer la complétion :

```php
public function recalculateCompletion(TalentProfile $profile): TalentProfile
{
    $percentage = 0;
    if (! empty($profile->bio)) {
        $percentage += 20;
    }
    if ($profile->is_verified) {
        $percentage += 20;
    }
    // Futurs critères (Stories 1.7, 1.8) :
    // if ($profile->has_profile_photo) $percentage += 20;
    // if ($profile->portfolioMedia()->count() >= 3) $percentage += 20;
    // if ($profile->servicePackages()->count() >= 1) $percentage += 20;

    return $this->repository->update($profile, [
        'profile_completion_percentage' => $percentage,
    ]);
}
```

**Important :** La méthode `calculateCompletionFromData(array $data)` existante reste pour `createProfile()` et `updateProfile()`. La nouvelle méthode `recalculateCompletion()` est utilisée par `IdentityVerificationService` après approbation, car elle a besoin de lire l'état actuel du profil.

### Endpoints API — Détail des Réponses

**`POST /api/v1/verifications` — Soumettre**
- Body : `multipart/form-data` avec `document` (file) et `document_type` (string)
- Réponse 201 :
```json
{
  "data": {
    "id": 1,
    "type": "identity_verification",
    "attributes": {
      "document_type": "cni",
      "verification_status": "pending",
      "rejection_reason": null,
      "submitted_at": "2026-02-17T14:30:00Z",
      "reviewed_at": null,
      "verified_at": null
    }
  }
}
```

**`GET /api/v1/verifications/me` — Mon statut**
- Réponse 200 : même structure
- Réponse 404 : `VERIFICATION_NOT_FOUND`

**`GET /admin/verifications` — Liste admin**
- Réponse 200 :
```json
{
  "data": [
    {
      "id": 1,
      "type": "identity_verification",
      "attributes": {
        "document_type": "cni",
        "verification_status": "pending",
        "submitted_at": "2026-02-17T14:30:00Z",
        "has_document": true,
        "user": { "id": 42, "name": "DJ Kerozen", "email": "dj@test.ci" },
        "reviewer": null
      }
    }
  ]
}
```

**`POST /admin/verifications/{id}/review` — Décision admin**
- Body : `{ "decision": "approved" }` ou `{ "decision": "rejected", "rejection_reason": "Document illisible" }`
- Réponse 200 : verification mise à jour

### Packages à NE PAS Installer

- `spatie/laravel-permission` → Story 2.1 (rôles et permissions)
- `spatie/laravel-activitylog` → on crée notre propre `ActivityLog` simple
- Aucun package d'OCR ou de vérification automatique d'identité
- Aucun package de traitement d'image (pas de compression, pas de resize)

### Gestion Temporaire de l'Admin (Décision Critique)

**`is_admin` boolean sera remplacé par les rôles spatie dans Story 2.1.** Pour cette story :
- Dans les **tests**, créer un admin avec `User::factory()->create(['is_admin' => true])`
- Dans le **UserFactory**, ajouter un état `admin()` → `['is_admin' => true]`
- Le `EnsureUserIsAdmin` middleware est le seul point de vérification — facile à remplacer plus tard
- NE PAS créer de seeder admin — les tests créent leurs propres utilisateurs

### Autorisation et Policy

```php
// IdentityVerificationPolicy.php
public function create(User $user): bool
{
    // L'utilisateur doit avoir un profil talent
    // ET ne pas avoir de vérification pending
    return $user->talentProfile !== null
        && ! $user->identityVerification()
              ->where('verification_status', VerificationStatus::Pending)
              ->exists();
}

public function view(User $user, IdentityVerification $verification): bool
{
    return $user->id === $verification->user_id || $user->is_admin;
}
```

### Conventions de Nommage — Rappel

| Élément | Convention | Exemple |
|---|---|---|
| Table | `snake_case` pluriel | `identity_verifications`, `activity_logs` |
| Model | `PascalCase` singulier | `IdentityVerification`, `ActivityLog` |
| Controller API | `Api/V1/VerificationController` | store(), showOwn() |
| Controller Admin | `Admin/VerificationController` | index(), show(), document(), review() |
| FormRequest | `Store/Review` + `PascalCase` + `Request` | `StoreVerificationRequest`, `ReviewVerificationRequest` |
| Resource | `PascalCase` + `Resource` | `VerificationResource`, `AdminVerificationResource` |
| Middleware | `PascalCase` descriptif | `EnsureUserIsAdmin` |
| Enum | `PascalCase` | `VerificationStatus` |
| Routes nommées API | `api.v1.verifications.*` | `api.v1.verifications.store` |
| Routes nommées Admin | `admin.verifications.*` | `admin.verifications.review` |

### Project Structure Notes

**Fichiers à créer dans cette story :**
```
bookmi/
├── app/
│   ├── Enums/
│   │   └── VerificationStatus.php                              ← NOUVEAU
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/V1/
│   │   │   │   └── VerificationController.php                  ← NOUVEAU
│   │   │   └── Admin/
│   │   │       └── VerificationController.php                  ← NOUVEAU
│   │   ├── Middleware/
│   │   │   └── EnsureUserIsAdmin.php                           ← NOUVEAU
│   │   ├── Requests/
│   │   │   ├── Api/
│   │   │   │   └── StoreVerificationRequest.php                ← NOUVEAU
│   │   │   └── Admin/
│   │   │       └── ReviewVerificationRequest.php               ← NOUVEAU
│   │   └── Resources/
│   │       ├── VerificationResource.php                        ← NOUVEAU
│   │       └── AdminVerificationResource.php                   ← NOUVEAU
│   ├── Models/
│   │   ├── User.php                                            ← MODIFIÉ (is_admin, relations)
│   │   ├── IdentityVerification.php                            ← NOUVEAU
│   │   └── ActivityLog.php                                     ← NOUVEAU
│   ├── Policies/
│   │   └── IdentityVerificationPolicy.php                      ← NOUVEAU
│   ├── Repositories/
│   │   ├── Contracts/
│   │   │   └── VerificationRepositoryInterface.php             ← NOUVEAU
│   │   └── Eloquent/
│   │       └── VerificationRepository.php                      ← NOUVEAU
│   ├── Services/
│   │   ├── IdentityVerificationService.php                     ← NOUVEAU
│   │   ├── AuditService.php                                    ← NOUVEAU
│   │   └── TalentProfileService.php                            ← MODIFIÉ (recalculateCompletion)
│   └── Providers/
│       └── AppServiceProvider.php                              ← MODIFIÉ (binding repository)
├── config/
│   ├── filesystems.php                                         ← MODIFIÉ (disk identity_documents)
│   └── bookmi.php                                              ← MODIFIÉ (section verification)
├── database/
│   ├── factories/
│   │   └── IdentityVerificationFactory.php                     ← NOUVEAU
│   └── migrations/
│       ├── xxxx_add_is_admin_to_users_table.php                ← NOUVEAU
│       ├── xxxx_create_identity_verifications_table.php        ← NOUVEAU
│       └── xxxx_create_activity_logs_table.php                 ← NOUVEAU
├── routes/
│   ├── api.php                                                 ← MODIFIÉ (routes verifications)
│   └── admin.php                                               ← MODIFIÉ (routes admin verifications)
├── bootstrap/
│   └── app.php                                                 ← MODIFIÉ (middleware alias admin)
└── tests/
    ├── Feature/
    │   ├── Api/V1/
    │   │   └── VerificationControllerTest.php                  ← NOUVEAU
    │   └── Admin/
    │       └── VerificationControllerTest.php                  ← NOUVEAU
    └── Unit/
        └── Services/
            └── IdentityVerificationServiceTest.php             ← NOUVEAU
```

**Fichiers existants à NE PAS modifier (sauf ceux listés ci-dessus) :**
- `app/Exceptions/BookmiException.php` — utiliser pour les erreurs métier VERIFICATION_*
- `app/Http/Traits/ApiResponseTrait.php` — utiliser `successResponse()` et `errorResponse()`
- `app/Http/Controllers/Api/V1/BaseController.php` — hériter dans le controller API (PAS le controller Admin)
- `app/Models/TalentProfile.php` — la colonne `is_verified` existe déjà (default false)

### Intelligence Story 1.3 (Story Précédente Backend)

**Patterns établis à respecter :**
- `BaseController` utilise `ApiResponseTrait` + `AuthorizesRequests` → tous les controllers API V1 héritent de `BaseController`
- Le controller Admin N'hérite PAS de BaseController (pas d'API — il retourne du JSON directement)
- `BookmiException` avec `errorCode`, `message`, `statusCode`, `details` → utiliser pour les erreurs VERIFICATION_*
- La Policy est auto-découverte par Laravel — pas besoin d'enregistrement manuel
- PHPStan level 5 — le code DOIT passer `--memory-limit=512M`
- Pint PSR-12 — le code DOIT passer le formatage

**Leçons apprises Story 1.3 :**
- PHPStan `when()` type mismatch — utiliser `!== null` au lieu de valeurs nullable dans les conditions
- Laravel 12 base Controller est VIDE — `AuthorizesRequests` est dans `BaseController`
- AuthenticationException handler retourne 401 JSON pour les routes API/JSON
- Les tests Feature utilisent `$this->actingAs($user, 'sanctum')` pour les routes API
- Les tests de validation vérifient `assertJsonPath('error.code', 'VALIDATION_FAILED')` (pas `assertJsonValidationErrors`)
- L'override `validated()` dans les FormRequest permet de filtrer les données avant traitement
- `calculateCompletionFromData(array $data)` calcule AVANT persistance dans createProfile/updateProfile
- Le `$this->authorize('action', $model)` lève `AuthorizationException` → 403 automatique

**Code review corrections Story 1.3 :**
- Les clés config sont `bookmi.talent.levels.X` (PAS `bookmi.talent_levels.X`)
- La validation de subcategory_id utilise une closure pour vérifier la relation parent-enfant
- Les social_links sont filtrés aux clés autorisées via `validated()` override

### Testing Requirements — Détail

**Feature Tests — `tests/Feature/Api/V1/VerificationControllerTest.php` :**

```
test_user_can_submit_verification_with_valid_document         → 201, fichier chiffré sur disque
test_cannot_submit_without_authentication                     → 401
test_cannot_submit_without_talent_profile                     → 422 VERIFICATION_NO_TALENT_PROFILE
test_cannot_submit_duplicate_pending_verification             → 422 VERIFICATION_ALREADY_PENDING
test_can_resubmit_after_rejection                             → 201
test_validation_fails_with_invalid_file_type                  → 422
test_validation_fails_with_missing_document                   → 422
test_user_can_check_own_verification_status                   → 200
test_returns_404_when_no_verification_exists                  → 404
```

**Feature Tests — `tests/Feature/Admin/VerificationControllerTest.php` :**

```
test_admin_can_list_pending_verifications                     → 200, JSON
test_non_admin_cannot_access_admin_routes                     → 403
test_unauthenticated_user_cannot_access_admin_routes          → 302 (redirect login)
test_admin_can_view_verification_details                      → 200
test_admin_can_stream_encrypted_document                      → 200, correct Content-Type
test_admin_can_approve_verification                           → 200, is_verified=true, file deleted
test_admin_can_reject_verification                            → 200, rejection_reason saved, file deleted
test_cannot_review_already_reviewed_verification              → 422 VERIFICATION_ALREADY_REVIEWED
test_rejection_requires_reason                                → 422
test_approval_updates_profile_completion_percentage           → is_verified=true, percentage augmenté
test_review_creates_audit_log                                 → activity_logs a un enregistrement
```

**Unit Tests — `tests/Unit/Services/IdentityVerificationServiceTest.php` :**

```
test_submit_stores_encrypted_file
test_submit_fails_if_pending_exists
test_review_approve_sets_verified_and_deletes_file
test_review_reject_saves_reason_and_deletes_file
test_recalculate_completion_adds_verification_bonus
test_get_document_content_decrypts_file
```

**Note Testing Storage :**
- Utiliser `Storage::fake('identity_documents')` dans les tests pour ne PAS écrire sur le vrai disque
- Pour tester le chiffrement réel, utiliser un seul test avec `Storage::disk('identity_documents')` réel
- Utiliser `UploadedFile::fake()->image('cni.jpg', 800, 600)` pour simuler les uploads

**Note Testing Admin routes :**
- Les routes admin utilisent le guard `web` — `$this->actingAs($admin)` sans second argument
- `$admin = User::factory()->create(['is_admin' => true])`
- Pour les tests JSON : `$this->actingAs($admin)->getJson('/admin/verifications')`

### Versions des Technologies

| Technologie | Version | Notes |
|---|---|---|
| PHP | 8.4 (FPM Debian) | Via Docker |
| Laravel | 12.51.0 | `Crypt` facade pour AES-256-CBC |
| MySQL | 8.4.8 LTS | Via Docker |
| Redis | 7.4-alpine | Via Docker |
| Laravel Sanctum | Latest | Auth API (routes user) |
| spatie/laravel-sluggable | 3.7.5 | Déjà installé |
| Larastan | 3.9.2 | Static analysis level 5 |
| Laravel Pint | 1.27.1 | PSR-12 formatting |
| PHPUnit | 11.5.3 | Testing framework |

### Critères de Validation Finale

- [ ] `php artisan migrate:fresh --seed` réussit sans erreur
- [ ] `php artisan test` — TOUS les tests passent (existants Story 1.1 + 1.3 + nouveaux)
- [ ] `./vendor/bin/pint --test` — 0 erreur de formatage
- [ ] `./vendor/bin/phpstan analyse --memory-limit=512M` — 0 erreur level 5
- [ ] `POST /api/v1/verifications` stocke le fichier chiffré et retourne 201
- [ ] `GET /api/v1/verifications/me` retourne le statut de vérification
- [ ] `GET /admin/verifications` accessible uniquement aux admins
- [ ] `POST /admin/verifications/{id}/review` avec `approved` → is_verified=true, fichier supprimé
- [ ] `POST /admin/verifications/{id}/review` avec `rejected` → rejection_reason sauvé, fichier supprimé
- [ ] L'audit trail est créé pour chaque action admin
- [ ] Le `profile_completion_percentage` augmente de +20% après approbation
- [ ] Les messages de validation sont en français
- [ ] Le format JSON envelope est respecté pour toutes les réponses

### References

- [Source: _bmad-output/planning-artifacts/architecture.md] — Security decisions (AES-256-CBC), admin routes (/admin/* web guard), activity_logs table, file storage patterns
- [Source: _bmad-output/planning-artifacts/epics.md#Epic-1] — Story 1.4 AC, FR3/FR4/FR5, NFR18 (suppression document après vérification)
- [Source: _bmad-output/planning-artifacts/ux-design-specification.md] — Badge "Vérifié" visible, Journey 5 (Onboarding Talent step 5/5: vérification CNI), design implications sécurité
- [Source: _bmad-output/implementation-artifacts/1-3-modele-talent-et-crud-profil.md] — BaseController + AuthorizesRequests, Policy auto-discovery, calculateCompletionFromData pattern, PHPStan/Pint corrections, test patterns, exception handler in bootstrap/app.php
- [Source: Laravel 12.x Crypt facade] — `Crypt::encrypt($data, serialize: false)` pour données binaires, `Crypt::decrypt($data, unserialize: false)` pour récupérer
- [Source: Laravel 12.x Storage] — `Storage::disk('name')->put()`, `Storage::fake()` pour tests
- [Source: PHP 8.4 Backed Enums] — VerificationStatus enum cast dans Eloquent

## Dev Agent Record

### Agent Model Used

Claude Opus 4.6

### Debug Log References

- PHPStan: Larastan ne résout pas les casts enum/datetime sur les modèles Eloquent — utiliser les propriétés directement dans les Resources (pas `->value` ni `->toIso8601String()`), et `@var` annotations dans les Services pour les comparaisons enum
- AuditService: L'injection de `Request` via constructeur ne fonctionne pas en tests unitaires car le user n'est pas set — utiliser `auth()->id()` et `request()->ip()` (helpers)
- Policy vs Service: Le `$this->authorize('create', ...)` retourne 403 générique — déléguer la validation métier au Service qui retourne des codes d'erreur spécifiques (VERIFICATION_NO_TALENT_PROFILE, VERIFICATION_ALREADY_PENDING)
- Admin routes: `getJson()` en test déclenche le handler JSON qui retourne 401, pas le redirect 302 du guard web

### Completion Notes List

- 67 tests passent (230 assertions) — 0 régression sur les 38 tests existants (Stories 1.1-1.3) + 29 nouveaux tests
- Pint PSR-12 : 0 erreur
- PHPStan level 5 : 0 erreur
- 29 nouveaux tests : 9 Feature API, 11 Feature Admin, 9 Unit Service
- AES-256-CBC encryption/decryption vérifié fonctionnel (Crypt::encrypt/decrypt avec serialize: false)
- Streaming de document déchiffré en mémoire (jamais sur disque en clair)
- Suppression automatique du document après toute décision (approved/rejected) — NFR18
- Audit trail créé pour chaque action admin avec IP, causer, subject polymorphique
- Profile completion recalculation (+20% pour is_verified) fonctionne correctement
- Code review: 6 issues fixées (3 HIGH, 3 MEDIUM), 2 action items LOW créés

### Change Log

- 2026-02-17: Story created by create-story workflow — exhaustive analysis of architecture, UX design, previous story 1.3 intelligence (patterns, corrections, leçons), web research (Laravel 12 file encryption, AES-256-CBC, admin middleware patterns, audit logging)
- 2026-02-17: Implementation complete — 12 tasks, 28 new tests, all ACs satisfied, PHPStan 0 errors, Pint 0 errors
- 2026-02-17: Code review — 8 issues trouvées (3H, 3M, 2L). 6 fixées automatiquement :
  - [H1] calculateCompletionFromData() n'incluait pas is_verified → régression si updateProfile après vérification — fixé
  - [H2] Race condition doublon pending — ajout DB::transaction + lockForUpdate via findPendingByUserId — fixé
  - [H3] Exception handlers ne couvraient pas /admin/* sans Accept header — ajout || $request->is('admin/*') — fixé
  - [M1] User::identityVerification() HasOne vs multiples vérifications — ajout identityVerifications() HasMany + latestOfMany() — fixé
  - [M2] Index dupliqués user_id/reviewer_id (FK crée déjà un index) — supprimés de la migration — fixé
  - [M3] Manque Content-Disposition: attachment sur le stream document — ajouté — fixé
  - 2 action items LOW créés dans Review Follow-ups (AI)

### File List

**New files:**
- bookmi/app/Enums/VerificationStatus.php
- bookmi/app/Models/IdentityVerification.php
- bookmi/app/Models/ActivityLog.php
- bookmi/app/Http/Middleware/EnsureUserIsAdmin.php
- bookmi/app/Http/Controllers/Api/V1/VerificationController.php
- bookmi/app/Http/Controllers/Admin/VerificationController.php
- bookmi/app/Http/Requests/Api/StoreVerificationRequest.php
- bookmi/app/Http/Requests/Admin/ReviewVerificationRequest.php
- bookmi/app/Http/Resources/VerificationResource.php
- bookmi/app/Http/Resources/AdminVerificationResource.php
- bookmi/app/Policies/IdentityVerificationPolicy.php
- bookmi/app/Repositories/Contracts/VerificationRepositoryInterface.php
- bookmi/app/Repositories/Eloquent/VerificationRepository.php
- bookmi/app/Services/IdentityVerificationService.php
- bookmi/app/Services/AuditService.php
- bookmi/database/migrations/2026_02_17_180000_add_is_admin_to_users_table.php
- bookmi/database/migrations/2026_02_17_180001_create_identity_verifications_table.php
- bookmi/database/migrations/2026_02_17_180002_create_activity_logs_table.php
- bookmi/database/factories/IdentityVerificationFactory.php
- bookmi/tests/Feature/Api/V1/VerificationControllerTest.php
- bookmi/tests/Feature/Admin/VerificationControllerTest.php
- bookmi/tests/Unit/Services/IdentityVerificationServiceTest.php

**Modified files:**
- bookmi/app/Models/User.php — added is_admin fillable/cast, identityVerifications() HasMany, identityVerification() HasOne latestOfMany(), activityLogs() HasMany
- bookmi/app/Services/TalentProfileService.php — added recalculateCompletion(), calculateCompletionFromData now includes is_verified
- bookmi/app/Providers/AppServiceProvider.php — added VerificationRepositoryInterface binding
- bookmi/config/filesystems.php — added identity_documents disk
- bookmi/config/bookmi.php — added verification section
- bookmi/routes/api.php — added verifications routes
- bookmi/routes/admin.php — added admin verification routes
- bookmi/bootstrap/app.php — added admin middleware alias, exception handlers cover admin/* routes
- bookmi/database/factories/UserFactory.php — added admin() state
