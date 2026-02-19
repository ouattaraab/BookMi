# Story 2.6: Écrans d'authentification Flutter (mobile)

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a visiteur,
I want m'inscrire et me connecter depuis l'app mobile avec une expérience fluide,
so that l'accès à BookMi soit simple et rapide.

**Functional Requirements:** FR1, FR2, FR3, FR4, FR5, FR9
**Non-Functional Requirements:** NFR5 (Flutter < 3s démarrage 2 Go RAM), NFR36-NFR40 (WCAG 2.1 AA, écrans 4,7"→6,7"), UX-FORM-2 (préfixe +225), UX-FORM-3 (validation temps réel), UX-FEEDBACK-2 (états de chargement)

## Acceptance Criteria (BDD)

**AC1 — Splash screen**
**Given** l'app est lancée
**When** l'écran de démarrage s'affiche
**Then** un splash screen avec le logo BookMi et un gradient `gradientHero` s'affiche pendant le chargement initial
**And** l'app vérifie la présence d'un token valide dans `SecureStorage`
**And** si un token existe, l'utilisateur est redirigé vers `/home`
**And** si aucun token, l'utilisateur est redirigé vers l'onboarding (première visite) ou le login

**AC2 — Onboarding (3 slides)**
**Given** c'est la première ouverture de l'app (pas de flag `has_seen_onboarding` dans Hive)
**When** l'utilisateur arrive après le splash
**Then** 3 slides d'onboarding s'affichent avec PageView + indicateurs de dots
**And** chaque slide a une illustration, un titre et une description en français
**And** un bouton "Suivant" avance au slide suivant, "Commencer" sur le dernier slide
**And** un lien "Passer" permet de skip l'onboarding
**And** le flag `has_seen_onboarding` est sauvegardé dans Hive après complétion ou skip
**And** le design glassmorphism est appliqué (GlassCard pour le contenu)

**AC3 — Écran Login**
**Given** l'utilisateur est sur l'écran de connexion
**When** il remplit le formulaire
**Then** les champs email et mot de passe sont affichés dans un GlassCard
**And** la validation temps réel (au blur) affiche les erreurs en français (UX-FORM-3)
**And** le bouton "Se connecter" appelle `POST /api/v1/auth/login` avec `{ email, password }`
**And** en cas de succès, le token est stocké dans `flutter_secure_storage` et l'utilisateur est redirigé vers `/home`
**And** en cas d'erreur, le message d'erreur API est affiché (codes `AUTH_INVALID_CREDENTIALS`, `AUTH_PHONE_NOT_VERIFIED`, `AUTH_ACCOUNT_DISABLED`, `AUTH_ACCOUNT_LOCKED`)
**And** un lien "Mot de passe oublié ?" navigue vers l'écran forgot-password
**And** un lien "Pas encore de compte ? S'inscrire" navigue vers l'écran register
**And** un indicateur de chargement (bouton disabled + spinner) s'affiche pendant la requête

**AC4 — Écran Register**
**Given** l'utilisateur est sur l'écran d'inscription
**When** il remplit le formulaire
**Then** les champs suivants sont affichés : prénom, nom, email, téléphone, mot de passe, confirmation mot de passe, rôle (client/talent)
**And** le champ téléphone affiche le préfixe `+225` automatiquement avec masque `XX XX XX XX XX` (UX-FORM-2)
**And** si le rôle "talent" est sélectionné, un dropdown catégorie et un dropdown sous-catégorie optionnel apparaissent
**And** les catégories sont chargées depuis `GET /api/v1/categories`
**And** la validation temps réel (au blur) affiche les erreurs en français :
  - Email : format invalide → "Veuillez entrer une adresse e-mail valide."
  - Téléphone : 10 chiffres après +225 → "Le numéro de téléphone doit contenir 10 chiffres."
  - Mot de passe : min 8 caractères → "Le mot de passe doit contenir au moins 8 caractères."
  - Confirmation : doit correspondre → "Les mots de passe ne correspondent pas."
**And** le bouton "S'inscrire" appelle `POST /api/v1/auth/register`
**And** en cas de succès, l'utilisateur est redirigé vers l'écran OTP avec le numéro de téléphone
**And** en cas d'erreur de validation (422), les erreurs champ par champ sont affichées
**And** le design glassmorphism est appliqué (GlassCard pour les formulaires)

**AC5 — Écran OTP Verification**
**Given** l'utilisateur vient de s'inscrire ou doit vérifier son téléphone
**When** il arrive sur l'écran de vérification OTP
**Then** 6 champs de saisie individuels s'affichent pour le code OTP
**And** le numéro de téléphone masqué s'affiche (ex: "+225 07 XX XX XX 01")
**And** un compteur de 60 secondes s'affiche avant de pouvoir renvoyer le code
**And** un bouton "Renvoyer le code" appelle `POST /api/v1/auth/resend-otp` après expiration du timer
**And** la saisie du 6ème chiffre déclenche automatiquement `POST /api/v1/auth/verify-otp`
**And** en cas de succès, le token est stocké et l'utilisateur est redirigé vers `/home`
**And** en cas d'erreur, le message est affiché (codes `AUTH_OTP_INVALID`, `AUTH_OTP_EXPIRED`, `AUTH_ACCOUNT_LOCKED`)
**And** le nombre de tentatives restantes est affiché si fourni par l'API

