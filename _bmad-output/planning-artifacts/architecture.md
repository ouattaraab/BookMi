---
stepsCompleted: [1, 2, 3, 4, 5, 6, 7, 8]
lastStep: 8
status: 'complete'
completedAt: '2026-02-17'
inputDocuments:
  - '_bmad-output/planning-artifacts/prd.md'
  - '_bmad-output/planning-artifacts/ux-design-specification.md'
  - '_bmad-output/planning-artifacts/product-brief-BookMi_v2-2026-02-16.md'
workflowType: 'architecture'
project_name: 'BookMi_v2'
user_name: 'Aboubakarouattara'
date: '2026-02-17'
---

# Architecture Decision Document

_This document builds collaboratively through step-by-step discovery. Sections are appended as we work through each architectural decision together._

## Project Context Analysis

### Requirements Overview

**Functional Requirements:**

72 exigences fonctionnelles réparties en 8 domaines architecturaux :

| Domaine | FRs | Nb | Implications architecturales |
|---|---|---|---|
| Gestion Utilisateurs & Identité | FR1-FR10 | 10 | Authentification multi-rôle (Client, Talent, Manager, Admin + sous-rôles), vérification identité avec pièces sensibles, sessions JWT + Sanctum |
| Découverte & Recherche | FR11-FR17 | 7 | Recherche filtrable multi-critères, géolocalisation, URL publiques SEO-friendly, suggestions |
| Réservation & Contrats | FR18-FR28 | 11 | Machine à états complexe (demande → acceptation → paiement → contrat → prestation → versement), génération PDF, politique d'annulation graduée |
| Paiement & Finances | FR29-FR38 | 10 | Escrow Mobile Money, double passerelle avec failover, versement multi-canal, dashboard financier, commission "cachet intact" |
| Communication | FR39-FR44 | 6 | Messagerie temps réel WhatsApp-style (texte, audio, photos), détection de coordonnées (regex côté serveur), push notifications FCM |
| Suivi de Prestation & Évaluation | FR45-FR51 | 7 | Tracking temps réel jour J (5 statuts), géolocalisation check-in, évaluation bilatérale multi-critères, enrichissement portfolio |
| Gestion des Talents & Calendrier | FR52-FR59 | 8 | Calendrier intelligent avec alertes, gestion multi-talents par manager, niveaux auto (Nouveau→Elite), analytics profil |
| Administration & Gouvernance | FR60-FR72 | 13 | Dashboards multi-vues temps réel, workflow litiges, rapport traçabilité horodaté, rôles collaborateurs spécialisés, piste d'audit complète |

**Non-Functional Requirements:**

52 NFRs critiques pour les décisions architecturales :

| Catégorie | NFRs | Nb | Impact architectural |
|---|---|---|---|
| Performance | NFR1-NFR10 | 10 | Pages < 3s (3G CI), API < 500ms, Mobile Money < 15s, Flutter < 3s démarrage sur 2 Go RAM, 1000 simultanés au lancement |
| Sécurité | NFR11-NFR22 | 12 | AES-256 repos, TLS 1.3, bcrypt 12 rounds, JWT 1h/7j, rate limiting 60/min, CSRF/XSS/SQLi, stockage séparé CNI, conformité ARTCI |
| Scalabilité | NFR23-NFR28 | 6 | 10 000 simultanés horizon 12 mois, 100 Go média, pics weekend x3, extensibilité multi-pays multi-devise |
| Fiabilité | NFR29-NFR35 | 7 | Uptime 99,5% global / 99,9% vendredi-samedi, failover < 30s, sauvegardes 6h, webhooks idempotents avec retry exponentiel |
| Accessibilité | NFR36-NFR41 | 6 | WCAG 2.1 AA, Dynamic Type, mode sombre, français unique MVP, écrans 4,7"→6,7" |
| Intégration | NFR42-NFR47 | 6 | Webhooks Paystack/CinetPay avec signature, FCM iOS+Android, CDN Afrique Ouest, Google Maps/OSM, PDF serveur, interfaces abstraites |
| Maintenabilité | NFR48-NFR52 | 5 | Conventions Laravel PSR-12 + Flutter BLoC/Riverpod, OpenAPI Swagger auto-généré, logs JSON centralisés 90j, CI/CD avec rollback < 5min |

**Scale & Complexity:**

- Domaine principal : **Full-stack multi-plateforme** (API REST + Mobile Flutter + Web Admin + Web SSR)
- Niveau de complexité : **Élevé**
- Composants architecturaux estimés : **12-15** (API Gateway, Auth Service, Booking Engine, Payment/Escrow, Messaging, Notification, Media/CDN, Search, Calendar, Admin Dashboard, Public Web, Mobile App, Audit/Logging, Geolocation, Contract/PDF)

### Technical Constraints & Dependencies

**Stack technique validée (Product Brief + PRD) :**
- **Backend** : Laravel (PHP) — API REST + Admin Web (Blade) + Web Public (SSR)
- **Mobile** : Flutter (Dart) — Cross-platform iOS 14+ / Android 8.0+ (API 26)
- **Base de données** : MySQL (relationnel)
- **Paiement** : Paystack (principal) + CinetPay (backup) — Mobile Money + Carte
- **Push** : Firebase Cloud Messaging (FCM)
- **Stockage** : AWS S3 ou équivalent + CDN avec PoP Afrique de l'Ouest
- **Email** : Mailgun ou SendGrid
- **Géolocalisation** : Google Maps API ou OpenStreetMap
- **PDF** : DomPDF ou équivalent Laravel

**Contraintes d'infrastructure :**
- Latence cible < 200ms depuis Abidjan → cloud avec présence Afrique de l'Ouest (AWS Lagos, GCP South Africa, ou OVH/Scaleway + CDN)
- Compression images WebP côté serveur — bande passante critique en CI
- Connectivité intermittente → cache agressif, retry automatique, mode offline limité
- Appareils entrée de gamme (2 Go RAM) → performance Flutter prioritaire, glassmorphism dégradé

**Contraintes de conformité :**
- Loi ARTCI 2013-450 : déclaration fichiers données personnelles, consentement explicite, droit de suppression, notification violation 72h
- Loi 2013-546 : contrats électroniques avec identification parties, description prestation, acceptation bilatérale horodatée
- Directives BCEAO : opération via prestataire agréé EME (Paystack/CinetPay)
- TVA 18% sur frais de service — facturation conforme
- Rétention données : 5 ans financières, 1 an pièces d'identité post-vérification

**Contraintes app stores :**
- Apple Guidelines 3.1.1 : services physiques exemptés commission Apple 30%
- Google Play : Target API 34 minimum, AAB obligatoire
- Data Safety / App Privacy : déclarations transparentes

### Cross-Cutting Concerns Identified

| Préoccupation transverse | Composants impactés | Impact architectural |
|---|---|---|
| **Authentification & Autorisation** | Tous | Système multi-rôle (7 rôles), pattern Manager sans finance, Laravel Sanctum (mobile) + sessions (web), middleware de permissions granulaires |
| **Sécurité financière** | Paiement, Réservation, Admin | Escrow en tant que machine à états, double passerelle avec failover, webhooks idempotents, audit trail financier |
| **Communication temps réel** | Messagerie, Notifications, Check-in | WebSocket ou polling pour messagerie, FCM pour push, événements temps réel pour tracker jour J |
| **Audit & Traçabilité** | Admin, Paiement, Litiges | Journalisation exhaustive horodatée, piste d'audit sur toutes actions admin, rapport de traçabilité par réservation |
| **Performance réseau CI** | Mobile, API, Média | CDN africain, compression WebP, pagination cursor-based, cache HTTP + applicatif, lazy loading |
| **Offline & Résilience** | Mobile | Cache local SQLite/Hive (7j), file d'attente sync, skeleton screens, mode dégradé paiement |
| **Dégradation gracieuse GPU** | Mobile Flutter | 3 tiers de rendu glassmorphism, détection capacité GPU au runtime, composants adaptatifs |
| **Internationalisation future** | Base de données, Paiement, UI | Architecture extensible multi-devise, multi-langue (V3), mais français unique en MVP |
| **Conformité réglementaire** | Stockage, Auth, Paiement | Chiffrement données sensibles, politique de rétention, déclaration ARTCI, contrats conformes |
| **Détection anti-désintermédiation** | Messagerie | Analyse regex côté serveur des patterns de coordonnées, approche éducative (pas punitive) |

## Starter Template Evaluation

### Primary Technology Domain

Full-stack multi-plateforme : **API REST Laravel** (backend) + **Flutter mobile** (frontend primaire) + **Web Admin Blade/Tailwind** (frontend secondaire) + **Web Public SSR** (SEO)

Projet bi-repo nécessitant deux initialisations distinctes.

### Starter Options Considered

#### Backend — Laravel 12.x

| Option | Starter | Évaluation | Verdict |
|---|---|---|---|
| A | `laravel new --no-starter` | API-first, flexibilité totale, Blade natif | **Retenu** |
| B | Livewire Starter Kit | Admin réactif mais SPA-oriented, ajoute Livewire non prévu au PRD | Écarté |
| C | React/Vue Starter Kit | SPA web, contradictoire avec l'approche Blade + API REST | Écarté |

#### Mobile — Flutter 3.38.x

| Option | Starter | Évaluation | Verdict |
|---|---|---|---|
| A | `flutter create` (bare) | Flexibilité totale mais setup manuel extensif | Écarté |
| B | Very Good CLI (`very_good create flutter_app`) | Production-ready : flavors, BLoC, i18n, testing | **Retenu** |
| C | Templates GitHub (Clean Arch) | Qualité variable, maintenance incertaine | Écarté |

### Selected Starters

#### Backend: Laravel 12.x — Installation bare

**Rationale :**
- BookMi est API-first : Flutter mobile est la plateforme primaire
- Le PRD spécifie Blade + Tailwind pour l'admin — pas de framework SPA
- Sanctum inclus nativement dans Laravel 12 pour l'authentification mobile
- Les nouveaux starter kits Laravel 12 (React/Vue/Livewire) sont orientés SPA web — inadaptés pour un backend API pur
- Installation propre sans code généré inutile

**Initialization Command:**

```bash
laravel new bookmi --database=mysql --no-starter
```

**Architectural Decisions Provided:**

- **Language & Runtime :** PHP 8.2+ avec Laravel 12.x
- **Database :** MySQL (choisi pour sa fiabilité, sa performance, sa large adoption et son support natif des fonctions spatiales — choix solide pour une marketplace avec géolocalisation et données financières)
- **Authentication API :** Laravel Sanctum (tokens pour mobile, sessions pour web admin)
- **Styling Admin :** Tailwind CSS 4 via Vite
- **Template Engine :** Blade (SSR natif Laravel)
- **Testing :** PHPUnit + Pest (natif Laravel 12)
- **API Documentation :** OpenAPI/Swagger via L5-Swagger ou Scribe
- **Build Tooling :** Vite (natif Laravel 12)

**Project Structure Convention :**

```
bookmi/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/           # API controllers (Flutter)
│   │   │   ├── Admin/         # Admin web controllers (Blade)
│   │   │   └── Web/           # Web public controllers (SSR)
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   ├── Services/              # Business logic layer
│   ├── Repositories/          # Data access layer
│   ├── Events/
│   ├── Listeners/
│   ├── Jobs/                  # Queue jobs (versements, notifications)
│   └── Policies/              # Authorization policies (rôles)
├── routes/
│   ├── api.php                # API REST routes (Flutter)
│   ├── web.php                # Web public routes (SSR)
│   └── admin.php              # Admin routes (Blade)
├── resources/
│   ├── views/
│   │   ├── admin/             # Blade admin templates
│   │   ├── web/               # Blade public templates (SEO)
│   │   └── emails/            # Email templates
│   ├── css/
│   └── js/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── tests/
│   ├── Feature/
│   └── Unit/
└── config/
```

#### Mobile: Very Good CLI — Flutter 3.38.x + BLoC

**Rationale :**
- Build flavors (dev/staging/prod) préconfigurés — critique pour les passerelles de paiement (sandbox Paystack vs production)
- BLoC 9.0 recommandé pour les industries financières/régulées : event-driven audit trails traçables pour les flux escrow
- Infrastructure de test 100% coverage — aligné avec NFR52 (CI/CD avec tests)
- Internationalisation prête — base pour l'expansion multi-pays V3
- Conventions strictes — cohérence pour une équipe de développeurs multiples
- Support natif de la communauté Very Good Ventures, l'un des plus réputés en Flutter

**Initialization Command:**

```bash
very_good create flutter_app bookmi_app --description "BookMi - Marketplace de réservation de talents" --org "ci.bookmi"
```

**Architectural Decisions Provided:**

- **Language & Runtime :** Dart 3.x avec Flutter 3.38.x
- **State Management :** BLoC 9.0 (flutter_bloc) — event-driven, traçable, testable
- **Build Flavors :** Development, Staging, Production — configuration par environnement
- **Testing :** flutter_test + bloc_test + mocktail — 100% coverage setup
- **Linting :** very_good_analysis (règles strictes)
- **Internationalization :** flutter_localizations + arb files — français par défaut, extensible
- **Code Generation :** build_runner pour les modèles et les blocs

**Project Structure Convention :**

```
bookmi_app/
├── lib/
│   ├── app/                   # App-level configuration
│   │   ├── app.dart
│   │   └── routes/            # GoRouter navigation
│   ├── core/
│   │   ├── design_system/     # Glassmorphism components
│   │   │   ├── tokens/        # Colors, typography, spacing, glass
│   │   │   ├── components/    # GlassCard, GlassShield, ChatBubble...
│   │   │   └── theme/         # BookMi ThemeData + adaptive glass
│   │   ├── network/           # API client, interceptors, retry
│   │   ├── storage/           # Local cache (Hive/SQLite)
│   │   ├── utils/             # Device capability, formatters
│   │   └── constants/         # API URLs, asset paths
│   ├── features/
│   │   ├── auth/              # Login, register, OTP
│   │   ├── discovery/         # Search, filters, talent grid
│   │   ├── talent_profile/    # Profile, portfolio, packages
│   │   ├── booking/           # Reservation flow, stepper
│   │   ├── payment/           # Mobile Money, escrow, Glass Shield
│   │   ├── messaging/         # WhatsApp-style chat
│   │   ├── tracking/          # Day-J status tracker
│   │   ├── evaluation/        # Rating, reviews
│   │   ├── calendar/          # Talent calendar, availability
│   │   ├── dashboard/         # Financial dashboard, analytics
│   │   └── settings/          # Profile, notifications, preferences
│   └── l10n/                  # Localization (French default)
├── test/
│   ├── app/
│   ├── core/
│   └── features/
├── packages/                  # Internal packages if needed
└── assets/
    ├── images/
    ├── icons/
    └── fonts/                 # Nunito font files
```

**Note:** L'initialisation des deux projets (Laravel + Flutter) devrait constituer la première story d'implémentation.

## Core Architectural Decisions

### Decision Priority Analysis

