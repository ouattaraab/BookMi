# Story 4.10: Écran paiement Flutter (mobile)

Status: done

## Story

As a client,
I want payer ma réservation via Mobile Money directement dans l'app,
So that je finalise ma réservation sans quitter l'application BookMi.

**Functional Requirements:** FR-PAY-MOBILE-1
**Non-Functional Requirements:** UX-FEEDBACK-1 (haptic), UX-ANIM-1 (CelebrationOverlay), UX-SECURE-1 (GlassShield)

## Acceptance Criteria (BDD)

**AC1 — Étape 4 dans le stepper de réservation**
**Given** un client au step 3 (Récap) du booking flow
**When** il appuie sur "Confirmer la réservation"
**Then** la réservation est créée via API (`POST /api/v1/booking_requests`)
**And** le stepper avance automatiquement à l'étape 4 "Paiement"
**And** le bouton Retour est masqué à l'étape 4 (la réservation est déjà créée)

**AC2 — Sélection de l'opérateur**
**Given** un client à l'étape Paiement
**When** il sélectionne un opérateur (Orange Money, Wave, MTN MoMo, Moov Money)
**Then** la tuile sélectionnée s'anime (couleur de l'opérateur, bordure)
**And** un retour haptique `selectionClick` est déclenché (UX-FEEDBACK-1)

**AC3 — Saisie du numéro Mobile Money**
**Given** un client à l'étape Paiement
**When** il saisit son numéro dans le champ filtré (chiffres + `+` seulement)
**Then** le bouton "Payer maintenant" est activé quand opérateur sélectionné ET numéro ≥ 8 chiffres

**AC4 — Paiement initié**
**Given** opérateur sélectionné et numéro valide
**When** le client appuie sur "Payer maintenant"
**Then** `POST /api/v1/payments/initiate` est appelé
**And** un `CelebrationOverlay` (fade + scale élastique) s'affiche pendant 2,5s
**And** après disparition, la bottom sheet se ferme

**AC5 — GlassShield visuel**
**Given** l'étape Paiement affichée
**When** le formulaire est rendu
**Then** le formulaire est encadré par un `GlassShield` avec icône bouclier et label "Paiement sécurisé"

## Implementation Notes

### Flutter — Nouveaux composants design system

- `core/design_system/components/glass_shield.dart` — `GlassShield` : conteneur glassmorphique avec header vert bouclier, wraps le form de paiement
- `core/design_system/components/mobile_money_selector.dart` — `MobileMoneySelector` + `MobileMoneyOperator` : grid 2×2 de tuiles `AnimatedContainer`
- `core/design_system/components/celebration_overlay.dart` — `CelebrationOverlay` : overlay plein écran, fade + scale (`Curves.elasticOut`), auto-dismiss, `static show()` retourne `OverlayEntry`
- `features/booking/presentation/widgets/step4_payment.dart` — `Step4Payment` : compose GlassShield + MobileMoneySelector + TextField téléphone

### Flutter — Intégration BLoC

**États ajoutés à `BookingFlowState` :**
- `BookingFlowPaymentSubmitting` — requête initiation en cours
- `BookingFlowPaymentSuccess` — paiement initié avec succès

**Événement ajouté à `BookingFlowEvent` :**
- `BookingFlowPaymentInitiated(bookingId, paymentMethod, phoneNumber)`

**`BookingFlowBloc` :** Nouveau handler `_onPaymentInitiated` appelle `BookingRepository.initiatePayment()`.

**`BookingRepository` :** Méthode `initiatePayment(bookingId, paymentMethod, phoneNumber)` → `POST /api/v1/payments/initiate`.

**`ApiEndpoints` :** `paymentsInitiate`, `meFinancialDashboard`, `mePayouts` ajoutés.

**`BookingFlowSheet` :**
- `_totalSteps = 4` (était 3)
- Étape 2 → "Confirmer la réservation" → soumet la réservation → listener avance à l'étape 4
- Étape 3 → "Payer maintenant" → initie le paiement → `CelebrationOverlay` → pop
- Gradient CTA (orange) pour étapes 2 et 3

### Code review fixes

- **H1 (duplication de réservation) :** Le bouton Retour est masqué à l'étape 4. Sans ce fix, revenir à l'étape Récap et cliquer "Confirmer" aurait créé une deuxième réservation.
- **M1 (context disposed) :** `onDismiss` de `CelebrationOverlay` vérifie `context.mounted` avant `Navigator.of(context).pop()`.

### Tests

**Flutter :** `test/features/booking/bloc/booking_flow_bloc_test.dart` — 3 tests ajoutés (payment success, failure, guard doublon)