**AC6 — Écran Forgot Password**
**Given** l'utilisateur est sur l'écran "Mot de passe oublié"
**When** il entre son email et soumet
**Then** `POST /api/v1/auth/forgot-password` est appelé avec `{ email }`
**And** un message de confirmation s'affiche : "Si un compte existe avec cet email, un lien de réinitialisation a été envoyé."
**And** un bouton "Retour à la connexion" ramène à l'écran login
**And** le même message s'affiche que l'email existe ou non (anti-énumération)

**AC7 — GoRouter guard + redirection**
**Given** l'utilisateur n'est pas authentifié (pas de token dans SecureStorage)
**When** il tente d'accéder à une route protégée (`/home`, `/search`, `/bookings`, `/messages`, `/profile`)
**Then** GoRouter le redirige vers `/login`
**And** les routes publiques (`/login`, `/register`, `/otp`, `/forgot-password`, `/onboarding`) restent accessibles sans token
**And** si l'utilisateur est authentifié et navigue vers `/login`, il est redirigé vers `/home`

**AC8 — Gestion du token et déconnexion**
**Given** l'utilisateur est connecté
**When** le Dio AuthInterceptor reçoit un 401
**Then** le token est supprimé de SecureStorage (déjà implémenté dans `auth_interceptor.dart`)
**And** l'AuthBloc émet un état `AuthUnauthenticated`
**And** GoRouter redirige vers `/login`

**AC9 — Mode sombre**
**Given** le système de l'appareil est en mode sombre
**When** l'utilisateur ouvre les écrans d'authentification
**Then** le design s'adapte au dark mode via `BookmiTheme.dark`
**And** les GlassCards, inputs et textes utilisent les couleurs du thème sombre

## Tasks / Subtasks

### Phase 1 — Feature Auth : Couche Data (P1)

- [ ] Task 1: Mettre à jour ApiEndpoints (AC: AC3, AC4, AC5, AC6)
  - [ ] 1.1: Ajouter dans `lib/core/network/api_endpoints.dart` : `authVerifyOtp = '/auth/verify-otp'`, `authResendOtp = '/auth/resend-otp'`, `authForgotPassword = '/auth/forgot-password'`, `authResetPassword = '/auth/reset-password'`, `me = '/me'`, `categories = '/categories'` (si non existant)
  - [ ] 1.2: Vérifier que `authLogin`, `authRegister`, `authLogout` existent déjà

- [ ] Task 2: Créer le modèle AuthUser (AC: AC3, AC5)
  - [ ] 2.1: Créer `lib/features/auth/data/models/auth_user.dart`
  - [ ] 2.2: Champs : `id` (int), `firstName` (String), `lastName` (String), `email` (String), `phone` (String), `phoneVerifiedAt` (String?), `isActive` (bool)
  - [ ] 2.3: Factory `AuthUser.fromJson(Map<String, dynamic> json)` — mapping snake_case direct (pas de renommage)
  - [ ] 2.4: Méthode `toJson()` pour sérialisation locale (Hive cache)

- [ ] Task 3: Créer AuthRepository (AC: AC3, AC4, AC5, AC6)
  - [ ] 3.1: Créer `lib/features/auth/data/repositories/auth_repository.dart`
  - [ ] 3.2: Constructeur avec injection `ApiClient` + `SecureStorage` + constructor `.forTesting()`
  - [ ] 3.3: Méthode `login(String email, String password) → Future<ApiResult<AuthResponse>>` — appelle `POST /auth/login`, parse réponse `{ data: { token, user, roles } }`
  - [ ] 3.4: Méthode `register(Map<String, dynamic> data) → Future<ApiResult<void>>` — appelle `POST /auth/register`
  - [ ] 3.5: Méthode `verifyOtp(String phone, String code) → Future<ApiResult<AuthResponse>>` — appelle `POST /auth/verify-otp`, parse token + user
  - [ ] 3.6: Méthode `resendOtp(String phone) → Future<ApiResult<void>>` — appelle `POST /auth/resend-otp`
  - [ ] 3.7: Méthode `forgotPassword(String email) → Future<ApiResult<void>>` — appelle `POST /auth/forgot-password`
  - [ ] 3.8: Méthode `logout() → Future<ApiResult<void>>` — appelle `POST /auth/logout`, supprime token SecureStorage
  - [ ] 3.9: Méthode `getProfile() → Future<ApiResult<AuthUser>>` — appelle `GET /me`, parse réponse
  - [ ] 3.10: Gestion d'erreurs API → mapping vers `ApiFailure` avec code et message français

