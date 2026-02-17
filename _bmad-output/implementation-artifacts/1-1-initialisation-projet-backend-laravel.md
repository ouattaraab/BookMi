# Story 1.1: Initialisation du projet backend Laravel

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a développeur,
I want le projet Laravel initialisé avec la configuration de base (MySQL, Redis, structure API, linting),
so that toutes les stories suivantes disposent d'une fondation backend solide.

## Acceptance Criteria (BDD)

1. **Given** la commande `laravel new bookmi --database=mysql --no-starter` est exécutée
   **When** le projet est créé
   **Then** la structure des dossiers suit exactement `app/Http/Controllers/Api/V1/`, `app/Services/`, `app/Repositories/`, `app/Enums/`

2. **Given** le projet est créé
   **When** on vérifie la configuration du linting
   **Then** les fichiers de configuration Pint (PSR-12) et Larastan sont en place

3. **Given** le projet est créé
   **When** on lance Docker Compose
   **Then** le `docker-compose.yml` fournit MySQL 8.4 LTS + Redis 7.4

4. **Given** un endpoint API est appelé
   **When** la réponse est retournée
   **Then** le format de réponse API envelope est implémenté (`{ "data": {...}, "error": {...} }`)

5. **Given** le projet est configuré
   **When** on consulte les routes
   **Then** les routes API versionnées `/api/v1/` sont configurées

6. **Given** le projet est créé
   **When** on vérifie la configuration métier
   **Then** le fichier `config/bookmi.php` existe avec les constantes métier (commission_rate, escrow settings, etc.)

## Tasks / Subtasks

