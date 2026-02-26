# BookMi v2 — Journal des modifications

Ce fichier recense toutes les actions réalisées sur le projet : fonctionnalités ajoutées, modifiées, supprimées et corrections de bugs. Les entrées sont classées par date décroissante.

---

## 2026-02-26

### Fix — Admin : champs vides dans les pages ViewRecord

**Problème racine :** Les pages `ViewRecord` de Filament remplissent le formulaire via `$record->toArray()` qui expose les relations eager-loadées en snake_case (ex. `talent_profile`), alors que les champs formulaire utilisent du camelCase dot-notation (ex. `talentProfile.stage_name`). La correspondance échouait silencieusement → tous les champs de relation apparaissaient vides.

**Solution :** Ajout de `mutateFormDataBeforeFill()` dans chaque page `ViewRecord` concernée pour injecter explicitement la relation sous la clé camelCase attendue par le formulaire.

**Pages corrigées (4 au total) :**

| Page | Relations injectées | Champs concernés |
|------|--------------------|--------------------|
| `TalentProfileResource/Pages/ViewTalentProfile` | `talentProfile` | stage_name, city, talent_level, is_verified, payout_method, payout_details, available_balance, payout_method_verified_at |
| `PayoutMethodResource/Pages/ViewPayoutMethod` | `user` | user.first_name, user.last_name, user.email |
| `BookingRequestResource/Pages/ViewBookingRequest` | `client`, `talentProfile` | client.email, talentProfile.stage_name |
| `WithdrawalRequestResource/Pages/ViewWithdrawalRequest` | `talentProfile` + `talentProfile.user` (2 niveaux) | talentProfile.stage_name, talentProfile.user.email |

**Pages non concernées :**
- `ViewActivityLog`, `ViewAdminAlert`, `ViewAdminWarning`, `ViewIdentityVerification`, `ViewReview` — utilisent un **infolist** (`hasInfolist(): true`) : Filament résout le dot-notation directement depuis le modèle Eloquent, pas de problème.
- `ViewClient`, `ViewUser` — aucun champ dot-notation, uniquement des attributs directs.

---

### Fix — Admin : affichage robuste des coordonnées Wave dans PayoutMethodResource

**Problème :** La colonne `payout_details` du tableau `/admin/payout-methods` affichait `—` si la clé JSON n'était ni `phone` ni `account_number`.

**Solution :** Ajout d'un fallback `implode(array_values())` dans `formatStateUsing` pour afficher toutes les valeurs du JSON quelle que soit la clé.

**Fichier :** `app/Filament/Resources/PayoutMethodResource.php`

---

### Fix — Style : Pint sur TalentProfileController et PayoutMethodAddedNotification

**Fichiers :** `app/Http/Controllers/Api/V1/TalentProfileController.php`, `app/Notifications/PayoutMethodAddedNotification.php`

**Règles corrigées :** `braces_position`, `new_with_parentheses`

---

## 2026-02-25

### Feat — Admin : page de validation des comptes de paiement (PayoutMethodResource)

**Fonctionnalité :** Nouvelle page admin `/admin/payout-methods` permettant de valider ou refuser les comptes de paiement soumis par les talents.

**Détails :**
- Navigation : groupe "Finance", badge avec le nombre de comptes en attente
- Requête filtrée : `payout_method IS NOT NULL` ET `payout_method_verified_at IS NULL`
- Action **Valider** : met à jour `payout_method_verified_at` et `payout_method_verified_by`, envoie `PayoutMethodVerifiedNotification` par e-mail au talent
- Action **Refuser** : efface les données de paiement, envoie `PayoutMethodRejectedNotification` par e-mail au talent avec le motif

**Fichiers créés :**
- `app/Filament/Resources/PayoutMethodResource.php`
- `app/Filament/Resources/PayoutMethodResource/Pages/ListPayoutMethods.php`
- `app/Filament/Resources/PayoutMethodResource/Pages/ViewPayoutMethod.php`
- `app/Notifications/PayoutMethodVerifiedNotification.php`
- `app/Notifications/PayoutMethodRejectedNotification.php`

---

### Feat — Service centralisé de notifications admin (AdminNotificationService)

**Fonctionnalité :** Centralisation de toutes les notifications envoyées aux administrateurs (e-mail + push in-app FCM).

**Méthodes :**
- `payoutMethodAdded(TalentProfile)` — notifie l'admin qu'un talent a soumis/modifié son compte de paiement
- `withdrawalRequested(WithdrawalRequest)` — notifie l'admin d'une nouvelle demande de reversement

**Impact :** 6 call-sites refactorisés (API controllers, web controllers, Filament pages) pour utiliser ce service.

**Fichier créé :** `app/Services/AdminNotificationService.php`

**Fichiers modifiés :**
- `app/Filament/Talent/Pages/PayoutMethodPage.php`
- `app/Filament/Talent/Pages/WithdrawalRequestTalentPage.php`
- `app/Http/Controllers/Web/Talent/PaiementController.php`
- `app/Http/Controllers/Api/V1/TalentProfileController.php`
- `app/Http/Controllers/Api/V1/WithdrawalRequestController.php`
- `app/Notifications/PayoutMethodAddedNotification.php` (URL mise à jour vers `/admin/payout-methods`)

---

### Feat — Flutter : réorganisation du menu profil talent

**Fonctionnalité :** Réorganisation de l'ordre des items du menu profil pour les talents et masquage de "Mes talents favoris" en mode talent.

**Nouvel ordre (talent) :**
1. Informations personnelles
2. Description & Réseaux sociaux
3. Gestion portfolio
4. Gestion packages
5. Vérification d'identité
6. Statistiques talent
7. Mes revenus
8. Moyens de paiement
9. Aide et support

**"Mes talents favoris"** : visible uniquement pour les clients (masqué pour les talents).

**Fichier modifié :** `bookmi_app/lib/features/profile/presentation/pages/profile_page.dart`

---

## Règles d'architecture établies

### Filament ViewRecord — champs dot-notation dans les formulaires

Lorsqu'une page `ViewRecord` utilise un **formulaire** (pas un infolist) avec des champs dot-notation pointant vers des relations (ex. `TextInput::make('user.email')`), il faut systématiquement ajouter `mutateFormDataBeforeFill()` pour injecter les données de la relation dans le tableau de données sous la clé camelCase correspondante.

```php
protected function mutateFormDataBeforeFill(array $data): array
{
    /** @var MyModel $record */
    $record = $this->record;

    if ($record->relation) {
        $data['relation'] = $record->relation->toArray();
    }

    return $data;
}
```

Pour les relations imbriquées (ex. `talentProfile.user.email`) :
```php
$talentProfileData = $talentProfile->toArray();
$talentProfileData['user'] = $talentProfile->user->toArray();
$data['talentProfile'] = $talentProfileData;
```

Les pages avec `hasInfolist(): true` ne sont **pas** concernées — Filament résout le dot-notation directement depuis le modèle Eloquent dans les infolists.

---

### Notifications admin

Toujours passer par `AdminNotificationService` pour notifier les admins — ne jamais appeler directement `$admin->notify()` ou `SendPushNotification::dispatch()` en dehors de ce service.
