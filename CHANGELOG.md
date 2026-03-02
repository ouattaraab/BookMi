# BookMi v2 — Journal des modifications

Ce fichier recense toutes les actions réalisées sur le projet : fonctionnalités ajoutées, modifiées, supprimées et corrections de bugs. Les entrées sont classées par date décroissante.

---

## 2026-03-02

### Fix — Firebase : configuration credentials en production

**Problème :** Les push notifications FCM échouaient silencieusement avec l'erreur `Unable to determine the Firebase Project ID`. Le fichier de compte de service Firebase existait sur le serveur mais la variable `FIREBASE_CREDENTIALS` n'était pas configurée dans le `.env` actif (`/home/u726808002/bookmi_app/.env`).

**Solution :**
1. Ajout de `FIREBASE_CREDENTIALS=/home/u726808002/bookmi_app/storage/app/firebase-credentials.json` dans le `.env` de production et dans `.env.prod` local
2. Publication de `config/firebase.php` dans le dépôt — sans ce fichier, `config:cache` ignore la config du vendor et `env('FIREBASE_CREDENTIALS')` retourne `null`
3. Suppression du fichier manuellement publié sur le serveur (fichier non suivi git qui bloquait le `git pull`)
4. Redéploiement manuel + `php artisan optimize`

**Résultat :** `Kreait\Firebase\Messaging` se résout correctement, 0 erreur Firebase dans les logs post-déploiement.

**Fichiers modifiés :**
- `config/firebase.php` *(créé — publié depuis vendor `kreait/laravel-firebase`)*
- `bookmi/.env.prod` — ajout `FIREBASE_CREDENTIALS`

**Commit :** `676d323`

---

### Fix — Web : 3 erreurs 500 production (apostrophes + travel_cost null)

**Contexte :** Trois pages retournaient HTTP 500. Erreurs diagnostiquées via SSH sur les logs Laravel de production.

#### Fix 1 — `GET /client/settings` et `GET /talent/settings`

**Erreur :** `ParseError: syntax error, unexpected identifier "un", expecting "]"`

**Cause :** Apostrophe non échappée dans `quelqu'un` à l'intérieur de chaînes PHP entre guillemets simples dans des blocs `@php` de Blade.

**Fix :** Escape `quelqu\'un` dans :
- `resources/views/client/settings/index.blade.php` (lignes 466-467)
- `resources/views/talent/settings/index.blade.php` (ligne 229)

---

#### Fix 2 — `GET /client/bookings/{id}`

**Erreur :** `ParseError: syntax error, unexpected token "\", expecting "]"`

