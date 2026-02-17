# Story 1.8: Packages de prestation (backend)

Status: done

## Story

As a talent,
I want créer et gérer mes packages de prestation (Essentiel, Standard, Premium),
So that les clients puissent choisir l'offre adaptée à leur budget.

**Functional Requirements:** FR23, FR24
**Non-Functional Requirements:** NFR3 (réponse < 1s)

## Acceptance Criteria (BDD)

**AC1 — Créer un package de prestation**
**Given** un talent authentifié avec un profil actif
**When** il envoie `POST /api/v1/service_packages` avec name, description, cachet_amount, duration_minutes, inclusions, type
**Then** le package est créé et associé au profil talent
**And** la réponse retourne le package créé avec le format JSON envelope standard (201)

**AC2 — Types de packages supportés**
**Given** un talent créant un package
**When** il spécifie le type du package
**Then** 3 types de packages classiques sont supportés : `essentiel`, `standard`, `premium`
**And** les micro-prestations sont supportées comme type spécial `micro` (FR24)
**And** un type invalide retourne une erreur 422

**AC3 — Modifier un package existant**
**Given** un talent authentifié propriétaire du package
**When** il envoie `PUT /api/v1/service_packages/{service_package}` avec les données modifiées
**Then** le package est mis à jour
**And** seul le talent propriétaire peut modifier son package (403 sinon)

**AC4 — Supprimer un package (soft delete)**
**Given** un talent authentifié propriétaire du package
**When** il envoie `DELETE /api/v1/service_packages/{service_package}`
**Then** le package est supprimé (soft delete) avec réponse 204
**And** seul le talent propriétaire peut supprimer son package (403 sinon)

**AC5 — Lister ses propres packages**
**Given** un talent authentifié
**When** il envoie `GET /api/v1/service_packages`
**Then** la liste de ses packages actifs est retournée (triée par sort_order puis created_at)
**And** seuls les packages du talent authentifié sont retournés

**AC6 — Packages affichés sur le profil public**
**Given** un profil talent public avec des packages actifs
**When** un visiteur accède à `GET /api/v1/talents/{slug}`
**Then** les packages actifs sont inclus dans `data.attributes.service_packages` (remplace le `[]` placeholder)
**And** les packages supprimés ou inactifs ne sont pas affichés

**AC7 — Impact sur le profil completion**
**Given** un talent avec au moins 1 package actif
**When** le profile_completion_percentage est recalculé
**Then** les packages ajoutent +20% au score de complétion (commentaire existant dans TalentProfileService)

**AC8 — Validation des données**
**Given** des données invalides envoyées en POST ou PUT
**When** la validation échoue
**Then** une réponse 422 est retournée avec les erreurs en français
**And** le cachet_amount est en centimes FCFA (entier positif, min 1000)
**And** le name est obligatoire, max 150 caractères
**And** le duration_minutes est obligatoire pour les types classiques, optionnel pour micro

## Tasks / Subtasks

- [x] Task 1: Créer la migration `service_packages` (AC: AC1, AC2)
  - [x] 1.1: Créer la migration `xxxx_xx_xx_create_service_packages_table.php`
  - [x] 1.2: Colonnes : `id`, `talent_profile_id` (FK), `name`, `description` (nullable), `cachet_amount` (integer), `duration_minutes` (integer, nullable), `inclusions` (JSON, nullable), `type` (enum: essentiel, standard, premium, micro), `is_active` (boolean, default true), `sort_order` (integer, default 0), timestamps, soft deletes
  - [x] 1.3: Index sur `talent_profile_id` + `is_active` pour les requêtes de listing
  - [x] 1.4: Contrainte FK `talent_profile_id` → `talent_profiles.id` avec `cascadeOnDelete()`

