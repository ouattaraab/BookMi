# Story 4.1 — Intégration Paystack — Paiement Mobile Money (backend)

## Statut : ✅ Done

**Epic :** 4 — Paiement & Séquestre
**Sprint :** Sprint 4

---

## Objectif

Mettre en place l'infrastructure de paiement Mobile Money (Orange Money, Wave, MTN MoMo, Moov Money) via l'API Paystack Charge. Crée les tables `transactions` et `escrow_holds`, l'abstraction `PaymentGatewayInterface`, et expose `POST /api/v1/payments/initiate`.

---

## Architecture

### Enums

**`PaymentMethod.php`** :
- Cases : `orange_money`, `wave`, `mtn_momo`, `moov_money`, `card`, `bank_transfer`
- `isMobileMoney(): bool` — distingue le flow Mobile Money
- `paystackProvider(): ?string` — mappe vers le code Paystack (`orange`, `wave`, `mtn`, `moov`)

**`TransactionStatus.php`** : `initiated` → `processing` → `succeeded` | `failed` | `refunded`

**`EscrowStatus.php`** : `held`, `released`, `refunded`, `disputed`

**`PayoutStatus.php`** : `pending`, `processing`, `succeeded`, `failed`

### Migrations

| Fichier | Table | Description |
|---|---|---|
| `2026_02_18_230000_create_transactions_table.php` | `transactions` | booking_request_id, payment_method, amount (XOF), currency, gateway, gateway_reference (unique), gateway_response (json), status, idempotency_key (unique), initiated_at, completed_at |
| `2026_02_18_230100_create_escrow_holds_table.php` | `escrow_holds` | transaction_id, booking_request_id, cachet_amount, commission_amount, total_amount, status, held_at, release_scheduled_at, released_at |
| `2026_02_18_230200_create_payouts_table.php` | `payouts` | talent_profile_id, escrow_hold_id, amount, payout_method, payout_details (json), gateway, gateway_reference, status, processed_at |
| `2026_02_18_230300_add_payout_fields_to_talent_profiles_table.php` | `talent_profiles` | payout_method (nullable), payout_details (nullable json) |

### Contrat

**`app/Contracts/PaymentGatewayInterface.php`** :
- `name(): string` — identifiant passerelle
- `initiateCharge(array $payload): array` — initie une charge (Mobile Money / carte)
- `verifyTransaction(string $reference): array` — vérifie une transaction
- `initiateTransfer(array $payload): array` — initie un virement (payout talent)

### Gateway

**`app/Gateways/PaystackGateway.php`** :
- Implémente `PaymentGatewayInterface`
- Base URL : `https://api.paystack.co`
- Auth : Bearer token via `config('services.paystack.secret_key')`
- `initiateCharge` → `POST /charge` avec `mobile_money: { phone, provider }`
- `verifyTransaction` → `GET /transaction/verify/{reference}`
- `initiateTransfer` → `POST /transfer`
- Erreurs Paystack → `PaymentException::gatewayError()`

### Service

**`app/Services/PaymentService.php`** — `initiatePayment(BookingRequest, array): Transaction` :

Pattern robuste en 3 phases :
1. **Validation synchrone** : booking `accepted`, méthode `isMobileMoney()`, eager-load client
2. **Courte DB transaction** : `lockForUpdate()` anti-doublon concurrent + `Transaction::create(status=initiated)`
3. **Appel HTTP hors transaction** : évite de tenir la connexion DB pendant 15s (NFR4)
   - Succès → `update(status=processing, gateway_reference, gateway_response)`
   - Échec → `update(status=failed)` puis re-throw `PaymentException`

**`app/Exceptions/PaymentException.php`** :
- `bookingNotPayable(status)` → 422 `PAYMENT_BOOKING_NOT_PAYABLE`
- `gatewayError(gateway, message)` → 502 `PAYMENT_GATEWAY_ERROR`
- `unsupportedMethod(method)` → 422 `PAYMENT_UNSUPPORTED_METHOD`
- `duplicateTransaction()` → 409 `PAYMENT_DUPLICATE`

