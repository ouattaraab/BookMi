# Story 1.9: Favoris (backend + mobile)

Status: done

## Story

As a client authentifié,
I want pouvoir suivre des talents en favoris,
So that je puisse facilement les retrouver plus tard.

**Functional Requirements:** FR16
**Non-Functional Requirements:** NFR2 (réponse API < 500ms), UX-OFFLINE-1 (cache Hive)

## Acceptance Criteria (BDD)

**AC1 — Ajouter un talent en favori**
**Given** un client authentifié
**When** il envoie `POST /api/v1/talents/{talent_profile_id}/favorite`
**Then** le talent est ajouté à ses favoris
**And** la réponse retourne le favori créé avec le format JSON envelope standard (201)
**And** si le talent est déjà en favori, une erreur 409 est retournée

**AC2 — Retirer un talent des favoris**
**Given** un client authentifié avec un talent en favori
**When** il envoie `DELETE /api/v1/talents/{talent_profile_id}/favorite`
**Then** le talent est retiré de ses favoris
**And** la réponse est 204 No Content
**And** si le talent n'est pas en favori, une erreur 404 est retournée

**AC3 — Lister ses favoris avec pagination**
**Given** un client authentifié avec des talents en favoris
**When** il envoie `GET /api/v1/me/favorites`
**Then** la liste de ses talents favoris est retournée avec pagination cursor-based
**And** chaque favori inclut les informations résumées du talent (TalentResource)
**And** la date d'ajout en favori est incluse (`favorited_at`)
**And** les talents supprimés ou non vérifiés ne sont pas retournés

**AC4 — Vérifier si un talent est en favori**
**Given** un client authentifié consultant un profil talent
**When** il envoie `GET /api/v1/talents/{talent_profile_id}/favorite`
**Then** la réponse retourne `{"data": {"is_favorite": true/false}}`
**And** le statut est 200

**AC5 — Authentification requise**
**Given** un utilisateur non authentifié
**When** il tente d'accéder aux endpoints favoris
**Then** une erreur 401 est retournée

**AC6 — Talent inexistant**
**Given** un client authentifié
**When** il tente d'ajouter un talent_profile_id inexistant en favori
**Then** une erreur 404 est retournée avec le code `TALENT_NOT_FOUND`

**AC7 — Flutter : icône coeur animée (toggle)**
**Given** un client sur l'écran découverte ou profil talent (Flutter)
**When** il appuie sur l'icône coeur d'un TalentCard
**Then** l'icône passe en mode rempli/vide avec animation
**And** l'état est synchronisé avec l'API en background
**And** un retour haptique confirme l'action
**Note:** Dépend de Story 1.10 (TalentCard) — créer le BLoC et le widget réutilisable, intégration UI dans 1.10/1.11

**AC8 — Flutter : cache hors-ligne Hive (UX-OFFLINE-1)**
**Given** un client ayant consulté ses favoris en ligne
**When** il perd la connexion
**Then** la liste des favoris est disponible depuis le cache Hive local
**And** les actions toggle sont mises en file d'attente et synchronisées au retour de la connexion

## Tasks / Subtasks

### Backend (Laravel)

- [x] Task 1: Créer la migration `user_favorites` (AC: AC1, AC2)
  - [x] 1.1: Créer la migration `2026_02_17_210000_create_user_favorites_table.php`
  - [x] 1.2: Table pivot : `id`, `user_id` (FK → users, cascadeOnDelete), `talent_profile_id` (FK → talent_profiles, cascadeOnDelete), `timestamps`
  - [x] 1.3: Contrainte UNIQUE sur `(user_id, talent_profile_id)` — empêche les doublons
  - [x] 1.4: Index sur `talent_profile_id` pour les requêtes inverses

- [x] Task 2: Ajouter les relations BelongsToMany (AC: AC1, AC2, AC3)
  - [x] 2.1: Ajouter `favorites(): BelongsToMany` dans `User` model → `TalentProfile` via `user_favorites` avec `withTimestamps()`
  - [x] 2.2: Ajouter `favoritedBy(): BelongsToMany` dans `TalentProfile` model → `User` via `user_favorites` avec `withTimestamps()`

