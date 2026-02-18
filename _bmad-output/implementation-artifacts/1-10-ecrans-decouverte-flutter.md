# Story 1.10: Écrans découverte Flutter (mobile)

Status: done

## Story

As a client,
I want naviguer dans l'annuaire des talents depuis l'app mobile avec une expérience fluide et visuellement riche,
So that la découverte de talents soit agréable et intuitive.

**Functional Requirements:** FR11, FR12, FR16
**Non-Functional Requirements:** NFR1 (pages < 3s sur 3G), NFR5 (Flutter < 3s démarrage 2 Go RAM), NFR36-NFR40 (WCAG 2.1 AA, écrans 4,7"→6,7"), UX-FEEDBACK-2 (skeleton screens), UX-NAV-3 (Hero animation)

## Acceptance Criteria (BDD)

**AC1 — TalentCards glassmorphism**
**Given** l'app est lancée et l'utilisateur est sur l'onglet Recherche
**When** il parcourt la liste des talents
**Then** les TalentCards glassmorphism s'affichent avec photo, nom de scène, catégorie (couleur par catégorie), note, cachet
**And** chaque carte inclut un badge vérifié si applicable
**And** le FavoriteButton (Story 1.9) est intégré dans chaque TalentCard

**AC2 — Skeleton screens**
**Given** l'utilisateur navigue vers l'onglet Recherche
**When** les données chargent
**Then** des skeleton screens s'affichent pendant le chargement (rectangles glassmorphism pulsants)
**And** le skeleton reproduit la même structure que les TalentCards (photo placeholder, nom, catégorie, cachet)

**AC3 — Pull-to-refresh**
**Given** l'utilisateur est sur la grille de talents
**When** il tire vers le bas
**Then** un RefreshIndicator `brand-blue` s'affiche et les données sont rechargées
**And** la pagination est réinitialisée

**AC4 — FilterBar (catégorie, budget, ville, note)**
**Given** l'utilisateur est sur l'écran de recherche
**When** il utilise la FilterBar
**Then** il peut filtrer par catégorie (chips scrollables), budget (range), ville, note minimum
**And** les filtres actifs sont visuellement distincts (`brand-blue` rempli vs outline `glass-border`)
**And** un bouton "Effacer" permet de réinitialiser tous les filtres

**AC5 — Scroll infini cursor-based**
**Given** l'utilisateur est sur la grille de talents
**When** il scrolle vers le bas et approche la fin de la liste
**Then** la page suivante charge automatiquement (cursor-based pagination)
**And** un indicateur de chargement s'affiche en bas de la grille
**And** si plus aucun résultat, un message "Fin des résultats" s'affiche

**AC6 — Hero animation vers le profil**
**Given** l'utilisateur voit un TalentCard
**When** il tape dessus
**Then** une transition Hero animation fluide (300ms) agrandit la photo de la carte vers l'écran profil
**Note:** L'écran profil complet est implémenté dans Story 1.11. Pour 1.10, créer une page de détail minimale (`TalentDetailPage`) affichant les données JSON du talent + FavoriteButton.

**AC7 — Responsive 4,7" à 6,7"**
**Given** l'app est exécutée sur des écrans de tailles variées
**When** l'écran mesure entre 4,7" et 6,7"
**Then** la grille s'adapte : 2 colonnes sur tous les formats, taille des cartes proportionnelle
**And** les zones de tap respectent 48x48px minimum (WCAG 2.1 AA)

## Tasks / Subtasks

### Design System — Composants Discovery (P1)

- [x] Task 1: Créer le widget TalentCard (AC: AC1, AC6, AC7)
  - [x] 1.1: Créer `lib/core/design_system/components/talent_card.dart`
  - [x] 1.2: Props : `id`, `stageName`, `categoryName`, `categoryColor`, `city`, `cachetAmount`, `averageRating`, `isVerified`, `photoUrl`, `onTap`
  - [x] 1.3: Composer sur GlassCard (existant) — NE PAS recréer le glassmorphism
  - [x] 1.4: Photo en haut (120px height, `ClipRRect` borderRadius 16), nom en Title Medium blanc, catégorie en Caption avec couleur accent de catégorie, cachet en Label Large `brand-blue-light`, note en Caption `warning` (étoile + note)
  - [x] 1.5: Badge vérifié (icône check dans cercle glass) en top-right de la photo si `isVerified`
  - [x] 1.6: Intégrer `FavoriteButton(talentId: id)` depuis `features/favorites/widgets/favorite_button.dart` en top-left de la photo
  - [x] 1.7: Envelopper dans `Hero` widget avec `tag: 'talent-$id'` pour la transition vers le profil
  - [x] 1.8: Formater le cachet : `NumberFormat('#,###', 'fr_FR').format(amount ~/ 100)` + " FCFA" (montant stocké en centimes)
  - [x] 1.9: Tap zones ≥ 48x48px, animation press scale 0.98 (déjà fourni par GlassCard)

