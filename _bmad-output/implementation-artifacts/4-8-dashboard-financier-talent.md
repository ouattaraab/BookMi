# Story 4.8: Dashboard financier talent (backend + mobile)

Status: done

## Story

As a talent authentifié,
I want consulter mon dashboard financier (revenus, historique des versements),
So that je puisse piloter ma trésorerie et comprendre mes gains sur BookMi.

**Functional Requirements:** FR-FIN-1, FR-FIN-2
**Non-Functional Requirements:** NFR2 (réponse API < 500ms), PERF-QUERY-1 (1 requête GROUP BY remplacée par PHP grouping)

## Acceptance Criteria (BDD)

**AC1 — Résumé financier**
**Given** un talent authentifié
**When** il envoie `GET /api/v1/me/financial_dashboard`
**Then** la réponse inclut `revenus_total`, `revenus_mois_courant`, `revenus_mois_precedent`, `comparaison_pourcentage`, `nombre_prestations`, `cachet_moyen`, `devise`, `mensuels`
**And** `mensuels` contient exactement 6 entrées (6 derniers mois) au format `YYYY-MM`
**And** seuls les versements avec statut `succeeded` sont comptabilisés

**AC2 — Comparaison mensuelle**
**Given** un talent avec des versements le mois courant mais aucun le mois précédent
**When** il consulte le dashboard
**Then** `comparaison_pourcentage` vaut `100` (nouveau client JSON = entier PHP 100.0)

**AC3 — Historique des versements paginé**
**Given** un talent authentifié
**When** il envoie `GET /api/v1/me/payouts`
**Then** ses versements sont retournés du plus récent au plus ancien, paginés par 20
**And** les données d'autres talents ne sont pas incluses

**AC4 — Écran Flutter**
**Given** un talent naviguant vers `FinancialDashboardPage`
**When** la page se charge
**Then** les revenus totaux s'affichent avec formatage FCFA
**And** un graphique à barres (6 mois) est rendu via `CustomPainter`
**And** le badge de comparaison affiche `▲/▼ X.X % vs mois préc.` en vert/rouge
**And** l'historique des versements est affiché avec statut coloré

**AC5 — Authentification**
**Given** une requête non authentifiée
**When** `GET /api/v1/me/financial_dashboard` est appelé
**Then** la réponse est 401

## Implementation Notes

### Backend (Laravel)

**Nouveaux fichiers :**
- `app/Http/Controllers/Api/V1/FinancialDashboardController.php`
  - `dashboard()` : agrège les versements succeeded, compare mois courant vs précédent, renvoie la ventilation 6 mois en 1 requête + grouping PHP
  - `payouts()` : pagination 20 items, tri DESC

**Routes ajoutées :**
```
GET /api/v1/me/financial_dashboard  → FinancialDashboardController@dashboard
GET /api/v1/me/payouts              → FinancialDashboardController@payouts
```

**Code review fix (M1) :** La ventilation mensuelle utilisait 6 requêtes séparées en boucle. Remplacé par 1 requête `SELECT amount, processed_at WHERE processed_at >= 6 mois` + grouping PHP, pour éviter les fonctions SQL DB-spécifiques (`DATE_FORMAT` ≠ SQLite).

### Flutter

**Nouveaux fichiers :**
- `features/finance/data/models/financial_dashboard_model.dart` — `FinancialDashboardModel` + `MonthlyRevenue`
- `features/finance/data/models/payout_model.dart` — `PayoutModel`
- `features/finance/data/repositories/financial_repository.dart` — `FinancialRepository`
- `features/finance/bloc/financial_dashboard_state.dart` — états sealed
- `features/finance/bloc/financial_dashboard_cubit.dart` — `FinancialDashboardCubit`
- `features/finance/presentation/pages/financial_dashboard_page.dart` — page complète avec `_RevenueBarChartPainter` (CustomPainter, no fl_chart)
- `features/finance/finance.dart` — barrel export

### Tests

**Backend :** `tests/Feature/Api/V1/FinancialDashboardControllerTest.php` — 10 tests
**Flutter :** `test/features/finance/bloc/financial_dashboard_cubit_test.dart` — 5 tests (mocktail)