- [ ] Task 4: Créer AuthResponse model (AC: AC3, AC5)
  - [ ] 4.1: Créer `lib/features/auth/data/models/auth_response.dart`
  - [ ] 4.2: Champs : `token` (String), `user` (AuthUser), `roles` (List<String>)
  - [ ] 4.3: Factory `AuthResponse.fromJson(Map<String, dynamic> json)`

### Phase 2 — Feature Auth : BLoC (P2)

- [ ] Task 5: Créer AuthBloc — Events (AC: AC1, AC3, AC4, AC5, AC6, AC7, AC8)
  - [ ] 5.1: Créer `lib/features/auth/bloc/auth_event.dart`
  - [ ] 5.2: `sealed class AuthEvent`
  - [ ] 5.3: Events :
    - `AuthCheckRequested` — vérifier token au démarrage (splash)
    - `AuthLoginSubmitted({required String email, required String password})` — soumission login
    - `AuthRegisterSubmitted({required Map<String, dynamic> data})` — soumission inscription
    - `AuthOtpSubmitted({required String phone, required String code})` — soumission OTP
    - `AuthOtpResendRequested({required String phone})` — renvoyer OTP
    - `AuthForgotPasswordSubmitted({required String email})` — mot de passe oublié
    - `AuthLogoutRequested` — déconnexion
    - `AuthSessionExpired` — session expirée (401 interceptor)

- [ ] Task 6: Créer AuthBloc — States (AC: tous)
  - [ ] 6.1: Créer `lib/features/auth/bloc/auth_state.dart`
  - [ ] 6.2: `sealed class AuthState`
  - [ ] 6.3: States :
    - `AuthInitial` — état initial avant vérification
    - `AuthLoading` — requête en cours (login, register, OTP, forgot)
    - `AuthAuthenticated({required AuthUser user, required List<String> roles})` — connecté avec succès
    - `AuthUnauthenticated` — pas de token ou token expiré
    - `AuthRegistrationSuccess({required String phone})` — inscription réussie, rediriger vers OTP
    - `AuthOtpResent` — OTP renvoyé avec succès
    - `AuthForgotPasswordSuccess` — email de reset envoyé
    - `AuthFailure({required String code, required String message})` — erreur avec code métier

- [ ] Task 7: Créer AuthBloc — Bloc (AC: tous)
  - [ ] 7.1: Créer `lib/features/auth/bloc/auth_bloc.dart`
  - [ ] 7.2: Constructeur avec injection `AuthRepository` + `SecureStorage`
  - [ ] 7.3: Handler `_onCheckRequested` — lire token depuis SecureStorage, si existe appeler `GET /me`, émettre `AuthAuthenticated` ou `AuthUnauthenticated`
  - [ ] 7.4: Handler `_onLoginSubmitted` — appeler `repository.login()`, si succès sauvegarder token dans SecureStorage, émettre `AuthAuthenticated`
  - [ ] 7.5: Handler `_onRegisterSubmitted` — appeler `repository.register()`, si succès émettre `AuthRegistrationSuccess(phone)`
  - [ ] 7.6: Handler `_onOtpSubmitted` — appeler `repository.verifyOtp()`, si succès sauvegarder token, émettre `AuthAuthenticated`
  - [ ] 7.7: Handler `_onOtpResendRequested` — appeler `repository.resendOtp()`, émettre `AuthOtpResent` ou `AuthFailure`
  - [ ] 7.8: Handler `_onForgotPasswordSubmitted` — appeler `repository.forgotPassword()`, émettre `AuthForgotPasswordSuccess`
  - [ ] 7.9: Handler `_onLogoutRequested` — appeler `repository.logout()`, supprimer token SecureStorage, émettre `AuthUnauthenticated`
  - [ ] 7.10: Handler `_onSessionExpired` — supprimer token SecureStorage, émettre `AuthUnauthenticated`

### Phase 3 — Feature Auth : Écrans (P3)

- [ ] Task 8: Créer SplashPage (AC: AC1)
  - [ ] 8.1: Créer `lib/features/auth/presentation/pages/splash_page.dart`
  - [ ] 8.2: Logo BookMi centré sur fond `gradientHero`
  - [ ] 8.3: Au `initState`, dispatcher `AuthCheckRequested` et écouter le résultat via `BlocListener`
  - [ ] 8.4: `AuthAuthenticated` → `context.go(RoutePaths.home)`
  - [ ] 8.5: `AuthUnauthenticated` → vérifier flag `has_seen_onboarding` dans Hive, rediriger vers onboarding ou login