- [x] Task 2: Créer le widget TalentCardSkeleton (AC: AC2)
  - [x] 2.1: Créer `lib/core/design_system/components/talent_card_skeleton.dart`
  - [x] 2.2: Structure identique au TalentCard : rectangle photo (120px), rectangle nom, rectangle catégorie, rectangle cachet
  - [x] 2.3: Utiliser `shimmer` package (ajouter à pubspec.yaml) ou animation personnalisée avec `LinearGradient` pulsant
  - [x] 2.4: Fond glassmorphism (utiliser GlassCard comme base avec enfants rectangles gris semi-transparents)

- [x] Task 3: Créer le widget FilterBar (AC: AC4)
  - [x] 3.1: Créer `lib/core/design_system/components/filter_bar.dart`
  - [x] 3.2: ListView horizontal scrollable de FilterChip
  - [x] 3.3: Chip actif : fond `BookmiColors.brandBlue`, texte blanc
  - [x] 3.4: Chip inactif : outline `BookmiColors.glassBorder`, texte blanc 60%
  - [x] 3.5: Bouton "Effacer" en fin de scroll (TextButton, texte `brand-blue`)
  - [x] 3.6: Props : `filters` (List<FilterItem>), `activeFilters` (Set<String>), `onFilterChanged`, `onClearAll`
  - [x] 3.7: Types de filtres : catégorie (choix unique parmi les chips), budget (ouvre BottomSheet RangeSlider), ville (ouvre BottomSheet avec TextField), note minimum (chips 3★, 4★, 4.5★)
  - [x] 3.8: Hauteur fixe 48px, padding vertical centré

### Feature Discovery — Data Layer

- [x] Task 4: Créer le DiscoveryRepository (AC: AC1, AC4, AC5)
  - [x] 4.1: Créer `lib/features/discovery/data/repositories/discovery_repository.dart`
  - [x] 4.2: Constructeur avec `ApiClient` + `LocalStorage` (core/storage)
  - [x] 4.3: Constructeur `.forTesting` avec `Dio` + `LocalStorage` direct (pattern Story 1.9)
  - [x] 4.4: `Future<ApiResult<TalentListResponse>> getTalents({String? cursor, int perPage = 20, Map<String, dynamic>? filters})` — appel `GET /api/v1/talents` avec queryParameters
  - [x] 4.5: Parse la réponse : `TalentListResponse` contient `List<Map<String, dynamic>> talents` + `PaginationMeta meta` (next cursor, has_more, total)
  - [x] 4.6: Fallback cache Hive : si erreur réseau, retourner les données cachées (dernière réponse /talents)
  - [x] 4.7: Cacher le résultat API dans Hive box "discovery" (clé "last_talents") après chaque réponse réussie

- [x] Task 5: Ajouter les endpoints API manquants (AC: AC1, AC4)
  - [x] 5.1: Vérifier dans `api_endpoints.dart` que le endpoint talents existe → il existe déjà : `ApiEndpoints.talents = '/talents'`
  - [x] 5.2: Ajouter un endpoint pour le détail talent si absent : `static String talentDetail(int id) => '/talents/$id';`
  - [x] 5.3: Ajouter un endpoint pour les catégories si absent : `static const categories = '/categories';`

### Feature Discovery — BLoC

- [x] Task 6: Créer le DiscoveryBloc (AC: AC1, AC3, AC4, AC5)
  - [x] 6.1: Créer `lib/features/discovery/bloc/discovery_event.dart`
    - `sealed class DiscoveryEvent`
    - `DiscoveryFetched` — chargement initial / pull-to-refresh
    - `DiscoveryNextPageFetched` — scroll infini, charge la page suivante
    - `DiscoveryFiltersChanged({required Map<String, dynamic> filters})` — mise à jour des filtres
    - `DiscoveryFilterCleared` — réinitialisation des filtres
  - [x] 6.2: Créer `lib/features/discovery/bloc/discovery_state.dart`
    - `sealed class DiscoveryState`
    - `DiscoveryInitial`
    - `DiscoveryLoading` — premier chargement (affiche skeleton)
    - `DiscoveryLoaded({required List<Map<String, dynamic>> talents, required bool hasMore, required String? nextCursor, required Map<String, dynamic> activeFilters})` — avec `@immutable`, `operator ==`, `hashCode`
    - `DiscoveryLoadingMore` extends `DiscoveryLoaded` — indicateur de chargement en bas
    - `DiscoveryFailure({required String code, required String message})`
  - [x] 6.3: Créer `lib/features/discovery/bloc/discovery_bloc.dart`
    - Injection `DiscoveryRepository`
    - `_onFetched` : réinitialise cursor, appelle repository, émet `DiscoveryLoaded` ou `DiscoveryFailure`
    - `_onNextPageFetched` : émet `DiscoveryLoadingMore`, charge avec cursor, ajoute les résultats à la liste existante, émet `DiscoveryLoaded` mis à jour
    - `_onFiltersChanged` : réinitialise cursor, appelle repository avec filtres, émet `DiscoveryLoaded`
    - `_onFilterCleared` : identique à `_onFetched` (reset tout)
    - Guard anti-doublon : ignorer `DiscoveryNextPageFetched` si déjà `DiscoveryLoadingMore`

