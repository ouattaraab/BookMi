# Story 1.2: Initialisation du projet mobile Flutter

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a développeur,
I want le projet Flutter initialisé avec Very Good CLI, BLoC, GoRouter et le design system de base,
So that toutes les stories mobiles suivantes disposent d'une fondation Flutter cohérente.

## Acceptance Criteria (BDD)

1. **Given** la commande `very_good create flutter_app bookmi_app` est exécutée
   **When** le projet est créé
   **Then** la structure features-based est en place (`lib/features/`, `lib/core/`)
   **And** les 3 flavors (development, staging, production) sont opérationnels

2. **Given** le projet est créé
   **When** on vérifie la navigation
   **Then** GoRouter est configuré avec deep linking et 5 routes de base (home, search, bookings, messages, profile)

3. **Given** le projet est créé
   **When** on vérifie le client HTTP
   **Then** Dio est configuré avec interceptors (auth, retry, logging)

4. **Given** le projet est créé
   **When** on vérifie le design system
   **Then** les tokens de design glassmorphism sont définis (couleurs, typographie Nunito, spacing, glass tokens)
   **And** les composants GlassCard et GlassAppBar sont implémentés avec dégradation GPU 3 tiers

5. **Given** le projet est créé
   **When** on vérifie la navigation principale
   **Then** la bottom navigation bar 5 onglets est en place (Accueil, Recherche, Réservations, Messages, Profil)

6. **Given** le projet est créé
   **When** on vérifie le stockage local
   **Then** Hive CE et flutter_secure_storage sont configurés

7. **Given** le projet est créé
   **When** on lance les tests et le linting
   **Then** `very_good test` passe et `dart analyze` ne retourne aucune erreur

## Tasks / Subtasks

