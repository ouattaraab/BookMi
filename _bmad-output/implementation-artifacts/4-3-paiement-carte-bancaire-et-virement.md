# Story 4.3 — Paiement carte bancaire et virement (backend)

## Statut : ✅ Done

**Epic :** 4 — Paiement & Séquestre
**Sprint :** Sprint 4

---

## Objectif

Étendre le système de paiement pour accepter `card` et `bank_transfer` via Paystack `POST /transaction/initialize` (flow 3D Secure redirect). Expose `GET /api/v1/payments/callback` (endpoint public pour le retour Paystack) et `GET /api/v1/payments/{transaction}/status` (polling client).

---

## Architecture

### Contrat — `PaymentGatewayInterface`

Nouvelle méthode ajoutée :
```php
/**
 * Initialize a transaction (card / bank_transfer — POST /transaction/initialize).
 * Returns authorization_url for 3D Secure redirect flow.
 */
public function initializeTransaction(array $payload): array;
```

### Gateway — `PaystackGateway`

- `initializeTransaction()` → `POST /transaction/initialize` (channels: `['card']` ou `['bank_transfer']`)
- **Toutes les méthodes HTTP** passent via `$this->http()` : `Http::withToken($key)->timeout(15)` (NFR4 — max 15s)
- Réponse : `{ authorization_url, access_code, reference }`

### Service — `PaymentService::initiatePayment` (mise à jour)

Nouveau branching selon `isMobileMoney()` :

```
PaymentMethod::isMobileMoney() true
  → initiateChargeForMobileMoney() → POST /charge { mobile_money }
PaymentMethod::card | bank_transfer
  → initializeTransactionForCard() → POST /transaction/initialize { channels, callback_url }
```

**Fix M3 — Re-check booking status DANS DB::transaction avec lockForUpdate()** : évite qu'une annulation concurrente crée une transaction sur une réservation annulée.

### Request — `InitiatePaymentRequest` (mise à jour)

- `payment_method` accepte désormais : `orange_money`, `wave`, `mtn_momo`, `moov_money`, **`card`**, **`bank_transfer`**
- `phone_number` : `required_if` → requis uniquement pour les méthodes Mobile Money

### Resource — `TransactionResource` (mise à jour)

Nouveaux champs pour le flow carte :
```json
{
  "authorization_url": "https://checkout.paystack.com/xxx",  // card/bank_transfer
  "access_code": "xxx"                                        // card/bank_transfer
}
```

Les champs mobiles (`gateway_status`, `display_text`) et carte (`authorization_url`, `access_code`) sont renvoyés conditionnellement (null si non applicable).

### Controller — `PaymentController` (nouvelles actions)

**`GET /v1/payments/callback`** (route publique) :
- Endpoint de retour Paystack après 3D Secure (redirect WebView Flutter)
- Vérifie que la référence existe dans la DB (anti-énumération — Fix H1)
- Retour `{ status: "received", reference: "..." }` (200) ou erreur 400/404
- La mise à jour d'état réelle est gérée par le webhook (Story 4.2)

**`GET /v1/payments/{transaction}/status`** (auth:sanctum) :
- Poll du statut courant d'une transaction
- Vérifie que `$transaction->bookingRequest->client_id === $request->user()->id` (403)
- Retourne `TransactionResource`

### Config — `config/bookmi.php`

```php
'payment' => [
    'primary_gateway'  => 'paystack',
    'fallback_gateway' => 'cinetpay',
    'callback_url'     => env('PAYMENT_CALLBACK_URL', 'http://localhost:8000/api/v1/payments/callback'),
],
```

---

## Routes

```
GET  /api/v1/payments/callback              [public]         PaymentController@callback
GET  /api/v1/payments/{transaction}/status  [auth:sanctum]   PaymentController@status
```

---

## Flow carte / virement (3D Secure)

```
Flutter → POST /api/v1/payments/initiate { payment_method: "card" }
  → PaymentService → initializeTransactionForCard()
  → POST api.paystack.co/transaction/initialize { channels: ['card'], callback_url }
  ← { authorization_url, access_code, reference }
  → transaction: initiated → processing
← 201 { status: "processing", authorization_url: "https://checkout.paystack.com/...", gateway_reference }

Flutter ouvre WebView sur authorization_url → utilisateur saisit CB + 3D Secure
Paystack redirige → GET /api/v1/payments/callback?reference=xxx&trxref=xxx
  ← 200 { status: "received" } (WebView Flutter détecte et ferme)

Flutter poll → GET /api/v1/payments/{transaction}/status
  ← { status: "processing" } (en attente webhook)

Paystack → POST /api/v1/webhooks/paystack { event: "charge.success" } (Story 4.2)
  → transaction: succeeded, booking: paid, EscrowHold: created
```

---

## Tests

**`tests/Feature/Api/V1/PaymentCardControllerTest.php`** — 13 tests :

| Test | Résultat |
|---|---|
| Client initie paiement carte → 201 + authorization_url | ✅ |
| Bank transfer accepté → 201 | ✅ |
| Carte ne nécessite pas phone_number | ✅ |
| Mobile Money nécessite toujours phone_number | ✅ |
| Paystack fail carte → 502 + transaction failed | ✅ |
| Callback référence connue → 200 | ✅ |
| Callback référence manquante → 400 | ✅ |
| Callback référence inconnue → 404 (anti-énumération) | ✅ |
| Callback accessible sans authentification | ✅ |
| Poll status → 200 + TransactionResource | ✅ |
| Poll status non-propriétaire → 403 | ✅ |
| Poll status transaction inconnue → 404 | ✅ |
| Poll status non-authentifié → 401 | ✅ |

**Total Story 4.3 : 30 tests, 106 assertions | Suite complète : 427 tests, 1389 assertions**

---

## Décisions d'architecture

- **`initializeTransaction` vs `initiateCharge`** : deux méthodes distinctes dans l'interface car le flow est fondamentalement différent (redirect URL vs charge directe OTP)
- **Helper `http()`** : centralise le timeout 15s (NFR4) sur toutes les méthodes Paystack
- **`callback_url` configurable** : stockée dans `config/bookmi.php` + `.env` pour s'adapter aux environnements (local, staging, prod)
- **Callback public** : conforme au fonctionnement Paystack (redirect navigateur) — la sécurité est gérée côté webhook
- **Vérification référence sur callback** : anti-énumération — référence doit exister en DB

## Issues corrigées lors du code review

- [H1] Callback sans vérification référence → lookup DB ajouté (404 si inconnu)
- [M2] Pas de timeout sur les appels gateway → `->timeout(15)` via helper `http()` (NFR4)
- [M3] Race condition sur booking status — re-check `lockForUpdate()` DANS `DB::transaction`
