# Story 1.5: Annuaire des talents avec filtres (backend)

Status: done

## Story

As a client,
I want parcourir l'annuaire des talents vérifiés et filtrer par critères,
So that je puisse trouver le talent adapté à mon événement.

**Functional Requirements:** FR11, FR12
**Non-Functional Requirements:** NFR3 (réponse < 1s), NFR24 (stable à 500 talents)

## Acceptance Criteria (BDD)

**Given** un client (authentifié ou non)
**When** il envoie `GET /api/v1/talents` avec des filtres optionnels
**Then** la liste des talents vérifiés est retournée avec pagination cursor-based
**And** les filtres supportés sont : `category_id`, `subcategory_id`, `min_cachet`, `max_cachet`, `city`, `min_rating`
**And** les résultats sont triables par `rating`, `cachet_amount`, `created_at`
**And** le temps de réponse est < 1 seconde (NFR3)
**And** le format de réponse suit le JSON envelope avec meta cursor

## Tasks / Subtasks

- [x] Task 1: Créer `SearchTalentRequest` — validation des filtres (AC: filtres supportés)
  - [x] 1.1: Créer `app/Http/Requests/Api/SearchTalentRequest.php`
  - [x] 1.2: `authorize()` retourne `true` (endpoint public, pas d'auth requise)
  - [x] 1.3: Règles de validation — tous les champs `nullable` (paramètres GET optionnels)
  - [x] 1.4: Messages de validation en français

- [x] Task 2: Créer `TalentResource` pour les listings (AC: format JSON envelope)
  - [x] 2.1: Créer `app/Http/Resources/TalentResource.php` — resource LÉGÈRE pour cartes listing
  - [x] 2.2: Champs retournés (alignés avec le TalentCard UX)
  - [x] 2.3: NE PAS inclure : `social_links`, `bio`, `total_bookings`, `profile_completion_percentage`, `created_at`, `updated_at`
  - [x] 2.4: Utiliser `$this->when($this->subcategory_id !== null, fn () => [...])` pour subcategory conditionnelle

- [x] Task 3: Ajouter `searchVerified()` au TalentRepository (AC: filtres, tri, pagination)
  - [x] 3.1: Ajouter signature dans `app/Repositories/Contracts/TalentRepositoryInterface.php`
  - [x] 3.2: Implémenter dans `app/Repositories/Eloquent/TalentRepository.php`
  - [x] 3.3: Ajouter les imports nécessaires : `use Illuminate\Pagination\CursorPaginator;`

- [x] Task 4: Créer `SearchService` (AC: logique métier recherche)
  - [x] 4.1: Créer `app/Services/SearchService.php`
  - [x] 4.2: Injection du `TalentRepositoryInterface` via constructeur
  - [x] 4.3: Méthode `searchTalents(array $params, ?string $sortBy, ?string $sortDirection, int $perPage): CursorPaginator`
  - [x] 4.4: Mapper le nom API vers le nom de colonne pour le tri : `rating` → `average_rating`
  - [x] 4.5: Valeurs par défaut : `$sortBy = 'created_at'`, `$sortDirection = 'desc'`, `$perPage = 20`
  - [x] 4.6: Extraire uniquement les clés de filtres pertinentes de `$params` (ignorer sort_by, sort_direction, per_page, cursor)

- [x] Task 5: Créer `TalentController` avec `index()` (AC: endpoint GET /api/v1/talents)
  - [x] 5.1: Créer `app/Http/Controllers/Api/V1/TalentController.php` — extends `BaseController`
  - [x] 5.2: Injection du `SearchService` via constructeur
  - [x] 5.3: Méthode `index(SearchTalentRequest $request): JsonResponse`
  - [x] 5.4: Extraire les filtres validés, déléguer au SearchService
  - [x] 5.5: Transformer via `$paginator->through(fn ($talent) => new TalentResource($talent))`
  - [x] 5.6: Retourner via `$this->paginatedResponse($paginator)`

- [x] Task 6: Ajouter la route publique (AC: accessible authentifié ou non)
  - [x] 6.1: Route `GET /talents` ajoutée dans `routes/api.php` — HORS du middleware `auth:sanctum`
  - [x] 6.2: Placée APRÈS la route `/categories` et AVANT le groupe `auth:sanctum`
  - [x] 6.3: `use App\Http\Controllers\Api\V1\TalentController;` ajouté

- [x] Task 7: Vérifier le binding DI du SearchService
  - [x] 7.1: Binding `TalentRepositoryInterface` → `TalentRepository` existe déjà dans `AppServiceProvider`
  - [x] 7.2: `SearchService` est une classe concrète — Laravel auto-résout via le binding existant

- [x] Task 8: Écrire les tests Feature — `TalentControllerTest` (AC: tous critères)
  - [x] 8.1: Créer `tests/Feature/Api/V1/TalentControllerTest.php`
  - [x] 8.2: `test_can_list_verified_talents`
  - [x] 8.3: `test_unverified_talents_are_excluded`
  - [x] 8.4: `test_can_filter_by_category_id`
  - [x] 8.5: `test_can_filter_by_subcategory_id`
  - [x] 8.6: `test_can_filter_by_cachet_range`
  - [x] ~~8.7: `test_cachet_filter_excludes_null_cachet`~~ — supprimé car `cachet_amount` est NOT NULL en DB
  - [x] 8.8: `test_can_filter_by_city`
  - [x] 8.9: `test_can_filter_by_min_rating`
  - [x] 8.10: `test_can_combine_multiple_filters`
  - [x] 8.11: `test_can_sort_by_rating_desc`
  - [x] 8.12: `test_can_sort_by_cachet_amount_asc`
  - [x] 8.13: `test_default_sort_is_created_at_desc`
  - [x] 8.14: `test_cursor_pagination_returns_meta`
  - [x] 8.15: `test_cursor_pagination_next_page`
  - [x] 8.16: `test_per_page_controls_result_count`
  - [x] 8.17: `test_accessible_without_authentication`
  - [x] 8.18: `test_accessible_with_authentication`
  - [x] 8.19: `test_validation_fails_with_invalid_category_id`
  - [x] 8.20: `test_validation_fails_with_invalid_sort_by`
  - [x] 8.21: `test_empty_results_returns_empty_data_array`
  - [x] 8.22: `test_response_format_matches_json_envelope`

- [x] Task 9: Écrire les tests Unit — `SearchServiceTest` (AC: logique métier)
  - [x] 9.1: Créer `tests/Unit/Services/SearchServiceTest.php`
  - [x] 9.2: `test_search_returns_only_verified_talents`
  - [x] 9.3: `test_search_maps_rating_to_average_rating_column`
  - [x] 9.4: `test_search_applies_default_sort_created_at_desc`
  - [x] 9.5: `test_search_filters_by_category`
  - [x] 9.6: `test_search_applies_cachet_range_filter`
  - [x] 9.7: `test_search_default_per_page_is_20`

- [x] Task 10: Vérifications qualité
  - [x] 10.1: `./vendor/bin/pint --test` — 0 erreurs
  - [x] 10.2: `./vendor/bin/phpstan analyse --memory-limit=512M` — 0 erreurs
  - [x] 10.3: `php artisan test` — 94 tests passent (316 assertions), 0 régressions

### Review Follow-ups (AI)

- [x] [AI-Review][MEDIUM] Ajouter validation croisée `min_cachet <= max_cachet` dans `SearchTalentRequest` [app/Http/Requests/Api/SearchTalentRequest.php]
- [x] [AI-Review][MEDIUM] Créer migration index `cachet_amount` pour optimiser les range queries [database/migrations/]
- [x] [AI-Review][MEDIUM] Déplacer `SearchServiceTest` de `tests/Unit/Services/` vers `tests/Feature/Services/` (classification correcte — utilise RefreshDatabase + container IoC)
- [ ] [AI-Review][LOW] Dev Notes contiennent info incorrecte sur nullabilité de `cachet_amount` — corriger dans la prochaine story ou lors de la revue globale
- [ ] [AI-Review][LOW] Ajouter tests validation limites `per_page` (0, 51, -1) dans `TalentControllerTest`
- [ ] [AI-Review][LOW] Éliminer duplication défaut `perPage=20` (TalentController + SearchService) — une seule source de vérité

## Dev Notes

### Architecture & Patterns Obligatoires

**Pattern Controller → Service → Repository → Model (ARCH-PATTERN-2) :**
```
TalentController::index(SearchTalentRequest)
  → SearchService::searchTalents(filters, sortBy, sortDirection, perPage)
    → TalentRepository::searchVerified(filters, sortBy, sortDirection, perPage)
      → TalentProfile::verified()->when(...)->cursorPaginate()
```

**Séparation TalentController vs TalentProfileController :**
- `TalentController` = découverte publique — `index` (cette story), `show` (Story 1.7)
- `TalentProfileController` = gestion profil propre (store, showOwn, update, destroy) — auth requise
- Deux controllers SÉPARÉS dans `app/Http/Controllers/Api/V1/`

### Composants existants à réutiliser — NE PAS recréer

**Scopes TalentProfile (app/Models/TalentProfile.php) :**
```php
scopeVerified($query)                        → where('is_verified', true)
scopeByCategory($query, int $categoryId)     → where('category_id', $categoryId)
scopeByCity($query, string $city)            → where('city', $city)
```

**ApiResponseTrait::paginatedResponse() (app/Http/Traits/ApiResponseTrait.php) :**
- Accepte `CursorPaginator|LengthAwarePaginator`
- Retourne : `{ "data": [...], "meta": { "next_cursor", "prev_cursor", "per_page", "has_more" } }`
- Utiliser `$paginator->through(fn ($t) => new TalentResource($t))` AVANT de passer au trait

**BaseController (app/Http/Controllers/Api/V1/BaseController.php) :**
- Utilise `ApiResponseTrait` + `AuthorizesRequests`
- TalentController DOIT `extends BaseController`

**TalentProfileResource (app/Http/Resources/TalentProfileResource.php) — NE PAS utiliser pour le listing :**
- Trop lourd (inclut social_links, bio, profile_completion_percentage)
- Créer `TalentResource` léger pour les cartes de listing

**Routes existantes (routes/api.php) :**
```php
// Structure actuelle — la nouvelle route va ICI (ligne publique) :
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/health', ...);
    Route::get('/categories', ...);
    // → AJOUTER ICI: Route::get('/talents', [TalentController::class, 'index'])
    Route::middleware('auth:sanctum')->group(function () { ... });
});
```

### Décisions techniques critiques

**1. cachet_amount nullable :**
La colonne peut être NULL (talent sans cachet défini). Quand `min_cachet` ou `max_cachet` est fourni dans les filtres, ajouter `whereNotNull('cachet_amount')` pour exclure les talents sans cachet. Sans ce filtre, les NULL ne sont pas filtrés (comportement attendu — on montre tous les talents).

**2. Mapping tri API → colonne DB :**
Le paramètre API est `sort_by=rating` mais la colonne est `average_rating`. Le SearchService fait le mapping :
```php
$columnMap = ['rating' => 'average_rating'];
$sortBy = $columnMap[$sortBy] ?? $sortBy;
```

**3. Tiebreaker cursor pagination :**
TOUJOURS ajouter `->orderBy('id', 'asc')` APRÈS le tri principal. Sans ce tiebreaker, deux talents avec le même `average_rating` pourraient corrompre la pagination cursor. Les colonnes ordonnées ne doivent PAS contenir de NULL — `average_rating` a un default de 0.00, `created_at` est toujours rempli, `cachet_amount` est nullable MAIS on ne trie par cachet_amount que si demandé et on peut gérer les NULL.

**4. Montants en centimes (integer) :**
`cachet_amount` stocké en centimes. Les paramètres `min_cachet` et `max_cachet` sont en centimes côté API. AUCUNE conversion nécessaire.

**5. Pas de validation parent-child sur subcategory_id :**
En recherche, on filtre simplement par `subcategory_id`. Pas besoin de vérifier que le subcategory est enfant du category fourni — c'est le rôle de la création de profil, pas de la recherche.

**6. Pas de full-text search :**
Cette story = annuaire avec filtres structurés, PAS recherche textuelle libre. La recherche par nom/bio serait une story future.

**7. city = correspondance exacte :**
Le filtre `city` utilise `scopeByCity()` qui fait un `where('city', $city)` exact. Pas de LIKE/fuzzy. Correspondance exacte, sensible à la casse par défaut MySQL (dépend de la collation). Pour Story 1.6, la géolocalisation remplacera la recherche par ville exacte.

### Fichiers à créer

| Fichier | Description |
|---|---|
| `app/Http/Controllers/Api/V1/TalentController.php` | Controller découverte publique |
| `app/Services/SearchService.php` | Service de recherche talents |
| `app/Http/Requests/Api/SearchTalentRequest.php` | Validation filtres recherche |
| `app/Http/Resources/TalentResource.php` | Resource légère pour listing |
| `tests/Feature/Api/V1/TalentControllerTest.php` | Tests Feature endpoint |
| `tests/Unit/Services/SearchServiceTest.php` | Tests Unit service |

### Fichiers à modifier

| Fichier | Modification |
|---|---|
| `app/Repositories/Contracts/TalentRepositoryInterface.php` | Ajouter `searchVerified()` |
| `app/Repositories/Eloquent/TalentRepository.php` | Implémenter `searchVerified()` |
| `routes/api.php` | Ajouter `GET /talents` route publique + use statement |

### Project Structure Notes

- `TalentController.php` dans `app/Http/Controllers/Api/V1/` — conforme architecture
- `SearchService.php` dans `app/Services/` — conforme (1 service par domaine métier)
- `TalentResource.php` dans `app/Http/Resources/` — prévu par l'architecture (distinct de `TalentProfileResource`)
- `SearchTalentRequest.php` dans `app/Http/Requests/Api/` — même dossier que `StoreTalentProfileRequest`
- Tests miroirs : `tests/Feature/Api/V1/TalentControllerTest.php` ↔ `TalentController.php`

### Testing Standards

**Feature Tests (`TalentControllerTest.php`) :**
- `use RefreshDatabase;`
- Créer talents vérifiés : `TalentProfile::factory()->create(['is_verified' => true, ...])`
- Créer talents non vérifiés : `TalentProfile::factory()->create(['is_verified' => false])`
- Tester sans auth : `$this->getJson('/api/v1/talents')` (pas de `actingAs`)
- Tester avec auth : `$this->actingAs($user, 'sanctum')->getJson('/api/v1/talents')`
- Assertions format : `assertJsonStructure(['data' => [['id', 'type', 'attributes' => [...]]], 'meta'])`
- Assertions count : `assertJsonCount(3, 'data')`
- Assertions filtre : créer des données variées, filtrer, vérifier le bon nombre de résultats
- Assertions tri : vérifier l'ordre des éléments via `assertJsonPath('data.0.attributes.stage_name', 'Expected First')`
- Assertions validation : `assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_FAILED')`
- Cursor navigation : extraire `meta.next_cursor` de la réponse, l'utiliser comme paramètre `cursor` dans la requête suivante

**Unit Tests (`SearchServiceTest.php`) :**
- `use RefreshDatabase;`
- Tester la logique métier indépendamment du HTTP
- `$service = app(SearchService::class);`
- Vérifier que seuls les talents vérifiés sont retournés
- Vérifier le mapping rating → average_rating

### References

- [Source: _bmad-output/planning-artifacts/architecture.md#API-Patterns] — JSON envelope, cursor pagination
- [Source: _bmad-output/planning-artifacts/architecture.md#Code-Structure] — TalentController vs TalentProfileController
- [Source: _bmad-output/planning-artifacts/architecture.md#Services] — SearchService.php
- [Source: _bmad-output/planning-artifacts/architecture.md#Testing-Standards] — tests miroirs, Feature/Unit structure
- [Source: _bmad-output/planning-artifacts/epics.md#Story-1.5] — AC, filtres, tri, NFR3
- [Source: _bmad-output/planning-artifacts/ux-design-specification.md#Discovery] — TalentCard fields, FilterBar
- [Source: _bmad-output/implementation-artifacts/1-3-modele-talent-et-crud-profil.md] — Patterns service/repository
- [Source: _bmad-output/implementation-artifacts/1-4-verification-didentite.md] — is_verified, code review fixes

### Previous Story Intelligence

**Story 1.3 (Modèle Talent & CRUD Profil) — Leçons appliquées :**
- PHPStan `when()` type mismatch → utiliser `$this->subcategory_id !== null` (pas truthy)
- Validation JSON envelope → `assertJsonPath('error.code', 'VALIDATION_FAILED')`
- FormRequest `authorize()` retourne `true` — le middleware gère l'auth
- `BaseController` hérite `ApiResponseTrait` + `AuthorizesRequests`
- Factories ont des states : `->create(['is_verified' => true])`
- Social links pattern `$this->when()` dans les Resources

**Story 1.4 (Vérification d'identité) — Leçons appliquées :**
- Larastan ne résout pas les casts Eloquent → propriétés directement dans Resources
- Feature tests API : `$this->actingAs($user, 'sanctum')` pour routes sanctum
- `BookmiException(code, message, statusCode, details)` pour erreurs métier
- `is_verified` inclus dans `calculateCompletionFromData()` (correction H1 code review)
- Exception handlers couvrent `api/*` ET `admin/*`

**Patterns établis :**
- Nommage JSON : `snake_case` partout
- Erreurs talents : préfixe `TALENT_`
- PHPStan : `--memory-limit=512M` obligatoire
- Pint : PSR-12

## Dev Agent Record

### Agent Model Used
Claude Opus 4.6 (claude-opus-4-6)

### Debug Log References
1. **Erreur NOT NULL constraint** — `cachet_amount` est NOT NULL en DB (contrairement aux Dev Notes qui le déclaraient nullable). Résolu en supprimant le test `test_cachet_filter_excludes_null_cachet` et les `whereNotNull('cachet_amount')` inutiles du repository.
2. **Erreur tri par défaut** — Les tests de tri `created_at DESC` échouaient car les factories créées dans la même seconde avaient le même timestamp. Résolu en utilisant `$this->travel(1)->minutes()` entre les créations.

### Completion Notes List
- ✅ Implémentation complète du pattern Controller → Service → Repository → Model (ARCH-PATTERN-2)
- ✅ TalentController séparé de TalentProfileController (découverte publique vs gestion profil)
- ✅ SearchService avec mapping tri API → colonne DB (`rating` → `average_rating`)
- ✅ TalentRepository::searchVerified() avec scopes existants, when() chains, tiebreaker cursor
- ✅ TalentResource léger pour cartes listing (distinct de TalentProfileResource)
- ✅ SearchTalentRequest avec validation nullable et messages français
- ✅ Route publique GET /api/v1/talents (hors auth:sanctum)
- ✅ 22 tests Feature TalentController + 6 tests Feature SearchService = 28 nouveaux tests
- ✅ 94 tests total, 316 assertions, 0 régressions
- ✅ Pint PSR-12 : 0 erreurs, PHPStan : 0 erreurs
- ⚠️ Task 8.7 supprimée : `cachet_amount` est NOT NULL en DB, scénario impossible
- ✅ Resolved review finding [MEDIUM]: Validation croisée min_cachet <= max_cachet ajoutée
- ✅ Resolved review finding [MEDIUM]: Index cachet_amount créé via nouvelle migration
- ✅ Resolved review finding [MEDIUM]: SearchServiceTest déplacé de Unit vers Feature (classification correcte)

### File List

**Fichiers créés :**
| Fichier | Description |
|---|---|
| `app/Http/Controllers/Api/V1/TalentController.php` | Controller découverte publique — index() |
| `app/Services/SearchService.php` | Service recherche talents avec mapping tri |
| `app/Http/Requests/Api/SearchTalentRequest.php` | FormRequest validation filtres (tous nullable) |
| `app/Http/Resources/TalentResource.php` | Resource légère pour listing cards |
| `tests/Feature/Api/V1/TalentControllerTest.php` | 22 tests Feature endpoint |
| `tests/Feature/Services/SearchServiceTest.php` | 6 tests Feature service (déplacé de Unit/) |
| `database/migrations/2026_02_17_184809_add_cachet_amount_index_to_talent_profiles_table.php` | Migration index cachet_amount (ajouté par code review) |

**Fichiers modifiés :**
| Fichier | Modification |
|---|---|
| `app/Repositories/Contracts/TalentRepositoryInterface.php` | Ajout signature `searchVerified()` |
| `app/Repositories/Eloquent/TalentRepository.php` | Implémentation `searchVerified()` avec filtres, tri, cursor |
| `routes/api.php` | Ajout route `GET /talents` publique + use TalentController |
| `app/Http/Requests/Api/SearchTalentRequest.php` | Ajout validation croisée `min_cachet <= max_cachet` (code review) |

**Fichiers déplacés :**
| Source | Destination | Raison |
|---|---|---|
| `tests/Unit/Services/SearchServiceTest.php` | `tests/Feature/Services/SearchServiceTest.php` | Classification correcte — tests d'intégration (code review) |

### Change Log
- **2026-02-17** — Implémentation Story 1.5 : Annuaire des talents avec filtres (backend). Endpoint `GET /api/v1/talents` avec filtres (category_id, subcategory_id, min_cachet, max_cachet, city, min_rating), tri (rating, cachet_amount, created_at), et pagination cursor-based. 28 nouveaux tests (22 Feature TalentController + 6 Feature SearchService).
- **2026-02-17** — Code review adversariale : 3 MEDIUM corrigés (validation croisée cachet, index DB cachet_amount, reclassification tests), 3 LOW en action items.

## Senior Developer Review (AI)

**Review Date:** 2026-02-17
**Review Outcome:** Changes Requested (3 MEDIUM, 3 LOW)
**Reviewer Model:** Claude Opus 4.6

### Action Items

- [x] [MEDIUM] Validation croisée `min_cachet <= max_cachet` manquante dans `SearchTalentRequest` — Corrigé : ajout méthode `after()` avec validation conditionnelle
- [x] [MEDIUM] Index manquant sur `cachet_amount` pour range queries — Corrigé : nouvelle migration `add_cachet_amount_index_to_talent_profiles_table`
- [x] [MEDIUM] Tests Unit utilisent RefreshDatabase + container IoC = tests d'intégration — Corrigé : déplacé vers `tests/Feature/Services/`
- [ ] [LOW] Dev Notes contiennent info incorrecte sur nullabilité `cachet_amount` (section non modifiable par dev agent)
- [ ] [LOW] Tests validation limites `per_page` (0, 51, -1) non couverts
- [ ] [LOW] Défaut `perPage=20` dupliqué dans TalentController et SearchService

**Post-review validation:** 94 tests (316 assertions), Pint ✅, PHPStan ✅, 0 régressions