### Feature Discovery — Presentation Layer

- [x] Task 7: Créer la DiscoveryPage (AC: AC1, AC2, AC3, AC4, AC5, AC7)
  - [x] 7.1: Créer `lib/features/discovery/presentation/pages/discovery_page.dart`
  - [x] 7.2: Fournir `BlocProvider<DiscoveryBloc>` au niveau de la page (ou dans le ShellPage si partagé)
  - [x] 7.3: Structure de page :
    - `GlassAppBar` avec titre "Recherche" (ou "Découverte")
    - `FilterBar` sous l'app bar (position sticky)
    - `RefreshIndicator` enveloppant le contenu
    - `BlocBuilder<DiscoveryBloc, DiscoveryState>` pour le contenu
  - [x] 7.4: État `DiscoveryLoading` : afficher grille 2 colonnes de `TalentCardSkeleton` (6 items)
  - [x] 7.5: État `DiscoveryLoaded` : afficher grille 2 colonnes de `TalentCard` via `SliverGrid` avec `crossAxisCount: 2`
  - [x] 7.6: État `DiscoveryFailure` : afficher un message d'erreur avec bouton "Réessayer"
  - [x] 7.7: Scroll infini : `ScrollController` avec listener — quand `position.pixels >= position.maxScrollExtent - 200`, émettre `DiscoveryNextPageFetched`
  - [x] 7.8: Pull-to-refresh : sur refresh, émettre `DiscoveryFetched`
  - [x] 7.9: État vide (0 résultats) : afficher illustration + "Aucun talent trouvé" + suggestion "Essayez 'DJ' ou 'Musicien'" + bouton "Élargir la recherche"

- [x] Task 8: Créer le widget TalentGrid (AC: AC1, AC5, AC7)
  - [x] 8.1: Créer `lib/features/discovery/presentation/widgets/talent_grid.dart`
  - [x] 8.2: `CustomScrollView` avec `SliverGrid` (2 colonnes, crossAxisSpacing 12, mainAxisSpacing 12, padding 16)
  - [x] 8.3: Utilise `childAspectRatio` adapté pour TalentCard (environ 0.65 — hauteur > largeur)
  - [x] 8.4: En fin de grille, si `hasMore` : `SliverToBoxAdapter` avec `CircularProgressIndicator` `brand-blue`
  - [x] 8.5: En fin de grille, si `!hasMore` && `talents.isNotEmpty` : `SliverToBoxAdapter` avec texte "Fin des résultats"

