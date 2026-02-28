# BookMi v2 — Journal des modifications

Ce fichier recense toutes les actions réalisées sur le projet : fonctionnalités ajoutées, modifiées, supprimées et corrections de bugs. Les entrées sont classées par date décroissante.

---

## 2026-02-28

### Style — Landing page et pages publiques : remplacement couleur orange → bleu #1AB3FF

**Contexte :** Refonte visuelle du design public de BookMi pour aligner toutes les couleurs d'accent sur le bleu `#1AB3FF` (couleur du "Mi" dans le logo officiel), en remplacement de l'orange `#FF6B35`.

**Pages modifiées :**

| Fichier | Éléments changés |
|---------|-----------------|
| `layouts/public.blade.php` | Logo "Mi" gradient bleu, taille augmentée (1.75rem nav / 1.5rem footer), bouton "Inscription" gradient bleu, icônes contact footer, ligne de séparation footer |
| `home.blade.php` | Titre "Talents Ivoiriens" gradient bleu, bouton "Rechercher", icônes search fields, tags populaires, stat icons, `.why-tag`, `.path-card-orange` → bleu, badges CTA, underlines |
| `talents/index.blade.php` | Variables CSS `--orange`/`--orange-glow`/`--orange-dim`, tous accents UI (eyebrow badge, titre, filtres, bouton "Réserver", cat-default badge, notification form) |
| `legal/conditions-utilisation.blade.php` | Badge légal orange → bleu |

**Note :** La couleur orange `#FF6B35` du badge "Orange Money" (paiement mobile) a été conservée car elle représente la marque Orange Money, pas BookMi. L'orbe décoratif de fond du hero (opacité 0.06) est également conservé.

**Commit :** `3d3b90c`

---

### Feat — Section avis sur le profil public talent et l'espace talent web

**Fonctionnalité :** Ajout d'une section "Avis" sur deux pages web :

1. **Profil public talent** (`/talents/{slug}`) : Nouvel onglet "Avis (N)" dans la navigation du profil. Affiche les avis clients avec étoiles, commentaire, date et la réponse du talent si elle existe.

2. **Détail réservation talent** (`/talent/bookings/{id}`) : Section affichant l'avis du client et permettant au talent de répondre via un formulaire (si le statut est `confirmed` ou `completed` et qu'aucune réponse n'existe encore).

**Nouvelles routes :**
- `POST /talent/bookings/{id}/reviews/{reviewId}/reply` → `Talent\ReviewController::reply()` (nommée `talent.bookings.review.reply`)

**Nouveau fichier :**
- `app/Http/Controllers/Web/Talent/ReviewController.php` — validation, autorisation, mise à jour `reply` + `reply_at`

**Fichiers modifiés :**
- `routes/talent.php` — ajout de l'import + route reply
- `app/Http/Controllers/Web/Talent/BookingController.php` — ajout de `reviews` dans l'eager-load du `show()`
- `resources/views/talent/bookings/show.blade.php` — section avis avec formulaire de réponse
- `resources/views/web/talent/show.blade.php` — onglet "Avis" + section reviews avec réponses talent

**Commits :** `2027d44`

---

### Fix — Flutter : corrections UX section avis talents (mobile)

**Problèmes corrigés :**
1. Spinner infini sur la page de profil talent lors du chargement des avis
2. Mauvaise gestion des casts null-safe dans `ReviewModel.fromJson`
3. Formatage Dart (`dart format`) sur `booking_model.dart`

**Fichiers modifiés :**
- `bookmi_app/lib/features/talent_profile/data/models/review_model.dart`
- `bookmi_app/lib/features/talent_profile/presentation/pages/talent_profile_page.dart`
- `bookmi_app/lib/features/booking/data/models/booking_model.dart`

**Commits :** `699d12d`, `6e60c0c`, `c99b608`

---

## 2026-02-27

### Feat — Groupe E : corrections jour-J (backend + Flutter)

**Contexte :** Corrections détectées lors des tests production le 27 fév 2026.

#### E1 — Backend : SendReminderNotifications — notifier aussi le talent

**Problème :** La commande `app:send-reminder-notifications` envoyait les rappels J-7/J-2 uniquement au client. Le talent ne recevait aucune notification.

