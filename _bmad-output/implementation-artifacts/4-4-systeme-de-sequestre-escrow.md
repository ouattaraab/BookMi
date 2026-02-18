# Story 4.4 — Système de séquestre escrow (backend)

## Statut : ✅ Done

**Epic :** 4 — Paiement & Séquestre
**Sprint :** Sprint 4

---

## Objectif

Implémenter la logique métier de séquestre (escrow) : libération manuelle par le client (`POST /booking_requests/{booking}/confirm_delivery`) et libération automatique après 48h (`artisan escrow:release-expired`, schedulé quotidiennement). Dispatche l'événement `EscrowReleased` après commit pour préparer Story 4.5 (versement talent).

---

## Architecture

### Exception — `EscrowException`

Nouveau fichier `app/Exceptions/EscrowException.php` :

```php
EscrowException::escrowNotHeld(string $status)      // 422 ESCROW_NOT_HELD
EscrowException::bookingNotConfirmable(string $status) // 422 ESCROW_BOOKING_NOT_CONFIRMABLE
EscrowException::forbidden()                          // 403 ESCROW_FORBIDDEN
```

### Event — `EscrowReleased`

`app/Events/EscrowReleased.php` — dispatché APRÈS commit de la DB transaction. Contiendra l'`EscrowHold` relâché pour que Story 4.5 puisse déclencher le payout.

### Service — `EscrowService`

**`releaseEscrow(EscrowHold $hold): void`**

Pattern : `DB::transaction (lockForUpdate + update)` → event AFTER commit.

```
lockForUpdate() → guard idempotence (status !== Held → return)
update escrow_hold: status=released, released_at=now()
update booking: status=confirmed (si status=paid)
$wasReleased = true
→ EscrowReleased::dispatch() (après commit)
```

**`confirmDelivery(BookingRequest $booking, User $client): void`**

Validations :
1. `$booking->client_id !== $client->id` → `EscrowException::forbidden()` (403)
2. `$booking->status !== Paid` → `EscrowException::bookingNotConfirmable()` (422)
3. `EscrowHold where booking_request_id + status=held` introuvable → `EscrowException::escrowNotHeld('not_found')` (422)
4. → `$this->releaseEscrow($hold)`

### Command — `ReleaseExpiredEscrows`

`app/Console/Commands/ReleaseExpiredEscrows.php` — `artisan escrow:release-expired`

- Traite par lots de 100 via `chunkById(100, ...)` (M3 fix — évite saturation RAM)
- Pour chaque EscrowHold `status=held` et `release_scheduled_at <= now()` → `releaseEscrow()`
- Retourne `self::FAILURE` si au moins un lot a échoué

**Schedule :** `routes/console.php`

```php
Schedule::command(ReleaseExpiredEscrows::class)->dailyAt('00:00');
```

### Controller — `EscrowController`

**`POST /v1/booking_requests/{booking}/confirm_delivery`** (auth:sanctum) :
- Délègue à `EscrowService::confirmDelivery()`
- Retourne `{ message, booking_status: "confirmed" }` — 200

### Model — `BookingRequest`

Ajout de la relation `escrowHold(): HasOne<EscrowHold>`.

---

## Routes

```
POST /api/v1/booking_requests/{booking}/confirm_delivery  [auth:sanctum]  EscrowController@confirmDelivery
```

---

## Flow complet

```
Client → POST /confirm_delivery
  → EscrowService::confirmDelivery()
    → validate owner, booking=paid, hold=held
    → DB::transaction: lockForUpdate → update hold=released, booking=confirmed
  → EscrowReleased::dispatch(hold)  ← Story 4.5 listener pour payout
← 200 { booking_status: "confirmed" }

[Automatique — 00:00 chaque nuit]
ReleaseExpiredEscrows command
  → chunkById(100) EscrowHold where status=held AND release_scheduled_at <= now()
  → EscrowService::releaseEscrow(hold) pour chacun
  → EscrowReleased::dispatch() pour chacun
```

---

## Tests

**`tests/Feature/Api/V1/EscrowControllerTest.php`** — 7 tests :

| Test | Résultat |
|---|---|
| Client confirme livraison → 200 + escrow released + booking confirmed + EscrowReleased dispatché | ✅ |
| Non-propriétaire → 403 | ✅ |
| Booking pas en statut paid → 422 ESCROW_BOOKING_NOT_CONFIRMABLE | ✅ |
| Aucun escrow held → 422 ESCROW_NOT_HELD | ✅ |
| Idempotence (double appel → 2e échoue gracieusement) | ✅ |
| Non authentifié → 401 | ✅ |
| Booking inconnu → 404 | ✅ |

**`tests/Feature/Commands/ReleaseExpiredEscrowsCommandTest.php`** — 5 tests :

| Test | Résultat |
|---|---|
| Commande libère les holds expirés + released_at asserté | ✅ |
| Holds futurs ignorés | ✅ |
| Holds déjà released ignorés | ✅ |
| Plusieurs holds expirés libérés | ✅ |
| Message "Nothing to release" si aucun hold | ✅ |

**Total Story 4.4 : 12 tests, 33 assertions | Suite complète : 439 tests, 1422 assertions**

---

## Décisions d'architecture

- **`lockForUpdate()` sur EscrowHold** — guard TOCTOU pour double-release concurrente (commande + confirm_delivery simultanés)
- **`$wasReleased` par référence** — event dispatché seulement si la closure s'est exécutée jusqu'au bout (= transaction committée)
- **`EscrowException::forbidden()` statusCode 403** — cohérent avec le pattern `BookmiException` (pas de `abort()`)
- **`chunkById(100)`** — évite la saturation RAM pour les grands volumes de holds expirés (M3 fix)
- **`EscrowReleased` event** — découplage : Story 4.5 n'a qu'à enregistrer un listener, sans modifier EscrowService

## Issues corrigées lors du code review

- [M2] `abort(403)` incohérent avec le pattern `BookmiException` → `EscrowException::forbidden()` (403)
- [M3] `->get()` charge tous les holds en mémoire → `chunkById(100, ...)` pour les grands volumes
- [L2] `released_at` non asserté dans les tests de commande → ajouté