**Critical Decisions (Bloquent l'implémentation) :**
- Database : MySQL
- Auth : Laravel Sanctum (mobile) + Sessions (web admin)
- Rôles : Spatie Laravel Permission v7 (7 rôles, 2 guards)
- State management : BLoC 9.0
- Cache/Queue : Redis + Laravel Horizon
- Temps réel : Laravel Reverb (WebSocket)

**Important Decisions (Façonnent l'architecture) :**
- HTTP client Flutter : Dio
- Navigation Flutter : GoRouter
- Stockage local : Hive
- CDN : Cloudflare
- Cloud : Hostinger VPS
- CI/CD : GitHub Actions
- Monitoring : Sentry + Laravel Telescope/Horizon

**Deferred Decisions (Post-MVP) :**
- Recherche intelligente ML (V2 — SQL + filtres suffisants pour 200 talents)
- Multi-devise / Multi-langue (V3 — français unique MVP)
- Load balancer horizontal (si > 10 000 simultanés)
- Log management centralisé (ELK/Grafana — logs fichiers suffisants MVP)

### Data Architecture

| Décision | Choix | Version | Rationale | Affecte |
|---|---|---|---|---|
| Database | MySQL | 8.x | Fiabilité, performance, large adoption, support spatial natif (ST_Distance_Sphere pour géolocalisation), excellent support Laravel — choix solide pour marketplace + données financières | Tous les composants |
| ORM | Eloquent (natif Laravel) | — | Standard Laravel, conventions fluentes, relations puissantes. Pas de raison de sortir de l'écosystème | Backend |
| Pattern accès données | Service Layer + Repository | — | Services = logique métier (escrow, versement, niveaux). Repositories = abstraction accès données via interfaces (NFR47 : remplacement fournisseur) | Backend |
| Validation | Laravel Form Requests | — | Validation déclarative, messages d'erreur en français, réutilisable API + web | Backend API |
| Migrations | Laravel Migrations (versionnées) | — | Standard Laravel, rollback, seeds pour données de référence (catégories, niveaux) | Backend |
| Cache | Redis | 7.x | Cache applicatif (profils, catégories), sessions web, queues, broadcasting — une infrastructure pour 4 usages | Tous |
| Queue | Redis + Laravel Horizon | — | Horizon = dashboard monitoring jobs. Jobs critiques : versements 24h, notifications push, webhooks paiement, génération PDF, compression images | Backend |
| Pagination API | Cursor-based (mobile) + Offset (admin) | — | Cursor pour scroll infini Flutter (performant), offset classique pour tables admin paginées | API + Flutter |

### Authentication & Security

| Décision | Choix | Version | Rationale | Affecte |
|---|---|---|---|---|
| Auth mobile | Laravel Sanctum (API tokens) | Natif Laravel 12 | Léger, tokens avec abilities granulaires par rôle | Flutter + API |
| Auth web admin | Sessions Laravel (cookie) | Natif Laravel 12 | Standard web, CSRF natif, pas de tokens pour Blade admin | Web admin |
| Rôles & Permissions | Spatie Laravel Permission | v7 | Référence Laravel RBAC. Multi-guard (api + web). 7 rôles : client, talent, manager, admin_ceo, admin_comptable, admin_controleur, admin_moderateur | Tous |
| Guards | 2 guards : `api` (Sanctum) + `web` (Session) | — | Séparation claire mobile/web. Permissions attribuées par guard | Auth |
| Token expiration | 24h access, re-login pour refresh | — | Plus adapté au mobile que 1h (moins de friction). Logout = révocation token | Flutter |
| Chiffrement repos | Laravel `encrypt()` (AES-256-CBC) | Natif Laravel | Chiffrement champs sensibles en base : pièces d'identité (avant suppression), données paiement intermédiaires | Backend |
| Rate limiting | Laravel throttle middleware | Natif Laravel | 60/min authentifié, 30/min non-authentifié, 10/min endpoints paiement | API |
| CORS | Laravel CORS middleware | Natif Laravel | Origines autorisées : app mobile, domaine bookmi.ci uniquement | API |
| Hashing passwords | bcrypt 12 rounds | Natif Laravel | Standard sécurité conforme NFR13 | Auth |

### API & Communication Patterns

| Décision | Choix | Version | Rationale | Affecte |
|---|---|---|---|---|
| API versioning | URL-based `/api/v1/` | — | Simple, explicite, évolution indépendante mobile/web | API |
| Format réponse | JSON envelope standard | — | `{ "data": {...}, "meta": {...}, "errors": [...] }` — cohérent, pagination dans meta | API + Flutter |
| Documentation API | Scribe | Latest | Auto-générée depuis routes/controllers Laravel. Plus léger que L5-Swagger, mise à jour automatique | Backend |
| Gestion d'erreurs | Format unifié + codes métier | — | HTTP status standard + codes BookMi (`BOOKING_UNAVAILABLE`, `PAYMENT_INSUFFICIENT_FUNDS`, `ESCROW_ALREADY_RELEASED`). Messages en français | API + Flutter |
| Temps réel | Laravel Reverb (WebSocket) | Natif Laravel 12 | Serveur WebSocket first-party, gratuit, intégré broadcasting/Echo. Usages : messagerie, tracker jour J, dashboard admin live | Backend + Flutter |
| Client WebSocket | laravel_echo_flutter + web_socket_channel | Latest | Compatibilité native protocole Pusher (Reverb). Private channels (messagerie), presence channels (tracker jour J) | Flutter |
| Push notifications | Firebase Cloud Messaging (FCM) | Latest | Natif Flutter iOS+Android, rich notifications avec actions. Déclenchées via Laravel Events → Jobs → FCM API | Backend + Flutter |
| Email transactionnel | Mailgun | — | Fiable, bon deliverability Afrique, API simple, intégration Laravel native | Backend |

### Frontend Architecture (Flutter)

| Décision | Choix | Version | Rationale | Affecte |
|---|---|---|---|---|
| State Management | BLoC 9.0 (flutter_bloc) | 9.x | Event-driven, traçable, testable — idéal pour flux financiers escrow | Flutter |
| Navigation | GoRouter | Latest | Routing déclaratif, deep linking natif (`bookmi.ci/talent/dj-kerozen`), guards auth, nested navigation | Flutter |
| HTTP Client | Dio | Latest | Interceptors (auth token, retry, logging), upload fichiers portfolio, timeout, annulation requête | Flutter |
| Stockage local | Hive | Latest | NoSQL léger et rapide, pas de dépendance native, cache offline (profils, réservations, calendrier). Rétention 7j | Flutter |
| Stockage sécurisé | flutter_secure_storage | Latest | Tokens Sanctum et données sensibles. Keychain (iOS) / KeyStore (Android) | Flutter |
| Images réseau | cached_network_image | Latest | Cache réseau automatique — critique pour bande passante CI | Flutter |
| Compression images | flutter_image_compress | Latest | Compression avant upload portfolio — réduit consommation data | Flutter |
| PDF viewer | flutter_pdfview | Latest | Affichage contrats PDF générés côté serveur | Flutter |
| Géolocalisation | geolocator + geocoding | Latest | Check-in jour J, recherche par proximité | Flutter |
| Push notifications | firebase_messaging + flutter_local_notifications | Latest | FCM pour push distantes, local pour rappels programmés | Flutter |

### Infrastructure & Deployment

| Décision | Choix | Version | Rationale | Affecte |
|---|---|---|---|---|
| Cloud provider | Hostinger VPS (KVM2 ou supérieur) | — | Bon rapport qualité/prix startup africaine. Datacenters Europe/US + CDN Cloudflare pour latence CI. Full root access pour Docker, Supervisor, Redis | Infrastructure |
| CDN | Cloudflare (gratuit/pro) | — | PoP Afrique (Lagos, Johannesburg, Nairobi), cache assets, protection DDoS, SSL gratuit. Latence < 200ms Abidjan | Infrastructure |
| Stockage média | Hostinger VPS local + Cloudflare CDN | — | Portfolio photos/vidéos stockés sur le VPS (disque SSD). Cloudflare CDN pour distribution globale. 100 Go lancement, extensible via upgrade VPS ou migration S3 | Backend + Flutter |
| CI/CD | GitHub Actions | — | Pipeline : lint → test → build → deploy. Rollback < 5min via tags/releases | DevOps |
| Conteneurisation | Docker + Docker Compose | — | Environnements dev reproductibles. Production : Docker sur Hostinger VPS + Nginx reverse proxy | DevOps |
| Reverse proxy | Nginx | — | Terminaison SSL, proxy Laravel (PHP-FPM) + Reverb (WebSocket), compression Gzip/Brotli | Infrastructure |
| Process manager | Supervisor | — | Maintient en vie : PHP-FPM, Laravel Horizon (queues), Laravel Reverb (WebSocket) | Infrastructure |
| Monitoring erreurs | Sentry (gratuit tier) | — | Capture erreurs Laravel + Flutter. Stack traces, contexte utilisateur. Alertes Slack/email | Tous |
| Monitoring app | Laravel Telescope (dev) + Horizon (prod) | Natif Laravel | Telescope : debug dev (requêtes, jobs, events). Horizon : monitoring queues production | Backend |
| Logs | Monolog → JSON structuré | Natif Laravel | Logs JSON fichiers + rotation quotidienne. Rétention 90j | Backend |
| Sauvegardes | Spatie Laravel Backup | Latest | Sauvegardes auto DB + fichiers toutes les 6h → stockage local VPS + copie externe (ex: Google Drive ou S3). Rétention 30j. Alertes échec | Infrastructure |
| SSL | Cloudflare SSL (full strict) | — | Certificat gratuit, renouvellement auto, HTTPS forcé | Infrastructure |

### Decision Impact Analysis

**Séquence d'implémentation :**
1. Infrastructure : Docker + MySQL + Redis + Nginx
2. Laravel bare install + Sanctum + Spatie Permission (auth/rôles)
3. Flutter Very Good CLI + Dio + GoRouter + Hive (fondation mobile)
4. Design system Flutter (tokens + composants glassmorphism)
5. API REST v1 (endpoints core par domaine)
6. Reverb WebSocket (messagerie + tracking jour J)
7. Intégrations tierces (Paystack, FCM, Mailgun, Cloudflare)

**Dépendances inter-composants :**

| Composant | Dépend de | Bloque |
|---|---|---|
| Auth (Sanctum + Spatie) | MySQL, Redis | Tous les endpoints API |
| API REST v1 | Auth, Eloquent Models | Flutter mobile, Web admin |
| Reverb (WebSocket) | Auth, Redis | Messagerie, Tracker jour J |
| Paiement (Paystack/CinetPay) | API REST, Queue (Horizon) | Réservation complète, escrow |
| Flutter Design System | — | Tous les écrans Flutter |
| Flutter API Layer (Dio) | API REST déployée | Toutes les features Flutter |
| Dashboard Admin (Blade) | API REST, Auth web | Opérations quotidiennes |

## Implementation Patterns & Consistency Rules

### Pattern Categories Defined

**Points de conflit critiques identifiés :** 47 zones où des agents IA pourraient faire des choix divergents, réparties en 5 catégories.

### Naming Patterns

#### Database Naming Conventions

| Élément | Convention | Exemple | Anti-pattern |
|---|---|---|---|
| Tables | `snake_case` pluriel | `users`, `booking_requests`, `talent_profiles` | `Users`, `BookingRequest`, `talentProfile` |
| Colonnes | `snake_case` singulier | `first_name`, `is_verified`, `cachet_amount` | `firstName`, `IsVerified`, `cachetAmount` |
| Clés primaires | `id` (auto-increment bigint) | `users.id` | `user_id`, `pk_user`, `ID` |
| Clés étrangères | `{table_singulier}_id` | `bookings.talent_id`, `messages.sender_id` | `fk_talent`, `talentId`, `talent_fk` |
| Tables pivot | `{table1_singulier}_{table2_singulier}` alpha | `category_talent`, `permission_role` | `talent_categories`, `TalentCategory` |
| Index | `{table}_{colonnes}_index` | `bookings_talent_id_status_index` | `idx_bookings_talent`, `booking_idx_1` |
| Timestamps | `created_at`, `updated_at`, `deleted_at` | Convention Eloquent native | `createdAt`, `date_creation`, `timestamp` |
| Booléens | `is_` ou `has_` préfixe | `is_verified`, `has_portfolio`, `is_active` | `verified`, `active`, `portfolio_exists` |
| Montants | `_amount` suffixe, stockés en **centimes (int)** | `cachet_amount = 15000000` (150 000 FCFA) | Float/decimal, stockage en unités |
| Enums | `snake_case` valeurs | `status: 'pending', 'confirmed', 'completed'` | `Status: 'Pending'`, `status: 'PENDING'` |

#### API Naming Conventions

| Élément | Convention | Exemple | Anti-pattern |
|---|---|---|---|
| Endpoints | `snake_case` pluriel, ressources REST | `/api/v1/talents`, `/api/v1/booking_requests` | `/api/v1/Talent`, `/api/v1/getBookings` |
| Paramètres URL | `snake_case` | `/api/v1/talents?category_id=5&min_cachet=50000` | `categoryId`, `minCachet` |
| Route params | `{model}` singulier | `/api/v1/talents/{talent}` (route model binding) | `/api/v1/talents/:id`, `/api/v1/talents/{talent_id}` |
| Actions custom | Verbes POST sur sous-ressource | `POST /api/v1/bookings/{booking}/confirm` | `POST /api/v1/confirmBooking` |
| Headers custom | `X-BookMi-` préfixe | `X-BookMi-Device-Tier`, `X-BookMi-App-Version` | `BookMi-Device`, `x_device_tier` |
| Versioning | URL-based `/api/v1/` | `/api/v1/talents` | Header versioning, query param `?v=1` |

#### Code Naming Conventions — Laravel (PHP)

| Élément | Convention | Exemple | Anti-pattern |
|---|---|---|---|
| Controllers | `PascalCase` + `Controller` suffixe | `BookingRequestController`, `TalentProfileController` | `bookingController`, `Bookings` |
| Models | `PascalCase` singulier | `BookingRequest`, `TalentProfile`, `User` | `booking_request`, `Bookings`, `users` |
| Services | `PascalCase` + `Service` suffixe | `EscrowService`, `BookingService`, `NotificationService` | `escrow_service`, `EscrowHelper` |
| Repositories | `PascalCase` + `Repository` suffixe | `TalentRepository`, `BookingRepository` | `TalentRepo`, `talent_repository` |
| Form Requests | `PascalCase` + `Request` suffixe | `StoreBookingRequest`, `UpdateTalentProfileRequest` | `BookingValidation`, `booking_request` |
| Jobs | `PascalCase` verbe + nom | `ProcessPayout`, `SendBookingConfirmation`, `CompressPortfolioImage` | `payout_job`, `PayoutProcessor` |
| Events | `PascalCase` nom + passé | `BookingConfirmed`, `PaymentReceived`, `TalentLevelUpgraded` | `booking_confirmed`, `OnBookingConfirm` |
| Listeners | `PascalCase` verbe + complément | `SendBookingNotification`, `UpdateTalentStatistics` | `BookingNotificationListener`, `handle_booking` |
| Policies | `PascalCase` + `Policy` suffixe | `BookingPolicy`, `TalentProfilePolicy` | `BookingAuth`, `booking_policy` |
| Middlewares | `PascalCase` descriptif | `EnsureEmailIsVerified`, `ThrottlePaymentRequests` | `auth_check`, `CheckAuth` |
| Méthodes | `camelCase` verbe-first | `confirmBooking()`, `calculateCommission()`, `getAvailableSlots()` | `booking_confirm()`, `ConfirmBooking()` |
| Variables | `camelCase` | `$talentProfile`, `$cachetAmount`, `$bookingStatus` | `$talent_profile`, `$TalentProfile` |
| Config keys | `snake_case` dot notation | `config('bookmi.commission_rate')`, `config('bookmi.escrow.hold_hours')` | `config('BookMi.commissionRate')` |
| Routes nommées | `dot.notation` | `api.v1.talents.index`, `admin.bookings.show` | `api-talents-index`, `talent_list` |

#### Code Naming Conventions — Flutter (Dart)

| Élément | Convention | Exemple | Anti-pattern |
|---|---|---|---|
| Fichiers | `snake_case.dart` | `booking_request_bloc.dart`, `glass_card.dart` | `BookingRequestBloc.dart`, `glassCard.dart` |
| Classes | `PascalCase` | `BookingRequestBloc`, `GlassCard`, `TalentProfile` | `booking_request_bloc`, `glasscard` |
| BLoC Events | `PascalCase` verbe + nom | `BookingConfirmed`, `PaymentInitiated`, `TalentSearched` | `confirmBooking`, `BOOKING_CONFIRMED` |
| BLoC States | `PascalCase` nom + status | `BookingInitial`, `BookingLoading`, `BookingSuccess`, `BookingFailure` | `booking_loading`, `LoadingState` |
| Variables | `camelCase` | `talentProfile`, `cachetAmount`, `isVerified` | `talent_profile`, `TalentProfile` |
| Constantes | `camelCase` (Dart convention) | `defaultPadding`, `maxCachetAmount`, `primaryNavy` | `DEFAULT_PADDING`, `MAX_CACHET`, `kPrimaryNavy` |
| Privées | `_camelCase` préfixe underscore | `_isLoading`, `_cachetController` | `isLoadingPrivate`, `m_isLoading` |
| Méthodes | `camelCase` verbe-first | `fetchTalents()`, `submitBooking()`, `calculateTotal()` | `TalentsFetch()`, `get_talents()` |
| Enums | `PascalCase` nom, `camelCase` valeurs | `BookingStatus.pending`, `BookingStatus.confirmed` | `BOOKING_STATUS.PENDING`, `booking_status.Pending` |
| Extensions | `PascalCase` + `Extension` | `StringExtension`, `DateTimeExtension` | `string_ext`, `DateExt` |
| Widgets | `PascalCase` descriptif | `TalentCard`, `GlassAppBar`, `BookingStatusTracker` | `talent_card_widget`, `talentCard` |
| Repositories | `PascalCase` + `Repository` | `TalentRepository`, `BookingRepository` | `TalentRepo`, `talent_api` |

### Structure Patterns

#### Project Organization — Laravel

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/V1/              # 1 controller par ressource REST
│   │   │   ├── TalentController.php
│   │   │   ├── BookingRequestController.php
│   │   │   └── MessageController.php
│   │   ├── Admin/               # 1 controller par section admin
│   │   │   ├── DashboardController.php
│   │   │   └── DisputeController.php
│   │   └── Web/                 # 1 controller par page publique
│   │       ├── TalentPageController.php
│   │       └── HomeController.php
│   ├── Middleware/               # Middlewares custom
│   └── Requests/
│       ├── Api/                 # Form Requests API
│       │   ├── StoreBookingRequest.php
│       │   └── UpdateTalentProfileRequest.php
│       └── Admin/               # Form Requests Admin
├── Models/                      # Eloquent models (flat, pas de sous-dossiers)
│   ├── User.php
│   ├── TalentProfile.php
│   ├── BookingRequest.php
│   └── Transaction.php
├── Services/                    # Logique métier (1 service par domaine)
│   ├── BookingService.php
│   ├── EscrowService.php
│   ├── PaymentService.php
│   ├── NotificationService.php
│   └── TalentLevelService.php
├── Repositories/                # Abstraction accès données
│   ├── Contracts/               # Interfaces
│   │   ├── TalentRepositoryInterface.php
│   │   └── BookingRepositoryInterface.php
│   └── Eloquent/                # Implémentations
│       ├── TalentRepository.php
│       └── BookingRepository.php
├── Events/                      # Domain events (flat)
├── Listeners/                   # Event handlers (flat)
├── Jobs/                        # Queue jobs (flat)
├── Policies/                    # Authorization (flat)
├── Enums/                       # PHP 8.1 enums
│   ├── BookingStatus.php
│   ├── PaymentStatus.php
│   └── UserRole.php
├── Notifications/               # Laravel Notifications (flat)
└── Exceptions/                  # Custom exceptions
    ├── BookingException.php
    └── PaymentException.php
```

**Règles strictes :**
- **1 Model = 1 fichier** dans `app/Models/` — jamais de sous-dossiers
- **1 Service = 1 domaine métier** — `EscrowService` ne gère PAS les notifications
- **Controllers API** : uniquement 7 méthodes REST (`index`, `store`, `show`, `update`, `destroy`) + actions custom via méthodes additionnelles
- **Tests miroirs** : `tests/Feature/Api/V1/TalentControllerTest.php` reflète `app/Http/Controllers/Api/V1/TalentController.php`

#### Project Organization — Flutter

```
lib/features/{feature_name}/
├── bloc/                        # BLoC pattern obligatoire
│   ├── {feature}_bloc.dart
│   ├── {feature}_event.dart
│   └── {feature}_state.dart
├── data/
│   ├── models/                  # Data models (JSON serialization)
│   │   └── {model}_model.dart
│   ├── repositories/            # Data source abstraction
│   │   └── {feature}_repository.dart
│   └── datasources/             # API calls / Local storage
│       ├── {feature}_remote_datasource.dart
│       └── {feature}_local_datasource.dart
├── presentation/
│   ├── pages/                   # Full-screen pages
│   │   └── {feature}_page.dart
│   ├── widgets/                 # Feature-specific widgets
│   │   └── {widget_name}.dart
│   └── {feature}_view.dart      # Main view (BLoC consumer)
└── {feature}.dart               # Barrel file (exports publics)
```

**Règles strictes :**
- **Chaque feature est autonome** — jamais d'import direct entre features (passer par le BLoC ou un service partagé dans `core/`)
- **Barrel files obligatoires** — chaque feature exporte via `{feature}.dart`
- **Pages vs Widgets** : une Page = un écran complet avec route, un Widget = un composant réutilisable
- **Tests miroirs** : `test/features/booking/bloc/booking_bloc_test.dart` reflète `lib/features/booking/bloc/booking_bloc.dart`

#### File Structure Patterns

| Type de fichier | Emplacement Laravel | Emplacement Flutter |
|---|---|---|
| Tests unitaires | `tests/Unit/{Domain}/` | `test/features/{feature}/bloc/` |
| Tests feature/intégration | `tests/Feature/Api/V1/`, `tests/Feature/Admin/` | `test/features/{feature}/presentation/` |
| Config | `config/{domain}.php` | `lib/core/constants/` |
| Variables d'env | `.env`, `.env.example` | Flavors : `lib/app/env/` (dev, staging, prod) |
| Assets statiques | `public/`, `resources/` | `assets/{images,icons,fonts}/` |
| Migrations | `database/migrations/{date}_{action}_{table}.php` | N/A |
| Seeders | `database/seeders/` | N/A |
| Traductions | `lang/fr/` | `lib/l10n/arb/app_fr.arb` |

### Format Patterns

#### API Response Formats

**Réponse succès (single resource) :**

```json
{
  "data": {
    "id": 42,
    "type": "talent_profile",
    "attributes": {
      "stage_name": "DJ Kerozen",
      "cachet_amount": 15000000,
      "is_verified": true,
      "category": {
        "id": 3,
        "name": "DJ"
      },
      "created_at": "2026-02-17T14:30:00Z",
      "updated_at": "2026-02-17T14:30:00Z"
    }
  }
}
```

**Réponse succès (collection paginée cursor-based) :**

```json
{
  "data": [
    { "id": 42, "type": "talent_profile", "attributes": { "..." } },
    { "id": 43, "type": "talent_profile", "attributes": { "..." } }
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

**Réponse succès (collection paginée offset — admin) :**

```json
{
  "data": [ "..." ],
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 25,
      "total": 156,
      "last_page": 7
    }
  }
}
```

**Réponse erreur :**

```json
{
  "error": {
    "code": "BOOKING_UNAVAILABLE",
    "message": "Ce talent n'est pas disponible à la date sélectionnée.",
    "status": 422,
    "details": {
      "field": "event_date",
      "reason": "conflict",
      "next_available": "2026-03-15"
    }
  }
}
```

**Réponse erreur validation :**

```json
{
  "error": {
    "code": "VALIDATION_FAILED",
    "message": "Les données envoyées sont invalides.",
    "status": 422,
    "details": {
      "errors": {
        "cachet_amount": ["Le montant du cachet est obligatoire."],
        "event_date": ["La date doit être dans le futur."]
      }
    }
  }
}
```

#### Data Exchange Formats

| Donnée | Format | Exemple | Règle |
|---|---|---|---|
| JSON field naming | `snake_case` partout | `"stage_name"`, `"cachet_amount"` | Cohérence Laravel ↔ Flutter (pas de transformation) |
| Dates API | ISO 8601 UTC | `"2026-02-17T14:30:00Z"` | Toujours UTC côté API, conversion timezone côté Flutter |
| Dates affichage Flutter | Format local | `"17 février 2026 à 14h30"` | `intl` package, timezone Afrique/Abidjan (UTC+0) |
| Montants API | Entier centimes FCFA | `15000000` (= 150 000 FCFA) | Jamais de float. Flutter formate l'affichage |
| Montants affichage | String formaté | `"150 000 FCFA"` | Formatage côté Flutter avec séparateur milliers espace |
| Booléens | `true` / `false` natif JSON | `"is_verified": true` | Jamais `1/0`, `"true"/"false"` string |
| Null | `null` natif JSON | `"manager_id": null` | Champ présent avec `null`, jamais omis |
| IDs | Integer (bigint) | `"id": 42` | Jamais UUID pour MVP (simplicité + performance) |
| Téléphone | Format E.164 | `"+2250700000000"` | Stockage E.164, affichage local côté Flutter |
| Images URL | URL CDN complète | `"https://cdn.bookmi.ci/portfolios/42/photo1.webp"` | Toujours URL absolue, jamais relative |

#### HTTP Status Codes Convention

| Situation | Status Code | Usage |
|---|---|---|
| Succès lecture | `200 OK` | GET réussi |
| Création réussie | `201 Created` | POST réussi (booking, inscription) |
| Succès sans contenu | `204 No Content` | DELETE réussi, action sans retour |
| Validation échouée | `422 Unprocessable Entity` | Form Request validation errors |
| Non authentifié | `401 Unauthorized` | Token manquant/expiré |
| Non autorisé | `403 Forbidden` | Rôle insuffisant (manager accès finance) |
| Non trouvé | `404 Not Found` | Ressource inexistante |
| Conflit | `409 Conflict` | Booking doublon, état incohérent |
| Rate limited | `429 Too Many Requests` | Throttle dépassé |
| Erreur serveur | `500 Internal Server Error` | Exception non gérée (loggée Sentry) |

### Communication Patterns

#### Event System Patterns — Laravel

| Convention | Pattern | Exemple |
|---|---|---|
| Nommage événement | `PascalCase` passé composé (ce qui s'est passé) | `BookingConfirmed`, `PaymentReceived`, `TalentLevelUpgraded` |
| Payload | Eloquent Model ou DTO typé | `BookingConfirmed(Booking $booking)` |
| Broadcasting channel | `snake_case` dot notation | `private:booking.{bookingId}`, `presence:tracking.{bookingId}` |
| Queue channel | `{domaine}` simple | Queue `payments`, `notifications`, `media`, `default` |
| Job naming | `PascalCase` verbe + nom | `ProcessPayout`, `CompressPortfolioImage` |
| Notification naming | `PascalCase` action + nom | `BookingConfirmedNotification`, `PayoutCompletedNotification` |

**Queues Laravel Horizon — 4 pipelines :**

| Pipeline | Queue | Priorité | Workers | Jobs typiques |
|---|---|---|---|---|
| Paiements | `payments` | Critique | 3 | `ProcessPayout`, `HandlePaymentWebhook`, `ReleaseEscrow` |
| Notifications | `notifications` | Haute | 2 | `SendPushNotification`, `SendBookingEmail` |
| Médias | `media` | Normale | 2 | `CompressPortfolioImage`, `GenerateContractPdf` |
| Défaut | `default` | Basse | 1 | `UpdateTalentStatistics`, `CleanExpiredTokens` |

#### State Management Patterns — Flutter BLoC

**Structure BLoC obligatoire par feature :**

```dart
// booking_event.dart
sealed class BookingEvent {}
final class BookingFetched extends BookingEvent {
  final int talentId;
  const BookingFetched({required this.talentId});
}
final class BookingSubmitted extends BookingEvent {
  final BookingFormData formData;
  const BookingSubmitted({required this.formData});
}
final class BookingConfirmationRequested extends BookingEvent {
  final int bookingId;
  const BookingConfirmationRequested({required this.bookingId});
}

// booking_state.dart
sealed class BookingState {}
final class BookingInitial extends BookingState {}
final class BookingLoading extends BookingState {}
final class BookingLoaded extends BookingState {
  final BookingDetails booking;
  const BookingLoaded({required this.booking});
}
final class BookingSubmitSuccess extends BookingState {
  final int bookingId;
  const BookingSubmitSuccess({required this.bookingId});
}
final class BookingFailure extends BookingState {
  final String code;
  final String message;
  const BookingFailure({required this.code, required this.message});
}
```

**Conventions BLoC strictes :**

| Convention | Règle | Exemple |
|---|---|---|
| Events | Passé/Participe passé — ce qui s'est produit | `BookingFetched`, `PaymentInitiated` |
| States | Nom + statut — état résultant | `BookingLoading`, `BookingSuccess`, `BookingFailure` |
| Sealed classes | `sealed class` pour Events ET States | Permet exhaustive pattern matching Dart 3 |
| Constructeurs | `const` + `required` named params | `const BookingLoaded({required this.booking})` |
| Équité | `Equatable` mixin sur tous les states | Empêche rebuilds inutiles |
| Erreurs dans states | `code` + `message` (jamais Exception brute) | `BookingFailure(code: 'BOOKING_UNAVAILABLE', message: '...')` |

#### WebSocket Channel Patterns — Laravel Reverb

| Type channel | Pattern | Usage | Exemple |
|---|---|---|---|
| Private | `private:user.{userId}` | Notifications personnelles | Nouveau message, booking accepté |
| Private | `private:booking.{bookingId}` | Updates booking | Changement statut, paiement reçu |
| Presence | `presence:tracking.{bookingId}` | Tracker jour J | Online status, check-in events |
| Private | `private:conversation.{conversationId}` | Chat messagerie | Messages temps réel |

**Nommage événements broadcast :**
- Pattern : `.{action}` avec point initial
- Exemples : `.message.sent`, `.booking.updated`, `.tracking.checkin`

### Process Patterns

#### Error Handling Patterns — Laravel

```php
// Pattern standard : Custom exceptions avec codes métier
class BookingException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $code,    // Code métier BookMi
        public readonly int $statusCode, // HTTP status
        public readonly ?array $details = null,
    ) {
        parent::__construct($message);
    }
}

// Usage dans Service
throw new BookingException(
    message: "Ce talent n'est pas disponible à la date sélectionnée.",
    code: 'BOOKING_UNAVAILABLE',
    statusCode: 422,
    details: ['next_available' => '2026-03-15'],
);

// Handler global (app/Exceptions/Handler.php)
// Toutes les BookingException → format JSON standard
// Toutes les exceptions non gérées → 500 + log Sentry
```

**Codes d'erreur métier BookMi :**

| Préfixe | Domaine | Exemples |
|---|---|---|
| `AUTH_` | Authentification | `AUTH_INVALID_CREDENTIALS`, `AUTH_TOKEN_EXPIRED`, `AUTH_PHONE_NOT_VERIFIED` |
| `BOOKING_` | Réservation | `BOOKING_UNAVAILABLE`, `BOOKING_ALREADY_CONFIRMED`, `BOOKING_CANCELLATION_TOO_LATE` |
| `PAYMENT_` | Paiement | `PAYMENT_INSUFFICIENT_FUNDS`, `PAYMENT_GATEWAY_ERROR`, `PAYMENT_TIMEOUT` |
| `ESCROW_` | Séquestre | `ESCROW_ALREADY_RELEASED`, `ESCROW_DISPUTE_ACTIVE`, `ESCROW_HOLD_PERIOD` |
| `TALENT_` | Talent | `TALENT_NOT_FOUND`, `TALENT_PROFILE_INCOMPLETE`, `TALENT_SUSPENDED` |
| `MEDIA_` | Médias | `MEDIA_FILE_TOO_LARGE`, `MEDIA_INVALID_FORMAT`, `MEDIA_UPLOAD_FAILED` |
| `VALIDATION_` | Validation | `VALIDATION_FAILED` (catch-all Form Request) |

#### Error Handling Patterns — Flutter

```dart
// Pattern standard : Result type avec sealed class
sealed class ApiResult<T> {
  const ApiResult();
}
final class ApiSuccess<T> extends ApiResult<T> {
  final T data;
  const ApiSuccess(this.data);
}
final class ApiFailure<T> extends ApiResult<T> {
  final String code;
  final String message;
  final Map<String, dynamic>? details;
  const ApiFailure({required this.code, required this.message, this.details});
}

// Usage dans Repository
Future<ApiResult<BookingDetails>> fetchBooking(int id) async {
  try {
    final response = await _dio.get('/api/v1/bookings/$id');
    return ApiSuccess(BookingDetails.fromJson(response.data['data']));
  } on DioException catch (e) {
    final error = e.response?.data['error'];
    return ApiFailure(
      code: error?['code'] ?? 'NETWORK_ERROR',
      message: error?['message'] ?? 'Erreur de connexion',
    );
  }
}

// Usage dans BLoC
final result = await _repository.fetchBooking(event.bookingId);
switch (result) {
  case ApiSuccess(:final data):
    emit(BookingLoaded(booking: data));
  case ApiFailure(:final code, :final message):
    emit(BookingFailure(code: code, message: message));
}
```

#### Loading State Patterns — Flutter

| Pattern | Convention | Exemple |
|---|---|---|
| État initial | `{Feature}Initial` | `BookingInitial`, `TalentSearchInitial` |
| Chargement | `{Feature}Loading` | `BookingLoading` — affiche skeleton/shimmer |
| Succès | `{Feature}Loaded` ou `{Feature}Success` | `BookingLoaded(booking: ...)` |
| Erreur | `{Feature}Failure` | `BookingFailure(code: ..., message: ...)` |
| Action en cours | `{Feature}{Action}InProgress` | `BookingSubmitInProgress` — bouton disabled + spinner |
| Skeleton screens | Toujours pour les listes | `TalentCardSkeleton`, `BookingListSkeleton` |
| Pull-to-refresh | Via `RefreshIndicator` + re-emit event | Pas de state dédié, re-dispatch `BookingFetched` |

#### Retry & Resilience Patterns

| Situation | Pattern | Configuration |
|---|---|---|
| Requête API échouée (réseau) | Dio interceptor retry automatique | 3 tentatives, backoff exponentiel (1s, 2s, 4s), seulement erreurs réseau/timeout |
| Webhook paiement échoué | Laravel Job retry | 5 tentatives, backoff exponentiel (10s, 30s, 90s, 270s, 810s) |
| Upload média échoué | Retry manuel utilisateur | Bouton "Réessayer" visible, pas de retry auto (consommation data) |
| WebSocket déconnecté | Reconnexion automatique | Backoff exponentiel (1s, 2s, 4s, 8s), max 30s, abandon après 5min |
| Queue job échoué | Laravel failed_jobs table | Log détaillé, retry admin via Horizon, alerte Sentry après 3 échecs |

#### Authentication Flow Pattern

```
Mobile App (Flutter)                    API (Laravel)
    │                                       │
    ├── POST /api/v1/auth/register ────────>│ Créer User + envoi OTP SMS
    │<──────────────── 201 Created ─────────┤
    │                                       │
    ├── POST /api/v1/auth/verify-otp ──────>│ Vérifier OTP
    │<────── 200 + { token, user } ─────────┤ Sanctum token 24h
    │                                       │
    ├── GET /api/v1/me ────────────────────>│ Authorization: Bearer {token}
    │<────── 200 + user profile ────────────┤
    │                                       │
    ├── [Token expiré après 24h] ──────────>│
    │<────── 401 AUTH_TOKEN_EXPIRED ─────────┤
    │                                       │
    ├── POST /api/v1/auth/login ───────────>│ Re-authentification
    │<────── 200 + { token, user } ─────────┤ Nouveau token 24h
```

**Règle Flutter :** Un Dio interceptor intercepte TOUT 401, vide le secure storage, et redirige vers la page de login via GoRouter.

### Enforcement Guidelines

**Tous les agents IA DOIVENT :**

1. Utiliser `snake_case` pour tous les champs JSON, colonnes DB, endpoints API, et noms de fichiers Dart
2. Stocker tous les montants financiers en **centimes (int)** — jamais de float
3. Retourner toutes les dates API en **ISO 8601 UTC** (`Z` suffix)
4. Suivre le format de réponse JSON envelope (`data` / `error`) sans exception
5. Créer un fichier BLoC séparé pour Events, States, et Bloc (3 fichiers minimum par feature)
6. Utiliser `sealed class` pour tous les Events et States BLoC (Dart 3 exhaustive matching)
7. Nommer les Events BLoC au passé (`BookingFetched`) et les States avec statut (`BookingLoading`)
8. Appliquer le pattern Service/Repository côté Laravel — Controllers ne contiennent PAS de logique métier
9. Préfixer les codes d'erreur métier avec le domaine (`BOOKING_`, `PAYMENT_`, `ESCROW_`)
10. Toujours inclure `created_at` et `updated_at` dans les migrations (Eloquent timestamps)

**Vérification des patterns :**

- **Laravel** : Pint (PSR-12) + Larastan (analyse statique) intégrés au CI/CD
- **Flutter** : `very_good_analysis` (lint strict) + `dart analyze` intégrés au CI/CD
- **API** : Tests automatisés vérifient le format de réponse envelope sur chaque endpoint
- **Revue** : Tout PR vérifié contre ce document avant merge

**Processus de mise à jour des patterns :**

- Toute modification de pattern nécessite la mise à jour de ce document
- Les nouveaux patterns sont documentés AVANT l'implémentation
- En cas de conflit, ce document fait autorité

### Pattern Examples

#### Bon Exemple — Endpoint API complet

```php
// routes/api.php
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('booking_requests', BookingRequestController::class);
    Route::post('booking_requests/{booking_request}/confirm', [BookingRequestController::class, 'confirm']);
});

// app/Http/Controllers/Api/V1/BookingRequestController.php
class BookingRequestController extends Controller
{
    public function __construct(private BookingService $bookingService) {}

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $booking = $this->bookingService->createBooking(
            talent_id: $request->validated('talent_id'),
            client: $request->user(),
            data: $request->validated(),
        );

        return response()->json(['data' => new BookingResource($booking)], 201);
    }
}
```

#### Bon Exemple — Feature Flutter complète

```dart
// lib/features/booking/bloc/booking_event.dart
sealed class BookingEvent {}
final class BookingDetailsFetched extends BookingEvent {
  final int bookingId;
  const BookingDetailsFetched({required this.bookingId});
}

// lib/features/booking/bloc/booking_state.dart
sealed class BookingState {}
final class BookingInitial extends BookingState {}
final class BookingLoading extends BookingState {}
final class BookingDetailsLoaded extends BookingState {
  final BookingDetails booking;
  const BookingDetailsLoaded({required this.booking});
}
final class BookingFailure extends BookingState {
  final String code;
  final String message;
  const BookingFailure({required this.code, required this.message});
}

// lib/features/booking/bloc/booking_bloc.dart
class BookingBloc extends Bloc<BookingEvent, BookingState> {
  final BookingRepository _repository;

  BookingBloc({required BookingRepository repository})
      : _repository = repository,
        super(BookingInitial()) {
    on<BookingDetailsFetched>(_onDetailsFetched);
  }

  Future<void> _onDetailsFetched(
    BookingDetailsFetched event,
    Emitter<BookingState> emit,
  ) async {
    emit(BookingLoading());
    final result = await _repository.fetchBooking(event.bookingId);
    switch (result) {
      case ApiSuccess(:final data):
        emit(BookingDetailsLoaded(booking: data));
      case ApiFailure(:final code, :final message):
        emit(BookingFailure(code: code, message: message));
    }
  }
}
```

#### Anti-Patterns — À éviter absolument

```php
// ❌ Logique métier dans le Controller
public function store(Request $request) {
    $booking = new Booking();
    $booking->talent_id = $request->talentId;  // ❌ camelCase
    $booking->amount = $request->amount * 100;  // ❌ Conversion dans controller
    $booking->save();
    return $booking;  // ❌ Pas de envelope, pas de Resource
}

// ✅ Correct : Controller délègue au Service, Form Request valide
public function store(StoreBookingRequest $request): JsonResponse {
    $booking = $this->bookingService->createBooking($request->validated());
    return response()->json(['data' => new BookingResource($booking)], 201);
}
```

```dart
// ❌ Appel API direct dans le Widget
class BookingPage extends StatefulWidget {
  void _loadBooking() async {
    final response = await Dio().get('/api/v1/bookings/42');  // ❌ Pas de BLoC
    setState(() { booking = response.data; });  // ❌ setState direct
  }
}

// ✅ Correct : Widget consomme le BLoC
class BookingPage extends StatelessWidget {
  Widget build(BuildContext context) {
    return BlocBuilder<BookingBloc, BookingState>(
      builder: (context, state) => switch (state) {
        BookingLoading() => const BookingPageSkeleton(),
        BookingDetailsLoaded(:final booking) => BookingDetailsView(booking: booking),
        BookingFailure(:final message) => ErrorView(message: message),
        _ => const SizedBox.shrink(),
      },
    );
  }
}
```

## Project Structure & Boundaries

### Requirements to Structure Mapping

**Mapping des 8 domaines fonctionnels aux composants architecturaux :**

| Domaine FR | Laravel (Backend) | Flutter (Mobile) |
|---|---|---|
| **FR1-FR10** Gestion Utilisateurs & Identité | `Controllers/Api/V1/Auth*`, `Models/User`, `Services/AuthService`, Spatie Roles | `features/auth/`, `core/network/auth_interceptor` |
| **FR11-FR17** Découverte & Recherche | `Controllers/Api/V1/TalentController`, `Services/SearchService`, `Repositories/TalentRepository` | `features/discovery/`, `features/talent_profile/` |
| **FR18-FR28** Réservation & Contrats | `Controllers/Api/V1/BookingRequestController`, `Services/BookingService`, `Services/ContractService`, `Jobs/GenerateContractPdf` | `features/booking/` |
| **FR29-FR38** Paiement & Finances | `Controllers/Api/V1/PaymentController`, `Services/EscrowService`, `Services/PaymentService`, `Services/PayoutService`, `Jobs/ProcessPayout` | `features/payment/`, `features/dashboard/` |
| **FR39-FR44** Communication | `Controllers/Api/V1/MessageController`, `Events/MessageSent`, Broadcasting Reverb channels | `features/messaging/` |
| **FR45-FR51** Suivi & Évaluation | `Controllers/Api/V1/TrackingController`, `Controllers/Api/V1/ReviewController`, `Services/TrackingService` | `features/tracking/`, `features/evaluation/` |
| **FR52-FR59** Gestion Talents & Calendrier | `Controllers/Api/V1/CalendarController`, `Services/TalentLevelService`, `Services/CalendarService` | `features/calendar/`, `features/talent_profile/` |
| **FR60-FR72** Administration & Gouvernance | `Controllers/Admin/*`, `Services/DisputeService`, `Services/AuditService` | N/A (admin web uniquement) |

**Préoccupations transverses → Emplacements :**

| Concern | Laravel | Flutter |
|---|---|---|
| Auth & Permissions | `Middleware/`, `Policies/`, `config/permission.php` | `core/network/auth_interceptor.dart`, `app/routes/guards/` |
| Audit & Logging | `Services/AuditService`, `Listeners/LogActivity`, Monolog config | N/A (côté serveur) |
| Notifications | `Notifications/`, `Jobs/Send*Notification`, `Events/` | `core/notifications/`, `firebase_messaging` |
| Media & CDN | `Services/MediaService`, `Jobs/CompressPortfolioImage`, S3 driver | `core/network/image_cache/` |
| Cache & Performance | `config/cache.php`, Redis, `Repositories/` (cache layer) | `core/storage/`, Hive |
| Sécurité financière | `Services/EscrowService`, `Enums/PaymentStatus`, `Jobs/ProcessPayout` | `features/payment/` |

### Complete Project Directory Structure

#### Backend — Laravel (`bookmi/`)

```
bookmi/
├── .env.example
├── .env.testing
├── .gitignore
├── .editorconfig
├── .github/
│   └── workflows/
│       ├── ci.yml                       # Lint + Test + Build
│       ├── deploy-staging.yml           # Deploy staging (auto sur develop)
│       └── deploy-production.yml        # Deploy production (manual sur main)
├── docker-compose.yml                   # Dev : PHP-FPM + MySQL + Redis + Reverb
├── docker-compose.prod.yml              # Prod : PHP-FPM + Nginx + Supervisor
├── Dockerfile                           # PHP 8.2 + extensions
├── nginx/
│   ├── default.conf                     # Reverse proxy Laravel + Reverb
│   └── ssl/                             # Certificats (prod)
├── supervisor/
│   ├── horizon.conf                     # Laravel Horizon process
│   └── reverb.conf                      # Laravel Reverb process
├── composer.json
├── composer.lock
├── artisan
├── phpunit.xml
├── pint.json                            # Laravel Pint config (PSR-12)
├── phpstan.neon                         # Larastan config
│
├── app/
│   ├── Console/
│   │   ├── Kernel.php
│   │   └── Commands/
│   │       ├── CleanExpiredTokensCommand.php
│   │       └── RecalculateTalentLevelsCommand.php
│   │
│   ├── Enums/
│   │   ├── BookingStatus.php            # pending, accepted, paid, confirmed, completed, cancelled, disputed
│   │   ├── PaymentStatus.php            # initiated, processing, succeeded, failed, refunded
│   │   ├── EscrowStatus.php             # held, released, refunded, disputed
│   │   ├── UserRole.php                 # client, talent, manager, admin_ceo, admin_comptable, admin_controleur, admin_moderateur
│   │   ├── TalentLevel.php              # nouveau, confirme, populaire, elite
│   │   ├── MessageType.php              # text, audio, image
│   │   ├── TrackingStatus.php           # preparing, en_route, arrived, performing, completed
│   │   └── NotificationType.php         # booking, payment, message, system
│   │
│   ├── Exceptions/
│   │   ├── Handler.php                  # Global exception handler → JSON envelope
│   │   ├── BookingException.php
│   │   ├── PaymentException.php
│   │   ├── EscrowException.php
│   │   └── MediaException.php
│   │
│   ├── Events/
│   │   ├── BookingCreated.php
│   │   ├── BookingConfirmed.php
│   │   ├── BookingCancelled.php
│   │   ├── PaymentReceived.php
│   │   ├── EscrowReleased.php
│   │   ├── PayoutCompleted.php
│   │   ├── MessageSent.php
│   │   ├── TrackingStatusChanged.php
│   │   ├── ReviewSubmitted.php
│   │   └── TalentLevelUpgraded.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   └── V1/
│   │   │   │       ├── AuthController.php           # register, login, logout, verify-otp, me
│   │   │   │       ├── TalentController.php         # index (search), show
│   │   │   │       ├── TalentProfileController.php  # store, update (own profile)
│   │   │   │       ├── PortfolioController.php      # index, store, destroy (media)
│   │   │   │       ├── BookingRequestController.php # index, store, show, update + confirm, cancel
│   │   │   │       ├── PaymentController.php        # initiate, callback, status
│   │   │   │       ├── MessageController.php        # index (conversations), store, show (thread)
│   │   │   │       ├── ConversationController.php   # index, show
│   │   │   │       ├── TrackingController.php       # show, update (check-in, status)
│   │   │   │       ├── ReviewController.php         # store, index
│   │   │   │       ├── CalendarController.php       # index (availability), update (slots)
│   │   │   │       ├── NotificationController.php   # index, markRead
│   │   │   │       ├── DashboardController.php      # stats (talent/client)
│   │   │   │       └── CategoryController.php       # index (public)
│   │   │   │
│   │   │   ├── Admin/
│   │   │   │   ├── AdminDashboardController.php     # KPIs temps réel
│   │   │   │   ├── AdminUserController.php          # CRUD users, vérification
│   │   │   │   ├── AdminBookingController.php       # Liste, détail, interventions
│   │   │   │   ├── AdminDisputeController.php       # Workflow litiges
│   │   │   │   ├── AdminPayoutController.php        # Versements manuels, suivi
│   │   │   │   ├── AdminReportController.php        # Rapports, exports
│   │   │   │   └── AdminSettingsController.php      # Config plateforme
│   │   │   │
│   │   │   └── Web/
│   │   │       ├── HomeController.php               # Landing page
│   │   │       ├── TalentPageController.php         # Profil public SEO
│   │   │       └── PageController.php               # CGU, mentions légales, FAQ
│   │   │
│   │   ├── Middleware/
│   │   │   ├── EnsurePhoneVerified.php
│   │   │   ├── ThrottlePaymentRequests.php
│   │   │   ├── DetectContactSharing.php             # Regex anti-désintermédiation
│   │   │   └── SetLocale.php                        # Force fr
│   │   │
│   │   ├── Requests/
│   │   │   ├── Api/
│   │   │   │   ├── StoreBookingRequest.php
│   │   │   │   ├── UpdateBookingRequest.php
│   │   │   │   ├── StoreTalentProfileRequest.php
│   │   │   │   ├── UpdateTalentProfileRequest.php
│   │   │   │   ├── StoreMessageRequest.php
│   │   │   │   ├── StoreReviewRequest.php
│   │   │   │   ├── InitiatePaymentRequest.php
│   │   │   │   ├── RegisterRequest.php
│   │   │   │   ├── LoginRequest.php
│   │   │   │   └── VerifyOtpRequest.php
│   │   │   └── Admin/
│   │   │       ├── AdminUpdateUserRequest.php
│   │   │       └── AdminResolveDisputeRequest.php
│   │   │
│   │   └── Resources/
│   │       ├── TalentResource.php
│   │       ├── TalentProfileResource.php
│   │       ├── BookingResource.php
│   │       ├── MessageResource.php
│   │       ├── ConversationResource.php
│   │       ├── ReviewResource.php
│   │       ├── TransactionResource.php
│   │       ├── CalendarSlotResource.php
│   │       ├── NotificationResource.php
│   │       └── UserResource.php
│   │
│   ├── Jobs/
│   │   ├── ProcessPayout.php                        # Queue: payments
│   │   ├── HandlePaymentWebhook.php                 # Queue: payments
│   │   ├── ReleaseEscrow.php                        # Queue: payments
│   │   ├── SendPushNotification.php                 # Queue: notifications
│   │   ├── SendBookingEmail.php                     # Queue: notifications
│   │   ├── SendSmsOtp.php                           # Queue: notifications
│   │   ├── CompressPortfolioImage.php               # Queue: media
│   │   ├── GenerateContractPdf.php                  # Queue: media
│   │   ├── UpdateTalentStatistics.php               # Queue: default
│   │   ├── CleanExpiredTokens.php                   # Queue: default
│   │   └── RecalculateTalentLevels.php              # Queue: default
│   │
│   ├── Listeners/
│   │   ├── SendBookingCreatedNotification.php
│   │   ├── SendBookingConfirmedNotification.php
│   │   ├── SendPaymentReceivedNotification.php
│   │   ├── SendPayoutCompletedNotification.php
│   │   ├── UpdateTalentStatsOnReview.php
│   │   ├── ScheduleEscrowRelease.php
│   │   ├── LogActivityEvent.php
│   │   └── BroadcastTrackingUpdate.php
│   │
│   ├── Models/
│   │   ├── User.php
│   │   ├── TalentProfile.php
│   │   ├── Category.php
│   │   ├── BookingRequest.php
│   │   ├── Transaction.php
│   │   ├── EscrowHold.php
│   │   ├── Payout.php
│   │   ├── Conversation.php
│   │   ├── Message.php
│   │   ├── Review.php
│   │   ├── CalendarSlot.php
│   │   ├── PortfolioMedia.php
│   │   ├── TrackingEvent.php
│   │   ├── Notification.php
│   │   ├── Dispute.php
│   │   ├── ActivityLog.php
│   │   └── ServicePackage.php
│   │
│   ├── Notifications/
│   │   ├── BookingConfirmedNotification.php
│   │   ├── PaymentReceivedNotification.php
│   │   ├── PayoutCompletedNotification.php
│   │   ├── NewMessageNotification.php
│   │   ├── BookingReminderNotification.php
│   │   └── DisputeOpenedNotification.php
│   │
│   ├── Policies/
│   │   ├── BookingPolicy.php
│   │   ├── TalentProfilePolicy.php
│   │   ├── MessagePolicy.php
│   │   ├── ReviewPolicy.php
│   │   ├── CalendarPolicy.php
│   │   ├── PortfolioPolicy.php
│   │   └── PayoutPolicy.php
│   │
│   ├── Repositories/
│   │   ├── Contracts/
│   │   │   ├── TalentRepositoryInterface.php
│   │   │   ├── BookingRepositoryInterface.php
│   │   │   ├── PaymentRepositoryInterface.php
│   │   │   └── MessageRepositoryInterface.php
│   │   └── Eloquent/
│   │       ├── TalentRepository.php
│   │       ├── BookingRepository.php
│   │       ├── PaymentRepository.php
│   │       └── MessageRepository.php
│   │
│   ├── Services/
│   │   ├── AuthService.php                          # Register, login, OTP, token
│   │   ├── BookingService.php                       # Create, confirm, cancel, state machine
│   │   ├── EscrowService.php                        # Hold, release, refund
│   │   ├── PaymentService.php                       # Initiate, process webhooks, failover
│   │   ├── PayoutService.php                        # Calculate commission, execute payout
│   │   ├── SearchService.php                        # Filter, sort, geocode
│   │   ├── CalendarService.php                      # Availability, conflicts, alerts
│   │   ├── MessagingService.php                     # Send, detect contacts, broadcast
│   │   ├── TrackingService.php                      # Status updates, geolocation check-in
│   │   ├── ReviewService.php                        # Submit, calculate averages
│   │   ├── TalentLevelService.php                   # Auto-calculation (Nouveau→Elite)
│   │   ├── MediaService.php                         # Upload, compress, S3
│   │   ├── ContractService.php                      # Generate PDF
│   │   ├── NotificationService.php                  # FCM push, in-app
│   │   ├── SmsService.php                           # OTP via provider
│   │   ├── DisputeService.php                       # Open, resolve, escalate
│   │   └── AuditService.php                         # Activity logging
│   │
│   └── Providers/
│       ├── AppServiceProvider.php                   # Repository bindings
│       ├── AuthServiceProvider.php
│       ├── EventServiceProvider.php                 # Event → Listener mapping
│       └── BroadcastServiceProvider.php             # Reverb channels
│
├── bootstrap/
│   └── app.php
│
├── config/
│   ├── app.php
│   ├── auth.php                                     # 2 guards: api (sanctum), web (session)
│   ├── broadcasting.php                             # Reverb config
│   ├── cache.php                                    # Redis driver
│   ├── database.php                                 # MySQL
│   ├── filesystems.php                              # Local disk (VPS) + Cloudflare CDN
│   ├── horizon.php                                  # Queue pipelines
│   ├── mail.php                                     # Mailgun
│   ├── permission.php                               # Spatie config
│   ├── queue.php                                    # Redis driver
│   ├── reverb.php                                   # WebSocket config
│   ├── sanctum.php                                  # Token expiration 24h
│   ├── sentry.php                                   # Error monitoring
│   ├── services.php                                 # Paystack, CinetPay, FCM keys
│   └── bookmi.php                                   # Custom: commission_rate, escrow.hold_hours, talent_levels, etc.
│
├── database/
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── xxxx_xx_xx_create_categories_table.php
│   │   ├── xxxx_xx_xx_create_talent_profiles_table.php
│   │   ├── xxxx_xx_xx_create_service_packages_table.php
│   │   ├── xxxx_xx_xx_create_portfolio_media_table.php
│   │   ├── xxxx_xx_xx_create_calendar_slots_table.php
│   │   ├── xxxx_xx_xx_create_booking_requests_table.php
│   │   ├── xxxx_xx_xx_create_transactions_table.php
│   │   ├── xxxx_xx_xx_create_escrow_holds_table.php
│   │   ├── xxxx_xx_xx_create_payouts_table.php
│   │   ├── xxxx_xx_xx_create_conversations_table.php
│   │   ├── xxxx_xx_xx_create_messages_table.php
│   │   ├── xxxx_xx_xx_create_tracking_events_table.php
│   │   ├── xxxx_xx_xx_create_reviews_table.php
│   │   ├── xxxx_xx_xx_create_disputes_table.php
│   │   ├── xxxx_xx_xx_create_notifications_table.php
│   │   ├── xxxx_xx_xx_create_activity_logs_table.php
│   │   └── xxxx_xx_xx_setup_permission_tables.php   # Spatie
│   ├── seeders/
│   │   ├── DatabaseSeeder.php
│   │   ├── CategorySeeder.php                       # 12 catégories (DJ, MC, Musicien, etc.)
│   │   ├── RoleAndPermissionSeeder.php              # 7 rôles + permissions
│   │   └── DemoDataSeeder.php                       # Dev uniquement
│   └── factories/
│       ├── UserFactory.php
│       ├── TalentProfileFactory.php
│       ├── BookingRequestFactory.php
│       └── TransactionFactory.php
│
├── lang/
│   └── fr/
│       ├── auth.php
│       ├── pagination.php
│       ├── passwords.php
│       ├── validation.php
│       └── bookmi.php                               # Messages métier BookMi
│
├── resources/
│   ├── css/
│   │   └── app.css                                  # Tailwind CSS 4
│   ├── js/
│   │   └── app.js                                   # Alpine.js (admin interactivité)
│   └── views/
│       ├── admin/
│       │   ├── layouts/
│       │   │   └── app.blade.php                    # Layout admin Blade + Tailwind
│       │   ├── dashboard/
│       │   │   └── index.blade.php
│       │   ├── users/
│       │   │   ├── index.blade.php
│       │   │   └── show.blade.php
│       │   ├── bookings/
│       │   │   ├── index.blade.php
│       │   │   └── show.blade.php
│       │   ├── disputes/
│       │   │   ├── index.blade.php
│       │   │   └── show.blade.php
│       │   ├── payouts/
│       │   │   └── index.blade.php
│       │   └── reports/
│       │       └── index.blade.php
│       ├── web/
│       │   ├── layouts/
│       │   │   └── app.blade.php                    # Layout public SEO
│       │   ├── home.blade.php                       # Landing page
│       │   ├── talent/
│       │   │   └── show.blade.php                   # Profil public SEO
│       │   └── pages/
│       │       ├── cgu.blade.php
│       │       ├── mentions-legales.blade.php
│       │       └── faq.blade.php
│       └── emails/
│           ├── booking-confirmed.blade.php
│           ├── payment-received.blade.php
│           ├── payout-completed.blade.php
│           └── welcome.blade.php
│
├── routes/
│   ├── api.php                                      # /api/v1/* (Sanctum auth)
│   ├── web.php                                      # Pages publiques SEO
│   ├── admin.php                                    # /admin/* (Session auth)
│   ├── channels.php                                 # Reverb broadcast channels
│   └── console.php                                  # Artisan schedule
│
├── storage/
│   ├── app/
│   │   ├── public/                                  # Symlink → public/storage
│   │   └── temp/                                    # Upload temporaire avant S3
│   ├── framework/
│   ├── logs/
│   └── backups/                                     # Spatie Backup local
│
├── tests/
│   ├── TestCase.php
│   ├── CreatesApplication.php
│   ├── Feature/
│   │   ├── Api/
│   │   │   └── V1/
│   │   │       ├── AuthControllerTest.php
│   │   │       ├── TalentControllerTest.php
│   │   │       ├── BookingRequestControllerTest.php
│   │   │       ├── PaymentControllerTest.php
│   │   │       ├── MessageControllerTest.php
│   │   │       └── CalendarControllerTest.php
│   │   └── Admin/
│   │       ├── AdminDashboardControllerTest.php
│   │       └── AdminBookingControllerTest.php
│   └── Unit/
│       ├── Services/
│       │   ├── BookingServiceTest.php
│       │   ├── EscrowServiceTest.php
│       │   ├── PaymentServiceTest.php
│       │   ├── TalentLevelServiceTest.php
│       │   └── CommissionCalculationTest.php
│       ├── Models/
│       │   ├── BookingRequestTest.php
│       │   └── UserTest.php
│       └── Jobs/
│           ├── ProcessPayoutTest.php
│           └── HandlePaymentWebhookTest.php
│
└── public/
    ├── index.php
    ├── robots.txt
    ├── sitemap.xml                                  # Auto-généré (profils talents)
    └── favicon.ico
```

#### Mobile — Flutter (`bookmi_app/`)

```
bookmi_app/
├── .gitignore
├── .metadata
├── analysis_options.yaml                            # very_good_analysis
├── pubspec.yaml
├── pubspec.lock
├── l10n.yaml                                        # Config localisation
├── build.yaml                                       # build_runner config
├── README.md
│
├── android/
│   ├── app/
│   │   ├── src/
│   │   │   ├── main/
│   │   │   │   └── AndroidManifest.xml
│   │   │   ├── development/                         # Flavor dev
│   │   │   ├── staging/                             # Flavor staging
│   │   │   └── production/                          # Flavor production
│   │   └── build.gradle
│   ├── build.gradle
│   └── google-services.json                         # FCM (per flavor)
│
├── ios/
│   ├── Runner/
│   │   ├── Info.plist
│   │   ├── GoogleService-Info.plist                 # FCM
│   │   └── Assets.xcassets/
│   ├── Runner.xcodeproj/
│   └── Podfile
│
├── assets/
│   ├── images/
│   │   ├── logo.png
│   │   ├── logo_white.png
│   │   ├── onboarding/                              # Images onboarding
│   │   └── placeholders/                            # Skeleton/placeholder images
│   ├── icons/
│   │   ├── categories/                              # Icônes catégories talents
│   │   └── navigation/                              # Icônes tab bar
│   ├── fonts/
│   │   ├── Nunito-Regular.ttf
│   │   ├── Nunito-SemiBold.ttf
│   │   ├── Nunito-Bold.ttf
│   │   └── Nunito-ExtraBold.ttf
│   └── animations/
│       ├── celebration.json                         # Lottie animation (booking confirmé)
│       ├── loading.json                             # Lottie loading
│       └── empty_state.json                         # Lottie empty
│
├── lib/
│   ├── main_development.dart                        # Entry point dev
│   ├── main_staging.dart                            # Entry point staging
│   ├── main_production.dart                         # Entry point production
│   ├── bootstrap.dart                               # App initialization
│   │
│   ├── app/
│   │   ├── app.dart                                 # MaterialApp + BlocProviders
│   │   ├── app_bloc_observer.dart                   # Debug logging BLoC events
│   │   ├── env/
│   │   │   ├── env.dart                             # Environment abstract
│   │   │   ├── env_development.dart                 # API URL dev, Paystack sandbox
│   │   │   ├── env_staging.dart
│   │   │   └── env_production.dart                  # API URL prod, Paystack live
│   │   └── routes/
│   │       ├── app_router.dart                      # GoRouter config
│   │       ├── route_names.dart                     # Constantes noms de routes
│   │       └── guards/
│   │           ├── auth_guard.dart                  # Redirect si non-authentifié
│   │           └── role_guard.dart                  # Redirect selon rôle
│   │
│   ├── core/
│   │   ├── design_system/
│   │   │   ├── tokens/
│   │   │   │   ├── colors.dart                      # Navy #1A2744, Blue #2196F3, Orange #FF6B35
│   │   │   │   ├── typography.dart                  # Nunito scale
│   │   │   │   ├── spacing.dart                     # 4px grid system
│   │   │   │   ├── radius.dart                      # Border radius tokens
│   │   │   │   ├── shadows.dart                     # Elevation tokens
│   │   │   │   └── glass.dart                       # Glassmorphism tokens (blur, opacity, tiers)
│   │   │   ├── components/
│   │   │   │   ├── glass_card.dart                  # Carte glassmorphism adaptative
│   │   │   │   ├── glass_app_bar.dart               # App bar translucide
│   │   │   │   ├── glass_shield.dart                # Bouclier sécurité paiement
│   │   │   │   ├── glass_bottom_nav.dart            # Bottom navigation bar vitrée
│   │   │   │   ├── talent_card.dart                 # Carte talent grille/liste
│   │   │   │   ├── status_tracker.dart              # Tracker jour J (5 étapes)
│   │   │   │   ├── celebration_overlay.dart          # Confettis/Lottie booking confirmé
│   │   │   │   ├── chat_bubble.dart                 # Bulle message WhatsApp-style
│   │   │   │   ├── progress_ring.dart               # Ring progression escrow
│   │   │   │   ├── mobile_money_selector.dart       # Sélecteur opérateurs MoMo
│   │   │   │   ├── filter_bar.dart                  # Barre filtres discovery
│   │   │   │   ├── skeleton_loader.dart             # Shimmer/skeleton réutilisable
│   │   │   │   ├── bookmi_button.dart               # Bouton primaire/secondaire
│   │   │   │   ├── bookmi_text_field.dart           # Champ de saisie styled
│   │   │   │   └── empty_state.dart                 # État vide avec illustration
│   │   │   └── theme/
│   │   │       ├── bookmi_theme.dart                # ThemeData complet
│   │   │       ├── dark_theme.dart                  # Theme sombre
│   │   │       └── gpu_tier_provider.dart            # Détection GPU tier (1/2/3)
│   │   │
│   │   ├── network/
│   │   │   ├── api_client.dart                      # Dio instance singleton
│   │   │   ├── api_endpoints.dart                   # Constantes endpoints
│   │   │   ├── api_result.dart                      # ApiResult<T> sealed class
│   │   │   ├── interceptors/
│   │   │   │   ├── auth_interceptor.dart            # Inject Bearer token
│   │   │   │   ├── retry_interceptor.dart           # 3 retries, backoff
│   │   │   │   └── logging_interceptor.dart         # Debug request/response
│   │   │   └── websocket/
│   │   │       ├── echo_client.dart                 # Laravel Echo setup
│   │   │       └── channel_manager.dart             # Subscribe/unsubscribe channels
│   │   │
│   │   ├── storage/
│   │   │   ├── secure_storage.dart                  # flutter_secure_storage wrapper
│   │   │   ├── local_storage.dart                   # Hive wrapper
│   │   │   └── cache_manager.dart                   # Cache 7j avec invalidation
│   │   │
│   │   ├── notifications/
│   │   │   ├── push_notification_service.dart       # FCM setup + handlers
│   │   │   └── local_notification_service.dart      # Rappels programmés
│   │   │
│   │   ├── utils/
│   │   │   ├── device_capability.dart               # GPU tier detection
│   │   │   ├── currency_formatter.dart              # "150 000 FCFA"
│   │   │   ├── date_formatter.dart                  # "17 février 2026 à 14h30"
│   │   │   ├── phone_formatter.dart                 # E.164 ↔ affichage local
│   │   │   ├── validators.dart                      # Validation formulaires
│   │   │   └── image_utils.dart                     # Compression avant upload
│   │   │
│   │   └── constants/
│   │       ├── api_constants.dart                   # Timeouts, limites
│   │       ├── asset_paths.dart                     # Chemins assets
│   │       └── app_constants.dart                   # Constantes métier
│   │
│   ├── features/
│   │   ├── auth/
│   │   │   ├── bloc/
│   │   │   │   ├── auth_bloc.dart
│   │   │   │   ├── auth_event.dart
│   │   │   │   └── auth_state.dart
│   │   │   ├── data/
│   │   │   │   ├── models/
│   │   │   │   │   ├── user_model.dart
│   │   │   │   │   └── auth_token_model.dart
│   │   │   │   ├── repositories/
│   │   │   │   │   └── auth_repository.dart
│   │   │   │   └── datasources/
│   │   │   │       └── auth_remote_datasource.dart
│   │   │   ├── presentation/
│   │   │   │   ├── pages/
│   │   │   │   │   ├── login_page.dart
│   │   │   │   │   ├── register_page.dart
│   │   │   │   │   ├── otp_verification_page.dart
│   │   │   │   │   └── role_selection_page.dart
│   │   │   │   └── widgets/
│   │   │   │       ├── phone_input_field.dart
│   │   │   │       └── otp_input_field.dart
│   │   │   └── auth.dart
│   │   │
│   │   ├── discovery/
│   │   │   ├── bloc/
│   │   │   │   ├── discovery_bloc.dart
│   │   │   │   ├── discovery_event.dart
│   │   │   │   └── discovery_state.dart
│   │   │   ├── data/
│   │   │   │   ├── models/
│   │   │   │   │   ├── talent_summary_model.dart
│   │   │   │   │   └── search_filters_model.dart
│   │   │   │   ├── repositories/
│   │   │   │   │   └── discovery_repository.dart
│   │   │   │   └── datasources/
│   │   │   │       ├── discovery_remote_datasource.dart
│   │   │   │       └── discovery_local_datasource.dart
│   │   │   ├── presentation/
│   │   │   │   ├── pages/
│   │   │   │   │   └── discovery_page.dart
│   │   │   │   └── widgets/
│   │   │   │       ├── talent_grid.dart
│   │   │   │       ├── talent_card_skeleton.dart
│   │   │   │       └── category_chips.dart
│   │   │   └── discovery.dart
│   │   │
│   │   ├── talent_profile/
│   │   │   ├── bloc/
│   │   │   │   ├── talent_profile_bloc.dart
│   │   │   │   ├── talent_profile_event.dart
│   │   │   │   └── talent_profile_state.dart
│   │   │   ├── data/
│   │   │   │   ├── models/
│   │   │   │   │   ├── talent_profile_model.dart
│   │   │   │   │   ├── portfolio_item_model.dart
│   │   │   │   │   └── service_package_model.dart
│   │   │   │   ├── repositories/
│   │   │   │   │   └── talent_profile_repository.dart
│   │   │   │   └── datasources/
│   │   │   │       └── talent_profile_remote_datasource.dart
│   │   │   ├── presentation/
│   │   │   │   ├── pages/
│   │   │   │   │   ├── talent_profile_page.dart
│   │   │   │   │   └── edit_profile_page.dart
│   │   │   │   └── widgets/
│   │   │   │       ├── portfolio_gallery.dart
│   │   │   │       ├── service_package_card.dart
│   │   │   │       └── reviews_section.dart
│   │   │   └── talent_profile.dart
│   │   │
│   │   ├── booking/
│   │   │   ├── bloc/
│   │   │   │   ├── booking_bloc.dart
│   │   │   │   ├── booking_event.dart
│   │   │   │   └── booking_state.dart
│   │   │   ├── data/
│   │   │   │   ├── models/
│   │   │   │   │   ├── booking_model.dart
│   │   │   │   │   ├── booking_form_data_model.dart
│   │   │   │   │   └── contract_model.dart
│   │   │   │   ├── repositories/
│   │   │   │   │   └── booking_repository.dart
│   │   │   │   └── datasources/
│   │   │   │       ├── booking_remote_datasource.dart
│   │   │   │       └── booking_local_datasource.dart
│   │   │   ├── presentation/
│   │   │   │   ├── pages/
│   │   │   │   │   ├── booking_stepper_page.dart
│   │   │   │   │   ├── booking_detail_page.dart
│   │   │   │   │   └── bookings_list_page.dart
│   │   │   │   └── widgets/
│   │   │   │       ├── booking_summary_card.dart
│   │   │   │       ├── booking_status_badge.dart
│   │   │   │       └── booking_list_skeleton.dart
│   │   │   └── booking.dart
│   │   │
│   │   ├── payment/
│   │   │   ├── bloc/
│   │   │   │   ├── payment_bloc.dart
│   │   │   │   ├── payment_event.dart
│   │   │   │   └── payment_state.dart
│   │   │   ├── data/
│   │   │   │   ├── models/
│   │   │   │   │   ├── payment_model.dart
│   │   │   │   │   └── mobile_money_operator_model.dart
│   │   │   │   ├── repositories/
│   │   │   │   │   └── payment_repository.dart
│   │   │   │   └── datasources/
│   │   │   │       └── payment_remote_datasource.dart
│   │   │   ├── presentation/
│   │   │   │   ├── pages/
│   │   │   │   │   └── payment_page.dart
│   │   │   │   └── widgets/
│   │   │   │       ├── escrow_progress.dart
│   │   │   │       └── payment_method_list.dart
│   │   │   └── payment.dart
│   │   │
│   │   ├── messaging/
│   │   │   ├── bloc/
│   │   │   │   ├── messaging_bloc.dart
│   │   │   │   ├── messaging_event.dart
│   │   │   │   └── messaging_state.dart
│   │   │   ├── data/
│   │   │   │   ├── models/
│   │   │   │   │   ├── conversation_model.dart
│   │   │   │   │   └── message_model.dart
│   │   │   │   ├── repositories/
│   │   │   │   │   └── messaging_repository.dart
│   │   │   │   └── datasources/
│   │   │   │       ├── messaging_remote_datasource.dart
│   │   │   │       └── messaging_local_datasource.dart
│   │   │   ├── presentation/
│   │   │   │   ├── pages/
│   │   │   │   │   ├── conversations_list_page.dart
│   │   │   │   │   └── chat_page.dart
│   │   │   │   └── widgets/
│   │   │   │       ├── conversation_tile.dart
│   │   │   │       ├── message_input_bar.dart
│   │   │   │       └── audio_message_player.dart
│   │   │   └── messaging.dart
│   │   │
│   │   ├── tracking/
│   │   │   ├── bloc/
│   │   │   │   ├── tracking_bloc.dart
│   │   │   │   ├── tracking_event.dart
│   │   │   │   └── tracking_state.dart
│   │   │   ├── data/
│   │   │   │   ├── models/
│   │   │   │   │   └── tracking_event_model.dart
│   │   │   │   ├── repositories/
│   │   │   │   │   └── tracking_repository.dart
│   │   │   │   └── datasources/
│   │   │   │       └── tracking_remote_datasource.dart
│   │   │   ├── presentation/
│   │   │   │   ├── pages/
│   │   │   │   │   └── tracking_page.dart
│   │   │   │   └── widgets/
│   │   │   │       ├── tracking_timeline.dart
│   │   │   │       └── checkin_button.dart
│   │   │   └── tracking.dart
│   │   │
│   │   ├── evaluation/
│   │   │   ├── bloc/
│   │   │   │   ├── evaluation_bloc.dart
│   │   │   │   ├── evaluation_event.dart
│   │   │   │   └── evaluation_state.dart
│   │   │   ├── data/
│   │   │   │   ├── models/
│   │   │   │   │   └── review_model.dart
│   │   │   │   ├── repositories/
│   │   │   │   │   └── evaluation_repository.dart
│   │   │   │   └── datasources/
│   │   │   │       └── evaluation_remote_datasource.dart
│   │   │   ├── presentation/
│   │   │   │   ├── pages/
│   │   │   │   │   └── review_page.dart
│   │   │   │   └── widgets/
│   │   │   │       ├── star_rating.dart
│   │   │   │       └── review_card.dart
│   │   │   └── evaluation.dart
│   │   │
│   │   ├── calendar/
│   │   │   ├── bloc/
│   │   │   │   ├── calendar_bloc.dart
│   │   │   │   ├── calendar_event.dart
│   │   │   │   └── calendar_state.dart
│   │   │   ├── data/
│   │   │   │   ├── models/
│   │   │   │   │   └── calendar_slot_model.dart
│   │   │   │   ├── repositories/
│   │   │   │   │   └── calendar_repository.dart
│   │   │   │   └── datasources/
│   │   │   │       ├── calendar_remote_datasource.dart
│   │   │   │       └── calendar_local_datasource.dart
│   │   │   ├── presentation/
│   │   │   │   ├── pages/
│   │   │   │   │   └── calendar_page.dart
│   │   │   │   └── widgets/
│   │   │   │       ├── calendar_grid.dart
│   │   │   │       └── availability_toggle.dart
│   │   │   └── calendar.dart
│   │   │
│   │   ├── dashboard/
│   │   │   ├── bloc/
│   │   │   │   ├── dashboard_bloc.dart
│   │   │   │   ├── dashboard_event.dart
│   │   │   │   └── dashboard_state.dart
│   │   │   ├── data/
│   │   │   │   ├── models/
│   │   │   │   │   └── dashboard_stats_model.dart
│   │   │   │   ├── repositories/
│   │   │   │   │   └── dashboard_repository.dart
│   │   │   │   └── datasources/
│   │   │   │       └── dashboard_remote_datasource.dart
│   │   │   ├── presentation/
│   │   │   │   ├── pages/
│   │   │   │   │   └── dashboard_page.dart
│   │   │   │   └── widgets/
│   │   │   │       ├── stats_card.dart
│   │   │   │       ├── revenue_chart.dart
│   │   │   │       └── recent_bookings_list.dart
│   │   │   └── dashboard.dart
│   │   │
│   │   └── settings/
│   │       ├── bloc/
│   │       │   ├── settings_bloc.dart
│   │       │   ├── settings_event.dart
│   │       │   └── settings_state.dart
│   │       ├── data/
│   │       │   ├── repositories/
│   │       │   │   └── settings_repository.dart
│   │       │   └── datasources/
│   │       │       └── settings_local_datasource.dart
│   │       ├── presentation/
│   │       │   ├── pages/
│   │       │   │   ├── settings_page.dart
│   │       │   │   ├── notification_preferences_page.dart
│   │       │   │   └── account_page.dart
│   │       │   └── widgets/
│   │       │       └── settings_tile.dart
│   │       └── settings.dart
│   │
│   └── l10n/
│       └── arb/
│           └── app_fr.arb                           # Français (unique MVP)
│
├── test/
│   ├── app/
│   │   └── app_test.dart
│   ├── core/
│   │   ├── network/
│   │   │   ├── api_client_test.dart
│   │   │   └── interceptors/
│   │   │       ├── auth_interceptor_test.dart
│   │   │       └── retry_interceptor_test.dart
│   │   ├── utils/
│   │   │   ├── currency_formatter_test.dart
│   │   │   └── date_formatter_test.dart
│   │   └── design_system/
│   │       └── theme/
│   │           └── gpu_tier_provider_test.dart
│   ├── features/
│   │   ├── auth/
│   │   │   ├── bloc/
│   │   │   │   └── auth_bloc_test.dart
│   │   │   └── data/
│   │   │       └── repositories/
│   │   │           └── auth_repository_test.dart
│   │   ├── booking/
│   │   │   ├── bloc/
│   │   │   │   └── booking_bloc_test.dart
│   │   │   └── data/
│   │   │       └── repositories/
│   │   │           └── booking_repository_test.dart
│   │   ├── payment/
│   │   │   └── bloc/
│   │   │       └── payment_bloc_test.dart
│   │   ├── discovery/
│   │   │   └── bloc/
│   │   │       └── discovery_bloc_test.dart
│   │   └── messaging/
│   │       └── bloc/
│   │           └── messaging_bloc_test.dart
│   └── helpers/
│       ├── pump_app.dart
│       └── mock_repositories.dart
│
└── packages/
```

### Architectural Boundaries

#### API Boundaries

```
┌─────────────────────────────────────────────────────────────┐
│                    CLIENTS EXTERNES                          │
├──────────┬──────────────┬──────────────┬────────────────────┤
│ Flutter  │  Web Public  │  Web Admin   │  Webhooks          │
│ Mobile   │  (SSR)       │  (Blade)     │  (Paystack/Cinet)  │
├──────────┴──────────────┴──────────────┴────────────────────┤
│                                                              │
│  routes/api.php          routes/web.php    routes/admin.php  │
│  /api/v1/*               /*               /admin/*           │
│  Guard: sanctum          Guard: aucun      Guard: web        │
│  Middleware: auth:sanctum Middleware: -     Middleware: auth  │
│  Format: JSON envelope   Format: Blade     Format: Blade     │
│                                                              │
├──────────────────────────────────────────────────────────────┤
│              CONTROLLERS (Orchestration uniquement)           │
│  Valident (FormRequest) → Délèguent (Service) → Répondent   │
├──────────────────────────────────────────────────────────────┤
│              SERVICES (Logique métier)                        │
│  BookingService, EscrowService, PaymentService...            │
│  ↕ Communiquent via Events (découplage)                     │
├──────────────────────────────────────────────────────────────┤
│              REPOSITORIES (Abstraction données)              │
│  Interface → Eloquent Implementation                         │
│  Cache Redis transparent via Repository                      │
├──────────────────────────────────────────────────────────────┤
│              MODELS (Eloquent ORM)                           │
│  Relations, scopes, accessors, casts                         │
├──────────────────────────────────────────────────────────────┤
│              DATABASE (MySQL 8.x)                            │
│              CACHE/QUEUE (Redis 7.x)                         │
│              STORAGE (Hostinger VPS SSD + Cloudflare CDN)    │
└──────────────────────────────────────────────────────────────┘
```

#### Component Boundaries — Flutter

```
┌─────────────────────────────────────────────────────────┐
│                    GoRouter (Navigation)                  │
│  Route → Page → BlocProvider → View                      │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  ┌─────────┐  ┌─────────┐  ┌──────────┐  ┌──────────┐  │
│  │  Auth   │  │Discovery│  │ Booking  │  │ Payment  │  │
│  │ Feature │  │ Feature │  │ Feature  │  │ Feature  │  │
│  │         │  │         │  │          │  │          │  │
│  │ BLoC    │  │ BLoC    │  │ BLoC     │  │ BLoC     │  │
│  │ Repo    │  │ Repo    │  │ Repo     │  │ Repo     │  │
│  │ Pages   │  │ Pages   │  │ Pages    │  │ Pages    │  │
│  └────┬────┘  └────┬────┘  └────┬─────┘  └────┬─────┘  │
│       │            │            │              │         │
│  ═════╪════════════╪════════════╪══════════════╪═══════  │
│       │      CORE (shared, jamais d'import feature↔feature)│
│       ▼            ▼            ▼              ▼         │
│  ┌──────────────────────────────────────────────────┐    │
│  │  core/network/          → Dio, interceptors       │    │
│  │  core/storage/          → Hive, secure_storage    │    │
│  │  core/design_system/    → GlassCard, tokens       │    │
│  │  core/utils/            → Formatters              │    │
│  │  core/notifications/    → FCM service             │    │
│  └──────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────┘
```

**Règle absolue :** Les features communiquent UNIQUEMENT via :
1. **Navigation GoRouter** (feature A navigue vers feature B par route name)
2. **Événements BLoC partagés** via `MultiBlocListener` au niveau app
3. **Services core** (network, storage) partagés — jamais d'import direct `features/booking` → `features/payment`

#### Data Boundaries

| Couche | Accès autorisé | Interdit |
|---|---|---|
| **Controller** | FormRequest (validation), Service (logique), Resource (réponse) | Model direct, Repository direct, DB queries |
| **Service** | Repository (données), autre Service (via injection), Events (dispatch) | Eloquent queries directes, Request/Response HTTP |
| **Repository** | Model (Eloquent), Cache (Redis) | Services, Controllers, HTTP |
| **Model** | Relations, scopes, casts, accessors | Logique métier complexe, accès HTTP |
| **Job** | Service (logique), Repository (données) | Controller, Request |
| **Flutter BLoC** | Repository (données) | Dio direct, autre BLoC direct |
| **Flutter Repository** | Datasource (remote/local) | BLoC, Widgets, Dio direct |
| **Flutter Datasource** | ApiClient (Dio), LocalStorage (Hive) | BLoC, Repository logic |

### Integration Points

#### Internal Communication

| Source | Destination | Mécanisme | Exemple |
|---|---|---|---|
| Controller → Service | Injection de dépendances | Constructor DI | `BookingRequestController → BookingService` |
| Service → Service | Injection de dépendances | Constructor DI | `BookingService → EscrowService` |
| Service → Repository | Interface + implémentation | AppServiceProvider bind | `BookingService → BookingRepositoryInterface` |
| Service → Event | `event()` helper | Asynchrone, découplé | `BookingService → BookingConfirmed` |
| Event → Listener | EventServiceProvider | 1 event → N listeners | `BookingConfirmed → [SendNotification, ScheduleEscrow]` |
| Event → Broadcast | `ShouldBroadcast` | Reverb WebSocket | `MessageSent → private:conversation.{id}` |
| Listener → Job | `dispatch()` | Queue Redis | `SendNotification → SendPushNotification` (queue: notifications) |

#### External Integrations

| Service externe | Point d'intégration Laravel | Configuration |
|---|---|---|
| **Paystack** | `Services/PaymentService` + `Jobs/HandlePaymentWebhook` | `config/services.php` → clés API, webhook secret |
| **CinetPay** (backup) | `Services/PaymentService` (failover) | `config/services.php` → clés API |
| **FCM** (Firebase) | `Jobs/SendPushNotification` | `config/services.php` → server key |
| **Mailgun** | `config/mail.php` + `Notifications/` | `config/mail.php` → domain, API key |
| **Hostinger VPS** (stockage local) | `config/filesystems.php` disk `public` | Disque SSD VPS, symlink `storage/app/public` → `public/storage`, Cloudflare CDN devant |
| **Cloudflare CDN** | Nginx config + DNS | Proxy DNS, SSL full strict |
| **Sentry** | `config/sentry.php` + `sentry/sentry-laravel` | DSN, environment |
| **SMS Provider** (OTP) | `Services/SmsService` | `config/services.php` → API OTP |
| **Google Maps / OSM** | `Services/SearchService` (geocoding) | `config/services.php` → API key |

#### Data Flow — Booking Lifecycle

```
Client Flutter                   API Laravel                        Services externes
     │                              │                                     │
     │  POST /api/v1/booking_requests                                     │
     ├──────────────────────────────>│                                     │
     │                              ├─ BookingService.createBooking()     │
     │                              ├─ event(BookingCreated)              │
     │                              │   ├─ SendBookingNotification → FCM ─┤
     │                              │   └─ Broadcast → Reverb             │
     │<── 201 + booking data ───────┤                                     │
     │                              │                                     │
     │  Talent accepte (app)        │                                     │
     │  POST .../booking/confirm    │                                     │
     ├──────────────────────────────>│                                     │
     │                              ├─ BookingService.confirm()           │
     │                              ├─ event(BookingConfirmed)            │
     │<── 200 + updated booking ────┤                                     │
     │                              │                                     │
     │  POST /api/v1/payments/initiate                                    │
     ├──────────────────────────────>│                                     │
     │                              ├─ PaymentService.initiate()          │
     │                              │   └──────────────────────── Paystack │
     │<── 200 + payment_url ────────┤                                     │
     │                              │                                     │
     │  [Client paie via Mobile Money]                                    │
     │                              │                                     │
     │                              │  POST /webhooks/paystack ───────────┤
     │                              ├─ HandlePaymentWebhook (Job)         │
     │                              ├─ EscrowService.hold()               │
     │                              ├─ event(PaymentReceived)             │
     │  <── Reverb: .payment.received                                     │
     │                              │                                     │
     │  [24h après confirmation prestation]                               │
     │                              ├─ ReleaseEscrow (Scheduled Job)      │
     │                              ├─ PayoutService.execute()            │
     │                              │   └────────────────── Paystack ──────┤
     │                              ├─ event(PayoutCompleted)             │
     │  <── Push FCM: Versement reçu                                      │
```

### Development Workflow Integration

#### Development Server Structure

| Composant | Commande | Port | Usage |
|---|---|---|---|
| Laravel API | `php artisan serve` | 8000 | API + Web |
| Laravel Reverb | `php artisan reverb:start` | 8080 | WebSocket |
| Laravel Horizon | `php artisan horizon` | — | Queue monitoring |
| Vite (assets) | `npm run dev` | 5173 | Hot reload CSS/JS admin |
| MySQL | Docker | 3306 | Base de données |
| Redis | Docker | 6379 | Cache, queue, sessions |
| Flutter | `flutter run` | — | App mobile (émulateur) |

**docker-compose.yml (dev) fournit :** MySQL 8 + Redis 7. Laravel tourne en local via `artisan serve`.

#### Build Process Structure

| Étape CI/CD | Laravel | Flutter |
|---|---|---|
| **Lint** | `./vendor/bin/pint --test` + `./vendor/bin/phpstan analyse` | `dart analyze` + `dart format --set-exit-if-changed .` |
| **Test** | `php artisan test --parallel` | `very_good test --coverage --min-coverage 80` |
| **Build** | `composer install --no-dev` + `npm run build` | `flutter build apk --release` / `flutter build ios --release` |
| **Deploy** | Docker build → push image → deploy Hostinger VPS | APK → Google Play Console / IPA → App Store Connect |

#### Deployment Structure

```
Production Server (Hostinger VPS)
├── /var/www/bookmi/                      # Laravel app (Docker)
│   ├── current/ → releases/v1.2.3/      # Symlink release active
│   ├── releases/                         # 5 dernières releases
│   ├── shared/
│   │   ├── .env                          # Variables production
│   │   └── storage/                      # Persisted storage
│   └── docker-compose.prod.yml
│
├── Nginx (reverse proxy)
│   ├── :80 → redirect :443
│   ├── :443 → PHP-FPM (Laravel)
│   ├── :443/reverb → Reverb WebSocket
│   └── SSL via Cloudflare (full strict)
│
├── Supervisor
│   ├── horizon.conf                      # Laravel Horizon (queues)
│   └── reverb.conf                       # Laravel Reverb (WebSocket)
│
└── Cron
    └── * * * * * artisan schedule:run    # Laravel scheduler
```

## Architecture Validation Results

### Coherence Validation ✅

**Decision Compatibility :**

| Vérification | Résultat | Détail |
|---|---|---|
| Laravel 12.x + PHP 8.2+ + MySQL 8.x | ✅ Compatible | Stack standard, support natif Laravel |
| Laravel Sanctum + Spatie Permission v7 | ✅ Compatible | Multi-guard (api+web) supporté par les deux packages |
| Redis 7.x (cache + queue + sessions + broadcasting) | ✅ Compatible | 4 usages unifiés, configuration Laravel native |
| Laravel Reverb + laravel_echo_flutter | ✅ Compatible | Protocole Pusher partagé, channels private/presence |
| Flutter 3.38.x + BLoC 9.0 + GoRouter + Dio | ✅ Compatible | Écosystème Very Good Ventures cohérent |
| Hive + flutter_secure_storage | ✅ Compatible | Usages distincts (cache vs tokens), pas de conflit |
| Paystack + CinetPay (failover) | ✅ Compatible | Interface abstraite `PaymentService`, switch par config |
| Hostinger VPS + Docker + Nginx + Supervisor + Cloudflare | ✅ Compatible | VPS KVM avec full root access, stack deployment standard, WebSocket supporté |
| Sentry (Laravel + Flutter) | ✅ Compatible | SDKs disponibles pour les deux plateformes |
| BLoC 9.0 sealed classes + Dart 3 patterns | ✅ Compatible | Dart 3 exhaustive pattern matching natif |

**Aucune incompatibilité détectée.** Toutes les technologies sont dans leurs versions stables actuelles et font partie d'écosystèmes matures.

**Note Hostinger VPS :** Les plans VPS KVM2+ offrent full root access, permettant l'installation de Docker, MySQL, Redis, Supervisor, Nginx et Reverb. Le stockage média est en local sur le disque SSD du VPS avec Cloudflare CDN devant pour la distribution globale. Migration vers un stockage S3 externe possible ultérieurement si les besoins dépassent la capacité disque du VPS.

**Pattern Consistency :**

| Pattern | Côté Laravel | Côté Flutter | Cohérence |
|---|---|---|---|
| Naming JSON | `snake_case` | `snake_case` (pas de transformation) | ✅ Zéro mapping |
| Dates API | ISO 8601 UTC | Reçu UTC, affiché local | ✅ Clair |
| Montants | Centimes (int) | Centimes (int) → formatage affichage | ✅ Pas de float |
| Erreurs | `{ error: { code, message, status, details } }` | `ApiFailure(code, message, details)` | ✅ Mapping 1:1 |
| Auth | Sanctum Bearer token 24h | Dio interceptor inject + 401 redirect | ✅ Flux complet |
| Events nommage | `PascalCase` passé (`BookingConfirmed`) | BLoC Events passé (`BookingFetched`) | ✅ Cohérent |
| Structure | Service/Repository/Controller | BLoC/Repository/Datasource | ✅ Séparation des responsabilités |

**Structure Alignment :**

- La structure bi-repo (Laravel `bookmi/` + Flutter `bookmi_app/`) reflète exactement la séparation API/Mobile décidée
- Les 11 features Flutter mappent 1:1 sur les controllers API V1
- Les 4 queues Horizon (payments, notifications, media, default) couvrent tous les Jobs identifiés
- Les 17 modèles Eloquent couvrent toutes les tables identifiées dans les migrations
- Le design system Flutter (15 composants) couvre les 10 composants custom du UX Design + 5 utilitaires

### Requirements Coverage Validation ✅

**Couverture Functional Requirements (72 FRs) :**

| Domaine | FRs | Couvert par | Statut |
|---|---|---|---|
| Gestion Utilisateurs (FR1-FR10) | 10 | AuthController, AuthService, Spatie Roles, auth feature Flutter | ✅ 10/10 |
| Découverte & Recherche (FR11-FR17) | 7 | TalentController, SearchService, discovery + talent_profile features | ✅ 7/7 |
| Réservation & Contrats (FR18-FR28) | 11 | BookingRequestController, BookingService, ContractService, booking feature | ✅ 11/11 |
| Paiement & Finances (FR29-FR38) | 10 | PaymentController, EscrowService, PayoutService, payment + dashboard features | ✅ 10/10 |
| Communication (FR39-FR44) | 6 | MessageController, MessagingService, Reverb channels, messaging feature | ✅ 6/6 |
| Suivi & Évaluation (FR45-FR51) | 7 | TrackingController, ReviewController, tracking + evaluation features | ✅ 7/7 |
| Gestion Talents (FR52-FR59) | 8 | CalendarController, TalentLevelService, calendar + talent_profile features | ✅ 8/8 |
| Administration (FR60-FR72) | 13 | Admin Controllers (7), DisputeService, AuditService, Blade views | ✅ 13/13 |

**Total : 72/72 FRs couverts architecturalement.**

**Couverture Non-Functional Requirements (52 NFRs) :**

| Catégorie | NFRs | Couvert par | Statut |
|---|---|---|---|
| Performance (NFR1-10) | 10 | Redis cache, cursor pagination, CDN Cloudflare, image compression, Hive cache 7j | ✅ |
| Sécurité (NFR11-22) | 12 | Sanctum tokens, bcrypt 12, AES-256, rate limiting, CORS, Spatie permissions, TLS via Cloudflare | ✅ |
| Scalabilité (NFR23-28) | 6 | Hostinger VPS (scale vertical via upgrade plan), Redis, Horizon workers, CDN, SSD storage | ✅ |
| Fiabilité (NFR29-35) | 7 | Spatie Backup 6h, Horizon retry, webhook idempotent (Jobs), Sentry alertes, Supervisor | ✅ |
| Accessibilité (NFR36-41) | 6 | Glassmorphism 3 tiers GPU, Nunito font, français unique, support 4.7"-6.7" | ✅ |
| Intégration (NFR42-47) | 6 | Paystack/CinetPay webhooks, FCM, Cloudflare CDN, Maps API, DomPDF, interfaces abstraites | ✅ |
| Maintenabilité (NFR48-52) | 5 | Pint PSR-12, Larastan, very_good_analysis, Scribe API docs, GitHub Actions CI/CD | ✅ |

**Total : 52/52 NFRs couverts architecturalement.**

### Implementation Readiness Validation ✅

**Decision Completeness :**

| Critère | Statut | Détail |
|---|---|---|
| Toutes les technologies versionnées | ✅ | Laravel 12.x, PHP 8.2+, MySQL 8.x, Redis 7.x, Flutter 3.38.x, BLoC 9.0, Dart 3.x |
| Commandes d'initialisation documentées | ✅ | `laravel new bookmi --database=mysql --no-starter`, `very_good create flutter_app bookmi_app` |
| Patterns avec exemples concrets | ✅ | Endpoint API complet (PHP), Feature BLoC complète (Dart), anti-patterns documentés |
| Codes d'erreur métier définis | ✅ | 7 préfixes (AUTH_, BOOKING_, PAYMENT_, ESCROW_, TALENT_, MEDIA_, VALIDATION_) |
| Format réponse API spécifié | ✅ | 5 formats (succès single, cursor, offset, erreur, validation) avec JSON exemples |
| Flux d'authentification complet | ✅ | Diagramme séquence register → OTP → token → 401 → re-login |
| Hébergement spécifié | ✅ | Hostinger VPS avec Docker, MySQL, Redis, Supervisor, Nginx, Cloudflare CDN |

**Structure Completeness :**

| Critère | Statut | Détail |
|---|---|---|
| Arborescence Laravel complète | ✅ | ~120 fichiers identifiés avec commentaires de rôle |
| Arborescence Flutter complète | ✅ | 11 features × (bloc + data + presentation) + core complet |
| Tests miroirs définis | ✅ | Feature/Unit côté Laravel, bloc/repository côté Flutter |
| CI/CD pipelines | ✅ | 3 workflows GitHub Actions (ci, deploy-staging, deploy-production) |
| Docker configs | ✅ | docker-compose.yml (dev), docker-compose.prod.yml, Dockerfile |
| Deployment structure | ✅ | Hostinger VPS : release symlinks, Nginx, Supervisor, Cron |

**Pattern Completeness :**

| Critère | Statut | Détail |
|---|---|---|
| Database naming | ✅ | 10 conventions avec exemples et anti-patterns |
| API naming | ✅ | 6 conventions REST |
| Code naming Laravel | ✅ | 14 conventions PHP |
| Code naming Flutter | ✅ | 12 conventions Dart |
| BLoC pattern | ✅ | Events, States, sealed classes, exemples complets |
| Error handling | ✅ | Laravel (exceptions custom) + Flutter (ApiResult sealed) |
| Loading states | ✅ | 7 patterns avec conventions de nommage |
| Retry & resilience | ✅ | 5 scénarios avec configurations |
| WebSocket channels | ✅ | 4 types de channels avec patterns de nommage |
| Queue pipelines | ✅ | 4 pipelines Horizon avec priorités et workers |

### Gap Analysis Results

**Gaps critiques :** 0 identifiés

**Gaps importants (à adresser en début d'implémentation) :**

| Gap | Impact | Recommandation | Priorité |
|---|---|---|---|
| Schéma ERD non inclus | Les agents IA devront déduire les relations des Models | Générer un ERD Excalidraw dans un workflow dédié | Moyenne |
| Politique d'annulation graduée non détaillée | Logique métier complexe dans `BookingService` | Documenter les règles exactes dans les stories (% remboursement par palier) | Moyenne |
| Machine à états booking non formalisée | Transitions de statuts critiques pour l'escrow | Documenter le state machine diagram dans les stories de booking | Moyenne |
| Stratégie de backup externe non finalisée | Hostinger VPS = single point, backups locales insuffisantes | Configurer Spatie Backup vers un stockage externe (Google Drive, S3, ou autre) dès la story infrastructure | Moyenne |

**Gaps nice-to-have :**

| Gap | Recommandation |
|---|---|
| Stratégie de migration BDD détaillée | Sera naturellement gérée par Laravel Migrations au fil des stories |
| Configuration Sentry détaillée (alertes, seuils) | Configurer lors de la story d'infrastructure |
| Stratégie de versioning API (quand passer à v2) | Post-MVP, pas nécessaire maintenant |
| Migration vers S3 pour stockage média | Envisager si > 100 Go ou besoin de CDN dédié médias |

### Validation Issues Addressed

**Issue résolue — Hébergement :**
La décision initiale (DigitalOcean) a été mise à jour pour **Hostinger VPS** conformément au choix utilisateur. Impact architectural :
- Stockage média : disque local SSD (au lieu de S3-compatible) + Cloudflare CDN
- Sauvegardes : nécessitent un stockage externe (pas de Spaces intégré)
- MySQL + Redis : installés manuellement sur le VPS (pas de managed services)
- Docker + Supervisor : full root access requis (VPS KVM2+ le permet)
- Scalabilité : verticale par upgrade de plan VPS. Migration vers cloud multi-serveur si > 10 000 simultanés

Les 3 gaps importants restants sont naturellement résolus par :
1. **ERD** → Peut être généré via le workflow Excalidraw dédié
2. **Politique d'annulation** → Sera spécifiée dans les acceptance criteria des stories de booking
3. **Machine à états** → Sera spécifiée dans les acceptance criteria avec l'enum `BookingStatus` déjà défini

### Architecture Completeness Checklist

**✅ Requirements Analysis**

- [x] Contexte projet analysé (72 FRs, 52 NFRs, 3 plateformes)
- [x] Échelle et complexité évaluées (Élevé, 12-15 composants)
- [x] Contraintes techniques identifiées (stack, infrastructure, compliance ARTCI, app stores)
- [x] Préoccupations transverses mappées (10 concerns avec composants impactés)

**✅ Starter Templates**

- [x] Options évaluées pour les 2 repos (3 options Laravel, 3 options Flutter)
- [x] Starters sélectionnés avec rationale (bare Laravel, Very Good CLI Flutter)
- [x] Commandes d'initialisation documentées
- [x] Conventions de structure projet établies

**✅ Architectural Decisions**

- [x] Décisions critiques documentées avec versions (47 décisions, 5 catégories)
- [x] Stack technique complètement spécifiée
- [x] Patterns d'intégration définis (REST, WebSocket, Events, Queues)
- [x] Considérations de performance adressées (cache, CDN, compression, pagination)
- [x] Séquence d'implémentation et dépendances inter-composants documentées

**✅ Implementation Patterns**

- [x] 47 points de conflit potentiels identifiés
- [x] Conventions de nommage établies (database, API, PHP, Dart)
- [x] Patterns de structure définis (Laravel service/repo, Flutter feature/bloc)
- [x] Patterns de communication spécifiés (Events, BLoC, WebSocket)
- [x] Patterns de processus documentés (error handling, loading states, retry, auth flow)
- [x] 10 règles d'enforcement obligatoires
- [x] Exemples concrets (bon et anti-patterns)

**✅ Project Structure**

- [x] Arborescence complète bi-repo (~200+ fichiers)
- [x] Frontières composants établies (API, Flutter, Data)
- [x] Points d'intégration mappés (interne + 9 services externes)
- [x] Mapping requirements → structure complet (8 domaines FR + 6 concerns transverses)
- [x] Data flow lifecycle documenté (booking complet)
- [x] Workflow développement et déploiement intégrés

### Architecture Readiness Assessment

**Overall Status:** READY FOR IMPLEMENTATION

**Confidence Level:** Élevé

**Points forts de l'architecture :**

1. **Cohérence totale** — Zéro incompatibilité détectée entre les 47+ décisions technologiques
2. **Couverture exhaustive** — 72/72 FRs et 52/52 NFRs couverts
3. **Anti-conflit agents IA** — 47 points de conflit identifiés avec conventions explicites, exemples concrets et anti-patterns
4. **Pragmatisme MVP** — Décisions différées judicieusement (ML search, multi-devise, horizontal scaling)
5. **Contexte Afrique de l'Ouest** — Mobile Money prioritaire, CDN africain, compression bande passante, glassmorphism dégradé GPU
6. **Sécurité financière** — Escrow machine à états, webhooks idempotents, audit trail, commission "cachet intact"
7. **Real-time natif** — Laravel Reverb (gratuit, first-party) pour messagerie et tracking jour J
8. **Hébergement optimisé** — Hostinger VPS + Cloudflare CDN : bon rapport qualité/prix pour une startup africaine

**Améliorations futures (post-MVP) :**

1. Recherche ML/IA pour recommandations talents (V2)
2. Multi-devise et multi-langue (V3)
3. Migration vers cloud multi-serveur si > 10 000 simultanés
4. Migration stockage média vers S3 si > 100 Go
5. Log management centralisé ELK/Grafana
6. ERD formel et diagrammes state machine
7. Tests E2E cross-platform (Maestro ou Patrol)

### Implementation Handoff

**Directives pour les agents IA :**

1. Suivre EXACTEMENT les décisions architecturales documentées — pas de substitution
2. Utiliser les patterns d'implémentation de manière cohérente sur tous les composants
3. Respecter la structure projet et les frontières entre couches
4. Consulter ce document pour toute question architecturale
5. Les 10 règles d'enforcement sont **non-négociables**
6. Hébergement sur Hostinger VPS — configurer Docker, MySQL, Redis, Supervisor en conséquence

**Première priorité d'implémentation :**

```bash
# 1. Backend
laravel new bookmi --database=mysql --no-starter

# 2. Mobile
very_good create flutter_app bookmi_app --description "BookMi - Marketplace de réservation de talents" --org "ci.bookmi"

# 3. Infrastructure locale (dev)
docker-compose up -d  # MySQL + Redis
```

## Architecture Completion Summary

### Workflow Completion

**Architecture Decision Workflow :** COMPLETED ✅
**Total Steps Completed :** 8
**Date Completed :** 2026-02-17
**Document Location :** `_bmad-output/planning-artifacts/architecture.md`

### Final Architecture Deliverables

**Complete Architecture Document**

- Toutes les décisions architecturales documentées avec versions spécifiques
- Patterns d'implémentation garantissant la cohérence entre agents IA
- Arborescence projet complète avec tous les fichiers et répertoires
- Mapping requirements → architecture complet
- Validation confirmant la cohérence et la complétude

**Implementation Ready Foundation**

- 47+ décisions architecturales prises
- 47 points de conflit adressés avec patterns explicites
- 12-15 composants architecturaux spécifiés
- 124 requirements (72 FRs + 52 NFRs) entièrement supportés

**AI Agent Implementation Guide**

- Stack technique avec versions vérifiées
- Règles de cohérence prévenant les conflits d'implémentation
- Structure projet avec frontières claires
- Patterns d'intégration et standards de communication

### Development Sequence

1. Initialiser les projets avec les starters documentés
2. Configurer l'environnement dev (Docker : MySQL + Redis)
3. Implémenter les fondations architecturales (auth, models, design system)
4. Construire les features en suivant les patterns établis
5. Maintenir la cohérence avec les règles documentées
6. Déployer sur Hostinger VPS avec Docker + Nginx + Supervisor

### Quality Assurance Checklist

**✅ Architecture Coherence**

- [x] Toutes les décisions fonctionnent ensemble sans conflit
- [x] Les choix technologiques sont compatibles
- [x] Les patterns supportent les décisions architecturales
- [x] La structure s'aligne avec tous les choix

**✅ Requirements Coverage**

- [x] Tous les requirements fonctionnels sont supportés (72/72)
- [x] Tous les requirements non-fonctionnels sont adressés (52/52)
- [x] Les préoccupations transverses sont gérées (10/10)
- [x] Les points d'intégration sont définis (9 services externes)

**✅ Implementation Readiness**

- [x] Les décisions sont spécifiques et actionnables
- [x] Les patterns préviennent les conflits entre agents
- [x] La structure est complète et non-ambiguë
- [x] Des exemples sont fournis pour la clarté

---

**Architecture Status :** READY FOR IMPLEMENTATION ✅

**Next Phase :** Commencer l'implémentation en utilisant les décisions et patterns documentés.

**Document Maintenance :** Mettre à jour cette architecture lors de décisions techniques majeures pendant l'implémentation.