- [x] Task 2: Créer le modèle `ServicePackage` (AC: AC1, AC2)
  - [x] 2.1: Créer `app/Models/ServicePackage.php` avec `HasFactory`, `SoftDeletes`
  - [x] 2.2: Définir `$fillable` : name, description, cachet_amount, duration_minutes, inclusions, type, is_active, sort_order, talent_profile_id
  - [x] 2.3: Définir `casts()` : cachet_amount → integer, duration_minutes → integer, inclusions → array, is_active → boolean, type → `PackageType::class` enum
  - [x] 2.4: Relation `talentProfile(): BelongsTo` vers `TalentProfile`
  - [x] 2.5: Scope `scopeActive()` : `where('is_active', true)`
  - [x] 2.6: Scope `scopeOrdered()` : `orderBy('sort_order')->orderBy('created_at')`

- [x] Task 3: Créer l'enum `PackageType` (AC: AC2)
  - [x] 3.1: Créer `app/Enums/PackageType.php` — enum string-backed : `essentiel`, `standard`, `premium`, `micro`

- [x] Task 4: Ajouter la relation `servicePackages()` à `TalentProfile` (AC: AC6)
  - [x] 4.1: Ajouter `servicePackages(): HasMany` dans `TalentProfile` model

- [x] Task 5: Créer la Factory `ServicePackageFactory` (AC: tous)
  - [x] 5.1: Créer `database/factories/ServicePackageFactory.php`
  - [x] 5.2: Définition par défaut : name, description, cachet_amount (random 5000000-50000000), duration_minutes (60-240), inclusions (null ou tableau), type (essentiel), is_active (true), sort_order (0), talent_profile_id
  - [x] 5.3: States : `micro()` (type micro, duration_minutes null), `inactive()` (is_active false), `premium()`, `standard()`

- [x] Task 6: Créer le Repository (AC: AC1, AC3, AC4, AC5, AC6)
  - [x] 6.1: Créer `app/Repositories/Contracts/ServicePackageRepositoryInterface.php` — interface avec : create, update, delete, findById, findByTalentProfileId, findActiveByTalentProfileId
  - [x] 6.2: Créer `app/Repositories/Eloquent/ServicePackageRepository.php` — implémentation Eloquent
  - [x] 6.3: `findByTalentProfileId(int $talentProfileId): Collection` — tous les packages (y compris inactifs)
  - [x] 6.4: `findActiveByTalentProfileId(int $talentProfileId): Collection` — scope `active()->ordered()`
  - [x] 6.5: Enregistrer le binding dans `AppServiceProvider` : `ServicePackageRepositoryInterface → ServicePackageRepository`

- [x] Task 7: Créer le Service `ServicePackageService` (AC: AC1, AC3, AC4, AC5, AC7)
  - [x] 7.1: Créer `app/Services/ServicePackageService.php`
  - [x] 7.2: Injection du `ServicePackageRepositoryInterface` + `TalentProfileService`
  - [x] 7.3: `createPackage(TalentProfile $profile, array $data): ServicePackage` — crée le package, met à jour le profile_completion
  - [x] 7.4: `updatePackage(ServicePackage $package, array $data): ServicePackage`
  - [x] 7.5: `deletePackage(ServicePackage $package): bool` — soft delete, recalcule profile_completion
  - [x] 7.6: `getPackagesForTalent(TalentProfile $profile): Collection` — packages actifs du talent, triés
  - [x] 7.7: Après create/delete, appeler `TalentProfileService::recalculateCompletion()` pour mettre à jour le +20%

- [x] Task 8: Mettre à jour `TalentProfileService::recalculateCompletion()` (AC: AC7)
  - [x] 8.1: Ajouter la logique : si au moins 1 `servicePackage` actif → +20%
  - [x] 8.2: Utiliser `$profile->servicePackages()->active()->exists()` pour le check
  - [x] 8.3: Mettre à jour aussi `calculateCompletionFromData()` si applicable (pas de packages lors de la création initiale du profil → ne pas toucher cette méthode)

