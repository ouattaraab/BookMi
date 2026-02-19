# Story 4.9: Export rapports financiers admin (backend)

Status: done

## Story

As an admin,
I want exporter un rapport financier CSV couvrant une période donnée,
So that je puisse analyser les transactions, versements et remboursements.

**Functional Requirements:** FR-ADMIN-RPT-1
**Non-Functional Requirements:** NFR-PERF-STREAM (StreamedResponse, chunkById), NFR-SEC-ADMIN (is_admin guard)

## Acceptance Criteria (BDD)

**AC1 — Téléchargement CSV**
**Given** un administrateur authentifié
**When** il envoie `GET /api/v1/admin/reports/financial?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD`
**Then** la réponse est un fichier CSV téléchargeable (Content-Type: text/csv; charset=UTF-8)
**And** le fichier inclut un BOM UTF-8 (`\xEF\xBB\xBF`) pour la compatibilité Excel
**And** le nom de fichier est `rapport-financier-{start}-au-{end}.csv`

**AC2 — Contenu du CSV**
**Given** des transactions, versements et remboursements dans la période
**When** le CSV est généré
**Then** il contient 3 sections : `=== TRANSACTIONS ===`, `=== VERSEMENTS ===`, `=== REMBOURSEMENTS ===`
**And** chaque section inclut une ligne `TOTAL`

**AC3 — Validation des paramètres**
**Given** une requête sans `start_date` ou `end_date`
**When** le endpoint est appelé
**Then** la réponse est 422 avec `error.code = VALIDATION_FAILED`
**And** si `end_date < start_date`, la réponse est 422

**AC4 — Contrôle d'accès**
**Given** un utilisateur non-admin authentifié
**When** il tente d'accéder au rapport
**Then** la réponse est 403
**And** si non authentifié, 401

## Implementation Notes

### Backend (Laravel)

**Nouveaux fichiers :**
- `app/Http/Requests/Api/AdminReportRequest.php` — `start_date` (required, date), `end_date` (required, date, after_or_equal:start_date), `format` (optional, in:csv)
- `app/Http/Controllers/Api/V1/AdminReportController.php`
  - `financial()` : `StreamedResponse` avec `fputcsv`, 3 sections, `chunkById(100)` pour sécurité mémoire

**Route ajoutée :**
```
GET /api/v1/admin/reports/financial → AdminReportController@financial  [middleware: admin]
```

**Code review fix (M1) — `fclose` sur exception :** La ressource `fopen('php://output', 'w')` est maintenant fermée dans un bloc `try/finally` pour garantir la fermeture même en cas d'exception dans les callbacks `chunkById`.

### Tests

**Backend :** `tests/Feature/Api/V1/AdminReportControllerTest.php` — 7 tests
- Téléchargement CSV (200 + Content-Type)
- Présence des 3 sections
- Inclut les remboursements (refund_reference + motif)
- 422 date manquante / inversée
- 403 non-admin, 401 non authentifié