**Solution :** Ajout d'un second `SendPushNotification::dispatch()` pour `$booking->talentProfile->user` après l'envoi au client.

**Fichier :** `app/Console/Commands/SendReminderNotifications.php`

---

#### E2 — Backend : BookingRequestResource — exposer les flags review

**Ajout :** Deux nouveaux champs dans `BookingRequestResource::toArray()` :
- `has_client_review` — `true` si un avis `client_to_talent` existe pour cette réservation
- `has_talent_review` — `true` si un avis `talent_to_client` existe

**Fichier :** `app/Http/Resources/BookingRequestResource.php`

---

#### E3 — Flutter : BookingModel — champs hasClientReview / hasTalentReview

**Ajout :** Deux nouveaux champs booléens dans `BookingModel` :
- `hasClientReview` (JSON: `has_client_review`, défaut `false`)
- `hasTalentReview` (JSON: `has_talent_review`, défaut `false`)
- Mise à jour de `fromJson()`, `copyWith()`, `toJson()` et fixtures de tests

**Fichiers :** `bookmi_app/lib/features/booking/data/models/booking_model.dart`, `test/...`

---

#### E4 — Flutter : BookingRepository — confirmDelivery()

**Ajout :** Nouvelle méthode `Future<void> confirmDelivery(int bookingId)` → `POST /api/v1/booking_requests/{bookingId}/confirm_delivery`.

**Fichier :** `bookmi_app/lib/features/booking/data/repositories/booking_repository.dart`

---

#### E5 — Flutter : BookingDetailPage — boutons d'action contextuels

**Ajout :** 4 boutons conditionnels selon le rôle et le statut de la réservation :

| Condition | Bouton | Action |
|-----------|--------|--------|
| talent + status `paid` | "Suivre la prestation" | Navigation vers `TrackingPage` |
| client + status `paid` | "Confirmer la fin de prestation" | `confirmDelivery()` → reload |
| client + status `confirmed/completed` + pas d'avis | "Laisser un avis" | Navigation `EvaluationPage` (type: client_to_talent) |
| talent + status `confirmed/completed` + pas d'avis | "Évaluer le client" | Navigation `EvaluationPage` (type: talent_to_client) |

**Fichier :** `bookmi_app/lib/features/booking/presentation/pages/booking_detail_page.dart`

---

### Fix — Flutter : formatage Dart sur booking_detail_page

`dart format` appliqué suite à l'implémentation du Groupe E.

**Commit :** `57026c8`

---

### Feat — Flutter mobile : wording "Réponse de l'artiste" → "Réponse du talent"

**Contexte :** Uniformisation du vocabulaire dans toute l'app mobile.

**Fichier :** `bookmi_app/lib/features/talent_profile/presentation/widgets/reviews_section.dart:159`

---

## 2026-02-26 (suite — non présent dans l'ancienne version du changelog)

### Feat — Groupes A/B/C : 13 fonctionnalités MVP (web client, talent, admin)

**Contexte :** Implémentation des fonctionnalités MVP planifiées qui n'avaient pas d'UI web. Toute la couche backend (modèles, services, routes API) existait déjà. Uniquement création de vues et contrôleurs web.

#### Groupe A — Client Web

**A1. Téléchargement contrat/reçu depuis l'espace client**
- Routes : `GET /bookings/{id}/contract` et `GET /bookings/{id}/receipt` (nommées `client.bookings.contract`, `client.bookings.receipt`)
- Génère un UUID token via `cache()->put('pdf_download:{token}', ...)` TTL 10 min → redirect vers `/api/v1/dl/{token}`
- Boutons visibles si status `paid|confirmed|completed`
- Fichiers : `app/Http/Controllers/Web/Client/BookingController.php`, `resources/views/client/bookings/show.blade.php`, `routes/client.php`

**A2. Soumission d'avis depuis le client web**
- Route : `POST /bookings/{id}/review` → `Client\ReviewController::store()`
- Vérifications : booking appartient au client, status `confirmed|completed`, pas encore évalué
- Formulaire étoiles (Alpine.js radio) + commentaire optionnel
- Fichiers : `app/Http/Controllers/Web/Client/ReviewController.php` *(créé)*, `resources/views/client/bookings/show.blade.php`, `routes/client.php`