- [x] Task 9: Créer les Form Requests (AC: AC1, AC3, AC8)
  - [x] 9.1: Créer `app/Http/Requests/Api/StoreServicePackageRequest.php` — rules pour name (required, max:150), description (nullable, max:1000), cachet_amount (required, integer, min:1000), duration_minutes (required_unless type micro, integer, min:1), inclusions (nullable, array), inclusions.* (string, max:200), type (required, in:essentiel,standard,premium,micro), is_active (boolean), sort_order (integer, min:0)
  - [x] 9.2: Messages de validation en français
  - [x] 9.3: Créer `app/Http/Requests/Api/UpdateServicePackageRequest.php` — mêmes règles mais tout `sometimes` (partial update)

- [x] Task 10: Créer la Policy `ServicePackagePolicy` (AC: AC3, AC4, AC5)
  - [x] 10.1: Créer `app/Policies/ServicePackagePolicy.php`
  - [x] 10.2: `update(User $user, ServicePackage $package): bool` — vérifie que l'utilisateur est le propriétaire du talent_profile associé au package
  - [x] 10.3: `delete(User $user, ServicePackage $package): bool` — même vérification
  - [x] 10.4: Enregistrer dans `AuthServiceProvider` ou via auto-discovery (vérifier le pattern du projet)

- [x] Task 11: Créer la Resource `ServicePackageResource` (AC: AC1, AC5, AC6)
  - [x] 11.1: Créer `app/Http/Resources/ServicePackageResource.php`
  - [x] 11.2: Format : `id`, `type: 'service_package'`, `attributes: { name, description, cachet_amount, duration_minutes, inclusions, type, is_active, sort_order, created_at }`

- [x] Task 12: Créer `ServicePackageController` (AC: AC1, AC3, AC4, AC5)
  - [x] 12.1: Créer `app/Http/Controllers/Api/V1/ServicePackageController.php` extending `BaseController`
  - [x] 12.2: Injection de `ServicePackageService`
  - [x] 12.3: `index(Request $request)` — liste les packages du talent authentifié (via `$request->user()->talentProfile`)
  - [x] 12.4: `store(StoreServicePackageRequest $request)` — crée un package, retourne 201
  - [x] 12.5: `update(UpdateServicePackageRequest $request, ServicePackage $servicePackage)` — `$this->authorize('update', $servicePackage)`, met à jour
  - [x] 12.6: `destroy(Request $request, ServicePackage $servicePackage)` — `$this->authorize('delete', $servicePackage)`, supprime, retourne 204
  - [x] 12.7: Gestion du cas "talent sans profil" → `errorResponse('TALENT_PROFILE_NOT_FOUND', ..., 404)`

- [x] Task 13: Ajouter les routes API (AC: AC1, AC3, AC4, AC5)
  - [x] 13.1: Ajouter dans `routes/api.php` sous le group `auth:sanctum` : `Route::apiResource('service_packages', ServicePackageController::class)->except(['show'])`
  - [x] 13.2: Les routes générées : GET index, POST store, PUT update, DELETE destroy (pas de show publique — le show est via le profil talent)

- [x] Task 14: Mettre à jour `TalentDetailResource` pour les packages (AC: AC6)
  - [x] 14.1: Remplacer `'service_packages' => []` par `ServicePackageResource::collection($this->whenLoaded('servicePackages'))`
  - [x] 14.2: Mettre à jour `TalentProfileService::getPublicProfile()` pour eager load `servicePackages` (scope active + ordered)
  - [x] 14.3: Importer `ServicePackageResource` dans `TalentDetailResource`

- [x] Task 15: Ajouter relation `talentProfile()` sur User si absente (AC: AC5)
  - [x] 15.1: Vérifier si `User::talentProfile(): HasOne` existe déjà
  - [x] 15.2: Si absent, ajouter la relation dans le modèle User

