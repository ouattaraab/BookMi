# Story 1.6: Recherche par géolocalisation (backend)

Status: done

## Story

As a client,
I want rechercher des talents par proximité géographique,
So that je puisse trouver des artistes disponibles près de mon événement.

**Functional Requirements:** FR13
**Non-Functional Requirements:** NFR3 (réponse < 1s), NFR24 (stable à 500 talents), NFR45 (précision géoloc)

## Acceptance Criteria (BDD)

**Given** un client avec sa position GPS (latitude, longitude)
**When** il envoie `GET /api/v1/talents?lat=5.36&lng=-4.01&radius=50`
**Then** les talents dans le rayon spécifié (km) sont retournés triés par distance
**And** la recherche utilise la formule Haversine (équivalent mathématique de ST_Distance_Sphere, compatible MySQL + SQLite)
**And** le champ `distance_km` est inclus dans chaque résultat
**And** la performance est < 1 seconde même avec 500 talents en base (NFR24)

## Tasks / Subtasks

- [x] Task 1: Créer migration pour ajouter `latitude` et `longitude` à `talent_profiles` (AC: support géolocalisation)
  - [x] 1.1: Ajouter `$table->decimal('latitude', 10, 8)->nullable()->after('city')`
  - [x] 1.2: Ajouter `$table->decimal('longitude', 11, 8)->nullable()->after('latitude')`
  - [x] 1.3: Ajouter index composé `$table->index(['latitude', 'longitude'])` — requis pour bounding box pre-filter
  - [x] 1.4: `down()` : drop les colonnes latitude et longitude

- [x] Task 2: Mettre à jour `TalentProfile` model (AC: colonnes géoloc accessibles)
  - [x] 2.1: Ajouter `'latitude'` et `'longitude'` dans `$fillable`
  - [x] 2.2: Ajouter casts : `'latitude' => 'decimal:8'`, `'longitude' => 'decimal:8'`
  - [x] 2.3: Créer scope `scopeWithinRadiusOf(Builder $query, float $lat, float $lng, float $radiusKm): Builder` — implémenté avec formule Haversine (compatible MySQL + SQLite)
  - [x] 2.4: Créer helper `hasCoordinates(): bool` → `$this->latitude !== null && $this->longitude !== null`

- [x] Task 3: Mettre à jour `TalentProfileFactory` (AC: tests avec coordonnées)
  - [x] 3.1: Ajouter `'latitude' => null, 'longitude' => null` dans `definition()` (optionnels par défaut)
  - [x] 3.2: Créer state `withCoordinates(float $lat = 5.3600, float $lng = -4.0083): static` — coordonnées par défaut = Abidjan
  - [x] 3.3: Créer state `inAbidjan(): static` → lat 5.3364, lng -3.9683 (centre Plateau)
  - [x] 3.4: Créer state `inBouake(): static` → lat 7.6939, lng -5.0308

- [x] Task 4: Étendre `SearchTalentRequest` — validation paramètres géoloc (AC: `lat`, `lng`, `radius`)
  - [x] 4.1: Ajouter règles : `'lat' => ['nullable', 'numeric', 'between:-90,90']`
  - [x] 4.2: Ajouter règles : `'lng' => ['nullable', 'numeric', 'between:-180,180']`
  - [x] 4.3: Ajouter règles : `'radius' => ['nullable', 'numeric', 'min:1', 'max:500']`
  - [x] 4.4: Ajouter validation croisée dans `after()` : lat, lng, radius doivent tous les trois être présents ensemble (ou aucun)
  - [x] 4.5: Ajouter `sort_by` value `'distance'` dans la règle `in:...`
  - [x] 4.6: Ajouter messages français pour les nouveaux champs
  - [x] 4.7: Valider que `sort_by=distance` ne peut être utilisé que si `lat` + `lng` sont fournis (dans `after()`)

- [x] Task 5: Étendre `TalentRepositoryInterface` et `TalentRepository` (AC: query spatiale)
  - [x] 5.1: Ajouter paramètre optionnel `?array $geoParams = null` à `searchVerified()` dans l'interface
  - [x] 5.2: Implémenter dans `TalentRepository::searchVerified()` :
    - Quand `$geoParams` est fourni : utiliser `whereNotNull('latitude')` + scope `withinRadiusOf`
    - Ajouter `selectRaw` pour le calcul `distance_km` avec Haversine
    - Géoloc utilise `paginate()` (offset) au lieu de `cursorPaginate()` — les alias computés ne supportent pas cursor WHERE
  - [x] 5.3: Quand `$geoParams` est null, le comportement actuel est préservé (ZERO régression)

