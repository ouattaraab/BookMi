# Story 4.7 — Remboursement en cas de litige (backend)

## Statut : ✅ Done

**Epic :** 4 — Paiement & Séquestre
**Sprint :** Sprint 4

---

## Objectif

Permettre à un administrateur de déclencher un remboursement total ou partiel via l'API Paystack Refund, avec rollback automatique en cas d'échec gateway et cascade sur le séquestre et la réservation.

---

## Architecture

### Pattern 3 étapes (identique à `PaymentService`)

```
Step 1 — DB::transaction (courte)
  ├── lockForUpdate() sur transaction (status = Succeeded)
  ├── Validation : amount ≤ transaction.amount
  └── Marque optimiste → status = Refunded (prévient les doublons concurrents)

Step 2 — HTTP OUTSIDE DB transaction
  ├── gateway->refundTransaction(reference, amount, reason)
  ├── OK → continue
  └── Erreur → reset status = Succeeded + re-throw (aucune corruption)

Step 3 — DB::transaction (courte)
  ├── Persiste refund_reference (id Paystack)
  ├── EscrowHold (rechargé via fresh()) → status = Refunded
  └── BookingRequest → status = Cancelled
```

### Données enrichies sur `transactions`

| Colonne | Type | Rôle |
|---|---|---|
| `refund_amount` | unsignedBigInteger nullable | Montant remboursé (XOF, pas de ×100) |
| `refund_reference` | string nullable | ID de remboursement Paystack |
| `refund_reason` | string nullable | Motif saisi par l'admin |
| `refunded_at` | timestamp nullable | Date/heure du remboursement |

---

## Fichiers créés / modifiés

### Nouveaux fichiers

| Fichier | Rôle |
|---|---|
| `database/migrations/2026_02_19_045521_add_refund_fields_to_transactions_table.php` | 4 colonnes refund sur `transactions` |
| `app/Exceptions/RefundException.php` | `amountExceedsTransaction`, `transactionNotRefundable`, `noSucceededTransaction` |
| `app/Services/RefundService.php` | Logique remboursement 3 étapes |
| `app/Http/Requests/Api/AdminRefundRequest.php` | Validation `amount` (int, min:1) + `reason` (str, max:500) |
| `app/Http/Controllers/Api/V1/AdminRefundController.php` | `POST /admin/booking_requests/{booking}/refund` |
| `tests/Feature/Api/V1/AdminRefundControllerTest.php` | 10 tests (succès, partiel, validations, rollback, ACL) |

### Fichiers modifiés

| Fichier | Modification |
|---|---|
| `app/Models/Transaction.php` | Champs refund dans `$fillable` + casts |
| `app/Contracts/PaymentGatewayInterface.php` | `refundTransaction(string, int, string): array` |
| `app/Gateways/PaystackGateway.php` | `refundTransaction()` → POST `/refund` |
| `app/Gateways/FedaPayGateway.php` | `refundTransaction()` → `unsupportedMethod` |
| `app/Gateways/PaymentGatewayResolver.php` | `refundTransaction()` → primary only (pas de fallback FedaPay) |
| `routes/api.php` | Route admin + import `AdminRefundController` |

---

## Route

```
POST /api/v1/admin/booking_requests/{booking}/refund
Middleware: auth:sanctum + admin
```

```json
// Request
{
  "amount": 10500000,
  "reason": "Litige résolu en faveur du client — prestation non effectuée."
}

// Response 200
{
  "message": "Remboursement effectué avec succès."
}
```

---

## Critères d'acceptation

| # | Critère | Testé |
|---|---|---|
| AC1 | Admin peut rembourser intégralement une réservation | ✅ |
| AC2 | Admin peut rembourser partiellement | ✅ |
| AC3 | `amount = 0` → 422 VALIDATION_FAILED | ✅ |
| AC4 | `reason` absent → 422 VALIDATION_FAILED | ✅ |
| AC5 | Montant > transaction.amount → 422 REFUND_AMOUNT_EXCEEDS_TRANSACTION | ✅ |
| AC6 | Aucune transaction Succeeded → 422 REFUND_NO_SUCCEEDED_TRANSACTION | ✅ |
| AC7 | Échec gateway → transaction reste Succeeded (rollback) + 502 | ✅ |
| AC8 | Non-admin → 403 | ✅ |
| AC9 | Non authentifié → 401 | ✅ |
| AC10 | Réservation inexistante → 404 | ✅ |

---

## Décisions techniques

- **Rollback optimiste** : la transaction est marquée `Refunded` avant l'appel HTTP pour bloquer les doublons. En cas d'échec, elle est réinitialisée à `Succeeded` dans le catch — aucune corruption possible.
- **`$transaction->fresh()->escrowHold`** (Step 3) : recharge la relation depuis la DB pour éviter d'écraser un statut `Released` modifié en parallèle par le cron `ReleaseExpiredEscrows`.
- **Pas de fallback FedaPay** pour `refundTransaction` : les remboursements passent toujours par Paystack (gateway primaire), même si la charge initiale a utilisé FedaPay.
- **Guard `refundAmount < 1` supprimé** : redondant avec la validation `AdminRefundRequest (min:1)` ; le guard interne `amount > fresh->amount` couvre déjà les montants invalides.

---

## Code review adversariale — problèmes résolus

| Sévérité | Problème | Fix |
|---|---|---|
| H1 | Guard `refundAmount < 1` → `amountExceedsTransaction(0, 0)` — message trompeur et redondant | Supprimé (FormRequest `min:1` suffit) |
| M2 | `$transaction->escrowHold` utilise instance cached → risque d'écraser `Released` concurrent | Remplacé par `$transaction->fresh()->escrowHold` |