- [x] Task 16: Écrire les tests Feature (AC: tous)
  - [x] 16.1: `test_talent_can_create_service_package` — POST 201 avec données valides
  - [x] 16.2: `test_create_package_validates_required_fields` — POST 422 sans name/cachet_amount
  - [x] 16.3: `test_create_package_validates_type_enum` — POST 422 avec type invalide
  - [x] 16.4: `test_create_micro_package_without_duration` — POST 201 avec type micro, sans duration_minutes
  - [x] 16.5: `test_create_package_requires_authentication` — POST 401 sans token
  - [x] 16.6: `test_create_package_requires_talent_profile` — POST 404 si user sans profil talent
  - [x] 16.7: `test_talent_can_list_own_packages` — GET 200 retourne uniquement ses packages
  - [x] 16.8: `test_list_packages_ordered_by_sort_order` — vérifier l'ordre
  - [x] 16.9: `test_talent_can_update_own_package` — PUT 200 avec données modifiées
  - [x] 16.10: `test_talent_cannot_update_other_talent_package` — PUT 403
  - [x] 16.11: `test_talent_can_delete_own_package` — DELETE 204
  - [x] 16.12: `test_talent_cannot_delete_other_talent_package` — DELETE 403
  - [x] 16.13: `test_public_profile_includes_active_packages` — GET /api/v1/talents/{slug} inclut les packages actifs
  - [x] 16.14: `test_public_profile_excludes_inactive_packages` — packages inactifs non retournés
  - [x] 16.15: `test_public_profile_excludes_deleted_packages` — packages soft-deleted non retournés
  - [x] 16.16: `test_create_package_updates_profile_completion` — profile_completion_percentage augmente de 20
  - [x] 16.17: `test_delete_last_package_updates_profile_completion` — profile_completion diminue si 0 packages actifs restants
  - [x] 16.18: `test_cachet_amount_stored_as_centimes` — montant entier (pas float)
  - [x] 16.19: `test_inclusions_stored_as_json_array` — inclusions correctement sérialisées/désérialisées

- [x] Task 17: Vérifications qualité
  - [x] 17.1: `./vendor/bin/pint --test` — 0 erreurs
  - [x] 17.2: `./vendor/bin/phpstan analyse --memory-limit=512M` — 0 erreurs
  - [x] 17.3: `php artisan test` — tous les tests passent, 0 régressions sur les 126 tests existants

### Review Follow-ups (AI)

- [ ] [AI-Review][LOW] L1 — Renommer `getPackagesForTalent()` → `getActivePackagesForTalent()` dans ServicePackageService [app/Services/ServicePackageService.php:54]
- [ ] [AI-Review][LOW] L2 — `findByTalentProfileId()` est du code mort — supprimer ou conserver pour future story de gestion des packages inactifs [app/Repositories/Eloquent/ServicePackageRepository.php:44]
- [ ] [AI-Review][LOW] L3 — `destroy()` utilise `response()->json(null, 204)` au lieu du pattern BaseController — aligner si BaseController offre une méthode 204 [app/Http/Controllers/Api/V1/ServicePackageController.php:80]

## Dev Notes

### Architecture & Patterns Obligatoires

**Pattern Controller → Service → Repository → Model (ARCH-PATTERN-2) :**
```
ServicePackageController::store(request)
  → ServicePackageService::createPackage(talentProfile, data)
    → ServicePackageRepository::create(data)
    → TalentProfileService::recalculateCompletion(profile)
  → ServicePackageResource(package)
```

**Pattern CRUD complet :**
```
ServicePackageController::index(request) → ServicePackageService::getPackagesForTalent(profile)
ServicePackageController::store(request) → ServicePackageService::createPackage(profile, data)
ServicePackageController::update(request, package) → ServicePackageService::updatePackage(package, data)
ServicePackageController::destroy(request, package) → ServicePackageService::deletePackage(package)
```

### Composants existants à réutiliser — NE PAS recréer

