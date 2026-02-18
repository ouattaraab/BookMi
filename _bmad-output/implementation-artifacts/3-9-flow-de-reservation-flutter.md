# Story 3.9 — Flow de réservation Flutter

## Statut : ✅ Done

**Epic :** 3 — Réservation
**Sprint :** Sprint 3
**Commit :** f3fb625

---

## Objectif

Implémenter le flow de réservation côté Flutter : un bottom sheet modal en 3 étapes permettant au client de sélectionner un package, choisir une date/lieu, et valider un devis transparent avant soumission.

---

## Architecture

### Couche Données

**`booking_model.dart`** — `BookingModel` immutable avec `fromJson` :
- Champs : `id`, `status`, `clientName`, `talentStageName`, `packageName`, `packageType`, `eventDate`, `eventLocation`, `cachetAmount`, `commissionAmount`, `totalAmount`, `isExpress`, `contractAvailable`, champs optionnels (`message`, `rejectReason`, `refundAmount`, `cancellationPolicyApplied`, `devisMessage`)
- `==` et `hashCode` basés sur `id` + `status`

**`booking_repository.dart`** — `BookingRepository` :
- `getBookings(status?, cursor?)` → `ApiResult<BookingListResponse>` avec pagination curseur
- `createBooking(...)` → `ApiResult<BookingModel>`
- `getBooking(id)` → `ApiResult<BookingModel>`
- Cache Hive (clé `bookings_accepted_paid`) : TTL 7 jours, fallback sur `connectionError`/`connectionTimeout`
- Constructeur `forTesting(dio:, localStorage:)` pour tests

**`api_endpoints.dart`** — Nouveaux endpoints :
```dart
static String bookingRequest(int id) => '/booking_requests/$id';
static String bookingContract(int id) => '/booking_requests/$id/contract';
static String talentCalendar(String talentId) => '/talents/$talentId/calendar';
```

### Couche BLoC

**`BookingFlowBloc`** — Gère la soumission API :
- `BookingFlowSubmitted` → `[Submitting, Success|Failure]`
- `BookingFlowReset` → `Initial`
- Guard `if (state is BookingFlowSubmitting) return;` (protection concurrence)

**États :** `BookingFlowInitial` | `BookingFlowSubmitting` | `BookingFlowSuccess(booking)` | `BookingFlowFailure(code, message)`

### Couche Présentation

**`StepperProgress`** — Indicateur 4 étapes horizontal :
- Étiquettes : `['Package', 'Date', 'Récap', 'Paiement']`
- `_StepDot` animé (22→28px) : complet=bleu+check, actif=orange, inactif=transparent
- Connecteur entre étapes coloré si étape complète

**`Step1PackageSelection`** — Sélection de package :
- Liste `ServicePackageCard` avec highlight bleu + ombre glow sur sélection
- Animation `AnimatedContainer` 150ms
- Extraction d'ID robuste : `attrs['id'] ?? pkg['id'] ?? index`

**`Step2DateLocation`** — Date + Lieu :
- `_CalendarPicker` : grille 7 colonnes, navigation mensuelle bornée (J0 → J0+24 mois)
- Jours passés et bloqués grisés/barrés
- Champ lieu `TextField` avec style glass
- `didUpdateWidget` sur le `TextEditingController` location

**`Step3Recap`** — Devis transparent :
- Lignes : Package (cachet), Commission 15%, Date, Lieu, **Total**
- Toggle express (si `enableExpress`)
- Champ message optionnel avec `didUpdateWidget` sur le controller
- Bouclier sécurité : "Paiement sécurisé · Contrat auto · Remboursement garanti"

**`BookingFlowSheet`** — Bottom sheet orchestrateur :
- `BookingFlowSheet.show(context, repository:, talentProfileId:, ...)` — factory statique
- 92% de hauteur écran, `gradientHero`, `BookmiRadius.sheetBorder`
- Navigation arrière (back/close), `StepperProgress` + titre dynamique
- `_BottomCta` : bouton désactivé si `!_canProceed || isSubmitting`, gradient CTA/Brand selon étape
- `BlocListener` : SnackBar succès (vert) ou erreur (rouge)
- Dévis local calculé : `commissionAmount = (cachetAmount * 0.15).round()`, `totalAmount = cachetAmount + commissionAmount`

### Intégration

**`TalentProfilePage`** — CTA "Réserver" câblé :
```dart
onTap: () => BookingFlowSheet.show(
  context,
  repository: context.read<BookingRepository>(),
  talentProfileId: talentId,
  talentStageName: stageName,
  servicePackages: servicePackages,
  enableExpress: profile['enable_express_booking'] as bool? ?? false,
),
```

**`app_router.dart`** — `TalentProfilePage` route enveloppée dans `MultiRepositoryProvider` pour injecter `BookingRepository`.

---

## Tests

**`booking_flow_bloc_test.dart`** — 4 tests :
- État initial = `BookingFlowInitial`
- `[Submitting, Success]` sur soumission réussie
- `[Submitting, Failure]` sur erreur API
- État `Submitting` immédiatement après add (UI peut désactiver le CTA)
- Réinitialisation à `Initial` après `Reset`

---

## Corrections Code Review

| N° | Fichier | Description | Fix |
|----|---------|-------------|-----|
| 1 | `step3_recap.dart` | Absence `didUpdateWidget` sur `_messageController` | Ajout `didUpdateWidget` |
| 2 | `step2_date_location.dart` | Navigation calendrier non bornée dans le passé | Méthodes `_canNavigatePrevious`/`_canNavigateNext`, bouton `null` si hors bornes |
| 3 | `step2_date_location.dart` | Absence `didUpdateWidget` sur `_locationController` | Ajout `didUpdateWidget` |