**A3. Centre de notifications web client**
- Routes : `GET /notifications`, `POST /notifications/{id}/read`, `POST /notifications/read-all`
- Liste paginée des `PushNotification` du client, badge rouge "non lu"
- Badge compteur dans la sidebar de navigation
- Fichiers : `app/Http/Controllers/Web/Client/NotificationController.php` *(créé)*, `resources/views/client/notifications/index.blade.php` *(créé)*, `routes/client.php`

#### Groupe B — Talent Web

**B1. Timeline suivi jour-J (lecture seule — web talent + client)**
- Affichage des `$booking->trackingEvents` en lecture seule (les mises à jour restent mobile-only)
- Visible si status `paid|confirmed|completed` et au moins un event
- Fichiers : `resources/views/talent/bookings/show.blade.php`, `resources/views/client/bookings/show.blade.php`

**B2. Attestation de revenus annuelle**
- Routes : `GET /revenue-certificate`, `GET /revenue-certificate/download?year=2025`
- Génère le PDF via DomPDF avec `resources/views/pdf/revenue_certificate.blade.php` existante
- Lien depuis la page "Mes Revenus"
- Fichiers : `app/Http/Controllers/Web/Talent/RevenueCertificateController.php` *(créé)*, `resources/views/talent/revenue-certificate/index.blade.php` *(créé)*, `routes/talent.php`

**B3. Configuration auto-réponse dans le profil talent**
- Champs : toggle `auto_reply_is_active` + textarea `auto_reply_message` (max 500 chars)
- Les colonnes existent déjà dans `TalentProfile` (no migration)
- Fichiers : `resources/views/talent/profile/edit.blade.php`, `app/Http/Controllers/Web/Talent/ProfileController.php`

**B4. Indicateur de niveau talent sur le dashboard**
- Badge niveau actuel + barre de progression + nombre de réservations vs seuil prochain niveau
- Seuils : Nouveau=0, Confirmé=6, Populaire=21, Elite=51
- Fichiers : `resources/views/talent/dashboard.blade.php`, `app/Http/Controllers/Web/Talent/DashboardController.php`

#### Groupe C — Admin Filament

**C1. Page Rapports/Export**
- Navigation groupe "Finances", sort 15
- Export CSV financier + export transactions avec filtre date
- Fichiers : `app/Filament/Pages/ReportsPage.php` *(créé)*, vue associée *(créée)*

**C2. Page Paramètres plateforme (lecture seule)**
- Affiche les valeurs de `config/bookmi.php` (commission, seuils niveaux, délais)
- Fichiers : `app/Filament/Pages/PlatformSettingsPage.php` *(créé)*, vue associée *(créée)*

**C3. SLA litiges + action Remboursement dans BookingRequestResource**
- Colonne `dispute_age` (rouge si > 48h) dans la vue filtrée sur status `disputed`
- Action "Rembourser" avec modal de confirmation → `RefundService::processRefund()`
- Badge nav : count bookings `disputed`
- Fichier : `app/Filament/Resources/BookingRequestResource.php`

**C4. Page Détection fraude/doublons**
- Navigation groupe "Sécurité", sort 20
- Sections : doublons téléphone (GROUP BY phone HAVING COUNT > 1) + comptes suspects
- Bouton "Suspendre" → `AdminUserController::suspend()`
- Fichiers : `app/Filament/Pages/FraudDetectionPage.php` *(créé)*, vue associée *(créée)*

**Commit :** `2b958e2`

---

### Feat — Groupe D : Niveau talent Flutter + Deep linking mobile

#### D1. Indicateur de niveau talent (Flutter)

**Widget :** `TalentLevelCard` dans la page dashboard/profil talent :
- Badge niveau actuel (Nouveau/Confirmé/Populaire/Elite) avec couleur distinctive
- Barre de progression vers le niveau suivant
- Données depuis `GET /talent_profiles/me` (`talent_level` + `total_bookings`)

**Fichiers modifiés :** page dashboard/profil talent Flutter

#### D2. Deep linking mobile