- [x] Task 6: Étendre `SearchService` (AC: orchestration géoloc)
  - [x] ~~6.1: Ajouter `'lat'`, `'lng'`, `'radius'` dans `FILTER_KEYS`~~ — Géoparams extraits séparément (pas dans FILTER_KEYS) car ils ne sont pas des filtres SQL standards
  - [x] 6.2: Ajouter `'distance' => 'distance_km'` dans `SORT_COLUMN_MAP`
  - [x] 6.3: Extraire `$geoParams` des filtres quand lat+lng+radius sont tous présents : `['lat' => ..., 'lng' => ..., 'radius' => ...]`
  - [x] 6.4: Quand géoloc active, sort par défaut = `distance_km` ASC (pas `created_at` DESC)
  - [x] 6.5: Passer `$geoParams` au repository

- [x] Task 7: Étendre `TalentResource` — champ `distance_km` conditionnel (AC: distance dans la réponse)
  - [x] 7.1: Ajouter dans attributes : `'distance_km' => $this->when($this->resource->getAttribute('distance_km') !== null, ...)` — utilise `getAttribute()` pour compatibilité PHPStan
  - [x] 7.2: Le champ n'apparaît QUE quand la recherche géoloc est active (grâce au `selectRaw` alias)

- [x] Task 8: Écrire les tests Feature — `TalentControllerTest` (AC: tous critères géoloc)
  - [x] 8.1: `test_can_search_by_geolocation` — 3 talents à distances variées, radius filtre correctement
  - [x] 8.2: `test_geolocation_results_sorted_by_distance_asc` — tri par distance croissante par défaut
  - [x] 8.3: `test_geolocation_response_includes_distance_km` — champ `distance_km` présent dans la réponse
  - [x] 8.4: `test_geolocation_excludes_talents_without_coordinates` — talents sans lat/lng exclus quand géoloc active
  - [x] 8.5: `test_geolocation_combined_with_category_filter` — géoloc + category_id ensemble
  - [x] 8.6: `test_validation_fails_when_lat_without_lng` — lat seul = 422
  - [x] 8.7: `test_validation_fails_when_radius_without_coordinates` — radius sans lat/lng = 422
  - [x] 8.8: `test_validation_fails_with_lat_out_of_range` — lat > 90 = 422
  - [x] 8.9: `test_validation_fails_with_sort_by_distance_without_geolocation` — sort_by=distance sans lat/lng = 422
  - [x] 8.10: `test_search_without_geolocation_still_works` — recherche classique Story 1.5 inchangée (vérifie cursor pagination meta)
  - [x] 8.11: `test_distance_km_absent_without_geolocation` — distance_km n'apparaît PAS sans géoloc
  - [x] 8.12: `test_geolocation_pagination_works` — offset pagination fonctionne avec tri distance

- [x] Task 9: Écrire les tests Feature — `SearchServiceTest` (AC: logique métier géoloc)
  - [x] 9.1: `test_search_with_geo_params_filters_by_radius`
  - [x] 9.2: `test_search_with_geo_defaults_sort_to_distance_asc`
  - [x] 9.3: `test_search_without_geo_preserves_default_sort`

- [x] Task 10: Vérifications qualité
  - [x] 10.1: `./vendor/bin/pint --test` — 0 erreurs
  - [x] 10.2: `./vendor/bin/phpstan analyse --memory-limit=512M` — 0 erreurs
  - [x] 10.3: `php artisan test` — 109 tests passent (356 assertions), 0 régressions sur les 94 tests existants

## Review Follow-ups (AI)

- [ ] [AI-Review][LOW] Supprimer la méthode `hasCoordinates()` inutilisée dans `TalentProfile.php:144-147` (dead code)
- [ ] [AI-Review][LOW] Ajouter tests validation limites manquants : `lng=200` (hors plage), `radius=0` (< min:1), `radius=501` (> max:500) dans `TalentControllerTest.php`
- [ ] [AI-Review][LOW] Utiliser les factory states `withCoordinates()`/`inAbidjan()`/`inBouake()` dans les tests géoloc au lieu des coordonnées inline pour améliorer la lisibilité