- [x] Task 3: Créer le Repository (AC: AC1, AC2, AC3, AC4)
  - [x] 3.1: Créer `app/Repositories/Contracts/FavoriteRepositoryInterface.php` — interface avec : getFavorites, addFavorite, removeFavorite, isFavorite
  - [x] 3.2: Créer `app/Repositories/Eloquent/FavoriteRepository.php` — implémentation Eloquent via les relations BelongsToMany
  - [x] 3.3: `getFavorites(int $userId, int $perPage): CursorPaginator` — cursor-based pagination, eager load TalentProfile avec category, filtrer verified uniquement
  - [x] 3.4: `addFavorite(int $userId, int $talentProfileId): void` — `attach()` sur la relation
  - [x] 3.5: `removeFavorite(int $userId, int $talentProfileId): int` — `detach()` sur la relation, retourne le nombre de lignes supprimées
  - [x] 3.6: `isFavorite(int $userId, int $talentProfileId): bool` — `exists()` sur la relation
  - [x] 3.7: Enregistrer le binding dans `AppServiceProvider` : `FavoriteRepositoryInterface → FavoriteRepository`

- [x] Task 4: Créer le Service `FavoriteService` (AC: AC1, AC2, AC3, AC4, AC6)
  - [x] 4.1: Créer `app/Services/FavoriteService.php`
  - [x] 4.2: Injection du `FavoriteRepositoryInterface`
  - [x] 4.3: `getFavorites(User $user, int $perPage = 20): CursorPaginator` — liste paginée
  - [x] 4.4: `addFavorite(User $user, int $talentProfileId): void` — vérifie que le talent existe et est vérifié, vérifie pas déjà en favori (sinon throw BookmiException ALREADY_FAVORITED 409)
  - [x] 4.5: `removeFavorite(User $user, int $talentProfileId): void` — vérifie que le favori existe (sinon throw BookmiException FAVORITE_NOT_FOUND 404)
  - [x] 4.6: `isFavorite(User $user, int $talentProfileId): bool` — check simple

- [x] Task 5: Créer la Resource `FavoriteResource` (AC: AC3)
  - [x] 5.1: Créer `app/Http/Resources/FavoriteResource.php`
  - [x] 5.2: Format : wraps TalentResource avec `favorited_at` depuis le pivot timestamp
  - [x] 5.3: Structure : `{ id, type: 'favorite', attributes: { talent: TalentResource, favorited_at: ISO8601 } }`

- [x] Task 6: Créer `FavoriteController` (AC: AC1, AC2, AC3, AC4, AC5, AC6)
  - [x] 6.1: Créer `app/Http/Controllers/Api/V1/FavoriteController.php` extending `BaseController`
  - [x] 6.2: Injection de `FavoriteService`
  - [x] 6.3: `index(Request $request)` — `$this->paginatedResponse()` avec FavoriteResource collection
  - [x] 6.4: `store(Request $request, int $talentProfileId)` — ajoute le favori, retourne 201
  - [x] 6.5: `destroy(Request $request, int $talentProfileId)` — retire le favori, retourne 204
  - [x] 6.6: `check(Request $request, int $talentProfileId)` — retourne `{data: {is_favorite: bool}}`

- [x] Task 7: Ajouter les routes API (AC: AC1, AC2, AC3, AC4, AC5)
  - [x] 7.1: Routes sous `auth:sanctum` dans `routes/api.php`
  - [x] 7.2: `GET /me/favorites` → `FavoriteController@index` (liste)
  - [x] 7.3: `POST /talents/{talentProfileId}/favorite` → `FavoriteController@store` (ajout)
  - [x] 7.4: `DELETE /talents/{talentProfileId}/favorite` → `FavoriteController@destroy` (retrait)
  - [x] 7.5: `GET /talents/{talentProfileId}/favorite` → `FavoriteController@check` (vérification)