- Android : `intent-filter` avec `autoVerify=true` pour `bookmi.click` dans `AndroidManifest.xml`
- iOS : associated domains `applinks:bookmi.click` dans `Info.plist`
- `app_router.dart` : handler `https://bookmi.click/talent/{slug}` → `TalentProfilePage`
- Backend : routes `/.well-known/assetlinks.json` et `apple-app-site-association` dans `routes/web.php`

**Commit :** `354de90`

---

### Feat — Refonte UX page paiement talent (web Blade)

**Contexte :** Refonte complète de la page de gestion des paiements dans l'espace talent web.

**Commit :** `fd88817`

---

### Chore — Mise à jour Logo.png

**Action :** Mise à jour du fichier `bookmi_app/assets/images/Logo.png` et suppression de `Logo-removebg-preview.png`.

**Commit :** `c0bfa8b`

---

## 2026-02-26

### Feat — Notifications : push FCM + in-app vers le talent sur toutes les actions admin

**Problème :** Seul l'e-mail était envoyé au talent lors des actions admin (validation/rejet de compte de paiement, changements de statut des reversements). Le push FCM et la notification in-app (cloche) manquaient.

**Solution :** Ajout de `SendPushNotification::dispatch()` après chaque `$user->notify()` dans les deux ressources concernées.

**Tableau récapitulatif :**

| Flow | Destinataire | Email | Push FCM + in-app |
|------|-------------|-------|-------------------|
| Talent soumet un compte de paiement | Admin | ✅ | ✅ (déjà présent) |
| Admin **valide** le compte | Talent | ✅ | ✅ *(ajouté)* |
| Admin **refuse** le compte | Talent | ✅ | ✅ *(ajouté)* |
| Talent soumet une demande de reversement | Admin | ✅ | ✅ (déjà présent) |
| Admin **approuve** la demande | Talent | ✅ | ✅ *(ajouté)* |
| Admin marque **en cours** | Talent | ✅ | ✅ *(ajouté)* |
| Admin marque **complété** | Talent | ✅ | ✅ *(ajouté)* |
| Admin **rejette** la demande | Talent | ✅ | ✅ *(ajouté)* |

**Types FCM et deep-links :**
- `payout_method_verified` → `/talent-portal/withdrawal-request`
- `payout_method_rejected` → `/talent-portal/payout-method`
- `withdrawal_approved` → `/talent-portal/withdrawal-request`
- `withdrawal_processing` → `/talent-portal/withdrawal-request`
- `withdrawal_completed` → `/talent-portal/withdrawal-request`
- `withdrawal_rejected` → `/talent-portal/withdrawal-request`

**Fichiers modifiés :**
- `app/Filament/Resources/PayoutMethodResource.php`
- `app/Filament/Resources/WithdrawalRequestResource.php`

---

### Feat — Traçabilité complète des comptes de paiement (payout_method_status)

**Problème :** Lors du rejet d'un compte de paiement, les données étaient effacées (`payout_method = null`) → historique perdu. Seuls les comptes en attente étaient listés dans `/admin/payout-methods`.

**Solution :** Ajout d'un statut explicite `payout_method_status` (pending / verified / rejected) sur `TalentProfile` et conservation des données lors d'un rejet.

**Détails :**
- Migration : `2026_02_26_120000_add_payout_method_status_to_talent_profiles.php`
  - Nouvelles colonnes : `payout_method_status` (string nullable), `payout_method_rejection_reason` (text nullable)
  - Backfill automatique des lignes existantes
- `PayoutMethodResource` :
  - Liste **tous** les comptes soumis (toutes statuts), pas seulement les en attente
  - Badge de statut coloré : orange (en attente) / vert (validé) / rouge (refusé)
  - Filtre par statut dans le tableau
  - Badge nav : compte uniquement les **en attente**
  - Action **Refuser** : ne plus effacer les données — marque `rejected` + stocke le motif
  - Action **Valider** : marque `verified` + efface le motif de refus précédent
  - Form view : affiche le statut, la date de validation et le motif de refus
- `PayoutMethodPage.php` + `TalentProfileController` : définissent `payout_method_status = 'pending'` à chaque nouvelle soumission
- `WithdrawalRequestResource` : déjà correct — historique complet, aucun changement nécessaire

