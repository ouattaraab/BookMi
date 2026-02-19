# Story 4.5 — Versement automatique au talent (backend)

## Statut : ✅ Done

**Epic :** 4 — Paiement & Séquestre
**Sprint :** Sprint 4

---

## Objectif

Déclencher automatiquement le versement du cachet au talent après libération du séquestre. Dès que `EscrowReleased` est dispatché (Story 4.4), un job `ProcessPayout` est mis en file avec un délai de `payout_delay_hours` (config: 24h). Le job crée le bénéficiaire Paystack si nécessaire et initie un virement bancaire / mobile money. Les webhooks `transfer.success` / `transfer.failed` mettent à jour le statut du Payout.

---

## Architecture

### Gateway — `PaymentGatewayInterface` + `PaystackGateway`

Nouvelle méthode ajoutée à l'interface et au gateway :
```php
public function createTransferRecipient(array $payload): array;
// POST /transferrecipient { type, name, account_number, bank_code, currency }
// → { recipient_code, ... }
```

### Model — `TalentProfile` (mise à jour)

- `payout_method` + `payout_details` ajoutés à `$fillable`
- `payout_details` casté en `array`

### Request — `UpdatePayoutMethodRequest`

```
PATCH /api/v1/talent_profiles/me/payout_method
- payout_method: required | in: PaymentMethod values
- payout_details: required | array
- payout_details.phone: required_if mobile money | regex E164
```

### Service — `PayoutService::processPayout(EscrowHold $hold): Payout`

```
1. Charger booking + talentProfile (loadMissing — M1 fix)
2. Valider payout_method configuré
3. Si pas de recipient_code dans payout_details → createTransferRecipient()
   → stocker recipient_code dans payout_details (idempotency retry)
4. DB::transaction: lockForUpdate → guard duplicate Payout (H1 fix)
   → Payout::create(status=Pending)
5. HTTP OUTSIDE tx: initiateTransfer()
   → payout.update(status=Processing, gateway_reference, processed_at)
   → catch: payout.update(status=Failed) + re-throw
```

### Job — `ProcessPayout`

- Queue: `payouts`, tries: 5, backoff: [10, 30, 90, 270, 810]s
- Reçoit `$escrowHoldId` (pas le modèle — survit à la sérialisation)
- Injecte `PayoutService` via constructor injection dans `handle()`

### Listener — `HandleEscrowReleased`

```php
ProcessPayout::dispatch($event->escrowHold->id)
    ->delay(now()->addHours(config('bookmi.escrow.payout_delay_hours', 24)));
```

Enregistré dans `AppServiceProvider::boot()` :
```php
Event::listen(EscrowReleased::class, HandleEscrowReleased::class);
```

### Webhook — `HandlePaymentWebhook` (mise à jour)

Deux nouveaux cas ajoutés :
- `transfer.success` → `Payout::where('gateway_reference', $transferCode)->update(status=Succeeded)`
- `transfer.failed` → `Payout::where('gateway_reference', $transferCode)->update(status=Failed)`

Idempotence : vérifie le statut avant de mettre à jour.

### Controller — `TalentProfileController::updatePayoutMethod()`

```
PATCH /api/v1/talent_profiles/me/payout_method
→ profile->update(payout_method, payout_details)
← 200 { data: { payout_method, payout_details } }
```

---

## Routes

```
PATCH /api/v1/talent_profiles/me/payout_method  [auth:sanctum]  TalentProfileController@updatePayoutMethod
```

---

## Flow complet

```
1. Talent → PATCH /talent_profiles/me/payout_method { payout_method: "orange_money", payout_details: { phone: "+22601234567" } }
   ← 200 { payout_method, payout_details }

2. [EscrowReleased dispatché — Story 4.4]
   → HandleEscrowReleased listener → ProcessPayout::dispatch(hold_id).delay(+24h)

3. [24h later — ProcessPayout job executes]
   → PayoutService::processPayout(hold)
     → POST /transferrecipient { type: 'mobile_money', phone, bank_code: 'ORAGEMONEY' }
     ← { recipient_code: 'RCP_xxx' } (stocké sur TalentProfile)
     → POST /transfer { source: 'balance', amount: cachet_amount, recipient: 'RCP_xxx' }
     ← { transfer_code: 'TRF_xxx', status: 'pending' }
     → Payout: status=Processing, gateway_reference='TRF_xxx'

4. Paystack → POST /webhooks/paystack { event: 'transfer.success', data: { transfer_code: 'TRF_xxx' } }
   → HandlePaymentWebhook → Payout: status=Succeeded
```

---

## Tests

**`tests/Feature/Api/V1/PayoutMethodControllerTest.php`** — 6 tests :

| Test | Résultat |
|---|---|
| Talent configure orange_money → 200 | ✅ |
| Talent configure bank_transfer → 200 | ✅ |
| Mobile money sans phone → 422 | ✅ |
| Méthode invalide → 422 | ✅ |
| Pas de profil talent → 404 | ✅ |
| Non authentifié → 401 | ✅ |

**`tests/Feature/Jobs/ProcessPayoutJobTest.php`** — 7 tests :

| Test | Résultat |
|---|---|
| EscrowReleased dispatch ProcessPayout avec delay | ✅ |
| Job crée Payout + initie transfer | ✅ |
| recipient_code caché sur TalentProfile | ✅ |
| recipient_code réutilisé (1 seul appel HTTP) | ✅ |
| Transfer gateway error → Payout Failed | ✅ |
| EscrowHold introuvable → skip silencieux | ✅ |
| payout_method non configuré → exception | ✅ |

**Total Story 4.5 : 13 tests, 21 assertions | Suite complète : 452 tests, 1443 assertions**

---

## Décisions d'architecture

- **`recipient_code` mis en cache** sur `TalentProfile.payout_details` — évite de recréer un bénéficiaire Paystack à chaque retry (Paystack facture les recipient créations)
- **Job reçoit `escrowHoldId` (int)** — pas le modèle Eloquent — évite les problèmes de sérialisation si le modèle change entre la mise en file et l'exécution
- **Délai 24h configurable** via `config('bookmi.escrow.payout_delay_hours')` — permet au back-office de revoir avant décaissement
- **Listener enregistré dans AppServiceProvider** — pas d'EventServiceProvider séparé (Laravel 12 convention)

## Issues corrigées lors du code review

- [H1] Duplicate Payout sur retry → `lockForUpdate` + guard `whereNotIn(Failed)` avant create
- [M1] `bookingRequest()->with(...)` ne charge pas la relation → `loadMissing('bookingRequest')`
- [L1] `assertDatabaseHas` après `expectException` inaccessible → try/catch explicite dans le test
