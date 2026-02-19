# Story 7.9 — Attestation de revenus annuelle

## Status: done

## Story

**As a** talent,
**I want** to download an annual revenue certificate as a PDF,
**So that** I can use it for tax declarations or bank applications.

## Acceptance Criteria

1. **AC1** — `GET /api/v1/me/revenue_certificate?year=YYYY` returns a PDF attachment.
2. **AC2** — Year parameter is required (min: 2020, max: current year).
3. **AC3** — Returns 404 if the user has no talent profile.
4. **AC4** — Certificate shows gross amount, BookMi commission, and net revenue by month and as annual total.
5. **AC5** — Works with 0 completed bookings (shows zeros).

## PDF Content

- Talent identity (stage name, full name, email, phone)
- Annual summary (total bookings, gross/commission/net amounts)
- Monthly breakdown table (month-by-month)

## Implementation Notes

### Route

```
GET /api/v1/me/revenue_certificate?year=YYYY → RevenueCertificateController::download
```

### Controller

- `app/Http/Controllers/Api/V1/RevenueCertificateController.php`
  - Uses DomPDF (`barryvdh/laravel-dompdf`) — already in composer.json
  - PHP-level grouping by month (DB-portable)
  - Returns `Content-Type: application/pdf` with download filename

### Blade Template

- `resources/views/pdf/revenue_certificate.blade.php`
  - Styled with inline CSS (DejaVu Sans font for special chars)
  - Header, identity section, summary box, monthly breakdown table

### Tests

- `tests/Feature/Api/V1/RevenueCertificateTest.php` — 4 test cases