- [x] **Task 1 : Initialisation du projet Laravel** (AC: #1)
  - [x] 1.1 Exécuter `composer create-project laravel/laravel bookmi` (Laravel 12.51.0, PHP 8.4.8)
  - [x] 1.2 Vérifier que Laravel 12.x est installé avec PHP 8.2+ minimum
  - [x] 1.3 Configurer `.env` avec les paramètres MySQL et Redis (DB_HOST=mysql, REDIS_HOST=redis)

- [x] **Task 2 : Structure des dossiers backend** (AC: #1)
  - [x] 2.1 Créer `app/Http/Controllers/Api/V1/` (controllers API Flutter)
  - [x] 2.2 Créer `app/Http/Controllers/Admin/` (controllers admin web Blade)
  - [x] 2.3 Créer `app/Http/Controllers/Web/` (controllers web public SSR)
  - [x] 2.4 Créer `app/Services/` (couche logique métier)
  - [x] 2.5 Créer `app/Repositories/Contracts/` (interfaces repository)
  - [x] 2.6 Créer `app/Repositories/Eloquent/` (implémentations repository)
  - [x] 2.7 Créer `app/Enums/` (PHP 8.1 enums)
  - [x] 2.8 Créer `app/Exceptions/` (exceptions custom avec codes métier)
  - [x] 2.9 Ajouter les fichiers `.gitkeep` dans les dossiers vides pour le commit initial
  - [x] 2.10 Créer `routes/admin.php` et l'enregistrer dans `bootstrap/app.php`

- [x] **Task 3 : Configuration Pint (PSR-12) + Larastan** (AC: #2)
  - [x] 3.1 Créer `pint.json` avec le preset `psr12`
  - [x] 3.2 Installer Larastan : `composer require --dev larastan/larastan:^3.9`
  - [x] 3.3 Créer `phpstan.neon` avec level 5 minimum et paths `app/`
  - [x] 3.4 Vérifier que `./vendor/bin/pint --test` passe sans erreur
  - [x] 3.5 Vérifier que `./vendor/bin/phpstan analyse` passe sans erreur

- [x] **Task 4 : Docker Compose (MySQL + Redis + Nginx)** (AC: #3)
  - [x] 4.1 Créer `docker-compose.yml` avec les services : `app` (PHP-FPM 8.4), `mysql` (8.4 LTS), `redis` (7.4-alpine), `nginx` (1.27-alpine)
  - [x] 4.2 Créer `docker/nginx/default.conf` pour le reverse proxy Laravel (PHP-FPM sur port 9000)
  - [x] 4.3 Créer `docker/php/Dockerfile` avec PHP 8.4 FPM (Debian), extensions requises (pdo_mysql, redis, gd, bcmath, zip, intl, mbstring, opcache, pcntl)
  - [x] 4.4 Configurer les volumes pour persistance MySQL et Redis
  - [x] 4.5 Configurer le réseau Docker pour communication inter-services
  - [x] 4.6 Mettre à jour `.env` avec les variables Docker (DB_HOST=mysql, REDIS_HOST=redis)
  - [x] 4.7 Vérifier que `docker compose up -d` démarre tous les services sans erreur
  - [x] 4.8 Vérifier que `php artisan migrate` fonctionne contre le MySQL Dockerisé

- [x] **Task 5 : Format de réponse API envelope** (AC: #4)
  - [x] 5.1 Créer le trait `app/Http/Traits/ApiResponseTrait.php` avec méthodes `successResponse()`, `errorResponse()`, `paginatedResponse()`
  - [x] 5.2 Créer la classe `app/Http/Controllers/Api/V1/BaseController.php` qui utilise le trait
  - [x] 5.3 Créer le handler d'exceptions global dans `bootstrap/app.php` → `withExceptions()` pour formater toutes les erreurs en JSON envelope
  - [x] 5.4 Créer `app/Exceptions/BookmiException.php` (exception de base avec `code`, `statusCode`, `details`)
  - [x] 5.5 Implémenter le format succès : `{ "data": {...} }`
  - [x] 5.6 Implémenter le format erreur : `{ "error": { "code": "...", "message": "...", "status": 422, "details": {...} } }`
  - [x] 5.7 Implémenter le format erreur validation : `{ "error": { "code": "VALIDATION_FAILED", "message": "...", "status": 422, "details": { "errors": {...} } } }`

- [x] **Task 6 : Routes API versionnées** (AC: #5)
  - [x] 6.1 Configurer `routes/api.php` avec le préfixe `/v1/` et le middleware `api`
  - [x] 6.2 Créer un endpoint health check : `GET /api/v1/health` retournant `{ "data": { "status": "ok", "version": "1.0.0" } }`
  - [x] 6.3 Configurer les routes nommées avec `api.v1.` préfixe (ex: `api.v1.health`)
  - [x] 6.4 Enregistrer le fichier `routes/admin.php` dans `bootstrap/app.php` avec middleware `web` et préfixe `/admin`

- [x] **Task 7 : Configuration métier BookMi** (AC: #6)
  - [x] 7.1 Créer `config/bookmi.php` avec toutes les constantes métier
  - [x] 7.2 Vérifier que `config('bookmi.commission_rate')` retourne 15 dans un test

- [x] **Task 8 : Configuration de base supplémentaire** (AC: #1, #2)
  - [x] 8.1 Configurer le timezone à `UTC` dans `config/app.php` (déjà UTC par défaut)
  - [x] 8.2 Configurer le locale à `fr` dans `.env` (APP_LOCALE=fr)
  - [x] 8.3 Configurer le rate limiting dans `AppServiceProvider` avec les limites définies dans `config/bookmi.php`
  - [x] 8.4 Configurer CORS dans `config/cors.php` (origines configurables via CORS_ALLOWED_ORIGINS)
  - [x] 8.5 Créer `.env.example` complet avec toutes les variables requises
  - [x] 8.6 Configurer les logs JSON structurés dans `config/logging.php` (channel `daily`, format JsonFormatter)

- [x] **Task 9 : Tests de fondation** (AC: tous)
  - [x] 9.1 Créer `tests/Feature/Api/V1/HealthCheckTest.php` : vérifie `GET /api/v1/health` retourne 200 avec format envelope
  - [x] 9.2 Créer `tests/Unit/Config/BookmiConfigTest.php` : vérifie que toutes les clés de `config/bookmi.php` sont présentes
  - [x] 9.3 Vérifier que `php artisan test` passe (12 tests, 43 assertions)
  - [x] 9.4 Créer la structure de test miroir : `tests/Feature/Api/V1/`, `tests/Feature/Admin/`, `tests/Unit/Config/`

## Dev Notes

### Architecture Patterns à respecter IMPÉRATIVEMENT

**Pattern Service Layer + Repository (ARCH-PATTERN-2) :**
- Les Controllers ne contiennent JAMAIS de logique métier
- La logique métier vit dans les Services (`app/Services/`)
- L'accès données est abstrait via les Repositories (`app/Repositories/`)
- Les interfaces Repository sont dans `app/Repositories/Contracts/`
- Les implémentations Eloquent dans `app/Repositories/Eloquent/`

**Format de réponse API (ARCH-API-1 à ARCH-API-5) :**
- JSON envelope : `{ "data": {...} }` pour succès, `{ "error": {...} }` pour erreur
- Pagination cursor-based pour mobile, offset pour admin
- Dates ISO 8601 UTC partout
- Montants en centimes (int), jamais de float
- `snake_case` partout (endpoints, paramètres, JSON fields)
- Codes d'erreur métier préfixés : `AUTH_`, `BOOKING_`, `PAYMENT_`, `ESCROW_`, `TALENT_`, `MEDIA_`, `VALIDATION_`

**Naming conventions strictes (voir architecture.md §Naming Patterns) :**
- Tables : `snake_case` pluriel (`booking_requests`, `talent_profiles`)
- Colonnes : `snake_case` singulier (`first_name`, `is_verified`)
- Controllers : `PascalCase` + `Controller` (`BookingRequestController`)
- Services : `PascalCase` + `Service` (`EscrowService`)
- Routes nommées : `dot.notation` (`api.v1.talents.index`)

**Gestion des erreurs (ARCH-API-5) :**
- Exception de base `BookmiException` avec `code` (string métier), `statusCode` (int HTTP), `details` (array optionnel)
- Handler global qui formate TOUTES les exceptions en JSON envelope
- Messages d'erreur toujours en français

### Versions des dépendances (recherche web 2026-02-17)

| Package | Version | Notes |
|---|---|---|
| Laravel | 12.50.0 | PHP 8.2+ minimum, `--no-starter` pour API-first |
| PHP | 8.2+ (recommandé 8.3/8.4) | 8.4 requis si Spatie Permission v7 |
| Laravel Pint | 1.27.1 | Preset `psr12` via `pint.json` |
| Larastan | 3.9.2 | Level 5 minimum, `phpstan.neon` requis |
| Laravel Sanctum | 4.3.1 | Package SÉPARÉ : `composer require laravel/sanctum` |
| Spatie Permission | v7.x | **ATTENTION : requiert PHP 8.4**. Utiliser v6.x si PHP 8.2/8.3 |
| Laravel Horizon | 5.44.0 | Pour monitoring queues Redis |
| Scribe | 5.7.0 | Documentation API auto-générée |
| MySQL Docker | 8.4.8 LTS | **MySQL 8.0 atteint EOL avril 2026 — utiliser 8.4** |
| Redis Docker | 7.4-alpine | Mode maintenance, mais conforme architecture |

### Alertes critiques pour le développeur

1. **Spatie Permission v7 vs PHP** : L'architecture spécifie PHP 8.2+ mais Spatie Permission v7 requiert PHP 8.4. **Décision requise** : soit utiliser PHP 8.4 dans Docker (recommandé), soit rester PHP 8.2/8.3 avec Spatie Permission v6.x. **Recommandation : PHP 8.4 + Spatie v7** pour rester sur la dernière version.

2. **MySQL 8.4 LTS** : L'architecture dit MySQL 8.x. MySQL 8.0 atteint EOL en avril 2026. Utiliser MySQL 8.4.8 LTS (la version Docker `mysql:8.4`).

3. **Sanctum n'est PAS inclus par défaut** : Contrairement à ce qu'on pourrait croire, Laravel Sanctum doit être installé séparément avec `composer require laravel/sanctum`. Ne pas l'installer dans cette story (sera fait dans Epic 2 : Authentification), mais noter la dépendance.

4. **Ne PAS installer Horizon/Reverb/Sentry dans cette story** : Ces packages seront installés dans les stories qui les utilisent (Epic 4 pour Horizon, Epic 5 pour Reverb, Story 1.12 pour Sentry). Cette story se concentre uniquement sur la fondation.

### Packages à installer dans cette story

```bash
# Dev dependencies
composer require --dev larastan/larastan:^3.9

# Aucun package production dans cette story - juste la fondation Laravel native
```

### Packages à NE PAS installer (seront dans les stories suivantes)

- `laravel/sanctum` → Story 2.1 (Authentification)
- `spatie/laravel-permission` → Story 2.1 (Authentification)
- `laravel/horizon` → Story 4.1 (Paiement)
- `laravel/reverb` → Story 5.1 (Messagerie)
- `sentry/sentry-laravel` → Story 1.12 (CI/CD)
- `knuckleswtf/scribe` → Story 1.7 (Profil public)
- `spatie/laravel-backup` → Story 8.13 (Monitoring)

### Project Structure Notes

**Structure finale attendue après cette story :**

```
bookmi/
├── app/
│   ├── Enums/                      # .gitkeep (vide pour l'instant)
│   ├── Exceptions/
│   │   └── BookmiException.php     # Exception métier de base
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/V1/
│   │   │   │   ├── BaseController.php
│   │   │   │   └── HealthCheckController.php
│   │   │   ├── Admin/              # .gitkeep
│   │   │   └── Web/                # .gitkeep
│   │   ├── Middleware/
│   │   └── Requests/
│   │       ├── Api/                # .gitkeep
│   │       └── Admin/              # .gitkeep
│   ├── Http/Traits/
│   │   └── ApiResponseTrait.php    # Format JSON envelope
│   ├── Models/
│   ├── Repositories/
│   │   ├── Contracts/              # .gitkeep
│   │   └── Eloquent/               # .gitkeep
│   └── Services/                   # .gitkeep
├── config/
│   ├── bookmi.php                  # Constantes métier BookMi
│   └── ...                         # Configs Laravel natives
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── docker/
│   ├── nginx/
│   │   └── default.conf
│   └── php/
│       └── Dockerfile
├── routes/
│   ├── api.php                     # Routes API /v1/
│   ├── web.php
│   └── admin.php                   # Routes admin /admin/
├── tests/
│   ├── Feature/
│   │   ├── Api/V1/
│   │   │   └── HealthCheckTest.php
│   │   └── Admin/                  # .gitkeep
│   └── Unit/
│       └── Config/
│           └── BookmiConfigTest.php
├── docker-compose.yml
├── pint.json                       # Preset PSR-12
├── phpstan.neon                    # Level 5
└── .env.example
```

### References

- [Source: architecture.md § Starter Template Evaluation] — Commande d'init, structure projet, rationale
- [Source: architecture.md § Implementation Patterns & Consistency Rules] — Naming, structure, format patterns
- [Source: architecture.md § API Response Formats] — Format JSON envelope détaillé
- [Source: architecture.md § Error Handling Patterns — Laravel] — BookmiException pattern
- [Source: architecture.md § Infrastructure & Deployment] — Docker, MySQL, Redis, Nginx
- [Source: architecture.md § Data Exchange Formats] — snake_case, centimes, ISO 8601
- [Source: architecture.md § HTTP Status Codes Convention] — Codes HTTP par situation
- [Source: epics.md § Story 1.1] — Acceptance criteria originaux
- [Source: prd.md § NFR48] — Conventions Laravel PSR-12
- [Source: prd.md § NFR52] — CI/CD avec rollback

### Testing Requirements

**Tests à créer dans cette story :**

1. `tests/Feature/Api/V1/HealthCheckTest.php`
   - `test_health_check_returns_200_with_envelope_format`
   - `test_health_check_returns_correct_data_structure`

2. `tests/Unit/Config/BookmiConfigTest.php`
   - `test_commission_rate_is_configured`
   - `test_escrow_settings_are_configured`
   - `test_auth_settings_are_configured`
   - `test_rate_limits_are_configured`

3. Vérifications post-setup :
   - `./vendor/bin/pint --test` passe
   - `./vendor/bin/phpstan analyse` passe (level 5)
   - `php artisan test` passe (tous les tests)
   - `docker compose up -d` démarre sans erreur
   - `php artisan migrate` fonctionne contre MySQL Dockerisé

**Pattern de test :**
- Tests miroirs : le fichier test reflète la structure du code source
- Feature tests pour les endpoints API
- Unit tests pour la configuration et les utilitaires
- Assertion du format JSON envelope dans CHAQUE test d'endpoint

## Dev Agent Record

### Agent Model Used

Claude Opus 4.6

### Debug Log References

### Completion Notes List

- Laravel 12.51.0 installé (via `composer create-project` car `laravel` CLI non installé globalement)
- PHP 8.4.8 local, PHP 8.4 FPM dans Docker (Debian-based au lieu d'Alpine suite à des erreurs TLS avec les dépôts Alpine)
- Larastan 3.9.2 + PHPStan 2.1.39 installés — analyse passe avec `--memory-limit=512M`
- Docker Compose : 4 services fonctionnels (app, mysql, nginx, redis) — migrations MySQL réussies
- Health check `GET /api/v1/health` retourne `{"data":{"status":"ok","version":"1.0.0"}}` via Docker
- 12 tests passent (43 assertions) : 8 unit (config) + 2 feature (health check) + 2 existants
- Pint PSR-12 passe sans erreur
- PHPStan level 5 passe sans erreur
- Rate limiting configuré dans AppServiceProvider (api, payment, auth)
- Exception handler global pour BookmiException, ValidationException, NotFoundHttpException, HttpException, Throwable

### Change Log
- 2026-02-17: Story créée par le workflow create-story — analyse exhaustive complète
- 2026-02-17: Implémentation complète — 9 tâches, 30+ sous-tâches terminées — Status: review

### File List
- `bookmi/app/Exceptions/BookmiException.php` — Exception métier de base
- `bookmi/app/Http/Traits/ApiResponseTrait.php` — Format JSON envelope
- `bookmi/app/Http/Controllers/Api/V1/BaseController.php` — Controller de base API
- `bookmi/app/Http/Controllers/Api/V1/HealthCheckController.php` — Health check endpoint
- `bookmi/app/Providers/AppServiceProvider.php` — Rate limiting configuration
- `bookmi/bootstrap/app.php` — Routes API/admin, exception handler, middleware
- `bookmi/config/bookmi.php` — Constantes métier BookMi
- `bookmi/config/cors.php` — Configuration CORS
- `bookmi/config/logging.php` — Logs JSON structurés (daily channel)
- `bookmi/routes/api.php` — Routes API /v1/ avec health check
- `bookmi/routes/admin.php` — Routes admin /admin/
- `bookmi/docker-compose.yml` — PHP-FPM 8.4 + MySQL 8.4 + Redis 7.4 + Nginx 1.27
- `bookmi/docker/php/Dockerfile` — PHP 8.4 FPM Debian avec extensions
- `bookmi/docker/nginx/default.conf` — Reverse proxy Nginx
- `bookmi/pint.json` — Preset PSR-12
- `bookmi/phpstan.neon` — Level 5, paths app/
- `bookmi/.env` — Configuration MySQL/Redis Docker
- `bookmi/.env.example` — Template complet
- `bookmi/.dockerignore` — Exclusions Docker
- `bookmi/tests/Feature/Api/V1/HealthCheckTest.php` — Tests health check
- `bookmi/tests/Unit/Config/BookmiConfigTest.php` — Tests config bookmi