- [x] Task 8: Écrire les tests Feature backend (AC: tous backend)
  - [x] 8.1: `test_client_can_add_talent_to_favorites` — POST 201
  - [x] 8.2: `test_add_favorite_returns_409_if_already_favorited` — POST 409 doublon
  - [x] 8.3: `test_add_favorite_returns_404_for_nonexistent_talent` — POST 404
  - [x] 8.4: `test_client_can_remove_talent_from_favorites` — DELETE 204
  - [x] 8.5: `test_remove_favorite_returns_404_if_not_favorited` — DELETE 404
  - [x] 8.6: `test_client_can_list_favorites_with_pagination` — GET 200 avec cursor
  - [x] 8.7: `test_favorites_list_excludes_unverified_talents` — talents non vérifiés exclus
  - [x] 8.8: `test_favorites_list_excludes_deleted_talents` — talents soft-deleted exclus
  - [x] 8.9: `test_client_can_check_favorite_status` — GET 200 avec is_favorite true/false
  - [x] 8.10: `test_favorites_require_authentication` — 401 sans token
  - [x] 8.11: `test_favorites_ordered_by_most_recent` — ordre desc par favorited_at
  - [x] 8.12: `test_favorite_includes_talent_summary_data` — TalentResource présent dans la réponse
  - [x] 8.13: `test_add_favorite_returns_404_for_unverified_talent` — test supplémentaire edge case

### Flutter (Mobile)

- [x] Task 9: Créer le FavoritesRepository Flutter (AC: AC7, AC8)
  - [x] 9.1: Créer `lib/features/favorites/data/repositories/favorites_repository.dart`
  - [x] 9.2: Méthodes : `getFavorites()`, `addFavorite(id)`, `removeFavorite(id)`, `isFavorite(id)`
  - [x] 9.3: Appels API via `ApiClient` (core/network)
  - [x] 9.4: Cache Hive pour stockage local des favoris

- [x] Task 10: Créer le FavoritesCubit/BLoC Flutter (AC: AC7)
  - [x] 10.1: Créer `lib/features/favorites/bloc/favorites_bloc.dart` avec Events et States sealed classes
  - [x] 10.2: Events : `FavoriteToggled(talentId)`, `FavoritesFetched`, `FavoriteStatusChecked(talentId)`
  - [x] 10.3: States : `FavoritesInitial`, `FavoritesLoading`, `FavoritesLoaded(favorites)`, `FavoritesError(message)`
  - [x] 10.4: Optimistic update : toggle l'état local immédiatement, rollback si erreur API

- [x] Task 11: Créer le widget FavoriteButton Flutter (AC: AC7)
  - [x] 11.1: Créer `lib/features/favorites/widgets/favorite_button.dart`
  - [x] 11.2: Widget réutilisable : icône coeur avec animation scale + couleur
  - [x] 11.3: Retour haptique (`HapticFeedback.lightImpact()`) au toggle
  - [x] 11.4: Intégration via `BlocBuilder<FavoritesBloc, FavoritesState>`
  - [x] 11.5: Ce widget sera intégré dans TalentCard (Story 1.10) et TalentDetailScreen (Story 1.11)

- [x] Task 12: Implémenter le cache Hive hors-ligne (AC: AC8)
  - [x] 12.1: Créer `lib/features/favorites/data/local/favorites_local_source.dart`
  - [x] 12.2: Adapter Hive box `favorites` pour stocker les talent IDs et données résumées
  - [x] 12.3: Synchronisation : file d'attente des actions offline, sync au retour réseau
  - [x] 12.4: Stratégie cache-first pour la liste, network-first pour les toggles

- [x] Task 13: Tests Flutter (AC: AC7, AC8)
  - [x] 13.1: Tests unitaires BLoC : toggle, fetch, optimistic update, rollback
  - [x] 13.2: Tests widget FavoriteButton : animation, état toggle
  - [x] 13.3: Tests repository : API calls, Hive cache read/write

- [x] Task 14: Vérifications qualité
  - [x] 14.1: `./vendor/bin/pint --test` — 0 erreurs
  - [x] 14.2: `./vendor/bin/phpstan analyse --memory-limit=512M` — 0 erreurs
  - [x] 14.3: `php artisan test` — 165 tests passent (152 existants + 13 nouveaux), 0 régressions
  - [x] 14.4: Flutter : `flutter analyze` — 0 issues
  - [x] 14.5: Flutter : `flutter test` — 87 tests passent (72 existants + 15 nouveaux), 0 régressions