## Dev Notes

### Architecture & Patterns Obligatoires

**Pattern Controller → Service → Repository → Model (ARCH-PATTERN-2) — MÊME que Story 1.5 :**
```
TalentController::index(SearchTalentRequest)
  → SearchService::searchTalents(filters, sortBy, sortDirection, perPage)
    → TalentRepository::searchVerified(filters, sortBy, sortDirection, perPage, geoParams)
      → TalentProfile::verified()->withinRadiusOf(...)->cursorPaginate()
```

**Cette story ÉTEND les fichiers Story 1.5 — elle ne crée PAS de nouveaux controllers/services.**

### Composants existants à réutiliser — NE PAS recréer

**Fichiers Story 1.5 à étendre (NE PAS dupliquer) :**

| Fichier existant | Extension requise |
|---|---|
| `app/Services/SearchService.php` | Ajouter FILTER_KEYS géoloc, SORT_COLUMN_MAP distance, extraction geoParams |
| `app/Repositories/Eloquent/TalentRepository.php` | Ajouter paramètre geoParams, selectRaw distance, scope |
| `app/Repositories/Contracts/TalentRepositoryInterface.php` | Ajouter paramètre `?array $geoParams = null` |
| `app/Http/Requests/Api/SearchTalentRequest.php` | Ajouter règles lat/lng/radius, validation croisée |
| `app/Http/Resources/TalentResource.php` | Ajouter champ conditionnel `distance_km` |
| `app/Models/TalentProfile.php` | Ajouter fillable, casts, scope withinRadiusOf |
| `database/factories/TalentProfileFactory.php` | Ajouter states withCoordinates, inAbidjan, inBouake |
| `tests/Feature/Api/V1/TalentControllerTest.php` | Ajouter tests géoloc (DANS LE MÊME fichier) |
| `tests/Feature/Services/SearchServiceTest.php` | Ajouter tests géoloc service |

**Endpoint existant utilisé :** `GET /api/v1/talents` — même endpoint, paramètres supplémentaires. PAS de nouvelle route.

### MySQL ST_Distance_Sphere — Implémentation exacte

**ATTENTION CRITIQUE : l'ordre des arguments POINT() est longitude FIRST, latitude SECOND.**

```php
// ❌ FAUX — erreur commune LLM
POINT(latitude, longitude)

// ✅ CORRECT — ordre MySQL natif
POINT(longitude, latitude)
```

**Formule ST_Distance_Sphere (retourne des MÈTRES, diviser par 1000 pour km) :**
```sql
ST_Distance_Sphere(
    POINT(talent_profiles.longitude, talent_profiles.latitude),
    POINT(?, ?)
) / 1000 AS distance_km
```

**Scope scopeWithinRadiusOf — implémentation avec bounding box pre-filter :**
```php
public function scopeWithinRadiusOf(
    \Illuminate\Database\Eloquent\Builder $query,
    float $lat,
    float $lng,
    float $radiusKm,
): \Illuminate\Database\Eloquent\Builder {
    // Bounding box pre-filter (utilise l'index B-tree latitude/longitude)
    $latDelta = $radiusKm / 111.0;
    $lngDelta = $radiusKm / (111.0 * cos(deg2rad($lat)));

    return $query
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
        ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta])
        ->whereRaw(
            'ST_Distance_Sphere(POINT(longitude, latitude), POINT(?, ?)) / 1000 <= ?',
            [$lng, $lat, $radiusKm],
        );
}
```

**Pourquoi bounding box :**
- `whereBetween` sur latitude/longitude utilise l'index B-tree composé → scan rapide
- `ST_Distance_Sphere` n'utilise PAS d'index → appliqué seulement sur les résultats pré-filtrés
- Performance < 1s même avec 500 talents (NFR24)

### selectRaw et cursor pagination — Compatibilité

**ATTENTION : `cursorPaginate()` nécessite que les colonnes de tri existent comme alias ou colonnes réelles.**

```php
// ✅ CORRECT — alias via selectRaw + orderBy sur l'alias
$query->selectRaw('talent_profiles.*, ST_Distance_Sphere(POINT(longitude, latitude), POINT(?, ?)) / 1000 AS distance_km', [$lng, $lat])
    ->orderBy('distance_km', 'asc')
    ->orderBy('id', 'asc')
    ->cursorPaginate($perPage);

// ❌ FAUX — orderByRaw avec paramètres ne fonctionne PAS avec cursorPaginate
$query->orderByRaw('ST_Distance_Sphere(...) ASC')  // BROKEN avec cursor
```