**Fichiers modifiés/créés :**
- `database/migrations/2026_02_26_120000_add_payout_method_status_to_talent_profiles.php` *(créé)*
- `app/Models/TalentProfile.php`
- `app/Filament/Resources/PayoutMethodResource.php`
- `app/Filament/Tenant/Pages/PayoutMethodPage.php`
- `app/Http/Controllers/Api/V1/TalentProfileController.php`

---

### Fix — Admin : champs vides dans les pages ViewRecord

**Problème racine :** Les pages `ViewRecord` de Filament remplissent le formulaire via `$record->toArray()` qui expose les relations eager-loadées en snake_case (ex. `talent_profile`), alors que les champs formulaire utilisent du camelCase dot-notation (ex. `talentProfile.stage_name`). La correspondance échouait silencieusement → tous les champs de relation apparaissaient vides.

**Solution :** Ajout de `mutateFormDataBeforeFill()` dans chaque page `ViewRecord` concernée pour injecter explicitement la relation sous la clé camelCase attendue par le formulaire.

**Pages corrigées (4 au total) :**

| Page | Relations injectées | Champs concernés |
|------|--------------------|--------------------|
| `TalentProfileResource/Pages/ViewTalentProfile` | `talentProfile` | stage_name, city, talent_level, is_verified, payout_method, payout_details, available_balance, payout_method_verified_at |
| `PayoutMethodResource/Pages/ViewPayoutMethod` | `user` | user.first_name, user.last_name, user.email |
| `BookingRequestResource/Pages/ViewBookingRequest` | `client`, `talentProfile` | client.email, talentProfile.stage_name |
| `WithdrawalRequestResource/Pages/ViewWithdrawalRequest` | `talentProfile` + `talentProfile.user` (2 niveaux) | talentProfile.stage_name, talentProfile.user.email |

**Pages non concernées :**
- `ViewActivityLog`, `ViewAdminAlert`, `ViewAdminWarning`, `ViewIdentityVerification`, `ViewReview` — utilisent un **infolist** (`hasInfolist(): true`) : Filament résout le dot-notation directement depuis le modèle Eloquent dans les infolists.
- `ViewClient`, `ViewUser` — aucun champ dot-notation, uniquement des attributs directs.

---

### Fix — Admin : affichage robuste des coordonnées Wave dans PayoutMethodResource

**Problème :** La colonne `payout_details` du tableau `/admin/payout-methods` affichait `—` si la clé JSON n'était ni `phone` ni `account_number`.

**Solution :** Ajout d'un fallback `implode(array_values())` dans `formatStateUsing` pour afficher toutes les valeurs du JSON quelle que soit la clé.

**Fichier :** `app/Filament/Resources/PayoutMethodResource.php`

---

### Fix — Style : Pint sur TalentProfileController et PayoutMethodAddedNotification

**Fichiers :** `app/Http/Controllers/Api/V1/TalentProfileController.php`, `app/Notifications/PayoutMethodAddedNotification.php`

**Règles corrigées :** `braces_position`, `new_with_parentheses`

---

## 2026-02-25

### Feat — Admin : page de validation des comptes de paiement (PayoutMethodResource)

**Fonctionnalité :** Nouvelle page admin `/admin/payout-methods` permettant de valider ou refuser les comptes de paiement soumis par les talents.

**Détails :**
- Navigation : groupe "Finance", badge avec le nombre de comptes en attente
- Requête filtrée : `payout_method IS NOT NULL` ET `payout_method_verified_at IS NULL`
- Action **Valider** : met à jour `payout_method_verified_at` et `payout_method_verified_by`, envoie `PayoutMethodVerifiedNotification` par e-mail au talent
- Action **Refuser** : efface les données de paiement, envoie `PayoutMethodRejectedNotification` par e-mail au talent avec le motif

**Fichiers créés :**
- `app/Filament/Resources/PayoutMethodResource.php`
- `app/Filament/Resources/PayoutMethodResource/Pages/ListPayoutMethods.php`
- `app/Filament/Resources/PayoutMethodResource/Pages/ViewPayoutMethod.php`
- `app/Notifications/PayoutMethodVerifiedNotification.php`
- `app/Notifications/PayoutMethodRejectedNotification.php`