- [ ] Task 9: Créer OnboardingPage (AC: AC2)
  - [ ] 9.1: Créer `lib/features/auth/presentation/pages/onboarding_page.dart`
  - [ ] 9.2: `PageView` avec 3 slides (illustrations placeholder, titres, descriptions en français)
  - [ ] 9.3: Slide 1 : "Découvrez les meilleurs talents" — thème découverte
  - [ ] 9.4: Slide 2 : "Réservez en toute simplicité" — thème réservation
  - [ ] 9.5: Slide 3 : "Paiement sécurisé" — thème paiement sécurisé
  - [ ] 9.6: Dots indicator en bas, bouton "Suivant" / "Commencer", lien "Passer"
  - [ ] 9.7: Sauvegarder `has_seen_onboarding = true` dans Hive à la complétion ou au skip
  - [ ] 9.8: Naviguer vers `/login` à la fin
  - [ ] 9.9: Design glassmorphism pour le contenu des slides (GlassCard)

- [ ] Task 10: Créer LoginPage (AC: AC3, AC9)
  - [ ] 10.1: Créer `lib/features/auth/presentation/pages/login_page.dart`
  - [ ] 10.2: Fond `gradientHero`, formulaire dans un GlassCard
  - [ ] 10.3: Champs : email (TextInputType.emailAddress), mot de passe (obscureText avec toggle visibilité)
  - [ ] 10.4: Validation au blur : email format regex, mot de passe non vide
  - [ ] 10.5: Bouton "Se connecter" avec gradient `gradientCta`, disabled pendant le chargement
  - [ ] 10.6: `BlocListener<AuthBloc, AuthState>` : `AuthAuthenticated` → `context.go(RoutePaths.home)`, `AuthFailure` → afficher SnackBar/message d'erreur
  - [ ] 10.7: Liens : "Mot de passe oublié ?" → `/forgot-password`, "S'inscrire" → `/register`
  - [ ] 10.8: Gérer les erreurs spécifiques : `AUTH_PHONE_NOT_VERIFIED` → rediriger vers OTP

- [ ] Task 11: Créer RegisterPage (AC: AC4, AC9)
  - [ ] 11.1: Créer `lib/features/auth/presentation/pages/register_page.dart`
  - [ ] 11.2: Fond `gradientHero`, formulaire scrollable dans GlassCard
  - [ ] 11.3: Champs : prénom, nom, email, téléphone (+225 préfixe fixe + masque `XX XX XX XX XX`), mot de passe, confirmation, sélecteur rôle (client/talent)
  - [ ] 11.4: Si rôle = talent : afficher dropdown catégorie (chargé depuis API `GET /categories`) + dropdown sous-catégorie optionnel
  - [ ] 11.5: Validation au blur avec messages français (cf. AC4)
  - [ ] 11.6: Bouton "S'inscrire" avec gradient `gradientCta`
  - [ ] 11.7: `BlocListener` : `AuthRegistrationSuccess(phone)` → `context.go('/otp', extra: phone)`
  - [ ] 11.8: Erreurs 422 (VALIDATION_FAILED) → afficher les erreurs champ par champ depuis `details`

- [ ] Task 12: Créer OtpPage (AC: AC5, AC9)
  - [ ] 12.1: Créer `lib/features/auth/presentation/pages/otp_page.dart`
  - [ ] 12.2: 6 `TextField` individuels (largeur fixe ~48px, auto-focus au suivant, masque numérique)
  - [ ] 12.3: Afficher le numéro masqué : `+225 07 XX XX XX 01` (masquer les chiffres du milieu)
  - [ ] 12.4: Timer 60 secondes avec `CountdownTimer` ou `Timer.periodic` — bouton "Renvoyer" activé après expiration
  - [ ] 12.5: Auto-submit au 6ème chiffre → dispatcher `AuthOtpSubmitted`
  - [ ] 12.6: `BlocListener` : `AuthAuthenticated` → `context.go(RoutePaths.home)`, `AuthFailure` → message d'erreur + tentatives restantes
  - [ ] 12.7: `AuthOtpResent` → réinitialiser le timer + message de confirmation

- [ ] Task 13: Créer ForgotPasswordPage (AC: AC6, AC9)
  - [ ] 13.1: Créer `lib/features/auth/presentation/pages/forgot_password_page.dart`
  - [ ] 13.2: Champ email dans un GlassCard, bouton "Envoyer le lien"
  - [ ] 13.3: `BlocListener` : `AuthForgotPasswordSuccess` → afficher message de confirmation (toujours le même, anti-énumération)
  - [ ] 13.4: Bouton "Retour à la connexion" → `context.go(RoutePaths.login)`

### Phase 4 — Widgets réutilisables Auth (P4)

