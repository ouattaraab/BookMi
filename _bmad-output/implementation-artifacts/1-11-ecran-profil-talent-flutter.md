# Story 1.11: Écran profil talent Flutter (mobile)

Status: done

## Story

As a client,
I want voir le profil complet d'un talent dans l'app avec un design glassmorphism immersif,
So that je puisse explorer son portfolio et ses offres avant de réserver.

**Functional Requirements:** FR14, FR15, FR16, FR23
**Non-Functional Requirements:** NFR1 (pages < 3s sur 3G), NFR5 (Flutter < 3s démarrage 2 Go RAM), NFR36-NFR40 (WCAG 2.1 AA, écrans 4,7"→6,7"), UX-NAV-2 (deep linking), UX-NAV-3 (Hero animation), UX-ACCESS-3 (mode sombre)

## Acceptance Criteria (BDD)

**AC1 — Header immersif avec photo et badge vérifié**
**Given** le client a tapé sur une TalentCard dans la grille de découverte
**When** l'écran profil s'affiche
**Then** un header immersif occupe ~60% de l'écran avec :
- Photo/image du talent en full-bleed avec Hero animation depuis la TalentCard
- Badge vérifié en glassmorphism (coin supérieur droit) flottant sur la photo
- Nom de scène, catégorie (avec couleur accent), ville en overlay glass en bas de la photo
- Score de fiabilité (reliability_score) affiché visuellement
- FavoriteButton interactif (Story 1.9)
**And** le scroll révèle progressivement les sections sous le header