**Le selectRaw doit être ajouté SEULEMENT quand geoParams est fourni :**
```php
// Dans TalentRepository::searchVerified()
if ($geoParams) {
    $query->selectRaw(
        'talent_profiles.*, ST_Distance_Sphere(POINT(longitude, latitude), POINT(?, ?)) / 1000 AS distance_km',
        [$geoParams['lng'], $geoParams['lat']],
    );
} else {
    $query->select('talent_profiles.*'); // Comportement Story 1.5 inchangé
}
```

### Colonnes DECIMAL vs POINT geometry

**Choix architecture : DECIMAL(10,8) / DECIMAL(11,8)**
- Plus simple que le type POINT de MySQL
- Fonctionne avec B-tree index standard (pas besoin de spatial index R-tree)
- Compatible avec `whereBetween` pour le bounding box
- Précision : ~1.1mm — largement suffisante (NFR45 demande précision au quartier)
- Les colonnes sont `nullable` car un talent peut ne pas avoir de coordonnées encore

### Validation croisée géoloc — Pattern after()

Étendre le `after()` existant dans `SearchTalentRequest` (ne pas le remplacer) :

```php
public function after(): array
{
    return [
        // Validation existante Story 1.5 — min_cachet <= max_cachet
        function (\Illuminate\Validation\Validator $validator) {
            if (
                $this->filled('min_cachet')
                && $this->filled('max_cachet')
                && (int) $this->input('min_cachet') > (int) $this->input('max_cachet')
            ) {
                $validator->errors()->add(
                    'min_cachet',
                    'Le montant minimum du cachet doit être inférieur ou égal au montant maximum.',
                );
            }
        },
        // NOUVELLE validation géoloc — lat, lng, radius doivent être tous présents ou tous absents
        function (\Illuminate\Validation\Validator $validator) {
            $hasLat = $this->filled('lat');
            $hasLng = $this->filled('lng');
            $hasRadius = $this->filled('radius');
            $anyGeo = $hasLat || $hasLng || $hasRadius;
            $allGeo = $hasLat && $hasLng && $hasRadius;

            if ($anyGeo && ! $allGeo) {
                $validator->errors()->add(
                    'lat',
                    'Les paramètres lat, lng et radius doivent tous être fournis ensemble.',
                );
            }
        },
        // NOUVELLE validation — sort_by=distance nécessite géoloc
        function (\Illuminate\Validation\Validator $validator) {
            if (
                $this->input('sort_by') === 'distance'
                && (! $this->filled('lat') || ! $this->filled('lng'))
            ) {
                $validator->errors()->add(
                    'sort_by',
                    'Le tri par distance nécessite les paramètres lat et lng.',
                );
            }
        },
    ];
}
```

**IMPORTANT : ajouter `'distance'` dans la règle `sort_by` existante :**
```php
'sort_by' => ['nullable', 'string', 'in:rating,cachet_amount,created_at,distance'],
```

### TalentResource — distance_km conditionnel

```php
// Dans toArray() → attributes :
'distance_km' => $this->when(
    isset($this->resource->distance_km) || property_exists($this->resource, 'distance_km'),
    fn () => round((float) $this->distance_km, 2),
),
```

**Attention :** `distance_km` n'est pas un attribut Eloquent — c'est un alias SQL ajouté via `selectRaw`. Utiliser `isset()` ou `property_exists()` pour vérifier sa présence. L'attribut est accessible via `__get()` d'Eloquent quand ajouté via selectRaw.

**Approche recommandée (plus simple et fiable) :**
```php
'distance_km' => $this->when(
    $this->resource->getAttributes()['distance_km'] ?? null !== null,
    fn () => round((float) $this->distance_km, 2),
),
```

Ou plus simplement, puisque `$this->distance_km` retourne null si absent :
```php
'distance_km' => $this->when($this->distance_km !== null, fn () => round((float) $this->distance_km, 2)),
```

### SearchService — Extraction geoParams