| Fichier existant | Utilisation |
|---|---|
| `app/Http/Controllers/Api/V1/BaseController` | Hériter — `successResponse()`, `errorResponse()` |
| `app/Http/Traits/ApiResponseTrait` | Via BaseController — format JSON envelope |
| `app/Exceptions/BookmiException` | Pour les erreurs métier (TALENT_PROFILE_NOT_FOUND) |
| `app/Models/TalentProfile` | Ajouter relation `servicePackages()` — NE PAS modifier la structure existante |
| `app/Services/TalentProfileService` | Étendre `recalculateCompletion()` — NE PAS recréer |
| `app/Http/Resources/TalentDetailResource` | Remplacer placeholder `service_packages: []` — NE PAS recréer |
| `app/Providers/AppServiceProvider` | Ajouter le binding ServicePackageRepository |
| `app/Policies/TalentProfilePolicy` | Pattern à suivre pour ServicePackagePolicy |

### Schema de la table `service_packages`

```php
Schema::create('service_packages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('talent_profile_id')->constrained()->cascadeOnDelete();
    $table->string('name', 150);
    $table->text('description')->nullable();
    $table->integer('cachet_amount');           // Centimes FCFA
    $table->integer('duration_minutes')->nullable(); // Nullable pour micro-prestations
    $table->json('inclusions')->nullable();     // ["Sonorisation", "2 danseurs", "Éclairage"]
    $table->string('type');                     // Enum: essentiel, standard, premium, micro
    $table->boolean('is_active')->default(true);
    $table->integer('sort_order')->default(0);
    $table->timestamps();
    $table->softDeletes();

    $table->index(['talent_profile_id', 'is_active']);
});
```

### Enum `PackageType` — Convention existante

```php
// Suivre le pattern de app/Enums/TalentLevel.php
namespace App\Enums;

enum PackageType: string
{
    case Essentiel = 'essentiel';
    case Standard = 'standard';
    case Premium = 'premium';
    case Micro = 'micro';
}
```

### ServicePackageResource — Structure exacte de la réponse

```json
{
    "data": {
        "id": 1,
        "type": "service_package",
        "attributes": {
            "name": "Standard",
            "description": "Prestation 2h avec 2 danseurs",
            "cachet_amount": 12000000,
            "duration_minutes": 120,
            "inclusions": ["Sonorisation", "2 danseurs"],
            "type": "standard",
            "is_active": true,
            "sort_order": 1,
            "created_at": "2026-02-17T14:30:00Z"
        }
    }
}
```

### Validation — duration_minutes conditionnel

```php
// StoreServicePackageRequest::rules()
'duration_minutes' => [
    Rule::requiredIf(fn () => $this->input('type') !== 'micro'),
    'nullable',
    'integer',
    'min:1',
],
```

Pour les micro-prestations (vidéo personnalisée, dédicace audio), `duration_minutes` est optionnel car la durée n'est pas pertinente.

### Intégration profil public — TalentDetailResource

```php
// AVANT (Story 1.7 — placeholder)
'service_packages' => [],

// APRÈS (Story 1.8 — données réelles)
'service_packages' => ServicePackageResource::collection(
    $this->whenLoaded('servicePackages')
),
```

Et dans `TalentProfileService::getPublicProfile()` :
```php
$profile->load(['category', 'subcategory', 'servicePackages' => function ($query) {
    $query->active()->ordered();
}]);
```

### Profile completion — Mise à jour recalculateCompletion()

```php
// AVANT (commentaire placeholder)
// Au moins 1 package (futur — Story 1.8) → +20%

// APRÈS (implémentation réelle)
if ($profile->servicePackages()->where('is_active', true)->exists()) {
    $percentage += 20;
}
```