### Controller & Route

**`POST /api/v1/payments/initiate`** (auth:sanctum, throttle:payment) :
- `InitiatePaymentRequest` valide : `booking_id` (exists), `payment_method` (orange_money|wave|mtn_momo|moov_money), `phone_number` (regex)
- Vérifie `$booking->client_id === $request->user()->id` (403 sinon)
- Retourne `TransactionResource` (201) avec `status`, `gateway_status`, `display_text` (pour prompt OTP Paystack Charge API)

**`TransactionResource`** : id, booking_id, payment_method, amount, currency, gateway, gateway_reference, status, gateway_status, display_text, initiated_at, completed_at

### Injection de dépendances

`AppServiceProvider::register()` :
```php
$this->app->bind(PaymentGatewayInterface::class, PaystackGateway::class);
```

### Configuration

**`config/services.php`** :
```php
'paystack' => [
    'secret_key' => env('PAYSTACK_SECRET_KEY'),
    'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
],
'cinetpay' => [...],
```

**Variables `.env`** requises : `PAYSTACK_SECRET_KEY`, `PAYSTACK_PUBLIC_KEY`, `PAYSTACK_WEBHOOK_SECRET`

---

## Flow Paystack Charge API (Mobile Money CI)

```
Flutter → POST /api/v1/payments/initiate
  → PaymentService (lock + create initiated)
  → POST api.paystack.co/charge { mobile_money: { phone, provider } }
  ← { status: "send_otp", display_text: "...", reference: "..." }
  → update transaction (processing)
← 201 { status: "processing", gateway_status: "send_otp", display_text: "...", gateway_reference: "..." }

Flutter affiche prompt OTP → POST /api/v1/payments/submit_otp (Story 4.2)
  → POST api.paystack.co/charge/submit_otp
Paystack → webhook charge.success → Story 4.2 webhook handler
```

---

## Tests

**`tests/Feature/Api/V1/PaymentControllerTest.php`** — 12 tests, 42 assertions :

| Test | Résultat |
|---|---|
| Orange Money initié avec succès | 201 + DB has processing transaction |
| Wave accepté | 201 |
| MTN MoMo accepté | 201 |
| Moov Money accepté | 201 |
| Booking non-accepted → 422 | ✅ |
| Transaction en doublon → 409 | ✅ |
| Non-client → 403 | ✅ |
| Non-authentifié → 401 | ✅ |
| payment_method invalide → 422 VALIDATION_FAILED | ✅ |
| card exclu du endpoint Mobile Money → 422 | ✅ |
| phone_number invalide → 422 | ✅ |
| Paystack fail → 502 + transaction marked failed | ✅ |

---

## Decisions d'architecture

- **HTTP hors DB::transaction** : évite l'épuisement du pool de connexions sous charge (appel Paystack peut durer jusqu'à 15s)
- **lockForUpdate()** anti-race condition : garantit une seule transaction en vol par booking
- **idempotency_key** : UUID unique stocké sur la transaction pour déduplication côté Paystack (référence)
- **gateway_response** stocké en JSON : permet audit complet et gestion des `send_otp`/`pending` states
- **`display_text` dans TransactionResource** : nécessaire pour que Flutter affiche le prompt OTP de Paystack Charge API
- **Pas d'SDK Paystack** : utilisation de `Http::withToken()` (Laravel HTTP Client) pour éviter une dépendance externe

## Issues corrigées lors du code review

- [H1] Appel HTTP sorti de DB::transaction (anti-ghost-charge, anti-pool-exhaustion)
- [H2] Race condition corrigée avec `lockForUpdate()` dans la transaction
- [M2] Eager-load `$booking->loadMissing('client')` avant l'appel HTTP
- [M3] Test assertion ajoutée : transaction `failed` après échec gateway
- [M4] Tests ajoutés pour MTN MoMo et Moov Money
- [L1] Guard `isMobileMoney()` ajouté dans `PaymentService`
- [L2] `Http::preventStrayRequests()` dans tous les tests sans fake
