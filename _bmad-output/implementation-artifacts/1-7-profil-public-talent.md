# Story 1.7: Profil public talent (backend)

Status: done

## Story

As a client ou visiteur,
I want consulter le profil complet d'un talent (bio, portfolio, avis, packages, disponibilités),
So that je puisse prendre une décision éclairée avant de réserver.

**Functional Requirements:** FR14, FR15, FR17
**Non-Functional Requirements:** NFR3 (réponse < 1s), NFR40 (responsive)

## Acceptance Criteria (BDD)

**AC1 — Profil complet API**
**Given** un visiteur ou client
**When** il accède à `GET /api/v1/talents/{slug}`
**Then** le profil complet est retourné : bio, social_links, note moyenne, nombre d'avis, cachet_amount, score de fiabilité, catégorie, sous-catégorie, city, talent_level, is_verified

**AC2 — Suggestions de talents similaires**
**Given** un profil talent affiché
**When** la réponse API est construite
**Then** des suggestions de talents similaires sont incluses (même catégorie, même ville, max 5) (FR15)

**AC3 — URL publique SEO**
**Given** un navigateur web
**When** il accède à `GET /talent/{slug}`
**Then** la page web SSR (Blade) est servie avec le profil complet
**And** le HTML contient les métadonnées Schema.org (type Person/PerformingGroup)
**And** les meta tags Open Graph sont présents pour le partage social (FR17)

**AC4 — 404 pour slug inexistant**
**Given** un slug qui ne correspond à aucun talent vérifié
**When** un visiteur accède à `GET /api/v1/talents/{slug_inexistant}`
**Then** une réponse 404 est retournée avec le format JSON envelope standard

**AC5 — Données futures structurées**
**Given** les modèles portfolio, packages et reviews n'existent pas encore
**When** le profil complet est retourné
**Then** les champs `portfolio_items`, `service_packages`, `recent_reviews` sont présents comme tableaux vides `[]`
**And** `reviews_count` est `0`
**And** la structure est prête pour les stories futures (1.8, 6.4, 6.5)

## Tasks / Subtasks