---

### Feat — Service centralisé de notifications admin (AdminNotificationService)

**Fonctionnalité :** Centralisation de toutes les notifications envoyées aux administrateurs (e-mail + push in-app FCM).

**Méthodes :**
- `payoutMethodAdded(TalentProfile)` — notifie l'admin qu'un talent a soumis/modifié son compte de paiement
- `withdrawalRequested(WithdrawalRequest)` — notifie l'admin d'une nouvelle demande de reversement

**Impact :** 6 call-sites refactorisés (API controllers, web controllers, Filament pages) pour utiliser ce service.

**Fichier créé :** `app/Services/AdminNotificationService.php`

**Fichiers modifiés :**
- `app/Filament/Tenant/Pages/PayoutMethodPage.php`
- `app/Filament/Tenant/Pages/WithdrawalRequestTalentPage.php`
- `app/Http/Controllers/Web/Talent/PaiementController.php`
- `app/Http/Controllers/Api/V1/TalentProfileController.php`
- `app/Http/Controllers/Api/V1/WithdrawalRequestController.php`
- `app/Notifications/PayoutMethodAddedNotification.php` (URL mise à jour vers `/admin/payout-methods`)

---

### Feat — Flutter : réorganisation du menu profil talent

**Fonctionnalité :** Réorganisation de l'ordre des items du menu profil pour les talents et masquage de "Mes talents favoris" en mode talent.

**Nouvel ordre (talent) :**
1. Informations personnelles
2. Description & Réseaux sociaux
3. Gestion portfolio
4. Gestion packages
5. Vérification d'identité
6. Statistiques talent
7. Mes revenus
8. Moyens de paiement
9. Aide et support

**"Mes talents favoris"** : visible uniquement pour les clients (masqué pour les talents).

**Fichier modifié :** `bookmi_app/lib/features/profile/presentation/pages/profile_page.dart`

---

## Règles d'architecture établies

### Filament ViewRecord — champs dot-notation dans les formulaires

Lorsqu'une page `ViewRecord` utilise un **formulaire** (pas un infolist) avec des champs dot-notation pointant vers des relations (ex. `TextInput::make('user.email')`), il faut systématiquement ajouter `mutateFormDataBeforeFill()` pour injecter les données de la relation dans le tableau de données sous la clé camelCase correspondante.

```php
protected function mutateFormDataBeforeFill(array $data): array
{
    /** @var MyModel $record */
    $record = $this->record;

    if ($record->relation) {
        $data['relation'] = $record->relation->toArray();
    }

    return $data;
}
```

Pour les relations imbriquées (ex. `talentProfile.user.email`) :
```php
$talentProfileData = $talentProfile->toArray();
$talentProfileData['user'] = $talentProfile->user->toArray();
$data['talentProfile'] = $talentProfileData;
```

Les pages avec `hasInfolist(): true` ne sont **pas** concernées — Filament résout le dot-notation directement depuis le modèle Eloquent dans les infolists.

---

### Notifications admin

Toujours passer par `AdminNotificationService` pour notifier les admins — ne jamais appeler directement `$admin->notify()` ou `SendPushNotification::dispatch()` en dehors de ce service.

### Notifications talent — pattern complet

Chaque action admin qui affecte un talent doit envoyer **les trois canaux** : e-mail + notification in-app (cloche) + push FCM. Pattern à respecter :

```php
$user = $record->user; // ou $record->talentProfile?->user
if ($user) {
    // 1. E-mail
    $user->notify(new MyNotification($record));

    // 2. Push in-app + FCM
    SendPushNotification::dispatch(
        $user->id,
        'Titre lisible',
        'Corps du message contextuel.',
        [
            'type' => 'event_type',   // snake_case, utilisé par l'app mobile pour le routing
            'url'  => '/talent-portal/...',
        ],
    );
}
```

**Types FCM définis (routing côté app mobile) :**
- `payout_method_verified` — compte de paiement validé
- `payout_method_rejected` — compte de paiement refusé
- `withdrawal_approved` — demande de reversement approuvée
- `withdrawal_processing` — reversement en cours de traitement
- `withdrawal_completed` — reversement effectué
- `withdrawal_rejected` — demande de reversement refusée