- [x] **Task 1 : Initialisation du projet Flutter avec Very Good CLI** (AC: #1)
  - [x] 1.1 Installer Very Good CLI globalement : `dart pub global activate very_good_cli`
  - [x] 1.2 Créer le projet : `very_good create flutter_app bookmi_app --description "BookMi - Marketplace de réservation de talents" --org "ci.bookmi"`
  - [x] 1.3 Vérifier que les 3 flavors (development, staging, production) sont générés
  - [x] 1.4 Vérifier que `very_good_analysis` est configuré dans `analysis_options.yaml`
  - [x] 1.5 Vérifier que `flutter analyze` passe sans erreur sur le projet initial
  - [x] 1.6 Vérifier que `very_good test` passe sur le projet initial

- [x] **Task 2 : Structure features-based du projet** (AC: #1)
  - [x] 2.1 Créer la structure `lib/core/` avec sous-dossiers : `design_system/`, `network/`, `storage/`, `utils/`, `constants/`, `notifications/`
  - [x] 2.2 Créer la structure `lib/core/design_system/` avec sous-dossiers : `tokens/`, `components/`, `theme/`
  - [x] 2.3 Créer la structure `lib/core/network/` avec sous-dossiers : `interceptors/`
  - [x] 2.4 Créer la structure `lib/features/` (vide pour l'instant — les features seront ajoutées dans les stories suivantes)
  - [x] 2.5 Créer `lib/app/routes/` et `lib/app/routes/guards/` pour GoRouter
  - [x] 2.6 Créer `lib/app/env/` avec les fichiers d'environnement : `env.dart` (abstract), `env_development.dart`, `env_staging.dart`, `env_production.dart`
  - [x] 2.7 Créer les barrel files pour chaque module core (`core.dart`, `design_system.dart`, etc.)

- [x] **Task 3 : Configuration des environnements (flavors)** (AC: #1)
  - [x] 3.1 Créer `lib/app/env/env.dart` : classe abstraite `Env` avec `apiBaseUrl`, `paystackPublicKey`, `sentryDsn`, `appName`
  - [x] 3.2 Créer `env_development.dart` : API URL `http://10.0.2.2:8080/api/v1` (émulateur Android), Paystack sandbox
  - [x] 3.3 Créer `env_staging.dart` : API URL staging, Paystack sandbox
  - [x] 3.4 Créer `env_production.dart` : API URL production, Paystack live
  - [x] 3.5 Vérifier que `flutter run --flavor development --target lib/main_development.dart` lance l'app sans erreur

- [x] **Task 4 : Configuration GoRouter avec deep linking** (AC: #2)
  - [x] 4.1 Ajouter la dépendance `go_router: ^17.1.0` dans `pubspec.yaml`
  - [x] 4.2 Créer `lib/app/routes/app_router.dart` avec GoRouter configuré
  - [x] 4.3 Définir les 5 routes principales correspondant aux 5 tabs de navigation : `/home`, `/search`, `/bookings`, `/messages`, `/profile`
  - [x] 4.4 Créer `lib/app/routes/route_names.dart` avec les constantes de noms de routes
  - [x] 4.5 Créer `lib/app/routes/guards/auth_guard.dart` : placeholder de guard d'authentification (redirect vers login si non-auth)
  - [x] 4.6 Configurer le shell route pour le bottom navigation bar avec `StatefulShellRoute.indexedStack`
  - [x] 4.7 Créer les pages placeholder temporaires pour chaque tab (sera remplacé par les vraies features plus tard)

- [x] **Task 5 : Configuration Dio avec interceptors** (AC: #3)
  - [x] 5.1 Ajouter la dépendance `dio: ^5.9.0` dans `pubspec.yaml`
  - [x] 5.2 Créer `lib/core/network/api_client.dart` : singleton Dio avec baseUrl depuis `Env`, timeouts (connect: 15s, receive: 30s)
  - [x] 5.3 Créer `lib/core/network/api_endpoints.dart` : constantes des endpoints API (`/health`, `/auth/login`, etc.)
  - [x] 5.4 Créer `lib/core/network/api_result.dart` : sealed class `ApiResult<T>` avec `ApiSuccess<T>` et `ApiFailure`
  - [x] 5.5 Créer `lib/core/network/interceptors/auth_interceptor.dart` : injecte le Bearer token depuis flutter_secure_storage, redirige sur 401
  - [x] 5.6 Créer `lib/core/network/interceptors/retry_interceptor.dart` : 3 tentatives, backoff exponentiel (1s, 2s, 4s), seulement erreurs réseau/timeout
  - [x] 5.7 Créer `lib/core/network/interceptors/logging_interceptor.dart` : log request/response en mode debug uniquement

- [x] **Task 6 : Design tokens glassmorphism** (AC: #4)
  - [x] 6.1 Ajouter `google_fonts: ^8.0.0` dans `pubspec.yaml`
  - [x] 6.2 Créer `lib/core/design_system/tokens/colors.dart` avec TOUS les tokens couleur :
    - Brand : `brandNavy` (#1A2744), `brandBlue` (#2196F3), `brandBlueDark` (#1976D2), `brandBlueLight` (#64B5F6), `brandBlue50` (#E3F2FD), `ctaOrange` (#FF6B35), `ctaOrangeDark` (#E55A2B), `ctaOrangeLight` (#FF8C5E)
    - Semantic : `success` (#00C853), `warning` (#FFB300), `error` (#FF1744), `errorLight` (#FFEBEE), `info` (#2196F3)
    - Glass : `glassWhite` (rgba 255,255,255,0.15), `glassWhiteMedium` (rgba 255,255,255,0.25), `glassWhiteStrong` (rgba 255,255,255,0.40), `glassDark` (rgba 26,39,68,0.80), `glassDarkMedium` (rgba 26,39,68,0.60), `glassBorder` (rgba 255,255,255,0.25), `glassBorderBlue` (rgba 33,150,243,0.30)
    - Gradients : `gradientHero`, `gradientBrand`, `gradientCta`, `gradientCard`, `gradientShield`, `gradientCelebration`
    - Catégories : 7 couleurs d'accent par catégorie de talent
  - [x] 6.3 Créer `lib/core/design_system/tokens/typography.dart` avec Nunito : 11 niveaux typographiques (Display Large 36px → Overline 10px) conformes au UX Design spec
  - [x] 6.4 Créer `lib/core/design_system/tokens/spacing.dart` avec les 8 tokens spacing (4px → 64px) : `spaceXs`, `spaceSm`, `spaceMd`, `spaceBase`, `spaceLg`, `spaceXl`, `space2xl`, `space3xl`
  - [x] 6.5 Créer `lib/core/design_system/tokens/radius.dart` avec les border radius : carte 24px, button 16px, input 12px, chip 999px (pill), bottomSheet 24px (top), avatar 999px (cercle), image 16px
  - [x] 6.6 Créer `lib/core/design_system/tokens/shadows.dart` avec les elevation tokens
  - [x] 6.7 Créer `lib/core/design_system/tokens/glass.dart` avec les tokens glassmorphism : `glassBlur` (20.0 sigma), `glassBlurLight` (10.0 sigma), constantes d'opacité par tier GPU

- [x] **Task 7 : Composants GlassCard et GlassAppBar avec dégradation GPU** (AC: #4)
  - [x] 7.1 Créer `lib/core/design_system/theme/gpu_tier_provider.dart` : détection GPU tier (1/2/3) au runtime basée sur capacités appareil (`dart:ui` + RAM + benchmark)
    - Tier 3 (GPU puissant) : iPhone 12+, Galaxy S21+, Pixel 6+ → Full glassmorphism blur(20)
    - Tier 2 (GPU moyen) : Galaxy A32, Redmi Note 10/11 → Blur réduit blur(10) + opacité augmentée
    - Tier 1 (GPU faible) : Tecno Spark, Galaxy A03, 2 Go RAM → Pas de blur, fond semi-transparent (0.85)
  - [x] 7.2 Créer `lib/core/design_system/components/glass_card.dart` : widget GlassCard adaptatif
    - Props : `backgroundColor`, `borderRadius` (défaut 24px), `border`, `blurSigma` (défaut 20.0), `padding` (défaut 16px), `child`
    - States : Default, Pressed (opacity 0.8 + scale 0.98), Disabled (opacity 0.4), Selected (border brand-blue 2px)
    - Dégradation automatique selon GPU tier (BackdropFilter → fond opaque)
    - ClipRRect wrapping obligatoire pour le blur
  - [x] 7.3 Créer `lib/core/design_system/components/glass_app_bar.dart` : AppBar translucide
    - Transparent → glass-dark au scroll (transition 200ms)
    - Blur 0 → 20 sigma au scroll
    - Hauteur 56px + status bar
    - Dégradation GPU identique à GlassCard
  - [x] 7.4 Créer `lib/core/design_system/components/glass_bottom_nav.dart` : Bottom navigation bar glassmorphism
    - Background glass avec blur
    - Hauteur 64px + safe area
    - Tab actif : brand-blue (#2196F3)
    - Tab inactif : gris semi-transparent
    - Border radius 24px (top corners)
    - Dégradation GPU identique

- [x] **Task 8 : ThemeData BookMi** (AC: #4)
  - [x] 8.1 Créer `lib/core/design_system/theme/bookmi_theme.dart` : ThemeData complet avec tokens
    - Light theme + Dark theme
    - ColorScheme basé sur les tokens brand
    - TextTheme basé sur les tokens typography (Nunito)
    - Input decoration, button themes, card theme utilisant les tokens radius/spacing
  - [x] 8.2 Intégrer le ThemeData dans `lib/app/app.dart` (MaterialApp)
  - [x] 8.3 Supporter le mode sombre automatique (suivre le paramètre système via `MediaQuery.platformBrightnessOf`)

- [x] **Task 9 : Bottom navigation bar 5 onglets** (AC: #5)
  - [x] 9.1 Implémenter la bottom navigation dans le shell route GoRouter avec `StatefulShellRoute.indexedStack`
  - [x] 9.2 Configurer les 5 tabs : Accueil (icône maison), Recherche (icône loupe), Réservations (icône calendrier), Messages (icône bulle), Profil (icône personne)
  - [x] 9.3 Utiliser le composant `GlassBottomNav` (Task 7.4) comme bottom navigation bar
  - [x] 9.4 Vérifier que la navigation entre tabs préserve l'état de chaque tab (IndexedStack)

- [x] **Task 10 : Configuration stockage local (Hive CE + flutter_secure_storage)** (AC: #6)
  - [x] 10.1 Ajouter les dépendances dans `pubspec.yaml` : `hive_ce: ^2.19.0`, `hive_ce_flutter: ^2.3.0`, `flutter_secure_storage: ^10.0.0`
  - [x] 10.2 Créer `lib/core/storage/local_storage.dart` : wrapper Hive CE pour le cache local (ouverture de box, CRUD, expiration 7 jours)
  - [x] 10.3 Créer `lib/core/storage/secure_storage.dart` : wrapper flutter_secure_storage pour tokens Sanctum et données sensibles (Keychain iOS / KeyStore Android)
  - [x] 10.4 Créer `lib/core/storage/cache_manager.dart` : gestionnaire de cache avec invalidation (TTL 7 jours)
  - [x] 10.5 Initialiser Hive dans `main.dart` (`Hive.initFlutter()`) avant le `runApp`

- [x] **Task 11 : Configuration BLoC** (AC: #7)
  - [x] 11.1 Ajouter les dépendances : `bloc: ^9.2.0`, `flutter_bloc: ^9.1.0`
  - [x] 11.2 Créer `lib/app/app_bloc_observer.dart` : observer de debug qui log les events/transitions BLoC en mode debug
  - [x] 11.3 Configurer le `BlocObserver` dans `main.dart`
  - [x] 11.4 Vérifier que BLoC est correctement initialisé (pas de features BLoC dans cette story — juste l'infrastructure)

- [x] **Task 12 : Tests de fondation** (AC: #7)
  - [x] 12.1 Créer `test/core/design_system/tokens/colors_test.dart` : vérifie que les tokens couleur sont correctement définis (brand, semantic, glass)
  - [x] 12.2 Créer `test/core/design_system/tokens/spacing_test.dart` : vérifie les 8 tokens spacing
  - [x] 12.3 Créer `test/core/design_system/components/glass_card_test.dart` : vérifie le rendu GlassCard pour chaque tier GPU (widget test)
  - [x] 12.4 Créer `test/core/network/api_result_test.dart` : vérifie ApiSuccess et ApiFailure (sealed class)
  - [x] 12.5 Créer `test/app/routes/app_router_test.dart` : vérifie que les 5 routes principales sont enregistrées
  - [x] 12.6 Vérifier que `dart analyze` passe sans erreur
  - [x] 12.7 Vérifier que `very_good test` passe avec tous les tests

### Review Follow-ups (AI)

- [ ] [AI-Review][MEDIUM] Câbler les classes Env (EnvDevelopment/Staging/Production) aux fichiers main_*.dart via DI — les classes existent mais ne sont utilisées nulle part [lib/app/env/]
- [ ] [AI-Review][MEDIUM] Clarifier gradientCelebration : listé en Task 6.2 mais absent du UX Design Spec — supprimer de la spec ou implémenter comme token [lib/core/design_system/tokens/colors.dart]
- [ ] [AI-Review][MEDIUM] GlassAppBar : ajouter animation lissée 200ms pour la transition scroll (le token BookmiGlass.scrollTransition existe mais n'est pas utilisé) [lib/core/design_system/components/glass_app_bar.dart]
- [ ] [AI-Review][LOW] Barrel files : exporter les interceptors depuis core.dart pour testabilité indépendante [lib/core/core.dart]
- [ ] [AI-Review][LOW] app_router.dart : refactorer en factory pour éliminer l'état global mutable (GlobalKey + GoRouter top-level) [lib/app/routes/app_router.dart]

## Dev Notes

### Architecture Patterns à respecter IMPÉRATIVEMENT

**Structure features-based (ARCH-FLUTTER-1 à ARCH-FLUTTER-5) :**
- Chaque feature est autonome — JAMAIS d'import direct entre features
- Communication inter-features uniquement via : navigation GoRouter (par route name), événements BLoC partagés via `MultiBlocListener`, services core partagés
- Barrel files obligatoires — chaque feature/module exporte via `{module}.dart`
- Pages vs Widgets : une Page = un écran complet avec route, un Widget = composant réutilisable

**Pattern BLoC obligatoire (ARCH-PATTERN-BLoC) :**
- `sealed class` pour TOUS les Events et States (Dart 3 exhaustive matching)
- Events nommés au passé : `BookingFetched`, `PaymentInitiated`
- States nommés avec statut : `BookingInitial`, `BookingLoading`, `BookingSuccess`, `BookingFailure`
- 3 fichiers minimum par feature BLoC : `{feature}_event.dart`, `{feature}_state.dart`, `{feature}_bloc.dart`
- JAMAIS d'appel Dio direct dans un Widget — toujours via BLoC → Repository → Datasource

**Pattern réseau (ARCH-NETWORK) :**
- `ApiResult<T>` sealed class : `ApiSuccess<T>(T data)` | `ApiFailure(String code, String message, Map? details)`
- Dio interceptor auth : injecte Bearer token, redirige GoRouter vers `/login` sur 401
- Dio interceptor retry : 3 tentatives, backoff exponentiel (1s, 2s, 4s), seulement erreurs réseau/timeout
- JSON `snake_case` partout — pas de transformation côté Flutter (cohérence avec l'API Laravel)

**Glassmorphism GPU dégradé (ARCH-UX-GPU) :**
- Tier 3 (Full) : `BackdropFilter(ImageFilter.blur(sigmaX: 20, sigmaY: 20))` + border semi-transparent
- Tier 2 (Light) : `BackdropFilter(ImageFilter.blur(sigmaX: 10, sigmaY: 10))` + opacité augmentée
- Tier 1 (Solid) : Pas de `BackdropFilter` — fond `Color.withOpacity(0.85)` + border
- Détection au runtime via capacités GPU/RAM
- `ClipRRect` wrapping OBLIGATOIRE autour de tout `BackdropFilter`

**Naming conventions strictes (Dart) :**
| Élément | Convention | Exemple |
|---|---|---|
| Fichiers | `snake_case.dart` | `glass_card.dart`, `booking_bloc.dart` |
| Classes | `PascalCase` | `GlassCard`, `BookingBloc` |
| BLoC Events | `PascalCase` passé | `BookingConfirmed`, `PaymentInitiated` |
| BLoC States | `PascalCase` + status | `BookingInitial`, `BookingLoading` |
| Variables | `camelCase` | `talentProfile`, `isVerified` |
| Constantes | `camelCase` | `defaultPadding`, `primaryNavy` |
| Privées | `_camelCase` | `_isLoading`, `_controller` |

**Format des données (cohérence API Laravel ↔ Flutter) :**
| Donnée | Format | Règle |
|---|---|---|
| JSON fields | `snake_case` | Zéro mapping Laravel ↔ Flutter |
| Dates API | ISO 8601 UTC | Reçu UTC, converti côté Flutter (Afrique/Abidjan UTC+0) |
| Montants | Entier centimes FCFA | `15000000` = 150 000 FCFA. Jamais de float. Formatage affichage côté Flutter |
| Montants affichage | `"150 000 FCFA"` | Séparateur milliers = espace |
| Téléphone | E.164 | `"+2250700000000"` |
| IDs | Integer (bigint) | Jamais UUID pour MVP |

### Versions des dépendances (recherche web 2026-02-17)

| Package | Version | Notes |
|---|---|---|
| Flutter SDK | 3.41.0 | Sortie 11 février 2026, glassmorphism amélioré (bounded blur fix) |
| Dart SDK | 3.11 | Dot shorthands syntax |
| Very Good CLI | 0.28.0 | `very_good create flutter_app` toujours fonctionnel |
| bloc | 9.2.0 | Sealed classes standard pour events ET states |
| flutter_bloc | 9.1.1 | BLoC 9.x series |
| go_router | 17.1.0 | TypedGoRoute améliorations, deep linking natif |
| dio | 5.9.1 | Version stable 5.x |
| hive_ce | 2.19.2 | **⚠️ UTILISER hive_ce — PAS l'original hive (ABANDONNÉ depuis 2022)** |
| hive_ce_flutter | 2.3.4 | Drop-in replacement de hive_flutter |
| flutter_secure_storage | 10.0.0 | **⚠️ BREAKING : min Android SDK 23, Java 17 requis** |
| google_fonts | 8.0.1 | Nunito disponible |
| very_good_analysis | 10.2.0 | Dernières règles lint strictes |
| cached_network_image | latest | Cache réseau automatique — critique pour bande passante CI |

### Alertes critiques pour le développeur

1. **⚠️ hive est ABANDONNÉ** : Le package `hive` original (pub.dev/packages/hive) n'a plus été mis à jour depuis 2022. **Utiliser `hive_ce`** (Community Edition, 2.19.2) qui est un drop-in replacement activement maintenu. Le import est identique : `import 'package:hive_ce/hive.dart';`. Ajouter `hive_ce` et `hive_ce_flutter` dans `pubspec.yaml`, PAS `hive` et `hive_flutter`.

2. **⚠️ flutter_secure_storage 10.0.0 — Breaking changes** : La version 10.0.0 requiert Android SDK minimum 23 (Android 6.0) et Java 17 pour le build. Vérifier que `android/app/build.gradle` a `minSdkVersion 23` et que le JDK local est 17+. L'architecture spécifie Android 8.0+ (API 26) donc c'est compatible, mais le fichier Gradle doit être vérifié.

3. **⚠️ ClipRRect obligatoire pour BackdropFilter** : Depuis Flutter 3.x, `BackdropFilter` doit être wrappé dans un `ClipRRect` pour limiter la zone de blur. Sans ClipRRect, le blur peut affecter toute la zone parente. Flutter 3.41 améliore le bounded blur mais le wrapping ClipRRect reste obligatoire pour un rendu correct.

4. **Ne PAS créer de features BLoC dans cette story** : Cette story initialise uniquement l'infrastructure (GoRouter, Dio, design tokens, composants de base). Les features métier (auth, discovery, booking, etc.) seront créées dans les stories suivantes. Les pages placeholder dans le bottom nav sont des `Scaffold` simples avec un texte "Coming soon".

5. **Ne PAS installer ces packages maintenant** (seront dans les stories suivantes) :
   - `firebase_messaging` + `flutter_local_notifications` → Story 5.4 (Notifications push)
   - `geolocator` + `geocoding` → Story 1.6 (Géolocalisation)
   - `flutter_pdfview` → Story 3.5 (Contrats PDF)
   - `flutter_image_compress` → Story 6.7 (Portfolio)
   - `sentry_flutter` → Story 1.12 (CI/CD)
   - `laravel_echo_flutter` + `web_socket_channel` → Story 5.1 (Messagerie temps réel)

6. **BlocObserver en debug uniquement** : L'`AppBlocObserver` ne doit logger les transitions que lorsque `kDebugMode` est `true`. Ne jamais logger en production — impact performance et potentielle fuite de données sensibles.

### Packages à installer dans cette story

```yaml
# pubspec.yaml dependencies
dependencies:
  flutter:
    sdk: flutter
  bloc: ^9.2.0
  flutter_bloc: ^9.1.0
  go_router: ^17.1.0
  dio: ^5.9.0
  hive_ce: ^2.19.0
  hive_ce_flutter: ^2.3.0
  flutter_secure_storage: ^10.0.0
  google_fonts: ^8.0.0

dev_dependencies:
  flutter_test:
    sdk: flutter
  very_good_analysis: ^10.2.0
  bloc_test: ^9.1.0
  mocktail: ^1.0.0
```

### Packages à NE PAS installer (seront dans les stories suivantes)

- `firebase_messaging` → Story 5.4
- `flutter_local_notifications` → Story 5.4
- `geolocator` → Story 1.6
- `geocoding` → Story 1.6
- `flutter_pdfview` → Story 3.5
- `flutter_image_compress` → Story 6.7
- `cached_network_image` → Story 1.10 (quand les écrans affichent des images réseau)
- `sentry_flutter` → Story 1.12
- `laravel_echo_flutter` → Story 5.1
- `web_socket_channel` → Story 5.1
- `intl` → Déjà inclus par Very Good CLI (l10n)

### Design System Tokens — Référence complète

#### Couleurs Brand
```dart
static const brandNavy = Color(0xFF1A2744);       // "Book" du logo, fonds, headers
static const brandBlue = Color(0xFF2196F3);        // "Mi" du logo, accents, liens
static const brandBlueDark = Color(0xFF1976D2);    // Pressed/hover du bleu
static const brandBlueLight = Color(0xFF64B5F6);   // Highlights, fonds légers
static const brandBlue50 = Color(0xFFE3F2FD);      // Background sections bleues
static const ctaOrange = Color(0xFFFF6B35);        // Boutons CTA "Réserver", "Payer"
static const ctaOrangeDark = Color(0xFFE55A2B);    // Pressed/hover des CTA
static const ctaOrangeLight = Color(0xFFFF8C5E);   // Actions secondaires
```

#### Couleurs Glass
```dart
static const glassWhite = Color.fromRGBO(255, 255, 255, 0.15);
static const glassWhiteMedium = Color.fromRGBO(255, 255, 255, 0.25);
static const glassWhiteStrong = Color.fromRGBO(255, 255, 255, 0.40);
static const glassDark = Color.fromRGBO(26, 39, 68, 0.80);
static const glassDarkMedium = Color.fromRGBO(26, 39, 68, 0.60);
static const glassBorder = Color.fromRGBO(255, 255, 255, 0.25);
static const glassBorderBlue = Color.fromRGBO(33, 150, 243, 0.30);
```

#### Typographie (Nunito)
| Niveau | Taille | Weight | Line Height | Usage |
|---|---|---|---|---|
| Display Large | 36px | Bold (700) | 44px | Montant dashboard, bienvenue |
| Display Medium | 32px | Bold (700) | 40px | Montants financiers, titres |
| Headline | 24px | SemiBold (600) | 32px | Nom talent, section |
| Title Large | 20px | SemiBold (600) | 28px | Sous-titres, packages |
| Title Medium | 16px | SemiBold (600) | 24px | Labels, noms listes |
| Body Large | 16px | Regular (400) | 24px | Texte courant, bios |
| Body Medium | 14px | Regular (400) | 20px | Texte secondaire |
| Label Large | 14px | Medium (500) | 20px | Boutons, labels |
| Label Medium | 12px | Medium (500) | 16px | Badges, chips |
| Caption | 12px | Regular (400) | 16px | Timestamps, metadata |
| Overline | 10px | SemiBold (600) | 14px | Labels ALL CAPS |

#### Spacing (base 4px)
```dart
static const spaceXs = 4.0;    // Icône ↔ label
static const spaceSm = 8.0;    // Padding petits éléments
static const spaceMd = 12.0;   // Liste compacte
static const spaceBase = 16.0; // Padding cartes, sections
static const spaceLg = 24.0;   // Groupes contenu
static const spaceXl = 32.0;   // Sections majeures
static const space2xl = 48.0;  // Blocs de page
static const space3xl = 64.0;  // Header → contenu
```

#### Border Radius
```dart
static const radiusCard = 24.0;     // Cartes talents, réservations
static const radiusButton = 16.0;   // Boutons CTA
static const radiusInput = 12.0;    // Input fields
static const radiusChip = 999.0;    // Catégories, filtres (pill)
static const radiusSheet = 24.0;    // Bottom sheet (top)
static const radiusAvatar = 999.0;  // Photo profil (cercle)
static const radiusImage = 16.0;    // Images portfolio
```

### Project Structure Notes

**Structure finale attendue après cette story :**

```
bookmi_app/
├── android/                              # Config Android (minSdkVersion 23)
├── ios/                                  # Config iOS
├── lib/
│   ├── app/
│   │   ├── app.dart                      # MaterialApp + BlocProviders + ThemeData
│   │   ├── app_bloc_observer.dart        # Debug logging BLoC events
│   │   ├── env/
│   │   │   ├── env.dart                  # Environment abstract
│   │   │   ├── env_development.dart      # API URL dev, Paystack sandbox
│   │   │   ├── env_staging.dart          # API URL staging
│   │   │   └── env_production.dart       # API URL prod, Paystack live
│   │   └── routes/
│   │       ├── app_router.dart           # GoRouter config + shell route
│   │       ├── route_names.dart          # Constantes noms de routes
│   │       └── guards/
│   │           └── auth_guard.dart       # Placeholder redirect si non-auth
│   ├── core/
│   │   ├── core.dart                     # Barrel file core
│   │   ├── design_system/
│   │   │   ├── design_system.dart        # Barrel file design system
│   │   │   ├── tokens/
│   │   │   │   ├── colors.dart           # Brand + semantic + glass colors
│   │   │   │   ├── typography.dart       # Nunito 11 niveaux
│   │   │   │   ├── spacing.dart          # 8 tokens (4px → 64px)
│   │   │   │   ├── radius.dart           # Border radius tokens
│   │   │   │   ├── shadows.dart          # Elevation tokens
│   │   │   │   └── glass.dart            # Glassmorphism tokens (blur, opacity, tiers)
│   │   │   ├── components/
│   │   │   │   ├── glass_card.dart       # Carte glassmorphism adaptative
│   │   │   │   ├── glass_app_bar.dart    # App bar translucide
│   │   │   │   └── glass_bottom_nav.dart # Bottom navigation bar vitrée
│   │   │   └── theme/
│   │   │       ├── bookmi_theme.dart     # ThemeData light + dark
│   │   │       └── gpu_tier_provider.dart # Détection GPU tier (1/2/3)
│   │   ├── network/
│   │   │   ├── api_client.dart           # Dio instance singleton
│   │   │   ├── api_endpoints.dart        # Constantes endpoints
│   │   │   ├── api_result.dart           # ApiResult<T> sealed class
│   │   │   └── interceptors/
│   │   │       ├── auth_interceptor.dart
│   │   │       ├── retry_interceptor.dart
│   │   │       └── logging_interceptor.dart
│   │   ├── storage/
│   │   │   ├── secure_storage.dart       # flutter_secure_storage wrapper
│   │   │   ├── local_storage.dart        # Hive CE wrapper
│   │   │   └── cache_manager.dart        # Cache 7j avec invalidation
│   │   ├── utils/                        # (vide — .gitkeep)
│   │   ├── constants/                    # (vide — .gitkeep)
│   │   └── notifications/                # (vide — .gitkeep)
│   └── features/                         # (vide — .gitkeep)
│       └── placeholder/                  # Pages placeholder pour bottom nav
│           ├── home_placeholder_page.dart
│           ├── search_placeholder_page.dart
│           ├── bookings_placeholder_page.dart
│           ├── messages_placeholder_page.dart
│           └── profile_placeholder_page.dart
├── test/
│   ├── core/
│   │   ├── design_system/
│   │   │   ├── tokens/
│   │   │   │   ├── colors_test.dart
│   │   │   │   └── spacing_test.dart
│   │   │   └── components/
│   │   │       └── glass_card_test.dart
│   │   └── network/
│   │       └── api_result_test.dart
│   └── app/
│       └── routes/
│           └── app_router_test.dart
├── assets/
│   ├── images/                           # (vide — .gitkeep)
│   ├── icons/                            # (vide — .gitkeep)
│   └── fonts/                            # (vide — Nunito via google_fonts)
├── pubspec.yaml
├── analysis_options.yaml                 # very_good_analysis
└── l10n.yaml                             # Localisation (French par défaut)
```

### Intelligence de la Story 1.1 (précédente)

**Leçons apprises de la Story 1.1 :**
- **Alpine Docker échoue** : Les images Docker Alpine ont eu des erreurs TLS avec les dépôts de packages. Utiliser des images basées sur Debian si un problème similaire se produit.
- **PHPStan memory** : Nécessite `--memory-limit=512M` — prévoir des flags similaires pour `dart analyze` si le projet grossit.
- **Port 8080 peut être occupé** : L'API tourne sur `${NGINX_PORT:-8080}` — l'app Flutter en dev se connecte via `10.0.2.2:8080` (émulateur Android) ou `localhost:8080` (iOS simulator).
- **Pint auto-fix** : `./vendor/bin/pint` corrige automatiquement. Équivalent Flutter : `dart format .` auto-fixe le formatage.
- **Le backend (Story 1.1) tourne en Docker** avec MySQL 8.4, Redis 7.4, Nginx 1.27, PHP-FPM 8.4 (Debian). L'API health check est disponible sur `/api/v1/health`.

### References

- [Source: architecture.md § Starter Template Evaluation — Mobile Flutter] — Very Good CLI, rationale, commande d'init
- [Source: architecture.md § Project Organization — Flutter] — Structure features-based, règles strictes
- [Source: architecture.md § Frontend Architecture (Flutter)] — BLoC 9.0, GoRouter, Dio, Hive, flutter_secure_storage
- [Source: architecture.md § State Management Patterns — Flutter BLoC] — Conventions BLoC, sealed classes, exemples
- [Source: architecture.md § Error Handling Patterns — Flutter] — ApiResult sealed class, pattern Dio
- [Source: architecture.md § Component Boundaries — Flutter] — Règles communication inter-features
- [Source: architecture.md § Data Exchange Formats] — snake_case, centimes, ISO 8601, montants
- [Source: architecture.md § Implementation Patterns & Consistency Rules] — Naming Dart, 10 règles enforcement
- [Source: ux-design-specification.md § Design Tokens] — Couleurs, typographie, spacing, glass tokens complets
- [Source: ux-design-specification.md § Glassmorphism GPU Degradation] — 3 tiers, détection runtime
- [Source: ux-design-specification.md § Component Library] — GlassCard, GlassAppBar, GlassBottomNav specs
- [Source: ux-design-specification.md § Bottom Navigation] — 5 tabs, couleurs, comportement
- [Source: ux-design-specification.md § Dark Mode] — Navy fond, accents bleu/orange, contraste WCAG AA
- [Source: epics.md § Story 1.2] — Acceptance criteria originaux
- [Source: prd.md § NFR7] — App Flutter < 3s démarrage sur 2 Go RAM
- [Source: prd.md § NFR40] — Écrans 4,7" à 6,7"
- [Source: prd.md § NFR41] — Mode sombre iOS et Android
- [Source: prd.md § NFR49] — Conventions Flutter/Dart, linting, architecture BLoC

### Testing Requirements

**Tests à créer dans cette story :**

1. `test/core/design_system/tokens/colors_test.dart`
   - `test_brand_colors_are_correctly_defined`
   - `test_glass_colors_have_correct_opacity`
   - `test_semantic_colors_are_correctly_defined`

2. `test/core/design_system/tokens/spacing_test.dart`
   - `test_all_spacing_tokens_are_multiples_of_4`
   - `test_spacing_values_are_in_ascending_order`

3. `test/core/design_system/components/glass_card_test.dart`
   - `test_glass_card_renders_with_default_properties`
   - `test_glass_card_tier3_uses_backdrop_filter`
   - `test_glass_card_tier1_uses_solid_fallback`

4. `test/core/network/api_result_test.dart`
   - `test_api_success_holds_data`
   - `test_api_failure_holds_error_details`
   - `test_api_result_exhaustive_switch`

5. `test/app/routes/app_router_test.dart`
   - `test_router_has_5_main_routes`
   - `test_router_initial_location_is_home`

6. Vérifications post-setup :
   - `dart analyze` passe sans erreur
   - `dart format --set-exit-if-changed .` passe
   - `very_good test` passe (tous les tests)
   - `flutter run --flavor development` lance l'app sans erreur

**Pattern de test :**
- Tests miroirs : `test/` reflète la structure de `lib/`
- Widget tests pour les composants design system
- Unit tests pour les tokens, network, storage
- `mocktail` pour les mocks
- `bloc_test` pour les BLoC (dans les stories futures)

## Dev Agent Record

### Agent Model Used
Claude Opus 4.6 (claude-opus-4-6)

### Debug Log References
- Flutter upgraded from 3.32.0/Dart 3.8.0 to 3.41.1/Dart 3.11.0 (very_good_core_hooks required Dart ^3.10.0)
- Very Good CLI 0.27.0 installed
- 24 lint issues fixed over multiple iterations (imports ordering, redundant args, setters, discarded futures, const declarations)

### Completion Notes List
- All 12 tasks and ~60 subtasks completed
- `flutter analyze`: 0 issues
- `very_good test`: 60 tests, all passing
- Design system: GlassCard, GlassAppBar, GlassBottomNav with 3-tier GPU degradation
- GoRouter: StatefulShellRoute.indexedStack with 5 tabs
- Dio: 3 interceptors (auth, retry with exponential backoff, logging)
- Storage: Hive CE (TTL-based) + flutter_secure_storage (Keychain/KeyStore)
- BLoC infrastructure: AppBlocObserver (debug-only), bloc_test + mocktail ready
- Environment configs: development, staging, production

### Change Log
- 2026-02-17: Story créée par le workflow create-story — analyse exhaustive complète (architecture, UX design, web research, intelligence Story 1.1)
- 2026-02-17: Implementation complete — all 12 tasks done, 60 tests passing, 0 lint issues
- 2026-02-17: Code review (adversarial) — 10 issues found (3 HIGH, 5 MEDIUM, 2 LOW). Fixed 6 issues, created 5 action items. 72 tests passing, 0 lint issues

### File List
**Created:**
- `bookmi_app/` — Flutter project via Very Good CLI (3 flavors)
- `lib/app/env/env.dart` — Abstract environment class
- `lib/app/env/env_development.dart` — Dev environment config
- `lib/app/env/env_staging.dart` — Staging environment config
- `lib/app/env/env_production.dart` — Production environment config
- `lib/app/routes/app_router.dart` — GoRouter with StatefulShellRoute
- `lib/app/routes/route_names.dart` — Route name/path constants
- `lib/app/routes/guards/auth_guard.dart` — Placeholder auth guard
- `lib/app/view/app.dart` — MaterialApp.router with BookmiTheme
- `lib/app/view/shell_page.dart` — Shell page with GlassBottomNav
- `lib/app/app_bloc_observer.dart` — Debug-only BLoC observer
- `lib/core/core.dart` — Core barrel file
- `lib/core/design_system/design_system.dart` — Design system barrel file
- `lib/core/design_system/tokens/colors.dart` — All color tokens (brand, semantic, glass, gradients, categories)
- `lib/core/design_system/tokens/typography.dart` — Nunito 11-level TextTheme
- `lib/core/design_system/tokens/spacing.dart` — 8 spacing tokens (4px-64px)
- `lib/core/design_system/tokens/radius.dart` — Border radius tokens
- `lib/core/design_system/tokens/shadows.dart` — 3 elevation levels
- `lib/core/design_system/tokens/glass.dart` — Glassmorphism blur/opacity tokens
- `lib/core/design_system/theme/bookmi_theme.dart` — Light + Dark ThemeData
- `lib/core/design_system/theme/gpu_tier_provider.dart` — GPU tier detection (high/medium/low)
- `lib/core/design_system/components/glass_card.dart` — Adaptive GlassCard widget
- `lib/core/design_system/components/glass_app_bar.dart` — Translucent AppBar
- `lib/core/design_system/components/glass_bottom_nav.dart` — Glass bottom nav (5 tabs)
- `lib/core/network/api_client.dart` — Dio singleton with interceptors
- `lib/core/network/api_endpoints.dart` — API endpoint constants
- `lib/core/network/api_result.dart` — Sealed class ApiResult<T>
- `lib/core/network/interceptors/auth_interceptor.dart` — Bearer token injection
- `lib/core/network/interceptors/retry_interceptor.dart` — 3 retries, exponential backoff
- `lib/core/network/interceptors/logging_interceptor.dart` — Debug-only logging
- `lib/core/storage/secure_storage.dart` — flutter_secure_storage wrapper
- `lib/core/storage/local_storage.dart` — Hive CE wrapper with TTL
- `lib/core/storage/cache_manager.dart` — Cache abstraction
- `lib/features/placeholder/home_placeholder_page.dart`
- `lib/features/placeholder/search_placeholder_page.dart`
- `lib/features/placeholder/bookings_placeholder_page.dart`
- `lib/features/placeholder/messages_placeholder_page.dart`
- `lib/features/placeholder/profile_placeholder_page.dart`
- `test/core/design_system/tokens/colors_test.dart` — 23 color tests
- `test/core/design_system/tokens/spacing_test.dart` — 9 spacing tests
- `test/core/design_system/components/glass_card_test.dart` — 8 widget tests (per GPU tier + pressed state)
- `test/core/design_system/components/glass_app_bar_test.dart` — 6 widget tests (scroll blur, GPU tiers)
- `test/core/design_system/components/glass_bottom_nav_test.dart` — 5 widget tests (tabs, taps, GPU tiers)
- `test/core/network/api_result_test.dart` — 7 sealed class tests
- `test/app/routes/app_router_test.dart` — 14 router tests

**Modified:**
- `lib/bootstrap.dart` — Added Hive init, BlocObserver in debug-only
- `pubspec.yaml` — Added all dependencies (bloc, dio, go_router, hive_ce, flutter_secure_storage, google_fonts, etc.)

**Modified by Code Review:**
- `lib/core/network/api_client.dart` — Refactored to singleton pattern (factory constructor)
- `lib/core/network/interceptors/retry_interceptor.dart` — Fixed: now uses parent Dio instance for retries (was creating bare Dio without interceptors)
- `lib/core/design_system/components/glass_card.dart` — Converted to StatefulWidget, added Pressed state (AnimatedScale 0.98 + AnimatedOpacity 0.8)
- `lib/features/placeholder/*_placeholder_page.dart` — Removed hardcoded brandNavy text color (invisible in dark mode), now uses theme-inherited color
- `test/core/design_system/components/glass_card_test.dart` — Added pressed state test