**ATTENTION :** Ne modifier QUE `recalculateCompletion()`. Ne PAS toucher `calculateCompletionFromData()` car il est appelé lors de la création/mise à jour du profil (quand les packages n'existent pas encore).

### Routes API — Placement

```php
// routes/api.php — Sous le groupe auth:sanctum
Route::middleware('auth:sanctum')->group(function () {
    // ... routes existantes talent_profiles, verifications ...

    Route::apiResource('service_packages', ServicePackageController::class)
        ->except(['show']);
});
```

**Pas de route `show` individuelle** — les packages sont consultables via le profil public talent (`GET /api/v1/talents/{slug}`). L'`apiResource` sans `show` génère : index, store, update, destroy.

### Policy — Vérification de propriété

```php
// ServicePackagePolicy — Accès via la relation talent_profile → user
public function update(User $user, ServicePackage $package): bool
{
    return $user->id === $package->talentProfile->user_id;
}
```

### Récupération du talent_profile dans le Controller

```php
// Pattern pour index/store : récupérer le profil du talent authentifié
$user = $request->user();
$profile = $user->talentProfile;

if (!$profile) {
    return $this->errorResponse(
        'TALENT_PROFILE_NOT_FOUND',
        'Vous devez créer un profil talent avant de gérer vos packages.',
        404,
    );
}
```

**Cela nécessite la relation `User::talentProfile(): HasOne`** — vérifier si elle existe, sinon la créer (Task 15).

### Gestion des erreurs — Codes d'erreur métier

| Code | HTTP | Contexte |
|---|---|---|
| `TALENT_PROFILE_NOT_FOUND` | 404 | User sans profil talent tente de créer un package |
| `VALIDATION_FAILED` | 422 | Form Request validation (automatique Laravel) |
| 403 Forbidden | 403 | Policy deny (automatique Laravel via `$this->authorize()`) |

### Testing Standards

**Pattern test CRUD :**
```php
// Créer un user authentifié avec un profil talent
$user = User::factory()->create();
$talent = TalentProfile::factory()->verified()->for($user)->create();

// Agir en tant que ce user
$this->actingAs($user, 'sanctum');

// Créer un package
$response = $this->postJson('/api/v1/service_packages', [
    'name' => 'Essentiel',
    'cachet_amount' => 8000000,
    'duration_minutes' => 90,
    'type' => 'essentiel',
]);

$response->assertStatus(201)
    ->assertJsonPath('data.type', 'service_package')
    ->assertJsonPath('data.attributes.name', 'Essentiel');
```

**Tests de non-régression obligatoires :**
- Les 126 tests existants doivent continuer à passer
- Le endpoint `GET /api/v1/talents/{slug}` doit toujours fonctionner (avec les packages en plus)
- Le recalcul de `profile_completion_percentage` ne doit pas casser les tests existants

### Project Structure Notes

**Nouveaux fichiers à créer :**
```
app/Models/ServicePackage.php                              # Modèle Eloquent
app/Enums/PackageType.php                                  # Enum string-backed
app/Http/Controllers/Api/V1/ServicePackageController.php   # Controller CRUD
app/Services/ServicePackageService.php                     # Service logique métier
app/Repositories/Contracts/ServicePackageRepositoryInterface.php  # Interface
app/Repositories/Eloquent/ServicePackageRepository.php     # Implémentation
app/Http/Requests/Api/StoreServicePackageRequest.php       # Validation création
app/Http/Requests/Api/UpdateServicePackageRequest.php      # Validation modification
app/Http/Resources/ServicePackageResource.php              # API Resource
app/Policies/ServicePackagePolicy.php                      # Autorisation
database/migrations/xxxx_xx_xx_create_service_packages_table.php  # Migration
database/factories/ServicePackageFactory.php               # Factory tests
tests/Feature/Api/V1/ServicePackageControllerTest.php      # Tests Feature
```

**Fichiers existants à modifier :**
```
app/Models/TalentProfile.php                  # Ajout relation servicePackages()
app/Models/User.php                           # Vérifier/ajouter relation talentProfile()
app/Services/TalentProfileService.php         # Mise à jour recalculateCompletion()
app/Http/Resources/TalentDetailResource.php   # Remplacer placeholder service_packages
app/Providers/AppServiceProvider.php          # Binding ServicePackageRepository
routes/api.php                                # Routes CRUD service_packages
```

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story-1.8] — AC, FR23, FR24
- [Source: _bmad-output/planning-artifacts/prd.md#FR23-FR24] — Packages Essentiel/Standard/Premium + micro-prestations
- [Source: _bmad-output/planning-artifacts/architecture.md#Data-Architecture] — Service Layer + Repository pattern
- [Source: _bmad-output/planning-artifacts/architecture.md#Naming-Patterns] — snake_case, centimes FCFA, conventions
- [Source: _bmad-output/planning-artifacts/architecture.md#API-Response-Formats] — JSON envelope, error codes
- [Source: _bmad-output/planning-artifacts/architecture.md#Project-Structure] — Emplacement modèles, migrations, controllers
- [Source: _bmad-output/implementation-artifacts/1-7-profil-public-talent.md] — TalentDetailResource placeholder, patterns existants

### Previous Story Intelligence

**Story 1.7 (Profil public talent) — Leçons directement applicables :**

- `TalentDetailResource` contient `'service_packages' => []` → À REMPLACER par les vraies données
- `TalentProfileService::recalculateCompletion()` contient le commentaire `// Au moins 1 package (futur — Story 1.8) → +20%` → À IMPLÉMENTER
- `TalentProfileService::getPublicProfile()` eager load `['category', 'subcategory']` → Ajouter `servicePackages` (avec scope active+ordered)
- Code review H1 (XSS JSON-LD) : s'assurer que les noms de packages ne contiennent pas de HTML (validation `strip_tags` ou `max:150` suffit)
- Code review M3 (reliability_score) : les tests de reliability_score existants utilisent `profile_completion_percentage` → s'assurer que le recalcul ne casse pas ces tests
- Pattern Policy : suivre `TalentProfilePolicy` qui utilise `$user->id === $profile->user_id`
- Pattern Form Request : suivre `StoreTalentProfileRequest` avec `rules()` + `messages()` en français
- Pattern Controller : suivre `TalentProfileController` qui utilise `try/catch BookmiException`
- Pattern Repository binding : suivre `AppServiceProvider` avec `$this->app->bind(Interface, Implementation)`

**Attention tests existants (reliability_score) :**
- `test_show_reliability_score_formula` attend score=79 avec `profile_completion_percentage: 60`
- `test_show_reliability_score_max_100` attend score=100 avec `profile_completion_percentage: 100`
- Le recalcul de completion ne doit PAS être déclenché lors de ces tests (ils créent des talents sans packages → completion inchangée)

## Dev Agent Record

### Agent Model Used

Claude Opus 4.6 (claude-opus-4-6)

### Debug Log References

Aucun debug log externe — tous les problèmes résolus dans la session.

### Completion Notes List

- **AC1 (Créer un package):** PASS — POST /api/v1/service_packages crée le package avec format JSON envelope 201. `fresh()` ajouté au Repository pour récupérer les valeurs par défaut DB (is_active=true).
- **AC2 (Types supportés):** PASS — Enum `PackageType` (essentiel, standard, premium, micro). Validation type invalide retourne 422. Micro-prestation sans duration_minutes acceptée.
- **AC3 (Modifier un package):** PASS — PUT met à jour partiellement (rules `sometimes`). Policy empêche modification par un autre talent (403).
- **AC4 (Supprimer un package):** PASS — DELETE soft-delete avec 204. Policy empêche suppression par un autre talent (403).
- **AC5 (Lister ses packages):** PASS — GET retourne uniquement les packages du talent authentifié, triés par sort_order.
- **AC6 (Profil public):** PASS — Packages actifs inclus dans `GET /api/v1/talents/{slug}`. Packages inactifs et soft-deleted exclus. Eager loading avec scope `active()->ordered()`.
- **AC7 (Profile completion):** PASS — +20% quand au moins 1 package actif. Recalcul après create et delete. Tests existants (reliability_score) non impactés.
- **AC8 (Validation):** PASS — Messages en français. `Rule::requiredIf` pour duration_minutes conditionnel. Assertions adaptées au format BookMi envelope (`error.code: VALIDATION_FAILED`).
- **Task 15 (User::talentProfile):** No-op — la relation existait déjà.
- **Qualité:** Pint PASS (2 auto-fixes appliqués), PHPStan 0 erreurs, 145 tests / 486 assertions / 0 failures.
- **[Code Review]** 8 findings (2 HIGH, 3 MEDIUM, 3 LOW). HIGH et MEDIUM corrigés automatiquement. LOW conservés en action items.
- **[Review Fix H1]** `updatePackage()` recalcule maintenant la completion quand `is_active` change.
- **[Review Fix H2]** `UpdateServicePackageRequest` valide la cohérence type↔duration_minutes via `withValidator()`.
- **[Review Fix M1]** Commentaire "futur — Story 1.8" mis à jour dans `calculateCompletionFromData()`.
- **[Review Fix M2]** 7 tests de bordure ajoutés (name max, cachet min/max, duration positive, type change micro→essentiel).
- **[Review Fix M3]** `max:2000000000` ajouté sur `cachet_amount` dans Store et Update requests.
- **Qualité post-review:** Pint PASS, PHPStan 0 erreurs, 152 tests / 516 assertions / 0 failures.

### File List

**Nouveaux fichiers créés (13):**
- `bookmi/database/migrations/2026_02_17_200000_create_service_packages_table.php`
- `bookmi/app/Enums/PackageType.php`
- `bookmi/app/Models/ServicePackage.php`
- `bookmi/database/factories/ServicePackageFactory.php`
- `bookmi/app/Repositories/Contracts/ServicePackageRepositoryInterface.php`
- `bookmi/app/Repositories/Eloquent/ServicePackageRepository.php`
- `bookmi/app/Services/ServicePackageService.php`
- `bookmi/app/Http/Requests/Api/StoreServicePackageRequest.php`
- `bookmi/app/Http/Requests/Api/UpdateServicePackageRequest.php`
- `bookmi/app/Policies/ServicePackagePolicy.php`
- `bookmi/app/Http/Resources/ServicePackageResource.php`
- `bookmi/app/Http/Controllers/Api/V1/ServicePackageController.php`
- `bookmi/tests/Feature/Api/V1/ServicePackageControllerTest.php`

**Fichiers existants modifiés (6):**
- `bookmi/app/Models/TalentProfile.php` — Ajout relation `servicePackages(): HasMany`
- `bookmi/app/Services/TalentProfileService.php` — `recalculateCompletion()` +20% packages, `getPublicProfile()` eager load servicePackages
- `bookmi/app/Http/Resources/TalentDetailResource.php` — Remplacement placeholder `[]` par `ServicePackageResource::collection()`
- `bookmi/app/Providers/AppServiceProvider.php` — Binding `ServicePackageRepositoryInterface → ServicePackageRepository`
- `bookmi/routes/api.php` — Route `apiResource('service_packages')` sous auth:sanctum
- `_bmad-output/implementation-artifacts/sprint-status.yaml` — Status tracking

### Change Log

| Changement | Raison |
|---|---|
| Ajout `->fresh()` dans `ServicePackageRepository::create()` | Les valeurs par défaut DB (is_active=true) ne sont pas présentes sur le modèle retourné par `create()` quand elles ne sont pas dans `$data` |
| Assertions test envelope format au lieu de `assertJsonValidationErrors` | Le custom exception handler BookMi wrap les erreurs de validation dans `{error: {code: 'VALIDATION_FAILED', details: {errors: ...}}}` |
| Pint auto-fix : `new class ()` parenthèses dans migration | Convention Laravel Pint |
| Pint auto-fix : suppression import `ServicePackageResource` dans `TalentDetailResource` | Même namespace `App\Http\Resources` — import inutile |
| `recalculateCompletion()` utilise `->where('is_active', true)->exists()` au lieu de `->active()->exists()` | Requête directe sur la relation sans scope pour éviter toute ambiguïté avec le scope global |
