# Story 3.10 — Écran Réservations Flutter

## Statut : ✅ Done

**Epic :** 3 — Réservation
**Sprint :** Sprint 3
**Commit :** f3fb625

---

## Objectif

Implémenter l'écran "Mes réservations" côté Flutter : liste paginée avec onglets par statut, et page de détail complète avec dévis, informations d'annulation et accès au contrat PDF.

---

## Architecture

### BLoC — BookingsListBloc

**`bookings_list_event.dart`** :
- `BookingsListFetched({String? status})` — fetch/refetch avec filtrage par statut
- `BookingsListNextPageFetched()` — charge la page suivante (curseur)

**`bookings_list_state.dart`** — États sealed :
- `BookingsListInitial` — état initial
- `BookingsListLoading` — chargement initial
- `BookingsListLoaded({bookings, hasMore, nextCursor, activeStatus})` — données disponibles
  - `copyWith(...)` avec `String? Function()? activeStatus` pour valeurs nullables
  - `==` et `hashCode` personnalisés avec `listEquals`
- `BookingsListLoadingMore extends BookingsListLoaded` — pagination en cours (affiche données existantes + spinner)
- `BookingsListFailure({code, message})` — erreur

**`bookings_list_bloc.dart`** :
- `_onFetched` → émet `Loading` puis `Loaded|Failure`
- `_onNextPageFetched` → garde si `LoadingMore` ou pas `Loaded`, ignore si `!hasMore`
- Émet `LoadingMore` (conserve données existantes), puis `Loaded` fusionné ou revert si échec

### Widgets

**`BookingCard`** — Carte glassmorphism :
- `GlassCard` avec `_StatusIcon` (cercle coloré), `_StatusBadge` (pill coloré), montant via `TalentCard.formatCachet()`
- Badge Express (⚡ orange) si `booking.isExpress`
- Formatage date local `_formatDate(isoDate)` : `"2026-06-15"` → `"15 juin. 2026"`
- Statuts : `pending`=warning, `accepted|paid|confirmed`=success, `completed`=brandBlueLight, `cancelled`=error, `disputed`=ctaOrange

**`BookingCardSkeleton`** — Shimmer placeholder :
- `Shimmer.fromColors` correspondant au layout `BookingCard`
- Cercle icon + 5 boîtes shimmer

### Pages

**`BookingsPage`** — Écran principal :
- `BlocProvider<BookingsListBloc>` avec fetch initial
- `TabController` 4 onglets : "En attente" (`pending`), "Confirmées" (`accepted`), "Passées" (`completed`), "Annulées" (`cancelled`)
- Listener `_onTabChanged` → dispatch `BookingsListFetched(status: tabStatus)`
- Fond `gradientHero`, `AppBar` transparent

**`_BookingsTab`** — Tab individuel :
- `BlocBuilder` sur `BookingsListBloc`
- `ListView.separated` + `NotificationListener` pour pagination (déclenchée à maxScrollExtent - 200px)
- `RefreshIndicator` : `future = stream.firstWhere(...)` souscrit AVANT l'add, évite la race condition deadlock
- États : Skeletons × 4, liste vide (icône `event_busy`), erreur + retry

**`BookingDetailPage`** — Détail :
- Accepte `preloaded: BookingModel?` pour affichage instantané sans requête
- `_fetch()` : repository capturé avant `await` (fix race condition `mounted`)
- `_buildContent()` : null-safe sur `_booking` (plus de `!`)
- Sections : StatusHeader (cercle + pill), Détails événement, Dévis (cachet/commission/total), Annulation (si `refundAmount != null`), Contrat PDF (si `contractAvailable`)
- Pull-to-refresh

### Routing

**`route_names.dart`** :
```dart
static const bookingDetail = 'bookingDetail';  // RouteNames
static const bookingDetail = 'booking/:id';     // RoutePaths
```

**`app_router.dart`** — Branche bookings :
```dart
GoRoute(
  path: RoutePaths.bookings,
  builder: (_) => RepositoryProvider.value(value: bookingRepo, child: BookingsPage()),
  routes: [
    GoRoute(
      path: RoutePaths.bookingDetail,
      parentNavigatorKey: rootNavigatorKey,
      builder: (_, state) => BookingDetailPage(
        bookingId: int.parse(state.pathParameters['id']!),
        preloaded: state.extra as BookingModel?,
      ),
    ),
  ],
),
```

**`app.dart`** — Initialisation :
```dart
final bookingsBox = await Hive.openBox<dynamic>('bookings');
final bookingLocalStorage = LocalStorage(box: bookingsBox);
final bookingRepo = BookingRepository(apiClient: apiClient, localStorage: bookingLocalStorage);
```

---

## Tests

**`bookings_list_bloc_test.dart`** — 5 tests :
- État initial = `BookingsListInitial`
- `[Loading, Loaded]` sur fetch réussi (2 bookings)
- `[Loading, Failure]` sur erreur API
- `[Loading, Loaded, LoadingMore, Loaded merged]` sur next page
- Revert à Loaded sur échec next page (données conservées)
- Ignore `NextPageFetched` si `hasMore = false`

**`booking_card_test.dart`** — 7 tests :
- Affiche nom talent et package
- Badge "En attente" pour statut `pending`
- Badge "Confirmée" pour statut `accepted`
- Badge "Express" si `isExpress = true`
- Pas de badge "Express" si `isExpress = false`
- Déclenche `onTap` sur tap
- Date formatée correctement (`"2026-06-15"` → `"15 juin. 2026"`)

---

## Corrections Code Review

| N° | Fichier | Description | Fix |
|----|---------|-------------|-----|
| 1 | `bookings_page.dart` | `RefreshIndicator` deadlock si état déjà Loaded | `future = stream.firstWhere(...)` souscrit AVANT `bloc.add(...)` |
| 2 | `booking_detail_page.dart` | `context.read()` après gap async (race condition) | Repository capturé avant `await` |
| 3 | `booking_detail_page.dart` | Déréférencement `_booking!` sans guard | `_booking` null-safe, `if (booking == null) return SizedBox` |