- [ ] Task 14: Créer les widgets partagés (AC: AC3, AC4, AC5)
  - [ ] 14.1: Créer `lib/features/auth/presentation/widgets/auth_text_field.dart` — TextFormField glassmorphism avec validation au blur, support dark mode, icône préfixe, toggle visibilité
  - [ ] 14.2: Créer `lib/features/auth/presentation/widgets/auth_button.dart` — ElevatedButton avec gradient `gradientCta`, état loading (spinner + disabled), borderRadius `BookmiRadius.button`
  - [ ] 14.3: Créer `lib/features/auth/presentation/widgets/phone_field.dart` — champ téléphone avec préfixe `+225` fixe non-éditable, masque `XX XX XX XX XX`, clavier numérique
  - [ ] 14.4: Créer `lib/features/auth/presentation/widgets/otp_input.dart` — 6 champs OTP avec auto-focus, paste support, auto-submit

### Phase 5 — Routing & Guard (P5)

- [ ] Task 15: Implémenter l'AuthGuard GoRouter (AC: AC7)
  - [ ] 15.1: Mettre à jour `lib/app/routes/guards/auth_guard.dart` — lire l'état `AuthBloc` via `context.read<AuthBloc>().state`
  - [ ] 15.2: Si état `AuthUnauthenticated` et route non-publique → retourner `RoutePaths.login`
  - [ ] 15.3: Si état `AuthAuthenticated` et route est login/register/onboarding → retourner `RoutePaths.home`
  - [ ] 15.4: Mettre à jour `publicRoutes` : ajouter `/register`, `/otp`, `/forgot-password`, `/onboarding`, `/splash`

- [ ] Task 16: Mettre à jour AppRouter (AC: AC1, AC2, AC3, AC4, AC5, AC6, AC7)
  - [ ] 16.1: Ajouter les routes auth dans `app_router.dart` :
    - `/splash` → `SplashPage` (route initiale)
    - `/onboarding` → `OnboardingPage`
    - `/login` → `LoginPage`
    - `/register` → `RegisterPage`
    - `/otp` → `OtpPage` (reçoit `phone` via `extra`)
    - `/forgot-password` → `ForgotPasswordPage`
  - [ ] 16.2: Ajouter les noms et chemins dans `route_names.dart` : `splash`, `onboarding`, `register`, `otp`, `forgotPassword`
  - [ ] 16.3: Changer `initialLocation` de `/home` à `/splash`
  - [ ] 16.4: Configurer `redirect` dans GoRouter avec `authGuard`
  - [ ] 16.5: Les routes auth utilisent `parentNavigatorKey: rootNavigatorKey` (hors shell bottom nav)

### Phase 6 — Intégration App-level (P6)

- [ ] Task 17: Intégrer AuthBloc dans l'app (AC: AC7, AC8)
  - [ ] 17.1: Ajouter `AuthBloc` dans le `MultiBlocProvider` de `app.dart`
  - [ ] 17.2: Initialiser `AuthRepository` dans `_AppDependencies.initialize()` avec `ApiClient.instance` + `SecureStorage()`
  - [ ] 17.3: Configurer le `listenable` de GoRouter pour écouter les changements d'état AuthBloc (redirect réactif)

- [ ] Task 18: Gérer la session expirée (AC: AC8)
  - [ ] 18.1: Créer un mécanisme pour que le Dio AuthInterceptor notifie l'AuthBloc quand un 401 survient
  - [ ] 18.2: Option recommandée : utiliser un `StreamController<void>` dans `SecureStorage` ou un callback injecté dans l'interceptor
  - [ ] 18.3: L'AuthBloc écoute ce stream et dispatch `AuthSessionExpired` automatiquement

### Phase 7 — Barrel file & Tests (P7)

- [ ] Task 19: Créer le barrel file (AC: tous)
  - [ ] 19.1: Créer `lib/features/auth/auth.dart` — exporter les fichiers publics (bloc, models, pages)

- [ ] Task 20: Tests BLoC (AC: tous)
  - [ ] 20.1: Créer `test/features/auth/bloc/auth_bloc_test.dart`
  - [ ] 20.2: Utiliser `bloc_test` + `mocktail` — mock `AuthRepository`, `SecureStorage`
  - [ ] 20.3: Tests :
    - `AuthCheckRequested` avec token valide → `AuthAuthenticated`
    - `AuthCheckRequested` sans token → `AuthUnauthenticated`
    - `AuthLoginSubmitted` succès → sauvegarde token + `AuthAuthenticated`
    - `AuthLoginSubmitted` échec credentials → `AuthFailure(AUTH_INVALID_CREDENTIALS)`
    - `AuthLoginSubmitted` échec phone not verified → `AuthFailure(AUTH_PHONE_NOT_VERIFIED)`
    - `AuthRegisterSubmitted` succès → `AuthRegistrationSuccess(phone)`
    - `AuthRegisterSubmitted` validation error → `AuthFailure(VALIDATION_FAILED)`
    - `AuthOtpSubmitted` succès → sauvegarde token + `AuthAuthenticated`
    - `AuthOtpSubmitted` code invalide → `AuthFailure(AUTH_OTP_INVALID)`
    - `AuthOtpSubmitted` code expiré → `AuthFailure(AUTH_OTP_EXPIRED)`
    - `AuthOtpResendRequested` succès → `AuthOtpResent`
    - `AuthOtpResendRequested` limite atteinte → `AuthFailure(AUTH_OTP_RESEND_LIMIT)`
    - `AuthForgotPasswordSubmitted` → `AuthForgotPasswordSuccess` (toujours succès)
    - `AuthLogoutRequested` → supprime token + `AuthUnauthenticated`
    - `AuthSessionExpired` → supprime token + `AuthUnauthenticated`