## Dev Notes

### Architecture & Patterns Obligatoires

**Pattern Controller → Service → Repository → Model (ARCH-PATTERN-2) :**
```
FavoriteController::store(request, talentProfileId)
  → FavoriteService::addFavorite(user, talentProfileId)
    → Vérifier TalentProfile existe + is_verified
    → FavoriteRepository::isFavorite(userId, talentProfileId) → si true, throw ALREADY_FAVORITED
    → FavoriteRepository::addFavorite(userId, talentProfileId)
  → successResponse({data: {is_favorite: true}}, 201)
```

**Pattern CRUD favoris :**
```
FavoriteController::index(request) → FavoriteService::getFavorites(user, perPage)
FavoriteController::store(request, talentProfileId) → FavoriteService::addFavorite(user, talentProfileId)
FavoriteController::destroy(request, talentProfileId) → FavoriteService::removeFavorite(user, talentProfileId)
FavoriteController::check(request, talentProfileId) → FavoriteService::isFavorite(user, talentProfileId)
```

### Composants existants à réutiliser — NE PAS recréer

| Fichier existant | Utilisation |
|---|---|
| `app/Http/Controllers/Api/V1/BaseController` | Hériter — `successResponse()`, `paginatedResponse()`, `errorResponse()` |
| `app/Http/Traits/ApiResponseTrait` | Via BaseController — format JSON envelope + pagination cursor |
| `app/Exceptions/BookmiException` | Pour les erreurs métier (ALREADY_FAVORITED, FAVORITE_NOT_FOUND, TALENT_NOT_FOUND) |
| `app/Models/User` | Ajouter relation `favorites()` — NE PAS modifier la structure existante |
| `app/Models/TalentProfile` | Ajouter relation `favoritedBy()` — NE PAS modifier la structure existante |
| `app/Http/Resources/TalentResource` | Réutiliser pour afficher les infos talent dans FavoriteResource |
| `app/Providers/AppServiceProvider` | Ajouter le binding FavoriteRepository |

### Schema de la table `user_favorites`

```php
Schema::create('user_favorites', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('talent_profile_id')->constrained()->cascadeOnDelete();
    $table->timestamps();

    $table->unique(['user_id', 'talent_profile_id']);
    $table->index('talent_profile_id');
});
```

