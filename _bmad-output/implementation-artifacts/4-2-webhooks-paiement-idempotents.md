# Story 4.2 — Webhooks paiement idempotents (backend)

## Statut : ✅ Done

**Epic :** 4 — Paiement & Séquestre
**Sprint :** Sprint 4

---

## Objectif

Implémenter la réception idempotente des webhooks Paystack (`charge.success`, `charge.failed`) et le flow OTP (`POST /payments/submit_otp`). À la réception d'un `charge.success`, crée le séquestre (`EscrowHold`), marque la réservation `paid`, et dispatche l'événement `PaymentReceived`. La validation de signature HMAC-SHA512 (NFR42) protège l'endpoint webhook.

---

## Architecture

### Middleware — Signature HMAC-SHA512

**`app/Http/Middleware/ValidatePaystackSignature.php`** :
- Lit le header `x-paystack-signature` (hex SHA512)
- Calcule `hash_hmac('sha512', raw_body, secret)` et compare avec `hash_equals()` (timing-safe)
- Si `PAYSTACK_WEBHOOK_SECRET` absent → laisse passer + `Log::warning()` pour alerter les opérateurs
- Invalide → 401 `WEBHOOK_SIGNATURE_INVALID`
- Alias enregistré dans `bootstrap/app.php` : `'paystack-webhook'`

### Job — HandlePaymentWebhook

**`app/Jobs/HandlePaymentWebhook.php`** :
- `implements ShouldQueue` — queue `payments` via `$this->onQueue('payments')`
- `$tries = 5` — 5 tentatives max (NFR35)
- `$backoff = [10, 30, 90, 270, 810]` — backoff exponentiel × 3 (NFR35)
- `match($this->event)` : `charge.success`, `charge.failed`, `default` (silence)

**`handleChargeSuccess()` — flow atomique anti-race** :
1. Cherche la transaction par `idempotency_key` OR `gateway_reference`
2. Ignore si référence manquante ou transaction inconnue
3. **Dans `DB::transaction` avec `lockForUpdate()`** : vérifie que le status n'est pas déjà `Succeeded` (idempotency H1), puis update → `succeeded` + `completed_at`
4. Crée la `EscrowHold` (status=`held`, `release_scheduled_at = +48h`) — NFR33 auto-confirm
5. Si `BookingRequest` introuvable → throw `RuntimeException` → rollback (H2 — cohérence DB)
6. **Après commit** → `PaymentReceived::dispatch($transaction->fresh(), $escrowHold)`

**`handleChargeFailure()`** :
- Lookup identique (idempotency_key OR gateway_reference)
- Guard idempotency : status déjà `Failed` ou `Succeeded` → skip
- Update `status = failed`

### Event

**`app/Events/PaymentReceived.php`** :
```php
class PaymentReceived {
    use Dispatchable, SerializesModels;
    public readonly Transaction $transaction,
    public readonly EscrowHold $escrowHold,
}
```
Dispatché APRÈS commit DB pour que les listeners voient les données commitées.

### Controller Webhook

**`app/Http/Controllers/Api/V1/PaystackWebhookController.php`** :
- `POST /api/v1/webhooks/paystack` (route **publique**, middleware `paystack-webhook`)
- Retourne immédiatement 200 `{ "status": "received" }` pour éviter les retries Paystack
- Dispatche `HandlePaymentWebhook` sur la queue `payments`
- Event vide → pas de dispatch

### OTP Submit

**`app/Http/Controllers/Api/V1/PaymentController.php::submitOtp`** :
- `POST /api/v1/payments/submit_otp` (auth:sanctum, throttle:payment)
- Vérifie que `$transaction->bookingRequest->client_id === $request->user()->id` (403 sinon) — M2 fix
- Délègue à `PaymentService::submitOtp(reference, otp)`

**`app/Services/PaymentService.php::submitOtp`** :
- Lookup par `idempotency_key`
- Guard : `status !== Processing` → `PaymentException::transactionNotProcessing()` (code `PAYMENT_TRANSACTION_NOT_PROCESSING`) — M1 fix
- Délègue à `PaymentGatewayInterface::submitOtp(reference, otp)` → `POST /charge/submit_otp`

**`app/Http/Requests/Api/SubmitOtpRequest.php`** :
- `reference`: `required`, `string`, `exists:transactions,idempotency_key`
- `otp`: `required`, `string`, `digits_between:4,8`

**`app/Gateways/PaystackGateway.php::submitOtp`** :
- `POST https://api.paystack.co/charge/submit_otp` `{ reference, otp }`
- Retourne `$data['data']` (status, display_text)

**`app/Contracts/PaymentGatewayInterface.php`** : méthode `submitOtp(string $reference, string $otp): array` ajoutée.

### Exceptions

**`app/Exceptions/PaymentException.php`** — factory method ajouté :
- `transactionNotProcessing(status)` → 422 `PAYMENT_TRANSACTION_NOT_PROCESSING`