- [x] Task 1: Créer `TalentDetailResource` (AC: AC1, AC5)
  - [x] 1.1: Créer `app/Http/Resources/TalentDetailResource.php` — resource détaillée pour le show endpoint
  - [x] 1.2: Inclure tous les attributs du profil : `id`, `type`, `attributes` (stage_name, slug, bio, city, cachet_amount, average_rating, is_verified, talent_level, profile_completion_percentage, social_links, reliability_score, reviews_count)
  - [x] 1.3: Inclure les relations : `category` (id, name, slug, color_hex), `subcategory` (conditionnel)
  - [x] 1.4: Inclure les champs futurs vides : `portfolio_items: []`, `service_packages: []`, `recent_reviews: []`
  - [x] 1.5: Calculer `reliability_score` : formule basée sur `is_verified`, `average_rating`, `total_bookings`, `profile_completion_percentage`
  - [x] 1.6: Inclure `created_at` (date d'inscription formatée ISO 8601)
  - [x] 1.7: Inclure `similar_talents` dans la réponse (via additional data passé au resource)

- [x] Task 2: Ajouter méthode `findSimilar()` au Repository (AC: AC2)
  - [x] 2.1: Ajouter `findSimilar(TalentProfile $profile, int $limit = 5): Collection` dans `TalentRepositoryInterface`
  - [x] 2.2: Implémenter dans `TalentRepository` : même `category_id`, même `city`, `is_verified = true`, exclure le talent courant, `orderBy('average_rating', 'desc')`, `limit($limit)`
  - [x] 2.3: Eager load `category` et `subcategory` pour les suggestions
  - [x] 2.4: Retourner une `Collection` de `TalentProfile` (pas de pagination — max 5 résultats)

- [x] Task 3: Ajouter méthode `getPublicProfile()` au Service (AC: AC1, AC2, AC4)
  - [x] 3.1: Créer `getPublicProfile(string $slug): ?array` dans `TalentProfileService`
  - [x] 3.2: Charger le profil via `findBySlug()` avec eager loading `['category', 'subcategory']`
  - [x] 3.3: Vérifier `is_verified === true` — retourner `null` si non vérifié (profil non accessible publiquement)
  - [x] 3.4: Charger les talents similaires via `findSimilar()`
  - [x] 3.5: Retourner `['profile' => $profile, 'similar_talents' => $similarTalents]`

- [x] Task 4: Ajouter `show()` à `TalentController` (AC: AC1, AC2, AC4)
  - [x] 4.1: Ajouter méthode `show(string $slug): JsonResponse`
  - [x] 4.2: Appeler `TalentProfileService::getPublicProfile($slug)`
  - [x] 4.3: Si `null` → retourner `errorResponse('TALENT_NOT_FOUND', '...', 404)`
  - [x] 4.4: Retourner `successResponse(new TalentDetailResource($profile), meta: ['similar_talents' => TalentResource::collection($similarTalents)])`

- [x] Task 5: Ajouter route API (AC: AC1)
  - [x] 5.1: Ajouter `Route::get('talents/{slug}', [TalentController::class, 'show'])` dans `routes/api.php` — route PUBLIQUE (pas de middleware auth)
  - [x] 5.2: Placer APRÈS la route `talents` (index) pour éviter conflit de priorité des routes

- [x] Task 6: Créer `TalentPageController` pour le web SSR (AC: AC3)
  - [x] 6.1: Créer `app/Http/Controllers/Web/TalentPageController.php`
  - [x] 6.2: Méthode `show(string $slug): View|Response` — charge le profil via `TalentProfileService::getPublicProfile()`
  - [x] 6.3: Si `null` → `abort(404)`
  - [x] 6.4: Retourner `view('web.talent.show', compact('profile', 'similarTalents'))`

- [x] Task 7: Créer les vues Blade SSR (AC: AC3)
  - [x] 7.1: Créer `resources/views/web/layouts/app.blade.php` — layout minimal avec head (meta, Schema.org), body, @yield('content')
  - [x] 7.2: Créer `resources/views/web/talent/show.blade.php` — page profil avec :
    - Meta tags Open Graph (`og:title`, `og:description`, `og:image`, `og:url`, `og:type=profile`)
    - Schema.org JSON-LD (`@type: Person` ou `PerformingGroup`, `name`, `description`, `address`, `aggregateRating`)
    - Contenu HTML minimal : nom de scène, catégorie, bio, note, ville, badge vérifié
  - [x] 7.3: La page Blade sert principalement pour le SEO — le contenu riche est dans l'app Flutter via deep linking

- [x] Task 8: Ajouter route web (AC: AC3)
  - [x] 8.1: Ajouter `Route::get('/talent/{slug}', [TalentPageController::class, 'show'])->name('talent.show')` dans `routes/web.php`

- [x] Task 9: Écrire les tests Feature (AC: tous)
  - [x] 9.1: `test_can_get_talent_profile_by_slug` — 200 avec données complètes
  - [x] 9.2: `test_show_returns_detailed_profile_attributes` — vérifier bio, social_links, reliability_score, reviews_count, portfolio_items, service_packages, recent_reviews
  - [x] 9.3: `test_show_includes_similar_talents` — suggestions présentes dans meta
  - [x] 9.4: `test_show_similar_talents_same_category_and_city` — vérifier le filtre catégorie + ville
  - [x] 9.5: `test_show_similar_talents_excludes_current_talent` — le talent lui-même n'est pas dans les suggestions
  - [x] 9.6: `test_show_similar_talents_max_5` — pas plus de 5 suggestions
  - [x] 9.7: `test_show_returns_404_for_unknown_slug` — slug inexistant = 404
  - [x] 9.8: `test_show_returns_404_for_unverified_talent` — talent non vérifié = 404 (même si slug existe)
  - [x] 9.9: `test_show_accessible_without_authentication` — endpoint public
  - [x] 9.10: `test_show_response_format_matches_json_envelope` — structure {data, meta}
  - [x] 9.11: `test_web_talent_page_returns_200` — page Blade SSR retourne 200
  - [x] 9.12: `test_web_talent_page_contains_schema_org` — HTML contient JSON-LD Schema.org
  - [x] 9.13: `test_web_talent_page_contains_open_graph_tags` — HTML contient og: meta tags
  - [x] 9.14: `test_web_talent_page_returns_404_for_unknown_slug` — page web 404

- [x] Task 10: Vérifications qualité
  - [x] 10.1: `./vendor/bin/pint --test` — 0 erreurs
  - [x] 10.2: `./vendor/bin/phpstan analyse --memory-limit=512M` — 0 erreurs
  - [x] 10.3: `php artisan test` — 126 tests, 433 assertions, 0 régressions

### Review Follow-ups

- [ ] [AI-Review][LOW] L1 — Tests web superficiels : renforcer les assertions pour valider la structure HTML des meta tags OG et la validité du JSON-LD Schema.org (`tests/Feature/Web/TalentPageControllerTest.php`)
- [ ] [AI-Review][LOW] L2 — `total_bookings` absent de la réponse API détaillée : ajouter `total_bookings` aux attributs de `TalentDetailResource` pour permettre à Flutter d'afficher "X prestations réalisées" (`app/Http/Resources/TalentDetailResource.php`)

## Dev Notes

### Architecture & Patterns Obligatoires

**Pattern Controller → Service → Repository → Model (ARCH-PATTERN-2) :**
```
TalentController::show(slug)
  → TalentProfileService::getPublicProfile(slug)
    → TalentRepository::findBySlug(slug)
    → TalentRepository::findSimilar(profile, limit)
  → TalentDetailResource(profile) + TalentResource::collection(similar)
```

**Pattern Web SSR (nouveau pattern introduit dans cette story) :**
```
TalentPageController::show(slug)
  → TalentProfileService::getPublicProfile(slug)
  → view('web.talent.show', data)
```

**Cette story crée de NOUVEAUX fichiers (contrairement aux stories 1.5/1.6 qui étendaient) :**
- `TalentDetailResource` (nouveau)
- `TalentPageController` (nouveau)
- Vues Blade (nouveaux)

**Et étend des fichiers existants :**
- `TalentController` → ajout `show()`
- `TalentProfileService` → ajout `getPublicProfile()`
- `TalentRepository` + Interface → ajout `findSimilar()`
- `routes/api.php` → ajout route show
- `routes/web.php` → ajout route web

### Composants existants à réutiliser — NE PAS recréer

| Fichier existant | Utilisation |
|---|---|
| `app/Repositories/Eloquent/TalentRepository::findBySlug()` | Récupération profil par slug — DÉJÀ implémenté |
| `app/Services/TalentProfileService::getBySlug()` | Service wrapper — existe mais doit être ÉTENDU (pas recréé) |
| `app/Http/Resources/TalentResource.php` | Pour les suggestions — NE PAS modifier, utiliser tel quel pour la liste |
| `app/Http/Traits/ApiResponseTrait` | `successResponse()`, `errorResponse()` — utiliser pour le show endpoint |
| `app/Http/Controllers/Api/V1/BaseController` | Hériter pour TalentController (déjà fait) |

### TalentDetailResource — Structure exacte de la réponse

```php
// Réponse JSON complète pour GET /api/v1/talents/{slug}
{
    "data": {
        "id": 42,
        "type": "talent_profile",
        "attributes": {
            "stage_name": "DJ Kerozen",
            "slug": "dj-kerozen",
            "bio": "Artiste ivoirien célèbre...",
            "city": "Abidjan",
            "cachet_amount": 15000000,
            "average_rating": 4.70,
            "is_verified": true,
            "talent_level": "elite",
            "profile_completion_percentage": 85,
            "social_links": {"instagram": "https://..."},
            "reliability_score": 92,
            "reviews_count": 0,
            "portfolio_items": [],
            "service_packages": [],
            "recent_reviews": [],
            "created_at": "2026-02-17T14:30:00Z",
            "category": {
                "id": 1,
                "name": "Musique",
                "slug": "musique",
                "color_hex": "#FF6B35"
            },
            "subcategory": {
                "id": 3,
                "name": "DJ",
                "slug": "dj"
            }
        }
    },
    "meta": {
        "similar_talents": [
            {
                "id": 43,
                "type": "talent_profile",
                "attributes": {
                    "stage_name": "DJ Arafat Jr",
                    "slug": "dj-arafat-jr",
                    ...
                }
            }
        ]
    }
}
```

**ATTENTION :** `TalentDetailResource` est DISTINCT de `TalentResource`. Ne PAS modifier `TalentResource` existant — c'est le format liste/card. `TalentDetailResource` est le format détaillé profil complet.

### Calcul reliability_score — Formule

```php
// Score de fiabilité sur 100 — basé sur les données disponibles
$reliabilityScore = 0;
$reliabilityScore += $this->is_verified ? 30 : 0;                    // Vérifié = +30
$reliabilityScore += min(30, $this->average_rating * 6);              // Rating max +30 (5.0 * 6)
$reliabilityScore += min(20, $this->total_bookings);                  // Bookings max +20 (1 pt/booking)
$reliabilityScore += min(20, (int) ($this->profile_completion_percentage * 0.2)); // Completion max +20
// Total max = 100
```

**NOTE :** Cette formule est temporaire. Quand les reviews (Story 6.4) et bookings (Epic 3) seront implémentés, le calcul s'enrichira. Pour l'instant, utiliser les données existantes.

### Données futures — Design for extensibility

**Les modèles suivants N'EXISTENT PAS encore — NE PAS les créer dans cette story :**

| Modèle | Story future | Champ placeholder |
|---|---|---|
| `PortfolioMedia` | Future story (pas encore planifiée) | `portfolio_items: []` |
| `ServicePackage` | Story 1.8 | `service_packages: []` |
| `Review` | Story 6.4 / 6.5 | `recent_reviews: []`, `reviews_count: 0` |

Quand ces stories seront implémentées, elles ajouteront les relations au modèle `TalentProfile` et `TalentDetailResource` remplacera les `[]` par les vraies données via `$this->whenLoaded('relation')`.

### Route API — Ordre critique

```php
// routes/api.php — ORDRE IMPORTANT
Route::get('talents', [TalentController::class, 'index']);        // Liste/recherche
Route::get('talents/{slug}', [TalentController::class, 'show']); // Profil détaillé
```

**`{slug}` est un string** (pas un `{talent}` avec route model binding). Le slug est résolu manuellement dans le service via `findBySlug()`. Cela permet de vérifier `is_verified` dans la logique métier.

### Route Web — Pattern et convention

```php
// routes/web.php
Route::get('/talent/{slug}', [TalentPageController::class, 'show'])->name('talent.show');
```

**Pas de préfixe `/api/`** — c'est une page web publique. Le slug dans l'URL web correspond au slug du talent dans la base.

### Blade SSR — Schema.org JSON-LD

```html
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Person",
    "name": "{{ $profile->stage_name }}",
    "description": "{{ Str::limit($profile->bio, 160) }}",
    "url": "{{ route('talent.show', $profile->slug) }}",
    "address": {
        "@type": "PostalAddress",
        "addressLocality": "{{ $profile->city }}",
        "addressCountry": "CI"
    },
    "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "{{ $profile->average_rating }}",
        "bestRating": "5",
        "ratingCount": "0"
    }
}
</script>
```

**Open Graph meta tags :**
```html
<meta property="og:title" content="{{ $profile->stage_name }} - BookMi">
<meta property="og:description" content="{{ Str::limit($profile->bio, 160) }}">
<meta property="og:url" content="{{ route('talent.show', $profile->slug) }}">
<meta property="og:type" content="profile">
<meta property="og:site_name" content="BookMi">
```

### Gestion de l'erreur 404 — Code d'erreur

```php
// API 404 — Format JSON envelope (codes d'erreur préfixés TALENT_)
return $this->errorResponse(
    code: 'TALENT_NOT_FOUND',
    message: 'Le profil talent demandé est introuvable.',
    statusCode: 404,
);

// Web 404 — Blade standard Laravel
abort(404);
```

### findSimilar() — Requête optimisée

```php
public function findSimilar(TalentProfile $profile, int $limit = 5): Collection
{
    return TalentProfile::query()
        ->verified()
        ->where('category_id', $profile->category_id)
        ->where('city', $profile->city)
        ->where('id', '!=', $profile->id)
        ->with(['category', 'subcategory'])
        ->orderBy('average_rating', 'desc')
        ->limit($limit)
        ->get();
}
```

**Fallback si pas assez de résultats (même catégorie, ville différente) :**
Ne PAS implémenter de fallback dans cette story. Si 0 talents similaires → retourner `[]`. Le fallback avec élargissement de critères sera ajouté dans une future story si nécessaire (KISS).

### Testing Standards

**Pattern test show endpoint :**
```php
public function test_can_get_talent_profile_by_slug(): void
{
    $talent = TalentProfile::factory()->verified()->create([
        'stage_name' => 'DJ Test',
        'bio' => 'Bio de test',
    ]);

    $response = $this->getJson("/api/v1/talents/{$talent->slug}");

    $response->assertStatus(200)
        ->assertJsonPath('data.type', 'talent_profile')
        ->assertJsonPath('data.attributes.stage_name', 'DJ Test')
        ->assertJsonPath('data.attributes.bio', 'Bio de test');
}
```

**Pattern test web SSR :**
```php
public function test_web_talent_page_returns_200(): void
{
    $talent = TalentProfile::factory()->verified()->create();

    $response = $this->get("/talent/{$talent->slug}");

    $response->assertStatus(200);
}

public function test_web_talent_page_contains_schema_org(): void
{
    $talent = TalentProfile::factory()->verified()->create(['stage_name' => 'DJ Test']);

    $response = $this->get("/talent/{$talent->slug}");

    $response->assertStatus(200)
        ->assertSee('application/ld+json', false)
        ->assertSee('"@type"', false)
        ->assertSee('DJ Test', false);
}
```

**Tests de non-régression obligatoires :**
- Tous les 109 tests existants doivent continuer à passer
- Le endpoint `GET /api/v1/talents` (index/recherche) ne doit PAS être impacté

### Project Structure Notes

**Nouveaux fichiers à créer :**
```
app/Http/Resources/TalentDetailResource.php         # Resource profil détaillé
app/Http/Controllers/Web/TalentPageController.php   # Controller web SSR
resources/views/web/layouts/app.blade.php            # Layout web minimal
resources/views/web/talent/show.blade.php            # Page profil SEO
```

**Fichiers existants à modifier :**
```
app/Http/Controllers/Api/V1/TalentController.php    # Ajout show()
app/Services/TalentProfileService.php                # Ajout getPublicProfile()
app/Repositories/Contracts/TalentRepositoryInterface.php  # Ajout findSimilar()
app/Repositories/Eloquent/TalentRepository.php       # Implém findSimilar()
routes/api.php                                        # Route show
routes/web.php                                        # Route web talent
tests/Feature/Api/V1/TalentControllerTest.php        # Tests show endpoint
```

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story-1.7] — AC, FR14, FR15, FR17
- [Source: _bmad-output/planning-artifacts/architecture.md#API-Patterns] — JSON envelope, error codes TALENT_, slug routing
- [Source: _bmad-output/planning-artifacts/architecture.md#Web-Public-Routes] — TalentPageController, Blade SSR, Schema.org
- [Source: _bmad-output/planning-artifacts/architecture.md#Project-Structure] — Emplacement fichiers Web/, Resources/
- [Source: _bmad-output/planning-artifacts/architecture.md#Security] — Endpoints publics, pas d'auth requise pour consultation
- [Source: _bmad-output/implementation-artifacts/1-6-recherche-par-geolocalisation.md] — Patterns existants, code review findings

### Previous Story Intelligence

**Story 1.6 (Recherche par géolocalisation) — Leçons directement applicables :**

- `findBySlug()` existe DÉJÀ dans TalentRepository → le réutiliser, NE PAS recréer
- `getBySlug()` existe DÉJÀ dans TalentProfileService → étendre avec `getPublicProfile()`, pas dupliquer
- `TalentResource` est le format LISTE/CARD → créer un `TalentDetailResource` SÉPARÉ pour le profil détaillé
- PHPStan : `$this->when()` pattern → utiliser `$this->subcategory_id !== null` (pas truthy)
- PHPStan : `getAttribute()` pour les attributs dynamiques/computés
- Tests : `assertJsonPath()`, `assertJsonCount()`, `assertJsonStructure()` — patterns établis
- Erreurs validation : `assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_FAILED')`
- 404 : utiliser `errorResponse('TALENT_NOT_FOUND', '...', 404)` — code préfixé TALENT_
- Factory : `TalentProfile::factory()->verified()->create([...])` — réutiliser pattern existant
- `has_more` existe maintenant dans le meta pour DEUX types de paginateurs (fix code review 1.6)
- Route placement : mettre la route `{slug}` APRÈS la route `talents` (index) pour éviter les conflits

**Review Follow-ups Story 1.6 encore ouverts (LOW) :**
- `hasCoordinates()` inutilisé — ne PAS ajouter de méthodes inutilisées dans cette story
- Tests validation limites manquants — s'assurer de couvrir les cas limites dans cette story

## Dev Agent Record

### Agent Model Used

Claude Opus 4.6

### Debug Log References

- Blade `@context` directive conflict: JSON-LD `"@context"` was interpreted by Laravel 12 Blade compiler as the `@context()` helper, causing `ParseError: unexpected end of file`. Fixed by rendering JSON-LD via `json_encode()` with `{!! !!}` instead of inline JSON with `@` symbols.
- Blade inline `@section` with `Str::limit()` comma: `@section('meta_description', Str::limit(..., 160))` — removed the `160` argument to avoid comma-parsing ambiguity in Blade's inline section syntax.

### Completion Notes List

- AC1 (Profil complet API): `GET /api/v1/talents/{slug}` retourne le profil détaillé avec tous les attributs requis
- AC2 (Suggestions similaires): `meta.similar_talents` inclut max 5 talents (même catégorie, même ville, vérifiés)
- AC3 (URL publique SEO): `GET /talent/{slug}` sert une page Blade SSR avec Schema.org JSON-LD + Open Graph meta tags
- AC4 (404 slug inexistant): Retourne 404 avec code `TALENT_NOT_FOUND` pour slugs inconnus ou talents non vérifiés
- AC5 (Données futures): `portfolio_items: []`, `service_packages: []`, `recent_reviews: []`, `reviews_count: 0`
- `reliability_score` calculé dynamiquement (max 100) : verified +30, rating +max 30, bookings +max 20, completion +max 20
- 14 tests écrits (10 API + 4 web), 123 tests total, 428 assertions, 0 régressions

### File List

**Nouveaux fichiers créés :**
- `app/Http/Resources/TalentDetailResource.php` — Resource profil détaillé avec reliability_score
- `app/Http/Controllers/Web/TalentPageController.php` — Controller web SSR pour profil public
- `resources/views/web/layouts/app.blade.php` — Layout web minimal (head, body, yields)
- `resources/views/web/talent/show.blade.php` — Page profil SEO (Open Graph + Schema.org JSON-LD)
- `tests/Feature/Web/TalentPageControllerTest.php` — 5 tests web SSR

**Fichiers modifiés :**
- `app/Http/Controllers/Api/V1/TalentController.php` — Ajout `show(string $slug)` + injection TalentProfileService
- `app/Services/TalentProfileService.php` — Ajout `getPublicProfile(string $slug): ?array`
- `app/Repositories/Contracts/TalentRepositoryInterface.php` — Ajout `findSimilar()` signature
- `app/Repositories/Eloquent/TalentRepository.php` — Implémentation `findSimilar()`
- `routes/api.php` — Route `GET /api/v1/talents/{slug}`
- `routes/web.php` — Route `GET /talent/{slug}`
- `tests/Feature/Api/V1/TalentControllerTest.php` — 12 tests API Story 1.7

### Change Log

- **2026-02-17**: Implémentation complète Story 1.7
  - Créé `TalentDetailResource` avec calcul `reliability_score` et champs futurs vides
  - Ajouté `findSimilar()` au Repository (même catégorie + ville, vérifiés, max 5)
  - Ajouté `getPublicProfile()` au Service (vérifie is_verified, charge relations, similar talents)
  - Ajouté `show()` à TalentController avec error code `TALENT_NOT_FOUND`
  - Créé TalentPageController + vues Blade SSR avec Schema.org JSON-LD et Open Graph
  - Fix Blade: `@context` JSON-LD rendu via `json_encode()` pour éviter conflit directive Laravel 12
  - 14 tests écrits (10 API + 4 web), 123 total, 428 assertions, 0 régressions
  - Pint: PASS, PHPStan: 0 erreurs
- **2026-02-17**: Code review adversarial — 5 fixes appliqués
  - [H1] Fix XSS: ajout `JSON_HEX_TAG` au `json_encode()` JSON-LD Blade pour échapper `</script>`
  - [H2] Fix Schema.org `ratingCount`: changé de `total_bookings` à `0` (pas de reviews encore)
  - [M1] Fix `meta_description`: passage au format block `@section...@endsection` avec 160 chars
  - [M2] Ajout test `test_web_talent_page_returns_404_for_unverified_talent`
  - [M3] Ajout tests `test_show_reliability_score_formula` (score=79) et `test_show_reliability_score_max_100` (score=100)
  - 2 action items LOW créés (tests web renforcés, total_bookings dans API)
  - 126 tests, 433 assertions, 0 régressions, Pint PASS, PHPStan 0 erreurs