**AC2 — Bio et informations détaillées**
**Given** l'écran profil est affiché
**When** l'utilisateur scrolle sous le header
**Then** la section bio s'affiche dans une GlassCard avec :
- Bio complète du talent (texte multi-lignes, gestion de l'état vide)
- Liens réseaux sociaux (social_links) si disponibles (icônes cliquables)
- Niveau talent (talent_level) avec badge visuel
- Membre depuis (created_at) formaté en français

**AC3 — Portfolio (grille photos/vidéos)**
**Given** l'écran profil est affiché
**When** l'utilisateur atteint la section portfolio
**Then** une grille de médias s'affiche (photos et vidéos du portfolio)
**And** si le portfolio est vide (API retourne `portfolio_items: []`), afficher un état vide élégant : "Pas encore de portfolio"
**Note :** L'API retourne actuellement `portfolio_items: []` (stub). Implémenter le widget et l'état vide. Le remplissage des données viendra dans un futur epic.

**AC4 — Packages de prestation (cartes comparatives)**
**Given** l'écran profil est affiché
**When** l'utilisateur atteint la section packages
**Then** les packages actifs du talent s'affichent sous forme de cartes comparatives (max 3-4 cartes)
**And** chaque carte affiche : nom, description, prix (formaté FCFA), durée, liste des inclusions
**And** le type de package (essentiel/standard/premium) détermine le style visuel
**And** le package recommandé (standard ou premium) est mis en avant avec une bordure orange (#FF6B35)
**And** si aucun package n'est disponible, afficher un état vide

**AC5 — Avis récents avec notes**
**Given** l'écran profil est affiché
**When** l'utilisateur atteint la section avis
**Then** les avis récents s'affichent avec notes étoilées
**And** le nombre total d'avis (reviews_count) et la note moyenne (average_rating) sont affichés en entête de section
**And** si les avis sont vides (API retourne `recent_reviews: []`), afficher un état vide : "Pas encore d'avis"
**Note :** L'API retourne actuellement `recent_reviews: []` et `reviews_count: 0` (stub). Implémenter le widget et l'état vide.

**AC6 — Bouton CTA de réservation**
**Given** l'écran profil est affiché
**When** l'utilisateur voit le profil
**Then** un bouton CTA "Réserver" est visible en permanence en bas de l'écran (sticky bottom)
**And** le bouton utilise le gradient orange CTA (#FF6B35 → #FF8C5E)
**And** le bouton affiche le cachet minimum du talent (formaté FCFA)
**And** le tap sur le bouton est un no-op pour cette story (la réservation est implémentée dans Epic 3)

**AC7 — Talents similaires**
**Given** l'écran profil est affiché
**When** l'utilisateur scrolle jusqu'en bas
**Then** une section "Talents similaires" affiche des TalentCards horizontalement scrollables
**And** les données viennent de `meta.similar_talents` de la réponse API
**And** le tap sur un talent similaire navigue vers son profil (même écran, nouvelles données)

**AC8 — Deep linking**
**Given** un lien profil talent est partagé (ex: `bookmi.ci/talent/{slug}`)
**When** l'utilisateur ouvre le lien (app installée ou non)
**Then** l'écran profil du talent s'affiche avec les données chargées depuis l'API
**And** la navigation via slug (pas id) fonctionne correctement
**Note :** Implémentation GoRouter avec slug. L'API backend utilise `GET /api/v1/talents/{slug}`.

**AC9 — Mode sombre et accessibilité**
**Given** l'utilisateur a activé le mode sombre sur son appareil
**When** il consulte le profil talent
**Then** le design s'adapte au mode sombre (les glass cards et le fond s'ajustent)
**And** les zones de tap respectent 48x48px minimum (WCAG 2.1 AA)
**And** les contrastes sont suffisants (≥ 4.5:1 pour le texte, ≥ 3:1 pour les éléments UI)

## Tasks / Subtasks

### Feature Talent Profile — Data Layer (P0)

- [x] Task 1: Créer le TalentProfileRepository (AC: AC1-AC7)
  - [x] 1.1: Créer `lib/features/talent_profile/data/repositories/talent_profile_repository.dart`
  - [x] 1.2: Constructeur avec `ApiClient` + `LocalStorage` (core/storage) — même pattern que `DiscoveryRepository`
  - [x] 1.3: Constructeur `.forTesting` avec `Dio` + `LocalStorage` direct (pattern Story 1.9/1.10)
  - [x] 1.4: `Future<ApiResult<TalentProfileResponse>> getTalentBySlug(String slug)` — appel `GET /api/v1/talents/{slug}`
  - [x] 1.5: Parse la réponse : `TalentProfileResponse` contient `Map<String, dynamic> profile` + `List<Map<String, dynamic>> similarTalents`
  - [x] 1.6: Le `profile` inclut : `stage_name`, `slug`, `bio`, `city`, `cachet_amount`, `average_rating`, `is_verified`, `talent_level`, `profile_completion_percentage`, `social_links`, `reliability_score`, `reviews_count`, `portfolio_items`, `service_packages`, `recent_reviews`, `created_at`, `category`, `subcategory`
  - [x] 1.7: Les `similarTalents` viennent de `meta.similar_talents` (format compact `TalentResource`)
  - [x] 1.8: Fallback cache Hive : si erreur réseau, retourner les données cachées (clé `talent_profile_{slug}`)
  - [x] 1.9: Cacher le résultat API dans Hive box après chaque réponse réussie

- [x] Task 2: Mettre à jour les endpoints API (AC: AC1, AC8)
  - [x] 2.1: Modifier `api_endpoints.dart` : remplacer `talentDetail(int id)` par `talentDetail(String slug) => '/talents/$slug'`
  - [x] 2.2: L'endpoint existant retournait par id — le backend utilise le slug. **IMPORTANT :** vérifier que la route GoRouter utilise aussi le slug

### Feature Talent Profile — BLoC (P0)

- [x] Task 3: Créer le TalentProfileBloc (AC: AC1-AC7)
  - [x] 3.1: Créer `lib/features/talent_profile/bloc/talent_profile_event.dart`
    - `sealed class TalentProfileEvent`
    - `TalentProfileFetched({required String slug})` — chargement initial par slug
    - `TalentProfileRefreshed` — pull-to-refresh (force re-fetch)
  - [x] 3.2: Créer `lib/features/talent_profile/bloc/talent_profile_state.dart`
    - `sealed class TalentProfileState`
    - `TalentProfileInitial`
    - `TalentProfileLoading`
    - `TalentProfileLoaded({required Map<String, dynamic> profile, required List<Map<String, dynamic>> similarTalents})` — avec `operator ==`, `hashCode`
    - `TalentProfileFailure({required String code, required String message})`
  - [x] 3.3: Créer `lib/features/talent_profile/bloc/talent_profile_bloc.dart`
    - Injection `TalentProfileRepository`
    - `_onFetched` : appelle repository `getTalentBySlug(slug)`, émet `TalentProfileLoaded` ou `TalentProfileFailure`
    - `_onRefreshed` : force re-fetch en ignorant le cache
    - Pattern `switch (result)` avec `ApiSuccess` / `ApiFailure` — jamais de try/catch

### Design System — Composants Profil (P0)

- [x] Task 4: Créer le widget ServicePackageCard (AC: AC4)
  - [x] 4.1: Créer `lib/core/design_system/components/service_package_card.dart`
  - [x] 4.2: Props : `name`, `description`, `cachetAmount`, `durationMinutes`, `inclusions` (List<String>?), `type` (String: essentiel/standard/premium/micro), `isRecommended` (bool)
  - [x] 4.3: Design : GlassCard avec nom en Title Medium, prix en Display Medium `brand-blue-light`, durée en Caption, inclusions en liste à puces
  - [x] 4.4: Package recommandé : bordure orange (#FF6B35) + badge "Recommandé" en haut à droite
  - [x] 4.5: Mapping type → style visuel :
    - `essentiel` : bordure glass-border standard
    - `standard` : bordure brand-blue + badge "Populaire"
    - `premium` : bordure cta-orange + badge "Recommandé" + fond glass légèrement plus opaque
    - `micro` : taille plus compacte, sans badge
  - [x] 4.6: Formater le prix avec `TalentCard.formatCachet(cachetAmount)` — NE PAS dupliquer
  - [x] 4.7: Formater la durée : `durationMinutes` → "2h" ou "1h30" (ex: 120 → "2h", 90 → "1h30")

- [x] Task 5: Créer le widget PortfolioGallery (AC: AC3)
  - [x] 5.1: Créer `lib/features/talent_profile/presentation/widgets/portfolio_gallery.dart`
  - [x] 5.2: Props : `items` (List<Map<String, dynamic>>) — liste de portfolio items
  - [x] 5.3: Grille 3 colonnes (style Instagram) de thumbnails avec `CachedNetworkImage`
  - [x] 5.4: Tap sur un item → ouverture plein écran (TODO: PageView simple pour cette story)
  - [x] 5.5: État vide : icône galerie + "Pas encore de portfolio" en texte glass + message encourageant
  - [x] 5.6: Note : l'API retourne `[]` actuellement — coder l'UI mais elle affichera l'état vide

- [x] Task 6: Créer le widget ReviewsSection (AC: AC5)
  - [x] 6.1: Créer `lib/features/talent_profile/presentation/widgets/reviews_section.dart`
  - [x] 6.2: Props : `reviews` (List<Map<String, dynamic>>), `reviewsCount` (int), `averageRating` (double)
  - [x] 6.3: En-tête : note moyenne (étoiles + chiffre) + nombre total d'avis
  - [x] 6.4: Liste de cartes d'avis : nom du reviewer, date, note étoilée, commentaire
  - [x] 6.5: État vide : icône avis + "Pas encore d'avis — Soyez le premier !"
  - [x] 6.6: Note : l'API retourne `[]` et `reviews_count: 0` actuellement — coder l'UI mais elle affichera l'état vide

- [x] Task 7: Créer le widget SimilarTalentsRow (AC: AC7)
  - [x] 7.1: Créer `lib/features/talent_profile/presentation/widgets/similar_talents_row.dart`
  - [x] 7.2: Props : `talents` (List<Map<String, dynamic>>), `onTalentTap` (callback)
  - [x] 7.3: ListView horizontal de TalentCards compactes (réutiliser `TalentCard` existant en mode compact)
  - [x] 7.4: En-tête "Talents similaires" en Title Medium blanc
  - [x] 7.5: Si la liste est vide, ne pas afficher la section

### Feature Talent Profile — Presentation Layer (P0)

- [x] Task 8: Créer la TalentProfilePage (AC: AC1-AC9)
  - [x] 8.1: Créer `lib/features/talent_profile/presentation/pages/talent_profile_page.dart`
  - [x] 8.2: **REMPLACE** `features/discovery/presentation/pages/talent_detail_page.dart` — cette page temporaire devient obsolète
  - [x] 8.3: Structure de page immersive (D5 + D1) :
    - `CustomScrollView` avec `SliverAppBar` expandable (flexibleSpace = photo hero 60% hauteur)
    - `SliverToBoxAdapter` pour chaque section en GlassCard
    - Fond `BookmiColors.gradientHero`
    - Bouton CTA sticky en `bottomNavigationBar` (ou via `Stack` + `Positioned`)
  - [x] 8.4: Section header hero :
    - `SliverAppBar` avec `expandedHeight: MediaQuery.of(context).size.height * 0.6`
    - `flexibleSpace: FlexibleSpaceBar` avec photo `CachedNetworkImage` en background
    - `Hero(tag: 'talent-$talentId')` wrapping l'image pour la transition depuis TalentCard
    - Badge vérifié flottant en haut à droite (Positioned)
    - Overlay gradient en bas de la photo (pour lisibilité du texte superposé)
    - Nom de scène, catégorie, ville en overlay bas
  - [x] 8.5: Section bio (GlassCard) :
    - Bio complète ou "Ce talent n'a pas encore renseigné sa bio"
    - Liens sociaux (Row d'IconButton si social_links non null)
    - Niveau talent : chip glass avec label (nouveau/confirmé/populaire/élite)
    - Membre depuis : "Membre depuis février 2026"
  - [x] 8.6: Section portfolio (GlassCard) :
    - `PortfolioGallery(items: profile['portfolio_items'])` — affichera l'état vide
  - [x] 8.7: Section packages (GlassCard) :
    - Titre "Packages" en Title Medium
    - Column de `ServicePackageCard` pour chaque package
    - Marquer le package standard ou premium comme recommandé
  - [x] 8.8: Section avis (GlassCard) :
    - `ReviewsSection(reviews: ..., reviewsCount: ..., averageRating: ...)`
  - [x] 8.9: Section talents similaires :
    - `SimilarTalentsRow(talents: similarTalents, onTalentTap: ...)` — visible uniquement si la liste n'est pas vide
  - [x] 8.10: Bouton CTA sticky :
    - Container en `bottomNavigationBar` ou `Positioned(bottom: 0)`
    - Gradient `BookmiColors.gradientCta` (#FF6B35 → #FF8C5E)
    - Texte "Réserver · {cachet formaté}"
    - Border radius 16px, marge horizontale 16px, marge basse safe area
    - `onTap` : no-op (SnackBar "Bientôt disponible" ou simplement rien)
  - [x] 8.11: Pull-to-refresh : sur refresh, émettre `TalentProfileRefreshed`
  - [x] 8.12: État loading : skeleton sections (SliverAppBar collapsed + skeleton GlassCards)
  - [x] 8.13: État erreur : message avec bouton "Réessayer"

### Intégration & Routing (P0)

- [x] Task 9: Mettre à jour le routing GoRouter (AC: AC1, AC8)
  - [x] 9.1: Modifier la route `talent/:id` en `talent/:slug` dans `app_router.dart`
  - [x] 9.2: Mettre à jour `route_names.dart` : `RoutePaths.talentDetail = 'talent/:slug'`
  - [x] 9.3: Importer `TalentProfilePage` au lieu de `TalentDetailPage` dans le builder
  - [x] 9.4: Le builder récupère `state.pathParameters['slug']` + `state.extra` (données talent optionnelles pour affichage instantané)
  - [x] 9.5: Fournir `BlocProvider<TalentProfileBloc>` au niveau de la route (créé dans le builder)
  - [x] 9.6: Mettre à jour `DiscoveryPage._onTalentTap` pour passer le `slug` au lieu de l'`id` dans pathParameters
  - [x] 9.7: Supprimer l'import de `TalentDetailPage` de discovery (nettoyage)

- [x] Task 10: Intégrer TalentProfileBloc dans l'app (AC: AC1)
  - [x] 10.1: Le TalentProfileBloc est créé au niveau de la route (pas au niveau app) car il est spécifique à un profil
  - [x] 10.2: Ajouter les dépendances (TalentProfileRepository) dans `_AppDependencies` dans `app.dart`
  - [x] 10.3: Passer le repository au router builder pour qu'il puisse créer le BLoC

- [x] Task 11: Créer le barrel file (AC: tous)
  - [x] 11.1: Créer `lib/features/talent_profile/talent_profile.dart` — exporter les éléments publics
  - [x] 11.2: Mettre à jour le barrel file `features/discovery/discovery.dart` pour retirer les exports liés à la page détail temporaire

### Tests (P1)

- [x] Task 12: Tests BLoC TalentProfile (AC: AC1-AC7)
  - [x] 12.1: Créer `test/features/talent_profile/bloc/talent_profile_bloc_test.dart`
  - [x] 12.2: Test : `TalentProfileFetched(slug)` → `TalentProfileLoading` → `TalentProfileLoaded`
  - [x] 12.3: Test : erreur réseau → `TalentProfileFailure`
  - [x] 12.4: Test : `TalentProfileRefreshed` → `TalentProfileLoading` → `TalentProfileLoaded` (force re-fetch)
  - [x] 12.5: Test : données retournées contiennent `profile` et `similarTalents`

- [x] Task 13: Tests Repository TalentProfile (AC: AC1, AC8)
  - [x] 13.1: Créer `test/features/talent_profile/data/repositories/talent_profile_repository_test.dart`
  - [x] 13.2: Test : succès API → retourne `ApiSuccess<TalentProfileResponse>` avec profile et similarTalents
  - [x] 13.3: Test : erreur réseau → fallback cache Hive
  - [x] 13.4: Test : erreur réseau sans cache → `ApiFailure`
  - [x] 13.5: Test : slug passé correctement dans l'URL (/talents/{slug})

- [x] Task 14: Tests Widget ServicePackageCard (AC: AC4)
  - [x] 14.1: Créer `test/core/design_system/components/service_package_card_test.dart`
  - [x] 14.2: Test : affiche le nom, la description, le prix formaté, la durée
  - [x] 14.3: Test : badge "Recommandé" affiché quand `isRecommended: true`
  - [x] 14.4: Test : inclusions affichées sous forme de liste à puces
  - [x] 14.5: Test : bordure orange quand type = premium

- [x] Task 15: Tests Widget ReviewsSection et PortfolioGallery (AC: AC3, AC5)
  - [x] 15.1: Créer `test/features/talent_profile/presentation/widgets/reviews_section_test.dart`
  - [x] 15.2: Test : état vide → affiche "Pas encore d'avis"
  - [x] 15.3: Test : affiche note moyenne et nombre d'avis en en-tête
  - [x] 15.4: Créer `test/features/talent_profile/presentation/widgets/portfolio_gallery_test.dart`
  - [x] 15.5: Test : état vide → affiche "Pas encore de portfolio"

- [x] Task 16: Vérifications qualité (AC: tous)
  - [x] 16.1: `dart analyze` — 0 issues
  - [x] 16.2: `flutter test` — 175/175 tests passent (144 existants + 31 nouveaux), 0 régressions
  - [x] 16.3: Vérifier que les 144 tests existants passent toujours (base de non-régression Story 1.10)

## Dev Notes

### Architecture & Patterns Obligatoires

**Pattern Feature autonome (ARCH-PATTERN-FLUTTER) :**
```
lib/features/talent_profile/
├── bloc/
│   ├── talent_profile_bloc.dart       # BLoC event-driven
│   ├── talent_profile_event.dart      # sealed class
│   └── talent_profile_state.dart      # sealed class avec equality
├── data/
│   └── repositories/
│       └── talent_profile_repository.dart  # ApiClient + Hive cache
├── presentation/
│   ├── pages/
│   │   └── talent_profile_page.dart       # Page profil immersive (remplace TalentDetailPage)
│   └── widgets/
│       ├── portfolio_gallery.dart          # Grille 3 colonnes portfolio
│       ├── reviews_section.dart            # Avis avec notes étoilées
│       └── similar_talents_row.dart        # ListView horizontal TalentCards
└── talent_profile.dart                     # Barrel file exports publics
```

**Pattern BLoC obligatoire :**
```dart
// Events au passé composé, States avec statut descriptif
// sealed class pour exhaustive matching Dart 3
sealed class TalentProfileEvent {}
final class TalentProfileFetched extends TalentProfileEvent {
  const TalentProfileFetched({required this.slug});
  final String slug;
}
final class TalentProfileRefreshed extends TalentProfileEvent {
  const TalentProfileRefreshed();
}

sealed class TalentProfileState {}
final class TalentProfileInitial extends TalentProfileState {}
final class TalentProfileLoading extends TalentProfileState {}
final class TalentProfileLoaded extends TalentProfileState {
  const TalentProfileLoaded({
    required this.profile,
    required this.similarTalents,
  });
  final Map<String, dynamic> profile;
  final List<Map<String, dynamic>> similarTalents;
  // + operator == et hashCode
}
```

**Pattern ApiResult (sealed class existant) :**
```dart
// Usage obligatoire — NE PAS utiliser try/catch dans le BLoC
final result = await _repository.getTalentBySlug(slug);
switch (result) {
  case ApiSuccess(:final data):
    emit(TalentProfileLoaded(
      profile: data.profile,
      similarTalents: data.similarTalents,
    ));
  case ApiFailure(:final code, :final message):
    emit(TalentProfileFailure(code: code, message: message));
}
```

### Composants existants à réutiliser — NE PAS recréer

| Fichier existant | Utilisation |
|---|---|
| `core/design_system/components/glass_card.dart` | Base pour toutes les sections info — composer dessus, NE PAS recréer le glassmorphism |
| `core/design_system/components/glass_app_bar.dart` | **Non utilisé ici** — cette page utilise `SliverAppBar` avec expandedHeight pour l'effet immersif |
| `core/design_system/components/talent_card.dart` | Réutiliser dans `SimilarTalentsRow` pour les cartes de talents similaires |
| `core/design_system/components/talent_card.dart` (`formatCachet`) | Méthode statique `TalentCard.formatCachet(int)` pour formater les montants FCFA — NE PAS dupliquer |
| `core/design_system/tokens/colors.dart` | `BookmiColors.brandBlue`, `.ctaOrange`, `.gradientCta`, `.gradientHero`, `.glassBorder`, catégories |
| `core/design_system/tokens/typography.dart` | `BookmiTypography.textTheme` — police Nunito, tailles standardisées |
| `core/design_system/tokens/spacing.dart` | `BookmiSpacing.sm`, `.md`, `.base`, `.lg`, `.xl` |
| `core/design_system/tokens/radius.dart` | `BookmiRadius.card` (24), `.button` (16), `.image` (16), `.chip` (999) |
| `core/design_system/tokens/glass.dart` | `BookmiGlass.blurFull`, `.blurLight`, `.blurNone` par GPU tier |
| `core/design_system/theme/gpu_tier_provider.dart` | Détection GPU pour adapter le glassmorphism |
| `core/design_system/theme/bookmi_theme.dart` | Theme Material 3 avec design tokens |
| `core/network/api_client.dart` | Singleton Dio avec interceptors |
| `core/network/api_endpoints.dart` | `ApiEndpoints.talentDetail(slug)` — **à modifier pour utiliser slug** |
| `core/network/api_result.dart` | `ApiSuccess<T>` / `ApiFailure<T>` sealed class |
| `core/storage/local_storage.dart` | Hive-based cache avec TTL (7 jours par défaut) |
| `features/favorites/bloc/favorites_bloc.dart` | BLoC favoris — déjà fourni au niveau app |
| `features/favorites/widgets/favorite_button.dart` | Widget coeur animé — intégrer dans le header profil |

### Format de réponse API GET /api/v1/talents/{slug}

**Succès (200) :**
```json
{
  "data": {
    "id": 1,
    "type": "talent_profile",
    "attributes": {
      "stage_name": "DJ Arafat",
      "slug": "dj-arafat",
      "bio": "Bio text or null",
      "city": "Abidjan",
      "cachet_amount": 500000,
      "average_rating": "4.50",
      "is_verified": true,
      "talent_level": "confirme",
      "profile_completion_percentage": 60,
      "social_links": { "instagram": "...", "facebook": "..." },
      "reliability_score": 78,
      "reviews_count": 0,
      "portfolio_items": [],
      "service_packages": [
        {
          "id": 1,
          "type": "service_package",
          "attributes": {
            "name": "Pack Essentiel",
            "description": "Description or null",
            "cachet_amount": 300000,
            "duration_minutes": 120,
            "inclusions": ["Sound system", "DJ set"],
            "type": "essentiel",
            "is_active": true,
            "sort_order": 0,
            "created_at": "2026-02-17T10:00:00+00:00"
          }
        }
      ],
      "recent_reviews": [],
      "created_at": "2026-02-17T10:00:00+00:00",
      "category": {
        "id": 1,
        "name": "Musique",
        "slug": "musique",
        "color_hex": "#FF5733"
      },
      "subcategory": {
        "id": 5,
        "name": "DJ",
        "slug": "dj"
      }
    }
  },
  "meta": {
    "similar_talents": [
      {
        "id": 2,
        "type": "talent_profile",
        "attributes": {
          "stage_name": "DJ Mix",
          "slug": "dj-mix",
          "city": "Abidjan",
          "cachet_amount": 400000,
          "average_rating": "4.00",
          "is_verified": true,
          "talent_level": "nouveau",
          "category": {
            "id": 1,
            "name": "Musique",
            "slug": "musique",
            "color_hex": "#FF5733"
          }
        }
      }
    ]
  }
}
```

**Erreur (404) :**
```json
{
  "error": {
    "code": "TALENT_NOT_FOUND",
    "message": "Le profil talent demandé est introuvable.",
    "status": 404,
    "details": {}
  }
}
```

**Points critiques API :**
1. **Slug-based routing** : l'API utilise `slug` (pas `id`). La route Flutter doit aussi utiliser le slug.
2. **`portfolio_items`** et **`recent_reviews`** sont actuellement des stubs (retournent `[]`). Le frontend doit gérer l'état vide avec élégance.
3. **`service_packages`** sont des données réelles. Types : `essentiel`, `standard`, `premium`, `micro`.
4. **`similar_talents`** vivent dans `meta.similar_talents` (pas dans `data`). Max 5, même catégorie + même ville, triés par note desc.
5. **`reliability_score`** est calculé côté serveur (0-100). Afficher visuellement.
6. **`social_links`** est un JSON nullable avec clés arbitraires (`instagram`, `facebook`, etc.).
7. **`talent_level`** est un enum string : `nouveau`, `confirme`, `populaire`, `elite`.
8. **`subcategory`** est conditionnel — absent si `subcategory_id` est null.
9. **`photo_url`** n'est PAS dans la réponse detail (seulement dans la liste). Utiliser les données passées via `extra` depuis TalentCard, ou afficher placeholder.

### Couleurs par catégorie talent (déjà dans colors.dart)

```dart
// BookmiColors — utiliser ces constantes pour les catégories
static const categoryDjMusique = Color(0xFF7C4DFF);
static const categoryGroupeMusical = Color(0xFF1565C0);
static const categoryHumoriste = Color(0xFFFF4081);
static const categoryDanseur = Color(0xFF00BFA5);
static const categoryMcAnimateur = Color(0xFFFFB300);
static const categoryPhotographe = Color(0xFF536DFE);
static const categoryDecorateur = Color(0xFFFF6E40);
```

**Mapping category slug → Color** (réutiliser la fonction `_categoryColor` de `talent_grid.dart` ou l'extraire dans un utilitaire) :
```dart
Color categoryColor(String? slug) {
  return switch (slug) {
    'dj' => BookmiColors.categoryDjMusique,
    'groupe-musical' => BookmiColors.categoryGroupeMusical,
    'humoriste' => BookmiColors.categoryHumoriste,
    'danseur' => BookmiColors.categoryDanseur,
    'mc-animateur' => BookmiColors.categoryMcAnimateur,
    'photographe' => BookmiColors.categoryPhotographe,
    'decorateur' => BookmiColors.categoryDecorateur,
    _ => BookmiColors.brandBlueLight,
  };
}
```

### Formatage des montants FCFA

```dart
// Utiliser la méthode statique existante — NE PAS dupliquer
TalentCard.formatCachet(int amountInCents)
// 15000000 → "150 000 FCFA"
```

### Formatage de la durée

```dart
// Pattern pour la durée des packages
String formatDuration(int? minutes) {
  if (minutes == null || minutes <= 0) return '';
  final hours = minutes ~/ 60;
  final mins = minutes % 60;
  if (hours == 0) return '${mins}min';
  if (mins == 0) return '${hours}h';
  return '${hours}h${mins.toString().padLeft(2, '0')}';
}
// 120 → "2h", 90 → "1h30", 45 → "45min"
```

### Dépendances inter-stories

| Dépendance | Story | Impact |
|---|---|---|
| API GET /talents/{slug} avec détail complet | Story 1.7 (done) | Backend prêt, endpoint profil public fonctionnel |
| API service_packages dans le détail | Story 1.8 (done) | Packages réels retournés par l'API |
| FavoriteButton widget + FavoritesBloc | Story 1.9 (done) | Intégrer dans le header profil |
| TalentCard widget (pour talents similaires) | Story 1.10 (done) | Réutiliser dans SimilarTalentsRow |
| DiscoveryPage navigation vers profil | Story 1.10 (done) | Modifier pour passer slug au lieu de id |
| Portfolio médias + Avis/Reviews backend | **Non implémenté** | API retourne des stubs — gérer l'état vide côté Flutter |
| Réservation (CTA Réserver) | Story 3.x (backlog) | Bouton CTA no-op pour cette story |

**Story 1.11 remplace la page TalentDetailPage temporaire de Story 1.10 par le profil complet immersif.**

### UX Design — Spécifications critiques

**Direction visuelle :** D5 (Immersive Media) + D1 (Deep Glassmorphism)
- Photo/vidéo principale occupe 60% de l'écran en haut (SliverAppBar expandedHeight)
- Badge vérifié flotte en glassmorphism sur la photo (coin supérieur droit)
- Le scroll révèle progressivement : bio → portfolio → packages → avis → talents similaires
- Les éléments financiers (prix des packages) apparaissent sur des glass cards avec fond `glassDark`
- Bouton CTA "Réserver" sticky en bas avec gradient orange

**Profil talent header specs UX :**

| Propriété | Valeur |
|---|---|
| `photo` | Full-bleed, 60% hauteur écran, `CachedNetworkImage` avec `BoxFit.cover` |
| `hero` | `Hero(tag: 'talent-$talentId')` pour transition depuis TalentCard |
| `badge` | Glass circle (28px) en top-right avec check icon, visible uniquement si `is_verified` |
| `overlay` | Gradient noir→transparent en bas de la photo pour lisibilité texte |
| `name` | Headline Large (24px SemiBold Nunito), blanc, en overlay bas |
| `category` | Label Medium (12px) avec couleur accent par catégorie |
| `city` | Body Small (12px) avec icône location, blanc 70% opacity |
| `collapseOnScroll` | `SliverAppBar(pinned: true)` — se collapse en app bar standard au scroll |

**Package cards specs UX :**

| Propriété | Valeur |
|---|---|
| `layout` | Column verticale de cards, spacing 12px |
| `card` | GlassCard avec padding 16px |
| `name` | Title Medium (16px SemiBold), blanc |
| `price` | Headline Large (24px Bold), `brand-blue-light` |
| `duration` | Body Small, blanc 60% opacity |
| `inclusions` | Body Medium, blanc 80% opacity, bullet list |
| `recommended` | Bordure orange #FF6B35, badge "Recommandé" |

**CTA button specs UX :**

| Propriété | Valeur |
|---|---|
| `gradient` | `gradientCta` (#FF6B35 → #FF8C5E) |
| `text` | "Réserver · 150 000 FCFA" (Title Medium, blanc) |
| `borderRadius` | 16px (BookmiRadius.button) |
| `height` | 56px |
| `margin` | 16px horizontal, safe area bottom |
| `sticky` | Toujours visible en bas de l'écran |

**Tab navigation (optionnel pour cette story) :**
Si la page devient trop longue, envisager une TabBar sous le header collapsé : "Infos / Portfolio / Packages / Avis". Pour cette story, un scroll continu est acceptable.

### Testing Standards

**Patterns de test Flutter établis (Story 1.9/1.10) :**
```dart
// Mock avec mocktail
class MockTalentProfileRepository extends Mock implements TalentProfileRepository {}

// BLoC test avec bloc_test
blocTest<TalentProfileBloc, TalentProfileState>(
  'emits [Loading, Loaded] when TalentProfileFetched is added',
  build: () {
    when(() => mockRepository.getTalentBySlug('dj-arafat')).thenAnswer(
      (_) async => ApiSuccess(TalentProfileResponse(
        profile: sampleProfile,
        similarTalents: sampleSimilar,
      )),
    );
    return TalentProfileBloc(repository: mockRepository);
  },
  act: (bloc) => bloc.add(const TalentProfileFetched(slug: 'dj-arafat')),
  expect: () => [
    isA<TalentProfileLoading>(),
    isA<TalentProfileLoaded>(),
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
      BlocProvider<TalentProfileBloc>.value(value: mockTalentProfileBloc),
    ],
    child: MaterialApp(home: TalentProfilePage(slug: 'dj-arafat')),
  ),
);
```

**Tests de non-régression obligatoires :**
- Les 144 tests existants (Story 1.10) doivent continuer à passer
- Le routing existant ne doit pas être cassé (changement id → slug affecte DiscoveryPage)

### Project Structure Notes

- La feature `talent_profile` est une **nouvelle feature** dans `lib/features/talent_profile/`
- `ServicePackageCard` va dans `core/design_system/components/` car réutilisable (page réservation, dashboard)
- `PortfolioGallery`, `ReviewsSection`, `SimilarTalentsRow` vont dans `features/talent_profile/presentation/widgets/` car spécifiques au profil
- Le barrel file `features/talent_profile/talent_profile.dart` exporte les éléments publics
- L'ancienne `TalentDetailPage` dans `features/discovery/` est **supprimée** et remplacée

### Packages — aucun nouveau package nécessaire

Tous les packages requis sont déjà dans pubspec.yaml :
- `flutter_bloc` / `bloc` / `bloc_test` — BLoC pattern
- `cached_network_image` — images réseau avec cache
- `intl` — formatage nombres (via TalentCard.formatCachet)
- `go_router` — routing et deep linking
- `hive_ce` — cache local
- `dio` — client HTTP
- `mocktail` — mocks pour tests

### Previous Story Intelligence (Story 1.10)

**Learnings de Story 1.10 à appliquer :**
1. **Constructeur `.forTesting`** : Créer un constructeur nommé pour injecter Dio directement dans le repository
2. **State equality** : Implémenter `operator ==` et `hashCode` sur les states BLoC — ajouter `other.runtimeType == runtimeType` dans le == pour éviter le bug subclass (cf. DiscoveryLoadingMore)
3. **Guard anti-doublon** : Pour cette story, un simple `if (state is TalentProfileLoading) return;` suffit
4. **NumberFormat locale** : French locale fr_FR utilise U+202F (narrow no-break space) — utiliser `TalentCard.formatCachet()` qui gère déjà ce cas avec `RegExp(r'[\s\u00A0\u202F,]')`
5. **Pagination failure preserves data** : Pattern appliqué dans Story 1.10 — pour le profil, pas de pagination mais le même esprit : ne pas détruire un état chargé si le refresh échoue
6. **ValueNotifier pour scroll** : Utiliser `ValueNotifier<double>` pour le scroll offset au lieu de `setState` (performance SliverAppBar)
7. **FavoritesFetched dispatch** : Déjà dispatché dans DiscoveryPage.initState — ne pas re-dispatcher ici. FavoritesBloc est global.
8. **SliverChildBuilderDelegate** : Utiliser le lazy rendering pour les listes (packages, avis)

### Code Review Deferred Issues (from Story 1.10)

Les issues suivantes de la code review Story 1.10 sont à résoudre dans cette story :
1. **Deep link talent detail = page vide** : L'ancienne TalentDetailPage recevait les données via `extra` (non persisté en deep link). La nouvelle TalentProfilePage charge les données depuis l'API via le slug, résolvant ce problème.
2. **Endpoint API utilise slug, pas id** : L'endpoint `talentDetail(int id)` dans api_endpoints.dart doit être changé en `talentDetail(String slug)`.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 1.11] — AC et user story
- [Source: _bmad-output/planning-artifacts/architecture.md#Frontend Architecture Flutter] — Stack, BLoC, GoRouter, Dio, Hive
- [Source: _bmad-output/planning-artifacts/architecture.md#Project Organization Flutter] — Structure features/
- [Source: _bmad-output/planning-artifacts/architecture.md#State Management Patterns Flutter BLoC] — Convention events/states
- [Source: _bmad-output/planning-artifacts/ux-design-specification.md#Profil talent] — D5+D1 direction, hero 60%, glass cards
- [Source: _bmad-output/planning-artifacts/ux-design-specification.md#Chosen Direction] — Composite D1+D5+D6
- [Source: _bmad-output/planning-artifacts/ux-design-specification.md#TabBar] — Sous-navigation profil
- [Source: _bmad-output/planning-artifacts/ux-design-specification.md#PackageCard] — Cartes comparatives packages
- [Source: _bmad-output/implementation-artifacts/1-10-ecrans-decouverte-flutter.md] — Patterns, TalentCard, DiscoveryBloc, code review findings
- [Source: bookmi/app/Http/Resources/TalentDetailResource.php] — Structure JSON réponse API
- [Source: bookmi/app/Http/Resources/ServicePackageResource.php] — Structure packages API
- [Source: bookmi/app/Services/TalentProfileService.php] — getPublicProfile logic
- [Source: bookmi/app/Repositories/Eloquent/TalentRepository.php] — findSimilar query

## Dev Agent Record

### Implementation Session — 2026-02-18

**Results:**
- `dart analyze lib/ test/` → 0 errors, 0 warnings (2 infos in tests — redundant defaults)
- `flutter test` → 175/175 tests passed (31 new + 144 existing, 0 regressions)
- All 16 tasks completed with all subtasks checked

**Architecture decisions:**
- `appRouter` global variable → `buildAppRouter(TalentProfileRepository)` function to enable route-level BLoC injection
- `TalentProfileBloc` created at route level (not app level) — scoped to a single profile view
- `photo_url` not in detail API response — passed via `extra` from TalentCard's `initialData`
- `_onSimilarTalentTap` navigates by slug via `pushNamed` — enables deep navigation stack
- `ServicePackageCard` placed in `core/design_system/components/` for cross-feature reusability

**Deferred items from Story 1.10 resolved:**
1. Deep link talent detail = page vide → Fixed: TalentProfilePage loads data from API via slug
2. Endpoint API uses slug not id → Fixed: `talentDetail(String slug)` in api_endpoints.dart

## File List

### New Files (12)
| File | Purpose |
|---|---|
| `lib/features/talent_profile/data/repositories/talent_profile_repository.dart` | Repository with API call + Hive cache fallback |
| `lib/features/talent_profile/bloc/talent_profile_event.dart` | Sealed events: Fetched, Refreshed |
| `lib/features/talent_profile/bloc/talent_profile_state.dart` | Sealed states: Initial, Loading, Loaded, Failure |
| `lib/features/talent_profile/bloc/talent_profile_bloc.dart` | BLoC with fetch + refresh handlers |
| `lib/core/design_system/components/service_package_card.dart` | Package card with type-based styling + badges |
| `lib/features/talent_profile/presentation/widgets/portfolio_gallery.dart` | 3-column grid with empty state |
| `lib/features/talent_profile/presentation/widgets/reviews_section.dart` | Reviews list with stars + empty state |
| `lib/features/talent_profile/presentation/widgets/similar_talents_row.dart` | Horizontal TalentCard row |
| `lib/features/talent_profile/presentation/pages/talent_profile_page.dart` | Immersive profile page (D5+D1) |
| `lib/features/talent_profile/talent_profile.dart` | Barrel file |
| `test/features/talent_profile/data/repositories/talent_profile_repository_test.dart` | 7 repository tests |
| `test/features/talent_profile/bloc/talent_profile_bloc_test.dart` | 7 BLoC tests |

### New Test Files (4)
| File | Tests |
|---|---|
| `test/core/design_system/components/service_package_card_test.dart` | 11 tests (widget + formatDuration) |
| `test/features/talent_profile/presentation/widgets/reviews_section_test.dart` | 3 tests |
| `test/features/talent_profile/presentation/widgets/portfolio_gallery_test.dart` | 2 tests |
| `test/features/talent_profile/bloc/talent_profile_bloc_test.dart` | 7 tests |

### Modified Files (7)
| File | Change |
|---|---|
| `lib/core/network/api_endpoints.dart` | `talentDetail(int id)` → `talentDetail(String slug)` |
| `lib/app/routes/route_names.dart` | `talent/:id` → `talent/:slug` |
| `lib/app/routes/app_router.dart` | `appRouter` → `buildAppRouter()`, TalentProfilePage + BlocProvider |
| `lib/app/view/app.dart` | Added TalentProfileRepository to _AppDependencies |
| `lib/features/discovery/presentation/pages/discovery_page.dart` | `_onTalentTap` passes slug instead of id |
| `lib/features/discovery/discovery.dart` | Removed TalentDetailPage export |
| `test/app/routes/app_router_test.dart` | Updated for `buildAppRouter()`, added slug test |

## Change Log

| Date | Change | Files |
|---|---|---|
| 2026-02-18 | Story created | 1-11-ecran-profil-talent-flutter.md |
| 2026-02-18 | Status: backlog → ready-for-dev → in-progress | sprint-status.yaml |
| 2026-02-18 | Tasks 1-16 implemented: full talent profile feature | 12 new files, 7 modified files |
| 2026-02-18 | All 175 tests passing (31 new + 144 existing) | test/ |
| 2026-02-18 | Code review: 7 findings fixed (1 critical, 3 medium, 3 low) | 9 files modified |

### Code Review — 2026-02-18

**7 findings identified and fixed:**

| # | Sévérité | Issue | Fix |
|---|----------|-------|-----|
| 1 | CRITIQUE | GoRouter recréé à chaque `App.build()` — perte de navigation | Stocké dans `_AppDependencies`, créé une seule fois |
| 2 | MOYENNE | Badge "Populaire" jamais affiché pour standard packages | `isRecommended: pkgType == 'premium'` (plus standard) |
| 3 | MOYENNE | `_categoryColor` dupliquée dans 3 fichiers | Extraite dans `BookmiColors.categoryColor()` |
| 4 | MOYENNE | Race condition fetch/refresh — appels API concurrents | Guard `if (state is TalentProfileLoading) return;` ajouté |
| 5 | BASSE | Task 5.4 tap-to-fullscreen marquée done mais absente | Implémenté `_PortfolioFullscreen` avec `PageView` |
| 6 | BASSE | `TalentProfileFailure` sans `operator ==` ni `hashCode` | Ajouté avec `@immutable` |
| 7 | BASSE | `response.data!` force-unwrap non protégé | Null guard avec `ApiFailure(code: 'EMPTY_RESPONSE')` |

**Post-review results:**
- `dart analyze lib/ test/` → 0 errors, 0 warnings (2 infos in tests — redundant defaults)
- `flutter test` → 175/175 tests passed, 0 regressions

## Completion Status

- **Status:** done
- **Created:** 2026-02-18
- **Completed:** 2026-02-18
- **Dependencies satisfied:** Stories 1.2, 1.3, 1.7, 1.8, 1.9, 1.10 (all done)
- **Test count:** 175 (31 new + 144 baseline)