---

## Routes

```
POST /api/v1/webhooks/paystack      [paystack-webhook]  PaystackWebhookController@handle
POST /api/v1/payments/submit_otp    [auth:sanctum, throttle:payment]  PaymentController@submitOtp
```

---

## Flow complet Paystack Charge API (Mobile Money CI)

```
Flutter → POST /api/v1/payments/initiate (Story 4.1)
  ← 201 { status: "processing", gateway_status: "send_otp", display_text: "..." }

Flutter affiche OTP prompt → utilisateur saisit OTP
  → POST /api/v1/payments/submit_otp { reference, otp }
  → PaystackGateway POST /charge/submit_otp
  ← { status: "success" | "pending" }

Paystack → POST /api/v1/webhooks/paystack { event: "charge.success", data: { reference } }
  → [queue: payments] HandlePaymentWebhook
    → DB::transaction + lockForUpdate()
    → transaction: succeeded, booking: paid, EscrowHold: created (held, +48h)
    → PaymentReceived::dispatch()
```

---

## Tests

**`tests/Feature/Api/V1/PaystackWebhookControllerTest.php`** — 8 tests :

| Test | Résultat |
|---|---|
| Webhook valide → 200 + job dispatché (queue payments) | ✅ |
| charge.failed dispatché | ✅ |
| Événement inconnu → 200 + job dispatché | ✅ |
| Événement vide → 200, pas de dispatch | ✅ |
| Signature invalide avec secret configuré → 401 | ✅ |
| Signature HMAC valide avec secret configuré → 200 | ✅ |
| Pas de secret → validation bypassée + 200 | ✅ |

**`tests/Feature/Jobs/HandlePaymentWebhookTest.php`** — 12 tests :

| Test | Résultat |
|---|---|
| charge.success → transaction succeeded + booking paid + completed_at | ✅ |
| charge.success → EscrowHold held + release_scheduled_at ~+48h | ✅ |
| charge.success → PaymentReceived dispatché | ✅ |
| charge.success idempotent (2× → 1 escrow, 1 event) | ✅ |
| charge.success référence manquante → ignoré | ✅ |
| charge.success référence inconnue → ignoré | ✅ |
| charge.failed → transaction failed | ✅ |
| charge.failed idempotent | ✅ |
| charge.failed référence manquante → ignoré | ✅ |
| Événement inconnu → silencieux | ✅ |

**`tests/Feature/Api/V1/PaymentControllerTest.php`** — 18 tests (dont 6 nouveaux Story 4.2) :

| Test (nouveaux 4.2) | Résultat |
|---|---|
| OTP soumis avec succès | ✅ |
| Référence inexistante → 422 VALIDATION_FAILED | ✅ |
| OTP non numérique → 422 VALIDATION_FAILED | ✅ |
| Transaction non-processing → 422 PAYMENT_TRANSACTION_NOT_PROCESSING | ✅ |
| Non-propriétaire → 403 | ✅ |
| Non-authentifié → 401 | ✅ |

**Total Story 4.2 : 35 tests, 103 assertions | Suite complète : 415 tests, 1349 assertions**

---

## Decisions d'architecture

- **Queue `payments`** : isolation des workers paiement pour limiter l'impact des retries sur les autres queues
- **5 retries + backoff × 3** : résilience aux indisponibilités temporaires Paystack (NFR35)
- **lockForUpdate() dans DB::transaction** : prévient le TOCTOU pour la création dupliquée d'EscrowHold sous webhooks concurrents (retry rapide Paystack)
- **throw RuntimeException si booking absent** : garantit le rollback DB → transaction ne reste pas en état `succeeded` sans EscrowHold (H2)
- **Event dispatché APRÈS commit** : les listeners voient les données cohérentes (relation EscrowHold commitée)
- **Retour 200 immédiat** : conforme à la recommandation Paystack pour éviter les retries inutiles
- **Log::warning() quand secret absent** : alerte opérateurs si `PAYSTACK_WEBHOOK_SECRET` manque en production (M3)

## Issues corrigées lors du code review

- [H1] Race condition TOCTOU — idempotency check déplacé DANS `DB::transaction` avec `lockForUpdate()`
- [H2] Incohérence état si booking absent — `return` remplacé par `throw RuntimeException` (rollback)
- [M1] Exception sémantiquement incorrecte dans `submitOtp` — `PaymentException::transactionNotProcessing()` créé
- [M2] Absence de vérification propriété dans `submitOtp` — check `client_id === user()->id` ajouté (403)
- [M3] Signature bypassée silencieusement — `Log::warning()` ajouté
- [L1] Test `submitOtp` avec transaction non-processing — ajouté
- [L2] `completed_at` non vérifié — assertion ajoutée dans test charge.success
- [L3] `charge.failed` référence manquante — test ajouté