```php
// Nouvelles constantes à ajouter :
private const GEO_KEYS = ['lat', 'lng', 'radius'];

// Dans searchTalents() :
$geoParams = null;
if (isset($params['lat'], $params['lng'], $params['radius'])) {
    $geoParams = [
        'lat' => (float) $params['lat'],
        'lng' => (float) $params['lng'],
        'radius' => (float) $params['radius'],
    ];
}

// Sort par défaut quand géoloc active :
if ($geoParams && $sortBy === null) {
    $sortBy = 'distance_km';
    $sortDirection = 'asc';
}
```

### Décisions techniques critiques

**1. Coordonnées nullable :**
Les colonnes `latitude` et `longitude` sont `nullable` car les talents existants n'ont pas de coordonnées. La recherche géoloc EXCLUT automatiquement les talents sans coordonnées via `whereNotNull` dans le scope.

**2. Rayon par défaut :**
Le rayon (`radius`) est OBLIGATOIRE quand lat/lng sont fournis. Pas de valeur par défaut — le client mobile décide du rayon. Valeur max : 500km (couvre toute la Côte d'Ivoire).

**3. Pas de geocoding API dans cette story :**
FR13 demande recherche par coordonnées GPS. Le geocoding (adresse → coordonnées) est hors scope. Le client mobile envoie directement lat/lng via le GPS du téléphone ou saisie manuelle. L'intégration Google Maps API / OSM sera pour une story future si nécessaire.

**4. Unité de distance :**
`ST_Distance_Sphere` retourne des MÈTRES. Division par 1000 dans le SQL pour obtenir des km. Le champ API `distance_km` est en kilomètres avec 2 décimales.

**5. Combinaison filtres :**
Les filtres géoloc se COMBINENT avec les filtres existants (category_id, city, min_rating, etc.). Un client peut chercher "musiciens à moins de 30km d'Abidjan avec un cachet entre 1M et 5M FCFA".

**6. Signature searchVerified mise à jour :**
Ajouter `?array $geoParams = null` comme DERNIER paramètre pour préserver la compatibilité. Les appels existants sans géoloc continuent de fonctionner sans modification.

**7. Formule bounding box — explication des constantes :**
- `111.0` km ≈ 1 degré de latitude partout sur terre
- `111.0 * cos(deg2rad($lat))` ≈ 1 degré de longitude à la latitude donnée
- Abidjan est à ~5.3° N → `cos(5.3°) ≈ 0.9957` → quasi identique à 111 km
- La formule est une approximation suffisante pour le bounding box (le ST_Distance_Sphere filtre ensuite précisément)

### Project Structure Notes

- Migration dans `database/migrations/` — nouvelle migration ajout colonnes (pas de modification de l'existante)
- AUCUN nouveau fichier PHP à créer dans `app/` — uniquement extensions de fichiers existants
- Tests ajoutés dans les fichiers de tests EXISTANTS (même pattern que Story 1.5)
- La route `GET /api/v1/talents` est INCHANGÉE — mêmes params optionnels supplémentaires

### Testing Standards

**Coordonnées de référence pour les tests (Côte d'Ivoire) :**
| Lieu | Latitude | Longitude | Usage test |
|---|---|---|---|
| Abidjan (Plateau) | 5.3364 | -3.9683 | Point de référence principal |
| Cocody (Abidjan) | 5.3488 | -3.9883 | ~2.5 km du Plateau |
| Yopougon (Abidjan) | 5.3390 | -4.0600 | ~10 km du Plateau |
| Bouaké | 7.6939 | -5.0308 | ~330 km d'Abidjan (hors rayon) |

**Pattern tests géoloc :**
```php
// Créer talents avec coordonnées
TalentProfile::factory()->verified()->withCoordinates(5.3488, -3.9883)->create(['stage_name' => 'Cocody']);
TalentProfile::factory()->verified()->withCoordinates(7.6939, -5.0308)->create(['stage_name' => 'Bouake']);
TalentProfile::factory()->verified()->create(); // Sans coordonnées

// Recherche géoloc — rayon 20km autour du Plateau
$response = $this->getJson('/api/v1/talents?lat=5.3364&lng=-3.9683&radius=20');
$response->assertStatus(200)
    ->assertJsonCount(1, 'data') // Seul Cocody est dans le rayon
    ->assertJsonPath('data.0.attributes.stage_name', 'Cocody')
    ->assertJsonPath('data.0.attributes.distance_km', fn ($v) => $v > 0 && $v < 20);
```

**Tests de non-régression obligatoires :**
- `test_search_without_geolocation_still_works` : vérifier que `GET /api/v1/talents` sans lat/lng fonctionne exactement comme avant
- `test_distance_km_absent_without_geolocation` : vérifier que `distance_km` N'apparaît PAS dans la réponse quand pas de géoloc
- Tous les 22 tests TalentController existants doivent continuer à passer

### References

- [Source: _bmad-output/planning-artifacts/architecture.md#Database-Decisions] — MySQL 8.x ST_Distance_Sphere
- [Source: _bmad-output/planning-artifacts/architecture.md#Services] — SearchService.php geocode
- [Source: _bmad-output/planning-artifacts/architecture.md#External-Integrations] — Google Maps / OSM config
- [Source: _bmad-output/planning-artifacts/architecture.md#API-Patterns] — JSON envelope, cursor pagination
- [Source: _bmad-output/planning-artifacts/epics.md#Story-1.6] — AC, FR13, NFR24, NFR45
- [Source: _bmad-output/implementation-artifacts/1-5-annuaire-des-talents-avec-filtres.md] — Patterns existants, code review findings
- [Source: MySQL 8.x Documentation] — ST_Distance_Sphere, POINT(lng, lat) order
- [Source: Laravel 12 Documentation] — cursorPaginate + selectRaw alias compatibility

### Previous Story Intelligence

**Story 1.5 (Annuaire des talents avec filtres) — Leçons directement applicables :**
- `cachet_amount` est NOT NULL en DB (contrairement aux Dev Notes) → ne pas ajouter whereNotNull inutile pour cachet
- `latitude` et `longitude` SERONT nullable → le whereNotNull est nécessaire pour eux
- `$this->travel(1)->minutes()` entre créations pour tester le tri par défaut
- PHPStan `when()` : utiliser `$this->subcategory_id !== null` (pas truthy) — même pattern pour `distance_km`
- Validation croisée via `after()` : le pattern est déjà en place pour min/max cachet → l'étendre (pas le remplacer)
- Erreurs validation : `assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_FAILED')`
- Factory state pattern : `->verified()->create([...])` — ajouter `->withCoordinates(...)` de la même manière
- L'index composé `['latitude', 'longitude']` suit le pattern de l'index `cachet_amount` ajouté en review

**Patterns établis à respecter :**
- Nommage JSON : `snake_case` → `distance_km` (pas `distanceKm`)
- PHPStan : `--memory-limit=512M` obligatoire
- Pint : PSR-12
- Tests : assertions `assertJsonPath`, `assertJsonCount`, `assertJsonStructure`
- Tiebreaker cursor : `->orderBy('id', 'asc')` après le tri principal

**Review findings Story 1.5 encore ouverts (LOW) — à garder en tête :**
- [ ] [LOW] Dev Notes contiennent info incorrecte sur nullabilité cachet_amount — cette story le corrige implicitement (latitude/longitude SONT nullable)
- [ ] [LOW] Tests validation limites per_page (0, 51, -1) non couverts
- [ ] [LOW] Défaut perPage=20 dupliqué dans TalentController et SearchService

## Dev Agent Record

### Agent Model Used
Claude Opus 4.6 (claude-opus-4-6)

### Debug Log References
1. **ST_Distance_Sphere incompatible SQLite** — Les tests utilisent SQLite en mémoire qui ne supporte pas `ST_Distance_Sphere` ni les fonctions trigonométriques (SIN, COS, RADIANS, etc.). Résolu en utilisant la formule Haversine (mathématiquement équivalente) et en enregistrant les fonctions math manquantes dans `TestCase::setUp()` via `sqliteCreateFunction`.
2. **cursorPaginate incompatible avec colonnes computées** — `cursorPaginate()` génère des conditions WHERE sur les colonnes de tri. Les alias SELECT (`distance_km`) ne sont pas accessibles dans WHERE. Résolu en utilisant `paginate()` (offset) pour les requêtes géoloc, et `cursorPaginate()` pour les requêtes classiques. `ApiResponseTrait::paginatedResponse()` supporte déjà les deux types.
3. **PHPStan property.notFound sur distance_km** — `$this->distance_km` dans `TalentResource` cause une erreur PHPStan car `distance_km` n'est pas une propriété déclarée du modèle. Résolu en utilisant `$this->resource->getAttribute('distance_km')`.
4. **PHPStan constant unused** — `GEO_KEYS` dans SearchService était déclaré mais non utilisé (geoParams extraits directement via `isset`). Résolu en supprimant la constante inutile.

### Completion Notes List
- ✅ Migration `add_geolocation_columns_to_talent_profiles_table` : colonnes DECIMAL(10,8)/DECIMAL(11,8) nullable + index composé
- ✅ TalentProfile : fillable, casts, scopeWithinRadiusOf (Haversine + bounding box), hasCoordinates()
- ✅ TalentProfileFactory : states withCoordinates, inAbidjan, inBouake
- ✅ SearchTalentRequest : validation lat/lng/radius + 3 closures after() (co-présence, sort_by=distance) + messages FR
- ✅ TalentRepository : Haversine selectRaw + withinRadiusOf scope + paginate() pour géoloc, cursorPaginate() pour classique
- ✅ SearchService : SORT_COLUMN_MAP distance→distance_km, extraction geoParams, sort par défaut distance_km ASC
- ✅ TalentResource : distance_km conditionnel via getAttribute()
- ✅ TestCase : fonctions SQLite math enregistrées (RADIANS, SIN, COS, ASIN, SQRT, POWER)
- ✅ 15 nouveaux tests (12 TalentControllerTest + 3 SearchServiceTest)
- ✅ 109 tests total, 356 assertions, 0 régressions
- ✅ Pint PSR-12 : 0 erreurs, PHPStan : 0 erreurs
- ⚠️ Task 6.1 adaptée : geoParams extraits séparément des FILTER_KEYS (pas des filtres SQL standards)
- ⚠️ Formule Haversine utilisée au lieu de ST_Distance_Sphere pour compatibilité SQLite tests

### File List

**Fichiers créés :**
| Fichier | Description |
|---|---|
| `database/migrations/2026_02_17_191919_add_geolocation_columns_to_talent_profiles_table.php` | Migration ajout latitude/longitude + index composé |

**Fichiers modifiés :**
| Fichier | Modification |
|---|---|
| `app/Models/TalentProfile.php` | Ajout fillable (latitude, longitude), casts decimal:8, scopeWithinRadiusOf, hasCoordinates() |
| `database/factories/TalentProfileFactory.php` | Ajout definition null coords, states withCoordinates/inAbidjan/inBouake |
| `app/Http/Requests/Api/SearchTalentRequest.php` | Ajout validation lat/lng/radius, sort_by=distance, 2 closures after() géoloc, messages FR |
| `app/Repositories/Contracts/TalentRepositoryInterface.php` | Ajout param ?array $geoParams, return type union CursorPaginator\|LengthAwarePaginator |
| `app/Repositories/Eloquent/TalentRepository.php` | Haversine SQL constant, selectRaw distance_km, withinRadiusOf scope, paginate() pour géoloc |
| `app/Services/SearchService.php` | SORT_COLUMN_MAP distance, extraction geoParams, sort défaut distance_km ASC, return type union |
| `app/Http/Resources/TalentResource.php` | Ajout distance_km conditionnel via getAttribute() |
| `tests/TestCase.php` | Enregistrement fonctions SQLite math (RADIANS, SIN, COS, ASIN, SQRT, POWER) |
| `tests/Feature/Api/V1/TalentControllerTest.php` | 12 nouveaux tests géolocalisation |
| `tests/Feature/Services/SearchServiceTest.php` | 3 nouveaux tests géolocalisation service |

### Change Log
- **2026-02-17** — Implémentation Story 1.6 : Recherche par géolocalisation (backend). Endpoint `GET /api/v1/talents` étendu avec paramètres `lat`, `lng`, `radius` pour recherche spatiale. Formule Haversine + bounding box pre-filter. 15 nouveaux tests (109 total, 356 assertions).
- **2026-02-17** — Code Review fixes : [H1] Ajout `has_more` au meta LengthAwarePaginator pour cohérence API. [M1] Déduplication formule Haversine → constante unique `TalentProfile::HAVERSINE_SQL`. [M2] Test geo pagination renforcé (page 2 navigation, total, has_more). [H2] AC mise à jour (ST_Distance_Sphere → Haversine). 3 action items LOW créés. 109 tests, 363 assertions.