- [ ] Task 21: Tests Repository (AC: AC3, AC4, AC5, AC6)
  - [ ] 21.1: Créer `test/features/auth/data/repositories/auth_repository_test.dart`
  - [ ] 21.2: Mock `Dio` et `SecureStorage` avec `mocktail`
  - [ ] 21.3: Tests : login succès/échec, register succès/validation error, verifyOtp succès/échec, resendOtp succès/limite, forgotPassword succès, logout succès, getProfile succès
  - [ ] 21.4: Vérifier le parsing correct des réponses API (codes d'erreur métier)

- [ ] Task 22: Tests Widget (AC: AC3, AC4, AC5)
  - [ ] 22.1: Créer `test/features/auth/presentation/pages/login_page_test.dart`
  - [ ] 22.2: Créer `test/features/auth/presentation/pages/register_page_test.dart`
  - [ ] 22.3: Créer `test/features/auth/presentation/pages/otp_page_test.dart`
  - [ ] 22.4: Utiliser `MockBloc<AuthEvent, AuthState>` pour injecter les états
  - [ ] 22.5: Tests : rendu initial, validation au blur, soumission formulaire, affichage erreurs, navigation liens
  - [ ] 22.6: Tests widget `phone_field.dart` : préfixe +225, masque, validation

- [ ] Task 23: Tests GoRouter guard (AC: AC7)
  - [ ] 23.1: Créer `test/app/routes/guards/auth_guard_test.dart`
  - [ ] 23.2: Tester : unauthenticated + route protégée → redirect login, authenticated + route login → redirect home, unauthenticated + route publique → null (pas de redirect)

- [ ] Task 24: Vérifier la suite de tests existante
  - [ ] 24.1: Exécuter `very_good test` — tous les tests existants doivent passer
  - [ ] 24.2: Exécuter `dart analyze` — 0 warnings
  - [ ] 24.3: Exécuter `dart format --set-exit-if-changed .` — pas de changements

## Dev Notes

### Architecture

- **BLoC pattern** obligatoire : 3 fichiers séparés (`auth_event.dart`, `auth_state.dart`, `auth_bloc.dart`) avec sealed classes
- **AuthBloc est global** : fourni au niveau `MultiBlocProvider` dans `app.dart` car l'état d'authentification affecte toute l'app (routing, interceptors)
- **ApiResult<T>** : utiliser le pattern sealed class existant (`ApiSuccess` / `ApiFailure`) pour toutes les réponses repository
- **SecureStorage** : déjà implémenté avec `getToken()`, `saveToken()`, `deleteToken()`, `deleteAll()` — ne pas recréer
- **AuthInterceptor** : déjà implémenté, injecte le Bearer token et clear le storage sur 401 — il manque seulement la notification vers le BLoC

### Composants existants à réutiliser

| Composant | Chemin | Usage |
|---|---|---|
| `GlassCard` | `core/design_system/components/glass_card.dart` | Conteneur formulaires |
| `GlassAppBar` | `core/design_system/components/glass_app_bar.dart` | AppBar transparente |
| `BookmiColors.gradientHero` | `core/design_system/tokens/colors.dart` | Fond de page |
| `BookmiColors.gradientCta` | `core/design_system/tokens/colors.dart` | Boutons d'action |
| `BookmiRadius.inputBorder` | `core/design_system/tokens/radius.dart` | Border radius inputs (12) |
| `BookmiRadius.buttonBorder` | `core/design_system/tokens/radius.dart` | Border radius boutons (16) |
| `BookmiSpacing` | `core/design_system/tokens/spacing.dart` | Espacements (4-64) |
| `BookmiTypography` | `core/design_system/tokens/typography.dart` | Styles texte Nunito |
| `SecureStorage` | `core/storage/secure_storage.dart` | Token auth (Keychain/KeyStore) |
| `LocalStorage` | `core/storage/local_storage.dart` | Cache Hive avec TTL (onboarding flag) |
| `ApiClient` | `core/network/api_client.dart` | Dio singleton |
| `ApiResult` | `core/network/api_result.dart` | Sealed class succès/échec |
| `ApiEndpoints` | `core/network/api_endpoints.dart` | Constantes endpoints |
| `AuthInterceptor` | `core/network/interceptors/auth_interceptor.dart` | Injection Bearer + clear 401 |

### Backend API Endpoints (Stories 2.1–2.5, tous implémentés)

| Endpoint | Méthode | Body | Réponse succès | Codes d'erreur |
|---|---|---|---|---|
| `/auth/register` | POST | `{ first_name, last_name, email, phone, password, role, category_id?, subcategory_id? }` | `201 { data: { user, message } }` | `422 VALIDATION_FAILED` |
| `/auth/verify-otp` | POST | `{ phone, code }` | `200 { data: { token, user, roles } }` | `AUTH_OTP_INVALID`, `AUTH_OTP_EXPIRED`, `AUTH_ACCOUNT_LOCKED` |
| `/auth/resend-otp` | POST | `{ phone }` | `200 { data: { message } }` | `AUTH_OTP_RESEND_LIMIT` |
| `/auth/login` | POST | `{ email, password }` | `200 { data: { token, user, roles } }` | `AUTH_INVALID_CREDENTIALS`, `AUTH_PHONE_NOT_VERIFIED`, `AUTH_ACCOUNT_DISABLED`, `AUTH_ACCOUNT_LOCKED` |
| `/auth/forgot-password` | POST | `{ email }` | `200 { data: { message } }` | `AUTH_RESET_THROTTLED` |
| `/auth/logout` | POST | — | `200 { data: { message } }` | `401 UNAUTHENTICATED` |
| `/me` | GET | — | `200 { data: { user, roles, permissions } }` | `401 UNAUTHENTICATED` |
| `/categories` | GET | — | `200 { data: [...] }` | — |

### Structure de fichiers à créer

```
lib/features/auth/
├── auth.dart                                   # Barrel file
├── bloc/
│   ├── auth_bloc.dart
│   ├── auth_event.dart
│   └── auth_state.dart
├── data/
│   ├── models/
│   │   ├── auth_user.dart
│   │   └── auth_response.dart
│   └── repositories/
│       └── auth_repository.dart
└── presentation/
    ├── pages/
    │   ├── splash_page.dart
    │   ├── onboarding_page.dart
    │   ├── login_page.dart
    │   ├── register_page.dart
    │   ├── otp_page.dart
    │   └── forgot_password_page.dart
    └── widgets/
        ├── auth_text_field.dart
        ├── auth_button.dart
        ├── phone_field.dart
        └── otp_input.dart

test/features/auth/
├── bloc/
│   └── auth_bloc_test.dart
├── data/
│   └── repositories/
│       └── auth_repository_test.dart
└── presentation/
    └── pages/
        ├── login_page_test.dart
        ├── register_page_test.dart
        └── otp_page_test.dart

test/app/routes/guards/
└── auth_guard_test.dart
```

### Fichiers existants à modifier

| Fichier | Modification |
|---|---|
| `lib/core/network/api_endpoints.dart` | Ajouter endpoints OTP, forgot-password, me |
| `lib/app/routes/app_router.dart` | Ajouter routes auth, changer initialLocation, configurer redirect |
| `lib/app/routes/route_names.dart` | Ajouter noms/chemins auth (splash, onboarding, register, otp, forgotPassword) |
| `lib/app/routes/guards/auth_guard.dart` | Implémenter la logique de redirection basée sur AuthBloc |
| `lib/app/view/app.dart` | Ajouter AuthBloc au MultiBlocProvider, initialiser AuthRepository |

### Project Structure Notes

- Structure 100% alignée avec l'architecture : `features/auth/` avec sous-dossiers `bloc/`, `data/`, `presentation/`
- Aucune dépendance directe entre features — l'auth communique via BLoC state et GoRouter navigation
- Le `core/` fournit toute l'infrastructure (network, storage, design system) — pas de duplication
- Les tests suivent le pattern miroir : `test/features/auth/` reflète `lib/features/auth/`

### References

- [Source: _bmad-output/planning-artifacts/architecture.md#Flutter Architecture — Feature-Based] — Structure features
- [Source: _bmad-output/planning-artifacts/architecture.md#BLoC Pattern] — Sealed classes Events/States
- [Source: _bmad-output/planning-artifacts/architecture.md#Authentication Flow] — Diagramme séquence auth complet
- [Source: _bmad-output/planning-artifacts/architecture.md#API Response Format] — Format JSON envelope
- [Source: _bmad-output/planning-artifacts/architecture.md#Error Codes] — Codes d'erreur AUTH_*
- [Source: _bmad-output/planning-artifacts/epics.md#Story 2.6] — Acceptance criteria epic
- [Source: _bmad-output/planning-artifacts/ux-design-specification.md#UX-FORM-2] — Masque téléphone +225
- [Source: _bmad-output/planning-artifacts/ux-design-specification.md#UX-FORM-3] — Validation temps réel
- [Source: bookmi/routes/api.php] — Routes API backend (toutes implémentées Stories 2.1-2.5)
- [Source: bookmi_app/lib/core/network/interceptors/auth_interceptor.dart] — AuthInterceptor existant
- [Source: bookmi_app/lib/core/storage/secure_storage.dart] — SecureStorage existant
- [Source: bookmi_app/lib/app/routes/guards/auth_guard.dart] — Placeholder guard à implémenter

## Dev Agent Record

### Agent Model Used
Claude Opus 4.6

### Completion Notes List
- All 24 tasks completed across 7 phases
- 0 dart analyze warnings on auth code
- 210 tests passing (50 new auth tests + 160 existing)
- dart format clean
- Session expired mechanism wired via ApiClient.onSessionExpired callback
- GoRouter redirect reactive via _GoRouterRefreshStream adapter
- Onboarding flag persisted in Hive 'settings' box

### File List

**Created:**
- `lib/features/auth/auth.dart` (barrel)
- `lib/features/auth/bloc/auth_bloc.dart`
- `lib/features/auth/bloc/auth_event.dart`
- `lib/features/auth/bloc/auth_state.dart`
- `lib/features/auth/data/models/auth_user.dart`
- `lib/features/auth/data/models/auth_response.dart`
- `lib/features/auth/data/repositories/auth_repository.dart`
- `lib/features/auth/presentation/pages/splash_page.dart`
- `lib/features/auth/presentation/pages/onboarding_page.dart`
- `lib/features/auth/presentation/pages/login_page.dart`
- `lib/features/auth/presentation/pages/register_page.dart`
- `lib/features/auth/presentation/pages/otp_page.dart`
- `lib/features/auth/presentation/pages/forgot_password_page.dart`
- `lib/features/auth/presentation/widgets/auth_text_field.dart`
- `lib/features/auth/presentation/widgets/auth_button.dart`
- `lib/features/auth/presentation/widgets/phone_field.dart`
- `lib/features/auth/presentation/widgets/otp_input.dart`
- `test/features/auth/bloc/auth_bloc_test.dart`
- `test/features/auth/data/repositories/auth_repository_test.dart`
- `test/features/auth/presentation/pages/login_page_test.dart`
- `test/features/auth/presentation/pages/register_page_test.dart`
- `test/features/auth/presentation/pages/otp_page_test.dart`

**Created (Code Review fixes — 2026-02-19):**
- `lib/core/validators/form_validators.dart` (regex email partagée)

**Modified:**
- `lib/core/network/api_endpoints.dart` (5 endpoints ajoutés)
- `lib/core/network/api_client.dart` (onSessionExpired getter/setter)
- `lib/core/network/interceptors/auth_interceptor.dart` (onSessionExpired callback)
- `lib/app/routes/route_names.dart` (6 routes auth ajoutées)
- `lib/app/routes/app_router.dart` (auth routes, splash initial, redirect, GoRouterRefreshStream)
- `lib/app/routes/guards/auth_guard.dart` (implémenté avec AuthBloc state checking)
- `lib/app/view/app.dart` (AuthBloc dans MultiBlocProvider, settings box, session expired wiring + fix H1 constructeur)
- `test/app/routes/app_router_test.dart` (adapté à nouvelle signature buildAppRouter)

## Code Review Record (2026-02-19)

**Reviewer :** BMAD Adversarial Code Review
**Résultat :** Approuvé après corrections

### Corrections appliquées

| ID | Sévérité | Problème | Fix |
|----|----------|----------|-----|
| H1 | 🔴 HIGH | `_AppDependencies` : `onboardingRepo` absent du constructeur (compile error) | Ajouté `required this.onboardingRepo,` dans `app.dart` |
| M3 | 🟡 MEDIUM | Regex email dupliquée dans `login_page.dart` et `register_page.dart` | Extrait dans `lib/core/validators/form_validators.dart` |
| L1 | 🟢 LOW | `_isSubmitting` dans `OtpPage` bloqué sur états inattendus | Reset ajouté dans le `default:` du switch BlocListener |
| L2 | 🟢 LOW | Pas de timeout fallback dans `SplashPage` | `Timer(10s)` ajouté → emit `AuthSessionExpired` si BLoC bloqué |

### Dettes techniques acceptées (action items)

| ID | Sévérité | Description | Priorité |
|----|----------|-------------|----------|
| M1 | 🟡 MEDIUM | `RegisterPage._loadCategories()` appelle `AuthRepository` directement (viole BLoC) → créer `AuthCategoriesRequested` event | P2 |
| M2 | 🟡 MEDIUM | `getProfile()` retourne `AuthResponse(token: '')` — sémantique incorrecte pour GET /me → rendre `token` nullable ou créer `UserProfile` model | P2 |
| M4 | 🟡 MEDIUM | AC9 (dark mode) non couvert par les tests widget | P3 |
| L3 | 🟢 LOW | `AuthRegisterSubmitted.data` est `Map<String, dynamic>` — manque un `RegisterRequest` value object typé | P3 |