**Design decision : table pivot et non modèle dédié**
- Pas de `Favorite` model — utiliser directement la relation `BelongsToMany` avec `withTimestamps()`
- La table pivot `user_favorites` est gérée via les méthodes `attach()` / `detach()` d'Eloquent
- Pas de soft delete nécessaire — un favori retiré est simplement supprimé (pas de valeur d'audit)
- Pas de Factory nécessaire — les tests utilisent `$user->favorites()->attach($talentId)` directement

### Routes API — Placement

```php
// routes/api.php — Sous le groupe auth:sanctum
Route::middleware('auth:sanctum')->group(function () {
    // ... routes existantes ...

    // Favoris
    Route::get('/me/favorites', [FavoriteController::class, 'index'])
        ->name('favorites.index');
    Route::post('/talents/{talentProfileId}/favorite', [FavoriteController::class, 'store'])
        ->name('favorites.store');
    Route::delete('/talents/{talentProfileId}/favorite', [FavoriteController::class, 'destroy'])
        ->name('favorites.destroy');
    Route::get('/talents/{talentProfileId}/favorite', [FavoriteController::class, 'check'])
        ->name('favorites.check');
});
```

**Note:** Le paramètre `{talentProfileId}` est un entier (int), pas un route model binding — le service gère la résolution et la validation d'existence.

### FavoriteResource — Structure exacte de la réponse

**Liste des favoris (GET /me/favorites) :**
```json
{
    "data": [
        {
            "id": 1,
            "type": "favorite",
            "attributes": {
                "talent": {
                    "id": 42,
                    "type": "talent_profile",
                    "attributes": {
                        "stage_name": "DJ Kerozen",
                        "slug": "dj-kerozen",
                        "city": "Abidjan",
                        "cachet_amount": 15000000,
                        "average_rating": "4.50",
                        "is_verified": true,
                        "category": { "id": 3, "name": "DJ", "slug": "dj" }
                    }
                },
                "favorited_at": "2026-02-17T14:30:00Z"
            }
        }
    ],
    "meta": {
        "cursor": { "next": "xxx", "prev": null, "per_page": 20, "has_more": true }
    }
}
```

**Check favori (GET /talents/{id}/favorite) :**
```json
{
    "data": {
        "is_favorite": true
    }
}
```

### Gestion des erreurs — Codes d'erreur métier

| Code | HTTP | Contexte |
|---|---|---|
| `ALREADY_FAVORITED` | 409 | Talent déjà en favori (doublon) |
| `FAVORITE_NOT_FOUND` | 404 | Tentative de retirer un favori inexistant |
| `TALENT_NOT_FOUND` | 404 | talent_profile_id inexistant ou non vérifié |
| 401 Unauthorized | 401 | Token manquant/expiré (automatique Sanctum) |

### Pagination — Cursor-based

```php
// Dans FavoriteRepository::getFavorites()
return User::findOrFail($userId)
    ->favorites()
    ->verified()              // Scope : uniquement les talents vérifiés
    ->with(['category'])      // Eager load pour TalentResource
    ->orderByPivot('created_at', 'desc')  // Plus récents en premier
    ->cursorPaginate($perPage);
```

**ATTENTION :** La méthode `cursorPaginate()` sur une relation `BelongsToMany` est supportée nativement par Laravel 12. Le `orderByPivot()` trie par la colonne du pivot.

### Flutter — Architecture favorites

```
lib/features/favorites/
├── bloc/
│   ├── favorites_bloc.dart
│   ├── favorites_event.dart      # sealed class
│   └── favorites_state.dart      # sealed class
├── data/
│   ├── repositories/
│   │   └── favorites_repository.dart
│   └── local/
│       └── favorites_local_source.dart   # Hive
└── widgets/
    └── favorite_button.dart      # Widget réutilisable
```

**FavoriteButton — Widget réutilisable :**
```dart
// Usage dans TalentCard (Story 1.10) ou TalentDetailScreen (Story 1.11) :
FavoriteButton(talentId: talent.id)
```

**Optimistic update pattern :**
1. Toggle l'état local immédiatement (coeur animé)
2. Envoyer la requête API en background
3. Si erreur → rollback l'état local + afficher snackbar

### Dépendances inter-stories

| Dépendance | Story | Impact |
|---|---|---|
| TalentCard widget | Story 1.10 | L'intégration du FavoriteButton dans TalentCard se fait dans 1.10, pas 1.9 |
| TalentDetailScreen | Story 1.11 | L'intégration du FavoriteButton dans le profil se fait dans 1.11, pas 1.9 |
| ApiClient (core/network) | Story 1.2 | Déjà créé — réutiliser pour les appels API |
| Hive setup | Story 1.2 | Vérifier si Hive est déjà configuré dans le bootstrap Flutter |

**Story 1.9 crée les composants réutilisables (BLoC, widget, repository). Stories 1.10/1.11 les intègrent dans l'UI.**

### Testing Standards

**Pattern test favoris backend :**
```php
// Créer un user authentifié
$user = User::factory()->create();
$this->actingAs($user, 'sanctum');

// Créer un talent vérifié à mettre en favori
$talent = TalentProfile::factory()->verified()->create();

// Ajouter en favori
$response = $this->postJson("/api/v1/talents/{$talent->id}/favorite");
$response->assertStatus(201);

// Vérifier en base
$this->assertDatabaseHas('user_favorites', [
    'user_id' => $user->id,
    'talent_profile_id' => $talent->id,
]);
```

**Tests de non-régression obligatoires :**
- Les 152 tests existants doivent continuer à passer
- Les endpoints `GET /api/v1/talents` et `GET /api/v1/talents/{slug}` ne doivent pas être affectés
- Les relations ajoutées à User et TalentProfile ne doivent pas casser les tests existants

### Project Structure Notes

**Nouveaux fichiers à créer (backend) :**
```
database/migrations/2026_02_17_210000_create_user_favorites_table.php
app/Repositories/Contracts/FavoriteRepositoryInterface.php
app/Repositories/Eloquent/FavoriteRepository.php
app/Services/FavoriteService.php
app/Http/Resources/FavoriteResource.php
app/Http/Controllers/Api/V1/FavoriteController.php
tests/Feature/Api/V1/FavoriteControllerTest.php
```

**Nouveaux fichiers à créer (Flutter) :**
```
lib/features/favorites/bloc/favorites_bloc.dart
lib/features/favorites/bloc/favorites_event.dart
lib/features/favorites/bloc/favorites_state.dart
lib/features/favorites/data/repositories/favorites_repository.dart
lib/features/favorites/data/local/favorites_local_source.dart
lib/features/favorites/widgets/favorite_button.dart
test/features/favorites/bloc/favorites_bloc_test.dart
test/features/favorites/widgets/favorite_button_test.dart
```

**Fichiers existants à modifier :**
```
app/Models/User.php                           # Ajout relation favorites()
app/Models/TalentProfile.php                  # Ajout relation favoritedBy()
app/Providers/AppServiceProvider.php          # Binding FavoriteRepository
routes/api.php                                # Routes favoris
```

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story-1.9] — AC, FR16
- [Source: _bmad-output/planning-artifacts/prd.md#FR16] — Suivre des talents en favoris
- [Source: _bmad-output/planning-artifacts/architecture.md#Data-Architecture] — Service Layer + Repository pattern, BelongsToMany
- [Source: _bmad-output/planning-artifacts/architecture.md#API-Response-Formats] — JSON envelope, cursor pagination, error codes
- [Source: _bmad-output/planning-artifacts/architecture.md#Naming-Patterns] — snake_case, conventions
- [Source: _bmad-output/implementation-artifacts/1-8-packages-de-prestation.md] — Patterns CRUD, tests, code review learnings

### Previous Story Intelligence

**Story 1.8 (Packages de prestation) — Leçons directement applicables :**

- **`fresh()` après `create()`** : Pour les favoris, pas nécessaire car on utilise `attach()` et non `create()` sur un modèle
- **Format BookMi envelope pour validation** : Les tests doivent utiliser `assertJsonPath('error.code', 'ALREADY_FAVORITED')` et non `assertJsonValidationErrors`
- **Policy vs Service check** : Pour les favoris, pas de Policy nécessaire — l'authentification suffit (tout user authentifié peut gérer ses favoris). La vérification de propriété est implicite (l'user ne peut voir/gérer que ses propres favoris)
- **Pint auto-fix** : Attention aux parenthèses `new class ()` dans les migrations et imports inutiles
- **`recalculateCompletion()`** : Les favoris n'impactent PAS le profile_completion_percentage (c'est une fonctionnalité client, pas talent)
- **Code review H1 (is_active toggle)** : Pour les favoris, pas de toggle complexe — c'est un simple attach/detach
- **Code review H2 (validation croisée)** : Pour les favoris, la validation est simple — vérifier que le talent existe et est vérifié

### Review Follow-ups (AI)

- [ ] [AI-Review][LOW] L1 — Table pivot `user_favorites` ne suit pas la convention architecture `{singulier}_{singulier}` alpha → devrait être `talent_profile_user` [migration]
- [ ] [AI-Review][LOW] L2 — Annotations `@mixin` et `@property` incohérentes dans FavoriteResource (utilise `$this->resource->pivot` au lieu de `$this->pivot`) [FavoriteResource.php:9-12]

## Change Log

| Date | Changement | Rationale |
|---|---|---|
| 2026-02-17 | Migration user_favorites, relations, Repository, Service, Resource, Controller, Routes | Implémentation complète backend favoris (AC1-AC6) |
| 2026-02-17 | 13 tests Feature backend + 1 test edge case (unverified talent) | Couverture exhaustive des ACs backend |
| 2026-02-17 | FavoritesRepository, FavoritesBloc, FavoriteButton, FavoritesLocalSource Flutter | Composants réutilisables favoris mobile (AC7-AC8) |
| 2026-02-17 | 9 tests BLoC + 6 tests widget Flutter | Couverture optimistic update, rollback, animation, toggle |
| 2026-02-17 | Endpoints ajoutés dans ApiEndpoints + Hive cache offline | Infrastructure réseau et cache hors-ligne |
| 2026-02-17 | **Code Review fixes** : H1-AC8 offline queue connecté, H2-tests repo/local ajoutés, M1-store retourne FavoriteResource, M2-per_page borné, M3-TOCTOU UniqueConstraintViolation, M4-DB::table sans User::findOrFail, M5-equality sur states, M6-debounce _pendingToggles | Revue adversariale Senior Developer (AI) |

## Dev Agent Record

### Agent Model Used

Claude Opus 4.6

### Debug Log References

- PHPStan : 3 erreurs corrigées (FavoriteResource pivot access, CursorPaginator contract vs concrete type)
- Pint : 1 auto-fix (new class () migration parentheses)
- Flutter analyze : 10 issues corrigées (immutable annotations, cascade_invocations, discarded_futures, null-aware elements conflict)
- Flutter test : 1 test FavoriteButton "dispatches FavoriteToggled on tap" fix (ajout == et hashCode overrides sur events)

### Completion Notes List

- AC1 PASS : POST /talents/{id}/favorite retourne 201, doublon 409 (ALREADY_FAVORITED)
- AC2 PASS : DELETE /talents/{id}/favorite retourne 204, non-favori 404 (FAVORITE_NOT_FOUND)
- AC3 PASS : GET /me/favorites retourne cursor pagination avec FavoriteResource (TalentResource + favorited_at), exclut non-vérifiés et soft-deleted, ordonné par plus récent
- AC4 PASS : GET /talents/{id}/favorite retourne {data: {is_favorite: true/false}}
- AC5 PASS : 401 sur tous les endpoints sans authentification
- AC6 PASS : 404 TALENT_NOT_FOUND pour talent inexistant ou non vérifié
- AC7 PASS : FavoritesBloc avec optimistic update + rollback, FavoriteButton avec animation scale + HapticFeedback
- AC8 PASS : FavoritesLocalSource avec cache Hive IDs, file d'attente offline, fallback cache-first
- Backend : 165 tests / 595 assertions / 0 failures / 0 régressions
- Flutter : 87 tests / 0 failures / 0 régressions
- Qualité : Pint PASS, PHPStan 0 errors, flutter analyze 0 issues

### File List

**Nouveaux fichiers (backend) :**
- `bookmi/database/migrations/2026_02_17_210000_create_user_favorites_table.php`
- `bookmi/app/Repositories/Contracts/FavoriteRepositoryInterface.php`
- `bookmi/app/Repositories/Eloquent/FavoriteRepository.php`
- `bookmi/app/Services/FavoriteService.php`
- `bookmi/app/Http/Resources/FavoriteResource.php`
- `bookmi/app/Http/Controllers/Api/V1/FavoriteController.php`
- `bookmi/tests/Feature/Api/V1/FavoriteControllerTest.php`

**Nouveaux fichiers (Flutter) :**
- `bookmi_app/lib/features/favorites/bloc/favorites_bloc.dart`
- `bookmi_app/lib/features/favorites/bloc/favorites_event.dart`
- `bookmi_app/lib/features/favorites/bloc/favorites_state.dart`
- `bookmi_app/lib/features/favorites/data/repositories/favorites_repository.dart`
- `bookmi_app/lib/features/favorites/data/local/favorites_local_source.dart`
- `bookmi_app/lib/features/favorites/widgets/favorite_button.dart`
- `bookmi_app/test/features/favorites/bloc/favorites_bloc_test.dart`
- `bookmi_app/test/features/favorites/widgets/favorite_button_test.dart`

**Fichiers modifiés :**
- `bookmi/app/Models/User.php` — ajout relation favorites() BelongsToMany
- `bookmi/app/Models/TalentProfile.php` — ajout relation favoritedBy() BelongsToMany
- `bookmi/app/Providers/AppServiceProvider.php` — binding FavoriteRepositoryInterface
- `bookmi/routes/api.php` — routes favoris (4 endpoints)
- `bookmi_app/lib/core/network/api_endpoints.dart` — ajout myFavorites + talentFavorite()