**Cause :** `{{ addslashes($cancelPolicy[\'label\']) }}` — les backslash-escaped single quotes compilent en PHP invalide (`[\'label\']` n'est pas une syntaxe légale en PHP brut).

**Fix :** Remplacement par guillemets doubles : `{{ addslashes($cancelPolicy["label"]) }}`

**Fichier :** `resources/views/client/bookings/show.blade.php` ligne 279

---

#### Fix 3 — `POST /client/bookings` (formulaire de réservation)

**Erreur :** `SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'travel_cost' cannot be null`

**Cause :** Les colonnes `travel_cost` et `express_fee` sont `NOT NULL DEFAULT 0` en base. Le contrôleur passait `$travelCost ?: null` — quand `$travelCost === 0`, PHP évalue `0 ?: null` en `null`.

**Fix :** Suppression du `?: null` — les valeurs `0` sont désormais envoyées directement.

**Fichier :** `app/Http/Controllers/Web/Client/BookingController.php` lignes 112-113

**Commit :** `c15d596`

---

### Feat — Groupe AA : notifications push "disponibilité talent" (`bookmi:notify-availability-alerts`)

**Contexte :** Le stockage des alertes de disponibilité (table `availability_alerts`, API `POST /api/v1/talents/{id}/notify-availability`, bouton Flutter) était déjà implémenté. Il manquait le mécanisme de dispatch.

**Nouveau — Command `bookmi:notify-availability-alerts {--dry-run}` :**
- Charge en chunk de 100 toutes les `AvailabilityAlert` où `notified_at IS NULL` et `event_date >= today`
- Pour chaque alerte : vérifie l'absence de booking actif (`pending|accepted|paid|confirmed`) sur cette date
- Si le créneau est libre : `SendPushNotification::dispatch()` → titre `"{stageName} est disponible ! 🎉"` + marque `notified_at`
- `--dry-run` : log sans modifier la base
- Schedule : `*/30 * * * *` (toutes les 30 minutes)

**Flutter — routing FCM :**
- Nouveau type `availability_alert` dans `app_router.dart` → redirige vers `/talent/{talent_profile_id}` (même branche que `talent_update`)

**Tests :** 5 PHPUnit dans `tests/Feature/Commands/NotifyAvailabilityAlertsCommandTest.php` :
- Slot libre → notification envoyée + `notified_at` mis à jour
- Slot occupé → aucune notification
- Déjà notifié → skip
- Date passée → skip (filtre SQL `event_date >= today`)
- `--dry-run` → aucun dispatch ni mise à jour

**Fichiers créés :**
- `app/Console/Commands/NotifyAvailabilityAlerts.php`
- `tests/Feature/Commands/NotifyAvailabilityAlertsCommandTest.php`

**Fichiers modifiés :**
- `routes/console.php` — ajout import + `->everyThirtyMinutes()`
- `bookmi_app/lib/app/routes/app_router.dart` — routing FCM `availability_alert`

**Commit :** `28f060e`

---

## 2026-03-01

### Feat — Groupe Z : filtre disponibilité par date + signalement réservation

**Z1 — API filtre `event_date` :**
- `GET /api/v1/talents?available_date=YYYY-MM-DD` : exclut les talents ayant un booking actif (`pending|accepted|paid|confirmed`) ce jour-là
- Migration `availability_alerts` : table `(user_id, talent_profile_id, event_date)` unique — stocke les demandes de notification
- API `POST /api/v1/talents/{id}/notify-availability` : enregistre l'alerte via `firstOrCreate`
- Modèle `AvailabilityAlert` + relations `user()` / `talentProfile()`

**Z2 — Signalement réservation Flutter :**
- Bouton "Signaler un problème" sur `BookingDetailPage` pour les clients (statuts `paid|confirmed`)
- Appel `POST /api/v1/booking_requests/{id}/dispute` → met le statut à `disputed`

**Z3 — Vérification identité client (web Filament) :**
- Actions Filament `verify_client` / `unverify_client` dans `ClientResource`
- Migration `2026_02_28_100000` : colonnes `is_client_verified` (bool) + `client_verified_at` (timestamp) sur `users`
- API : champ `is_client_verified` exposé dans la resource de login/profil
- Flutter `AuthUser` : champ `isClientVerified` (bool, défaut `false`) — badge vert "Client vérifié" sur `ProfilePage`

**Commits :** `cfdf9da`, `3c6759e`, `25e9c5b`, `918737e`, `3b11e86`

---

### Feat — Groupe Y : annulation réservation Flutter + top villes analytics

**Y1 — Annulation Flutter :**
- Bouton "Annuler" sur `BookingDetailPage` pour statuts `pending|accepted`
- Appel `DELETE /api/v1/booking_requests/{id}` → `BookingRepository::cancel()`
- Confirmation modal avant action

**Y2 — Top villes analytics :**
- `GET /api/v1/me/analytics` expose désormais `top_cities` : top 5 villes par nombre de réservations complétées
- Flutter `TalentStatisticsPage` : nouvelle section "Top villes" avec barres horizontales

**Commit :** `1382cee`

---

### Feat — Groupes H–X : fonctionnalités MVP (récapitulatif)

> Ces groupes constituent le cœur du déploiement MVP du 2026-03-01. Chaque groupe correspond à une ou plusieurs stories du document MVP.

#### Groupe H — Évaluation multi-critères, frais déplacement, médiation (#38 #56)

- Système d'évaluation multi-critères (note globale + critères détaillés) côté Flutter
- Frais de déplacement renseignés dans le formulaire de réservation
- Interface de médiation enrichie admin

**Commit :** `fab25ba`

---

#### Groupe I — Collectif/label, RBAC Filament, détection contacts (#29 #49 #53)

- Talent peut appartenir à un collectif/label (champ `group_name` / `is_collective`)
- RBAC Filament : `Gate::before` pour `is_admin` — accès panel superadmin
- `ContactDetectionService` : détecte les coordonnées partagées dans les messages (téléphone, email, réseaux)
- UI Flutter : indicateur visuel sur les messages flaggés

**Commits :** `97ba2b3`, `2fa2bca`, `0250c96`, `d0cf1a9`, `5a15cd6`

---

#### Groupe J — Suivi d'artistes + notifications de mise à jour (#22)

- `POST /api/v1/talents/{id}/follow` / `DELETE /api/v1/talents/{id}/follow`
- Notifications push aux followers lors d'une mise à jour du profil talent (`talent_update`)
- Flutter : bouton Suivre/Ne plus suivre sur `TalentProfilePage` avec état réactif

**Commit :** `f93a6d6`

---

#### Groupe K — Codes promo + 2FA settings (#25 #48)

- Backend : modèle `PromoCode`, service `PromoCodeService::apply()`, Filament CRUD
- API : `POST /api/v1/promo-codes/apply` → valide et retourne `discount_amount`
- Flutter : champ code promo sur `Step3Recap` du booking flow
- Dispute web (`POST /client/bookings/{id}/dispute`) + paramètres 2FA dans l'espace client web

**Commits :** `726dcbc`, `448f8ec`

---

#### Groupe L — Reschedule Flutter + gestion calendrier + parrainage (#20 #17)

- Reschedule Flutter : `PATCH /api/v1/booking_requests/{id}/reschedule`
- Gestion calendrier Flutter : créneaux bloqués / disponibles manuels
- Système de parrainage : code unique, bonus à la première réservation
- Export CSV réservations depuis l'espace talent web

**Commits :** `030da03`, `d2d659b`

---

#### Groupe M — Quality dashboard admin + notification préférences (#57)

- Page `QualityDashboardPage` dans Filament : talents à risque (faible note, litiges, inactivité)
- Scores critiques sur avis : pondération par critère
- Score visibilité affiché dans l'admin (TalentProfileResource)
- Notification préférences : `GET/PATCH /api/v1/me/notification-preferences`

**Commits :** `56d4d7d`, `912c674`

---

#### Groupe N — Express booking + manager Flutter + portfolio post-événement (#10 #17)

- Express booking surcharge : +10% sur le cachet si `is_express = true` et `enable_express_booking = true`
- Interface manager Flutter : délégation, assignation de manager
- Portfolio post-événement : client peut soumettre des photos après une prestation (page dédiée Flutter + approval workflow admin)
- Attestation de revenus Flutter

**Commits :** `495e760`, `6ae4a5b`

---

#### Groupe O — Délégation admin + micro package type + géo-filtre (#29 #17 #23)

- Admin : délégation de tâches entre admins (assignation + notification)
- Type de package `micro` : réservation sans date d'événement, flux dédié
- Geo-filtre discovery : `GET /api/v1/talents?lat=&lng=&radius_km=`
- `canAccessPanel()` : vérification rôle `admin` / flag `is_admin` à l'entrée du panel Filament

**Commits :** `7ca1309`, `f056bbe`

---

#### Groupe P — Auto-reply + auto-complete bookings + export CSV revenus (#50 #20)

- Auto-reply : réponse automatique du talent lors de la création d'une réservation (`autoReplyOnBookingCreated`)
- Auto-complete : commande `bookmi:auto-complete-bookings` — passe en `completed` les réservations `confirmed` dont `event_date + 7j` est dépassé
- Alertes calendrier in-app : `GET /api/v1/me/calendar-alerts` — banner Flutter
- Export CSV revenus talent : `GET /api/v1/me/revenue-export?year=YYYY`

**Commits :** `b3219d0`, `846a003`

---

#### Groupe Q — Analytics enrichies + messages flaggés admin + avis multi-critères web (#21 #53 #38)

- Analytics `GET /api/v1/me/analytics` : ajout `top_cities`, `booking_status_distribution`, `rating_history`
- Flutter `TalentStatisticsPage` : sections top villes, répartition statuts, courbe notes
- Messages flaggés : vue admin dédiée + action "Ignorer le flag"
- Critères d'avis web : affichage des scores détaillés sur la page de réservation talent
- Messages vocaux (type `voice`) dans le chat Flutter

**Commits :** `03a604d`, `1750e17`

---

#### Groupe R — Score visibilité Flutter + paramètres collectif web (#56 #24)

- Affichage `visibility_score` dans les statistiques talent Flutter
- Page paramètres collectif/label dans l'espace talent web

**Commit :** `d672830`

---

#### Groupe S — Micro-service packages : `delivery_days` + UI Flutter (#23)

- Migration : colonne `delivery_days` (int nullable) sur `service_packages`
- API : calcul automatique `estimated_delivery_date = event_date + delivery_days` dans la resource booking
- Flutter : carte violette distincte pour les packages micro, section séparée dans `TalentProfilePage`
- Paramètres collectif/label sur le profil talent web

**Commit :** `39f4082`

---

#### Groupe T — Frais de déplacement dans le flux de réservation Flutter (#11)

- Étape 2 du booking flow : champ optionnel "Frais de déplacement (FCFA)" (masqué pour les packages micro)
- Étape 3 récapitulatif : ligne `Frais de déplacement` si > 0
- BLoC : événement `TravelCostChanged` + state `travelCost`

**Commit :** `576b859`

---

#### Groupe U — Flux UX micro-service + express booking web + préférences notification web (#10 #11 #30)

- Flux booking micro : l'étape 2 affiche une carte "Infos de livraison" au lieu du sélecteur de date
- `event_date`, `start_time`, `event_location` : nullable en base et dans le BLoC pour les packages micro
- Express booking web : case à cocher + affichage surcharge sur le formulaire client web
- Frais de déplacement web : champ `travel_cost` sur le formulaire
- Préférences de notification web : page dédiée avec toggles par type d'événement

**Commits :** `2fde9e9`, `3d00c46`

---

#### Groupe V — Avis bilatéral talent→client + check-in jour J web (#38 #37)

- Avis talent vers client : formulaire sur la page de réservation talent web (statuts `confirmed|completed`)
- Check-in jour J web : bouton "Marquer comme arrivé" sur la page de suivi talent web

**Commit :** `bd1c50f`

---

#### Groupe W — Politique d'annulation graduée web + bouton "Prestation terminée" (#34 #26)

- Politique d'annulation : remboursement 100% si annulation > 7j avant, 50% entre 2–7j, 0% < 2j
- `BookingService::cancelBooking()` applique la politique et crée un `refund_amount` + `cancellation_policy_applied`
- Bouton "Marquer la prestation comme terminée" côté talent web (statut `paid|confirmed`)
- `BookingController::complete()` → passe le statut en `completed`

**Commit :** `b54702c`

---

#### Groupe X — Devis express+travel + dropdown type package + manager finance (#6 #10 #11 #14 #23)

- API devis : `GET /api/v1/quotes?talent_profile_id=&service_package_id=&is_express=&travel_cost=` → retourne le détail des montants
- Dropdown type de package (standard / micro) dans le formulaire admin
- Corrections manager finance : affichage correct des reversements du talent dans la vue manager

**Commit :** `ba6e986`

---

#### Groupe S (web) — Paramètres collectif/groupe sur le profil talent web (#24)

- Section "Collectif / Label" dans `resources/views/talent/profile/edit.blade.php`
- Champs : `group_name`, `is_collective` (toggle), `group_description`

**Commit :** `6aff92f`

---

#### Groupe T (web) — Dispute web + partage profil public + filtre prix (#25 #22 #7)

- Dispute web : `POST /client/bookings/{id}/dispute` avec confirmation modale
- Partage profil public : bouton "Partager" → URL copyée (`/talents/{slug}`)
- Filtre fourchette de prix sur la page de recherche publique

**Commit :** `aec4619`

---

#### Groupe V (mvp-v) — Level-up talent : notification push + barre progression (#57)

- Notification push lors du passage de niveau talent (`RecalculateTalentLevels` command)
- Flutter : barre de progression animée vers le niveau suivant dans `TalentStatisticsPage`

**Commit :** `09ed838`

---

#### Groupe W (mvp-w) — Auto-reply à la création de réservation (#58)

- `MessagingService::autoReplyOnBookingCreated()` : appelé depuis le listener `NotifyTalentOfNewBooking`
- Crée ou récupère la conversation liée à la réservation, envoie le message d'auto-réponse du talent (si actif et configuré)
- Idempotent : no-op si un message `is_auto_reply = true` existe déjà

**Commit :** `04542f5`

---

### Feat — Traçabilité des statuts de réservation sur toutes les vues (#58)

- Timeline `BookingStatusLog` affichée sur :
  - Espace talent web (`/talent/bookings/{id}`) — section chronologie avec icônes et acteurs
  - Espace client web (`/client/bookings/{id}`) — même affichage, lecture seule
  - Filament admin : `BookingRequestResource` → slide-over avec historique complet
- `BookingStatusLog` enregistré à chaque transition de statut via `BookingRequestObserver`

**Commit :** `7ce0d6c`

---

### Feat — Check-in GPS (statut `arrived`) (#tracking)

- Flutter `TrackingPage` : bouton "Je suis arrivé" → `POST /api/v1/tracking/arrived`
- Backend : nouveau statut de tracking `arrived`, timestamp `arrived_at` sur `BookingRequest`
- Notification push au client lors de l'arrivée du talent

**Commit :** `6b48c90`

---

### Feat — Filtres avancés recherche Flutter (#56)

- Panneau de filtres sur `DiscoveryPage` : tri (note, prix, popularité), ville, budget min/max, note minimale
- Persiste dans le BLoC de recherche entre navigations

**Commit :** `4bd21b7`

---

### Feat — Désactivation de compte (backend + Flutter) (#admin)

- Admin Filament : action "Désactiver le compte" sur `ClientResource` et `TalentProfileResource`
- API : `PATCH /api/v1/me` accepte `is_active: false` — désactive le compte et révoque tous les tokens Sanctum
- Flutter : bouton "Désactiver mon compte" dans `PersonalInfoPage` avec confirmation et déconnexion automatique

**Commits :** `3b11e86`, `918737e`

---

### Fix — Filament : Gate::before + is_admin bypass (#menu)

- `Gate::before` : correction `property_exists` → `isset` pour les attributs Eloquent dynamiques
- `canViewAny` / `canAccess` : vérification `is_admin` ajoutée dans les resources manquantes
- PHPStan : remplacement `?->is_admin ?? false` par `=== true` (no mixed)

**Commits :** `2fa2bca`, `0250c96`, `d0cf1a9`, `5a15cd6`

---

## 2026-02-28 (suite)

### Feat — Groupe G : vérification client, boost visibilité, alertes calendrier vide

#### G1 — Vérification d'identité client (#33)

- Migration `2026_02_28_100000` : colonnes `is_client_verified` (bool, défaut `false`) + `client_verified_at` (timestamp nullable) sur `users`
- `User` model : fillable + casts mis à jour ; `AuthService` expose `is_client_verified` dans la resource login et profil
- Filament `ClientResource` : actions `verify_client` / `unverify_client` avec notification FCM
- Flutter `AuthUser` : champ `isClientVerified` (défaut `false`) dans `fromJson` / `toJson` / `copyWith`
- Flutter `ProfilePage` : badge vert "Client vérifié" affiché pour les clients non-talent

**Commit :** `a0dc285` + `3c6759e`

---

#### G2 — Boost de visibilité automatique (#56)

- Migration `2026_02_28_110000` : colonne `visibility_score FLOAT(5,2) DEFAULT 0` + index sur `talent_profiles`
- Command `bookmi:update-visibility-scores {--dry-run}` :
  - Score = activité récente (max 40) + note moyenne (max 40) + vérifié (20)
  - Traitement en chunk de 100
- Schedule : `dailyAt('02:00')`
- `SearchService` / `TalentRepository` : `orderByDesc('visibility_score')` comme tiebreaker secondaire

---

#### G3 — Alertes calendrier vide (#19)

- Migration `2026_02_28_120000` : colonne `calendar_empty_notified_at` (timestamp nullable) sur `talent_profiles`
- Command `bookmi:detect-empty-calendar {--dry-run}` :
  - Talents sans booking actif dans les 30 prochains jours
  - Rate-limit : 1 notification par talent toutes les 7 jours
- Schedule : `weeklyOn(1, '09:00')` (lundi 9h)

**Commit :** `a0dc285`

---

### Feat — Browse-first / mode invité Flutter

- Les invités arrivent directement sur `/home` depuis le splash (sans login obligatoire)
- `guestRoutes` : `/home`, `/search`, `/profile`, `/talent/` accessibles sans auth
- Onglets "Réservations" et "Messages" → bottom sheet "Connexion requise" pour les invités
- `GuestProfilePage` : page bénéfices pour les non-connectés
- `_AuthRequiredReservationSheet` : modal avant le bouton "Réserver" sur `TalentProfilePage`

**Commit :** `e3e6eb6`

---

### Fix — Flutter : affichage des montants (division par 100 erronée)

- Les montants s'affichaient divisés par 100 (centimes au lieu de FCFA)
- Suppression de la division `/100` dans `formatCachet()`

**Commits :** `c435547`, `093a110`

---

### Feat — Flutter : pages auth — bouton "Retour à l'accueil"

- Ajout d'un bouton retour vers `/home` (invité) sur les pages login, inscription et récupération de mot de passe
- Permet aux invités de revenir sur l'app sans se connecter

**Commit :** `edf5054`

---

### Style — Pages web auth + profil talent : effets atmosphériques + couleur bleue #1AB3FF

- Pages auth (login, inscription, 2FA, reset mdp, vérif téléphone) : effets visuels atmosphériques (particules, gradients animés)
- Profil talent public + hero talent : hero plus clair, effets atmosphériques
- Remplacement de toutes les occurrences de l'orange `#FF6B35` par le bleu `#1AB3FF` sur les pages auth et erreurs

**Commits :** `a4144f1`, `8dadeca`, `3d430ac`, `ef88480`, `6219cf2`, `ee07f74`, `92c9afe`

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
