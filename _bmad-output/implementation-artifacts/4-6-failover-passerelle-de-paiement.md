# Story 4.6 — Failover passerelle de paiement (backend)

## Statut : ✅ Done

**Epic :** 4 — Paiement & Séquestre
**Sprint :** Sprint 4

---

## Objectif

Implémenter FedaPay comme gateway de failover : si Paystack échoue (PAYMENT_GATEWAY_ERROR), le système bascule automatiquement sur FedaPay pour `initiateCharge` / `initializeTransaction` / `verifyTransaction`. Les opérations Paystack-spécifiques (`submitOtp`, `createTransferRecipient`, `initiateTransfer`) ne basculent pas.

---

## Architecture

### Pattern — Decorator `PaymentGatewayResolver`

`PaymentGatewayResolver implements PaymentGatewayInterface` wraps primary (Paystack) + fallback (FedaPay).

```
initiateCharge(payload)
  → primary.initiateCharge(payload)     ← OK → return
  → PAYMENT_GATEWAY_ERROR caught
    → Log::warning("Primary [paystack] failed... switching to [fedapay]")
    → fallback.initiateCharge(payload)  ← OK → return
    → PAYMENT_GATEWAY_ERROR re-thrown   ← 502 au client
```

**Méthodes sans fallback** (`NO_FALLBACK_METHODS`) :
- `submitOtp` — spécifique Paystack (OTP flow)
- `createTransferRecipient` — spécifique Paystack
- `initiateTransfer` — Payouts restent sur Paystack

Seules les erreurs `PAYMENT_GATEWAY_ERROR` déclenchent le fallback. Les autres exceptions (PAYMENT_DUPLICATE, PAYMENT_BOOKING_NOT_PAYABLE, etc.) sont re-thrown immédiatement.

### Gateway — `FedaPayGateway`

Implémente `PaymentGatewayInterface` via l'API FedaPay `https://api.fedapay.com/v1` :

| Méthode | Implémentation |
|---|---|
| `name()` | `'fedapay'` |
| `initiateCharge()` | POST `/transactions` → POST `/transactions/{id}/pay { mobile_money }` |
| `initializeTransaction()` | POST `/transactions` → GET `/transactions/{id}/token` → `authorization_url` |
| `verifyTransaction()` | GET `/transactions/{reference}` |
| `submitOtp()` | `throw PaymentException::unsupportedMethod('fedapay:submit_otp')` |
| `createTransferRecipient()` | `throw PaymentException::unsupportedMethod(...)` |
| `initiateTransfer()` | `throw PaymentException::unsupportedMethod(...)` |

Auth : `Authorization: Bearer FEDAPAY_SECRET_KEY`, timeout 15s.

### Binding IoC — `AppServiceProvider`

```php
$this->app->bind(PaymentGatewayInterface::class, function ($app) {
    return new PaymentGatewayResolver(
        $app->make(PaystackGateway::class),
        $app->make(FedaPayGateway::class),
    );
});
```

`PaymentService`, `PayoutService`, etc. continuent d'injecter `PaymentGatewayInterface` sans changement.

### Configuration

```php
// config/bookmi.php
'payment' => [
    'primary_gateway'  => 'paystack',
    'fallback_gateway' => 'fedapay',
    ...
]

// config/services.php
'fedapay' => [
    'secret_key' => env('FEDAPAY_SECRET_KEY'),
    'public_key'  => env('FEDAPAY_PUBLIC_KEY'),
]
```

```env
FEDAPAY_SECRET_KEY=sk_sandbox_
FEDAPAY_PUBLIC_KEY=pk_sandbox_
```

---

## Tests

**`tests/Feature/Gateways/PaymentGatewayResolverTest.php`** — 5 tests :

| Test | Résultat |
|---|---|
| Paystack disponible → transaction via Paystack | ✅ |
| Paystack échoue → fallback FedaPay + Log::warning | ✅ |
| Les deux échouent → 502 PAYMENT_GATEWAY_ERROR | ✅ |
| Card Paystack échoue → FedaPay fallback + authorization_url | ✅ |
| submitOtp Paystack échoue → 502 (pas de fallback FedaPay) | ✅ |

**Total Story 4.6 : 5 tests, 10 assertions | Suite complète : 457 tests, 1453 assertions**

---

## Décisions d'architecture

- **Decorator pattern** sur `PaymentGatewayInterface` — aucun changement dans `PaymentService`, `PayoutService`, ni les controllers
- **`NO_FALLBACK_METHODS`** — liste explicite des méthodes Paystack-spécifiques pour éviter des comportements inattendus avec FedaPay
- **Only `PAYMENT_GATEWAY_ERROR`** déclenche le fallback — les autres exceptions (validation, duplicate) passent directement
- **`Log::warning`** systématique sur chaque basculement — observabilité pour l'équipe ops
- **FedaPay unsupported methods** → `PaymentException::unsupportedMethod()` — pattern cohérent
- **Payouts restent sur Paystack** — FedaPay est utilisé uniquement comme fallback de collecte
