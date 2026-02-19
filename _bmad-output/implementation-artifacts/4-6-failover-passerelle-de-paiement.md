# Story 4.6 — Failover passerelle de paiement (backend)

## Statut : ✅ Done

**Epic :** 4 — Paiement & Séquestre
**Sprint :** Sprint 4

---

## Objectif

Implémenter CinetPay comme gateway de failover : si Paystack échoue (PAYMENT_GATEWAY_ERROR), le système bascule automatiquement sur CinetPay pour `initiateCharge` / `initializeTransaction` / `verifyTransaction`. Les opérations Paystack-spécifiques (`submitOtp`, `createTransferRecipient`, `initiateTransfer`) ne basculent pas.

---

## Architecture

### Pattern — Decorator `PaymentGatewayResolver`

`PaymentGatewayResolver implements PaymentGatewayInterface` wraps primary (Paystack) + fallback (CinetPay).

```
initiateCharge(payload)
  → primary.initiateCharge(payload)     ← OK → return
  → PAYMENT_GATEWAY_ERROR caught
    → Log::warning("Primary [paystack] failed... switching to [cinetpay]")
    → fallback.initiateCharge(payload)  ← OK → return
    → PAYMENT_GATEWAY_ERROR re-thrown   ← 502 au client
```

**Méthodes sans fallback** (`NO_FALLBACK_METHODS`) :
- `submitOtp` — spécifique Paystack (OTP flow)
- `createTransferRecipient` — spécifique Paystack
- `initiateTransfer` — spécifique Paystack

Seules les erreurs `PAYMENT_GATEWAY_ERROR` déclenchent le fallback. Les autres exceptions (PAYMENT_DUPLICATE, PAYMENT_BOOKING_NOT_PAYABLE, etc.) sont re-thrown immédiatement.

### Gateway — `CinetPayGateway`

Implémente `PaymentGatewayInterface` :

| Méthode | Implémentation |
|---|---|
| `name()` | `'cinetpay'` |
| `initiateCharge()` | POST `/v2/payment` channels=MOBILE_MONEY |
| `initializeTransaction()` | POST `/v2/payment` channels=ALL → `authorization_url` |
| `verifyTransaction()` | POST `/v2/payment/check` |
| `submitOtp()` | `throw PaymentException::unsupportedMethod('cinetpay:submit_otp')` |
| `createTransferRecipient()` | `throw PaymentException::unsupportedMethod(...)` |
| `initiateTransfer()` | `throw PaymentException::unsupportedMethod(...)` |

CinetPay répond avec `{ code: '201', data: { payment_url } }` (pas de `status: true` comme Paystack).

### Binding IoC — `AppServiceProvider`

```php
$this->app->bind(PaymentGatewayInterface::class, function ($app) {
    return new PaymentGatewayResolver(
        $app->make(PaystackGateway::class),
        $app->make(CinetPayGateway::class),
    );
});
```

`PaymentService`, `PayoutService`, etc. continuent d'injecter `PaymentGatewayInterface` sans changement.

### Configuration

```env
CINETPAY_API_KEY=
CINETPAY_SITE_ID=
CINETPAY_NOTIFY_URL=
```

(Déjà présents dans `config/services.php` et `.env.example`.)

---

## Tests

**`tests/Feature/Gateways/PaymentGatewayResolverTest.php`** — 5 tests :

| Test | Résultat |
|---|---|
| Paystack disponible → transaction via Paystack | ✅ |
| Paystack échoue → fallback CinetPay + Log::warning | ✅ |
| Les deux échouent → 502 PAYMENT_GATEWAY_ERROR | ✅ |
| Card Paystack échoue → CinetPay fallback | ✅ |
| submitOtp Paystack échoue → 502 (pas de fallback CinetPay) | ✅ |

**Total Story 4.6 : 5 tests, 10 assertions | Suite complète : 457 tests, 1453 assertions**

---

## Décisions d'architecture

- **Decorator pattern** sur `PaymentGatewayInterface` — aucun changement dans `PaymentService`, `PayoutService`, ni les controllers
- **`NO_FALLBACK_METHODS`** — liste explicite des méthodes Paystack-spécifiques pour éviter des comportements inattendus avec CinetPay
- **Only `PAYMENT_GATEWAY_ERROR`** déclenche le fallback — les autres exceptions (validation, duplicate) passent directement
- **`Log::warning`** systématique sur chaque basculement — observabilité pour l'équipe ops
- **CinetPay unsupported methods** → `PaymentException::unsupportedMethod()` — pattern cohérent avec le reste de l'app