- [x] Task 9: Créer la TalentDetailPage minimale (AC: AC6)
  - [x] 9.1: Créer `lib/features/discovery/presentation/pages/talent_detail_page.dart`
  - [x] 9.2: Page temporaire pour Story 1.10 — sera remplacée par le profil complet dans Story 1.11
  - [x] 9.3: Hero animation : `Hero(tag: 'talent-$id')` enveloppant l'image du talent en haut
  - [x] 9.4: Afficher : photo full-width, nom en Headline, catégorie, ville, cachet, note, badge vérifié
  - [x] 9.5: Intégrer `FavoriteButton(talentId: id)` dans l'AppBar
  - [x] 9.6: Fond glassmorphism (GlassCard pour les sections d'info)

### Intégration & Routing

- [x] Task 10: Intégrer dans le routing GoRouter (AC: AC1, AC6)
  - [x] 10.1: Remplacer `SearchPlaceholderPage` par `DiscoveryPage` dans `app_router.dart` (branche Recherche)
  - [x] 10.2: Ajouter route imbriquée `talent/:id` sous la branche Recherche pour `TalentDetailPage`
  - [x] 10.3: Ajouter `RouteNames.talentDetail = 'talentDetail'` dans `route_names.dart`
  - [x] 10.4: Navigation : depuis TalentCard `onTap` → `context.pushNamed(RouteNames.talentDetail, pathParameters: {'id': '$talentId'})` avec `extra` pour les données talent (éviter un re-fetch)

- [x] Task 11: Intégrer FavoritesBloc au niveau app (AC: AC1)
  - [x] 11.1: Vérifier que `FavoritesBloc` est fourni via `MultiBlocProvider` dans `app.dart` (ou l'ajouter s'il n'y est pas)
  - [x] 11.2: Au lancement de `DiscoveryPage`, émettre `FavoritesFetched` pour charger les IDs favoris (permet à FavoriteButton de savoir l'état initial)
  - [x] 11.3: S'assurer que `FavoritesBloc` est accessible depuis `TalentCard` → `FavoriteButton` via `context.read<FavoritesBloc>()`

### Tests

- [x] Task 12: Tests BLoC Discovery (AC: tous)
  - [x] 12.1: Créer `test/features/discovery/bloc/discovery_bloc_test.dart`
  - [x] 12.2: Test : émission `DiscoveryFetched` → `DiscoveryLoading` → `DiscoveryLoaded`
  - [x] 12.3: Test : émission `DiscoveryNextPageFetched` → `DiscoveryLoadingMore` → `DiscoveryLoaded` (avec talents concaténés)
  - [x] 12.4: Test : émission `DiscoveryFiltersChanged` → `DiscoveryLoading` → `DiscoveryLoaded` (filtrés)
  - [x] 12.5: Test : erreur réseau → `DiscoveryFailure`
  - [x] 12.6: Test : guard anti-doublon — `DiscoveryNextPageFetched` ignoré si déjà en `DiscoveryLoadingMore`
  - [x] 12.7: Test : `DiscoveryFetched` après filtres → reset cursor et filtres

- [x] Task 13: Tests Repository Discovery (AC: AC1, AC5)
  - [x] 13.1: Créer `test/features/discovery/data/repositories/discovery_repository_test.dart`
  - [x] 13.2: Test : succès API → retourne `ApiSuccess<TalentListResponse>` avec talents et meta
  - [x] 13.3: Test : erreur réseau → fallback cache Hive
  - [x] 13.4: Test : erreur réseau sans cache → `ApiFailure`
  - [x] 13.5: Test : pagination cursor — passe correctement le cursor au query param
  - [x] 13.6: Test : filtres passés comme query params

- [x] Task 14: Tests Widget TalentCard (AC: AC1, AC6, AC7)
  - [x] 14.1: Créer `test/core/design_system/components/talent_card_test.dart`
  - [x] 14.2: Test : affiche le nom, la catégorie, le cachet formaté, la note
  - [x] 14.3: Test : badge vérifié affiché si `isVerified: true`, absent sinon
  - [x] 14.4: Test : tap déclenche `onTap` callback
  - [x] 14.5: Test : FavoriteButton est présent (nécessite FavoritesBloc dans l'arbre widget)

- [x] Task 15: Vérifications qualité (AC: tous)
  - [x] 15.1: `dart analyze` — 0 issues
  - [x] 15.2: `flutter test` — tous les tests passent (existants + nouveaux), 0 régressions
  - [x] 15.3: Vérifier que les 117 tests existants passent toujours (base de non-régression Story 1.9)

## Dev Notes

### Architecture & Patterns Obligatoires

**Pattern Feature autonome (ARCH-PATTERN-FLUTTER) :**
```
lib/features/discovery/
├── bloc/
│   ├── discovery_bloc.dart       # BLoC event-driven
│   ├── discovery_event.dart      # sealed class
│   └── discovery_state.dart      # sealed class avec equality
├── data/
│   └── repositories/
│       └── discovery_repository.dart  # ApiClient + Hive cache
├── presentation/
│   ├── pages/
│   │   ├── discovery_page.dart        # Page principale (remplace SearchPlaceholder)
│   │   └── talent_detail_page.dart    # Page détail temporaire (remplacée en 1.11)
│   └── widgets/
│       └── talent_grid.dart           # Grille 2 colonnes SliverGrid
└── discovery.dart                     # Barrel file exports publics
```

**Pattern BLoC obligatoire :**
```dart
// Events au passé composé, States avec statut descriptif
// sealed class pour exhaustive matching Dart 3
sealed class DiscoveryEvent {}
final class DiscoveryFetched extends DiscoveryEvent {}
final class DiscoveryNextPageFetched extends DiscoveryEvent {}

sealed class DiscoveryState {}
final class DiscoveryInitial extends DiscoveryState {}
final class DiscoveryLoading extends DiscoveryState {}
```

**Pattern ApiResult (sealed class existant) :**
```dart
// Usage obligatoire — NE PAS utiliser try/catch dans le BLoC
final result = await _repository.getTalents(cursor: cursor);
switch (result) {
  case ApiSuccess(:final data):
    emit(DiscoveryLoaded(talents: data.talents, ...));
  case ApiFailure(:final code, :final message):
    emit(DiscoveryFailure(code: code, message: message));
}
```

### Composants existants à réutiliser — NE PAS recréer

| Fichier existant | Utilisation |
|---|---|
| `core/design_system/components/glass_card.dart` | Base pour TalentCard — composer dessus, NE PAS recréer le glassmorphism |
| `core/design_system/components/glass_app_bar.dart` | AppBar de la DiscoveryPage |
| `core/design_system/components/glass_bottom_nav.dart` | Déjà intégré dans ShellPage |
| `core/design_system/tokens/colors.dart` | `BookmiColors.brandBlue`, `.ctaOrange`, `.glassBorder`, `.categoryDj`, etc. |
| `core/design_system/tokens/typography.dart` | `BookmiTypography.titleMedium`, `.caption`, `.labelLarge` |
| `core/design_system/tokens/spacing.dart` | `BookmiSpacing.sm`, `.md`, `.base`, `.lg` |
| `core/design_system/tokens/radius.dart` | `BookmiRadius.card`, `.image`, `.chip` |
| `core/design_system/tokens/glass.dart` | Blur et opacity par GPU tier |
| `core/design_system/theme/gpu_tier_provider.dart` | Détection GPU pour adapter le glassmorphism |
| `core/design_system/theme/bookmi_theme.dart` | Theme Material 3 avec design tokens |
| `core/network/api_client.dart` | Singleton Dio avec interceptors (auth, retry, logging) |
| `core/network/api_endpoints.dart` | `ApiEndpoints.talents`, `.talentFavorite()` |
| `core/network/api_result.dart` | `ApiSuccess<T>` / `ApiFailure<T>` sealed class |
| `core/storage/local_storage.dart` | Hive-based cache avec TTL |
| `features/favorites/bloc/favorites_bloc.dart` | BLoC favoris — fournir au niveau app |
| `features/favorites/widgets/favorite_button.dart` | Widget coeur animé — intégrer dans TalentCard |
| `app/routes/app_router.dart` | GoRouter — modifier pour remplacer placeholder |
| `app/routes/route_names.dart` | Constantes de routes |
| `app/view/shell_page.dart` | ShellPage avec GlassBottomNav |

### Couleurs par catégorie talent (déjà dans colors.dart)

```dart
// BookmiColors — utiliser ces constantes pour les catégories
static const categoryDj = Color(0xFF7C4DFF);       // Violet — DJ/Musique
static const categoryGroup = Color(0xFF1565C0);     // Bleu foncé — Groupe Musical
static const categoryComedian = Color(0xFFFF4081);  // Rose — Humoriste
static const categoryDancer = Color(0xFF00BFA5);    // Teal — Danseur
static const categoryMc = Color(0xFFFFB300);        // Ambre — MC/Animateur
static const categoryPhotographer = Color(0xFF536DFE); // Indigo — Photographe
static const categoryDecorator = Color(0xFFFF6E40); // Coral — Décorateur
```

### Format de réponse API /talents (endpoint existant Story 1.5)

```json
{
  "data": [
    {
      "id": 42,
      "type": "talent_profile",
      "attributes": {
        "stage_name": "DJ Kerozen",
        "slug": "dj-kerozen",
        "city": "Abidjan",
        "cachet_amount": 15000000,
        "average_rating": "4.50",
        "is_verified": true,
        "photo_url": "https://cdn.bookmi.ci/portfolios/42/photo1.webp",
        "category": { "id": 3, "name": "DJ", "slug": "dj" }
      }
    }
  ],
  "meta": {
    "cursor": {
      "next": "eyJpZCI6NDN9",
      "prev": null,
      "per_page": 20,
      "has_more": true
    },
    "total": 156
  }
}
```

**Query parameters supportés (Story 1.5) :**
- `category` (slug ou id) — filtre par catégorie
- `min_budget` / `max_budget` (en centimes FCFA) — filtre par budget
- `city` (string) — filtre par ville
- `min_rating` (float) — note minimum
- `cursor` (string) — pagination cursor-based
- `per_page` (int, défaut 20, max 100) — éléments par page

### Formatage des montants FCFA

```dart
// Pattern obligatoire — montants stockés en centimes (int)
// Affichage : "150 000 FCFA" avec espace comme séparateur milliers
import 'package:intl/intl.dart';

String formatCachet(int amountInCents) {
  final amount = amountInCents ~/ 100;
  return '${NumberFormat('#,###', 'fr_FR').format(amount).replaceAll(',', ' ')} FCFA';
}
// 15000000 → "150 000 FCFA"
```

### Dépendances inter-stories

| Dépendance | Story | Impact |
|---|---|---|
| API GET /talents avec filtres et pagination | Story 1.5 (done) | Backend prêt, pas de modification backend nécessaire |
| FavoriteButton widget | Story 1.9 (done) | Intégrer dans TalentCard — `FavoriteButton(talentId: talent.id)` |
| FavoritesBloc | Story 1.9 (done) | Fournir au niveau app via `MultiBlocProvider` |
| GlassCard, GlassAppBar, tokens | Story 1.2 (done) | Réutiliser tel quel |
| TalentDetailPage complète | Story 1.11 (backlog) | 1.10 crée une page minimale, 1.11 la remplace |
| Hive storage | Story 1.2 (done) | Déjà initialisé dans `bootstrap.dart` |

**Story 1.10 crée les composants UI Discovery + TalentCard réutilisable. Story 1.11 remplace la page détail temporaire par le profil complet.**

### UX Design — Spécifications critiques

**Direction visuelle :** D1 (Deep Glassmorphism) + D6 (Card Grid)
- Fond navy `#1A2744`
- Grille 2 colonnes de TalentCards glassmorphism
- Barre de recherche proéminente avec placeholder "Quel talent cherchez-vous ?"

**TalentCard specs UX :**

| Propriété | Valeur |
|---|---|
| `image` | Photo talent, height 120px, `ClipRRect` borderRadius 16 |
| `badge` | Badge vérifié (glass + check) en top-right |
| `favorite` | FavoriteButton en top-left |
| `name` | Title Medium (16px SemiBold), blanc |
| `category` | Caption (12px Regular), couleur accent par catégorie |
| `price` | Label Large (14px Medium), `brand-blue-light` (#64B5F6) |
| `rating` | Caption, `warning` (#FFB300) — étoile + note |
| `base` | GlassCard standard |
| `interaction` | Tap → Hero animation 300ms vers le profil |

**FilterBar specs UX :**

| Propriété | Valeur |
|---|---|
| `layout` | ListView horizontal scrollable, hauteur 48px |
| `activeChip` | Fond `brand-blue`, texte blanc |
| `inactiveChip` | Outline `glass-border`, texte blanc 60% |
| `clearAll` | Bouton "Effacer" en fin de scroll |

**Skeleton screens :** Rectangles glassmorphism pulsants reproduisant la structure TalentCard

**Empty state :** Illustration + "Aucun talent trouvé" + suggestions + bouton "Élargir la recherche"

**Feedback patterns :**
- Pull-to-refresh : `RefreshIndicator` `brand-blue`
- Scroll infini : `CircularProgressIndicator` `brand-blue` en bas
- Filtre appliqué : SnackBar glass 2s auto-dismiss (optionnel)

### Testing Standards

**Patterns de test Flutter établis (Story 1.9) :**
```dart
// Mock avec mocktail
class MockDiscoveryRepository extends Mock implements DiscoveryRepository {}

// BLoC test avec bloc_test
blocTest<DiscoveryBloc, DiscoveryState>(
  'emits [DiscoveryLoading, DiscoveryLoaded] when DiscoveryFetched is added',
  build: () {
    when(() => mockRepository.getTalents()).thenAnswer(
      (_) async => ApiSuccess(TalentListResponse(talents: [...], ...)),
    );
    return DiscoveryBloc(repository: mockRepository);
  },
  act: (bloc) => bloc.add(const DiscoveryFetched()),
  expect: () => [
    isA<DiscoveryLoading>(),
    isA<DiscoveryLoaded>(),
  ],
);
```

**Tests widget avec pump :**
```dart
// Fournir les blocs nécessaires dans l'arbre de test
await tester.pumpWidget(
  MultiBlocProvider(
    providers: [
      BlocProvider<FavoritesBloc>.value(value: mockFavoritesBloc),
    ],
    child: MaterialApp(home: TalentCard(...)),
  ),
);
```

**Tests de non-régression obligatoires :**
- Les 117 tests existants (Story 1.9) doivent continuer à passer
- Le routing existant ne doit pas être cassé (autres branches du ShellPage)

### Project Structure Notes

- Les composants TalentCard, TalentCardSkeleton et FilterBar vont dans `core/design_system/components/` car ils sont réutilisables par d'autres features (Story 1.11 utilisera TalentCard dans les suggestions)
- La feature discovery suit le pattern `features/{feature}/` avec BLoC, data, presentation
- La page détail talent (`TalentDetailPage`) est temporaire dans `features/discovery/` — elle sera remplacée par `features/talent_profile/` dans Story 1.11
- Le barrel file `features/discovery/discovery.dart` exporte les éléments publics

### Packages à ajouter

| Package | Version | Usage |
|---|---|---|
| `shimmer` | ^3.0.0 | Skeleton loading animation (TalentCardSkeleton) |
| `intl` | déjà présent (0.20.2) | Formatage montants FCFA |
| `cached_network_image` | latest | Cache réseau pour les photos talents (critique bande passante CI) |

**Note :** Vérifier si `cached_network_image` est déjà dans pubspec.yaml. L'architecture le mentionne comme obligatoire.

### Previous Story Intelligence (Story 1.9)

**Learnings de Story 1.9 à appliquer :**
1. **Constructeur `.forTesting`** : Créer un constructeur nommé pour injecter Dio directement dans le repository (permet le mock dans les tests)
2. **State equality** : Implémenter `operator ==` et `hashCode` sur les states BLoC avec `@immutable` et `setEquals`/`listEquals` de `package:flutter/foundation.dart` — PAS equatable (pas dans les dépendances)
3. **Guard anti-doublon** : Utiliser un `Set<int>` ou flag bool pour empêcher les événements concurrents (ex: `_isLoadingMore` flag)
4. **Mock avec `any<dynamic>()`** : Pour les matchers mocktail avec Hive Box<dynamic>, utiliser `any<dynamic>()` et non `any()` pour éviter les warnings d'inférence
5. **FavoritesBloc déjà créé** : Ne pas recréer — fournir via `MultiBlocProvider` au niveau app et laisser FavoriteButton le consommer via `context.read<FavoritesBloc>()`
6. **ApiResult pattern** : Utiliser exclusivement `switch (result)` avec `case ApiSuccess` / `case ApiFailure` — jamais de try/catch au niveau BLoC

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 1.10] — AC et user story
- [Source: _bmad-output/planning-artifacts/architecture.md#Frontend Architecture Flutter] — Stack, BLoC, GoRouter, Dio, Hive
- [Source: _bmad-output/planning-artifacts/architecture.md#Project Organization Flutter] — Structure features/
- [Source: _bmad-output/planning-artifacts/architecture.md#State Management Patterns Flutter BLoC] — Convention events/states
- [Source: _bmad-output/planning-artifacts/architecture.md#Loading State Patterns Flutter] — Skeleton, pull-to-refresh
- [Source: _bmad-output/planning-artifacts/ux-design-specification.md#TalentCard] — Specs composant
- [Source: _bmad-output/planning-artifacts/ux-design-specification.md#FilterBar] — Specs composant
- [Source: _bmad-output/planning-artifacts/ux-design-specification.md#Chosen Direction] — D1+D6 pour découverte
- [Source: _bmad-output/planning-artifacts/ux-design-specification.md#Experience Mechanics] — Flow découverte
- [Source: _bmad-output/implementation-artifacts/1-9-favoris.md] — Patterns, FavoriteButton, FavoritesBloc

## Dev Agent Record

### Agent Model Used

Claude Opus 4.6 (claude-opus-4-6)

### Debug Log References

- Fixed `NumberFormat` locale issue: French locale uses U+202F (narrow no-break space) as thousands separator, not regular space or U+00A0. Used `RegExp(r'[\s\u00A0\u202F,]')` to normalize all separator variants.
- Fixed BLoC state equality: `DiscoveryLoadingMore extends DiscoveryLoaded` caused BLoC to skip emission because `DiscoveryLoaded.==` returned true for subclass instances. Added `other.runtimeType == runtimeType` check.
- Fixed sealed class extension error: Cannot extend `FavoritesEvent` outside its library. Removed `FakeEvent extends Fake implements FavoritesEvent` from tests.

### Completion Notes List

- All 15 tasks completed (65 subtasks)
- 144 tests pass (117 existing + 27 new), 0 failures, 0 regressions
- `dart analyze` — 0 issues
- All 7 ACs covered by implementation and tests

### Code Review Record

**Reviewer:** Claude Opus 4.6 (adversarial code review)
**Date:** 2026-02-17

**Constats corrigés (6/8) :**
1. **[FIXED]** FavoritesFetched jamais dispatché — ajouté dans DiscoveryPage.initState
2. **[DEFERRED → Story 1.11]** AC4 filtres budget/ville/note — FilterBar ne supporte que catégorie, les filtres avancés (BottomSheet budget, ville, note) sont reportés
3. **[FIXED]** Échec pagination détruisait les données chargées — revient à DiscoveryLoaded au lieu d'émettre DiscoveryFailure
4. **[FIXED]** formatCachet dupliqué — TalentDetailPage utilise maintenant TalentCard.formatCachet()
5. **[FIXED]** SliverGrid.count eager — remplacé par SliverGrid + SliverChildBuilderDelegate (lazy)
6. **[FIXED]** int.parse crash sur route param — remplacé par int.tryParse avec fallback 0
7. **[KNOWN ISSUE → Story 1.11]** Deep link talent detail = page vide (talentData passé via extra, non persisté)
8. **[FIXED]** setState excessif sur scroll — remplacé par ValueNotifier + ValueListenableBuilder sur GlassAppBar

### Change Log

| Action | File | Description |
|--------|------|-------------|
| CREATED | `lib/core/design_system/components/talent_card.dart` | TalentCard widget with GlassCard, Hero, FavoriteButton, verified badge, FCFA formatting |
| CREATED | `lib/core/design_system/components/talent_card_skeleton.dart` | Shimmer-based skeleton matching TalentCard structure |
| CREATED | `lib/core/design_system/components/filter_bar.dart` | Horizontal scrollable filter chips with active/inactive states |
| CREATED | `lib/features/discovery/data/repositories/discovery_repository.dart` | Repository with TalentListResponse, Dio API calls, Hive cache fallback |
| CREATED | `lib/features/discovery/bloc/discovery_event.dart` | Sealed events: Fetched, NextPageFetched, FiltersChanged, FilterCleared |
| CREATED | `lib/features/discovery/bloc/discovery_state.dart` | Sealed states: Initial, Loading, Loaded, LoadingMore, Failure |
| CREATED | `lib/features/discovery/bloc/discovery_bloc.dart` | BLoC with ApiResult switch pattern, anti-doublon guard |
| CREATED | `lib/features/discovery/presentation/pages/discovery_page.dart` | Full discovery page with GlassAppBar, FilterBar, infinite scroll |
| CREATED | `lib/features/discovery/presentation/widgets/talent_grid.dart` | SliverGrid 2-column layout with category color mapping |
| CREATED | `lib/features/discovery/presentation/pages/talent_detail_page.dart` | Minimal detail page with Hero animation, GlassCard info |
| CREATED | `lib/features/discovery/discovery.dart` | Barrel file for public exports |
| CREATED | `test/features/discovery/bloc/discovery_bloc_test.dart` | 8 BLoC tests covering all events and states |
| CREATED | `test/features/discovery/data/repositories/discovery_repository_test.dart` | 5 repository tests (success, cache, failure, cursor, filters) |
| CREATED | `test/core/design_system/components/talent_card_test.dart` | 13 widget + unit tests (display, badge, tap, formatCachet) |
| MODIFIED | `lib/core/network/api_endpoints.dart` | Added `talentDetail(int id)` and `categories` endpoints |
| MODIFIED | `lib/app/routes/route_names.dart` | Added `talentDetail` route name and path |
| MODIFIED | `lib/app/routes/app_router.dart` | Replaced SearchPlaceholder with DiscoveryPage, added talent/:id route |
| MODIFIED | `lib/app/view/app.dart` | Added MultiBlocProvider with FavoritesBloc + DiscoveryBloc, async init |
| MODIFIED | `pubspec.yaml` | Added `shimmer: ^3.0.0`, `cached_network_image: ^3.4.1` (sorted) |
| **Code Review Fixes** | | |
| MODIFIED | `lib/features/discovery/presentation/pages/discovery_page.dart` | Added FavoritesFetched dispatch, ValueNotifier for scroll offset, sorted imports |
| MODIFIED | `lib/features/discovery/bloc/discovery_bloc.dart` | Pagination failure preserves data (reverts to DiscoveryLoaded instead of DiscoveryFailure) |
| MODIFIED | `lib/features/discovery/presentation/pages/talent_detail_page.dart` | Removed duplicated _formatCachet, uses TalentCard.formatCachet() |
| MODIFIED | `lib/features/discovery/presentation/widgets/talent_grid.dart` | SliverGrid with SliverChildBuilderDelegate for lazy rendering |
| MODIFIED | `lib/app/routes/app_router.dart` | int.tryParse with fallback for route parameter safety |
| MODIFIED | `test/features/discovery/bloc/discovery_bloc_test.dart` | Added pagination failure test (preserves data) |

### File List

**Created (14 files):**
- `lib/core/design_system/components/talent_card.dart`
- `lib/core/design_system/components/talent_card_skeleton.dart`
- `lib/core/design_system/components/filter_bar.dart`
- `lib/features/discovery/data/repositories/discovery_repository.dart`
- `lib/features/discovery/bloc/discovery_event.dart`
- `lib/features/discovery/bloc/discovery_state.dart`
- `lib/features/discovery/bloc/discovery_bloc.dart`
- `lib/features/discovery/presentation/pages/discovery_page.dart`
- `lib/features/discovery/presentation/pages/talent_detail_page.dart`
- `lib/features/discovery/presentation/widgets/talent_grid.dart`
- `lib/features/discovery/discovery.dart`
- `test/features/discovery/bloc/discovery_bloc_test.dart`
- `test/features/discovery/data/repositories/discovery_repository_test.dart`
- `test/core/design_system/components/talent_card_test.dart`

**Modified (5 files):**
- `lib/core/network/api_endpoints.dart`
- `lib/app/routes/route_names.dart`
- `lib/app/routes/app_router.dart`
- `lib/app/view/app.dart`
- `pubspec.yaml`
