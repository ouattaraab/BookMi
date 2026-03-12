@component('mail::message')

# Vos revenus sont disponibles 💰

Bonjour **{{ $talentName }}**,

Bonne nouvelle ! L'équipe BookMi a **libéré manuellement** les fonds bloqués en séquestre pour votre prestation.

@component('mail::table')
| | |
|:--|--:|
| **Client** | {{ $clientName }} |
| **Date de l'événement** | {{ $eventDate }} |
| **Cachet crédité** | **{{ $amount }} XOF** |
@endcomponent

@component('mail::panel')
💼 **Montant crédité sur votre solde**

Les **{{ $amount }} XOF** ont été ajoutés à votre solde disponible sur BookMi. Vous pouvez effectuer un virement vers votre compte bancaire depuis votre espace talent.
@endcomponent

@component('mail::button', ['url' => $actionUrl, 'color' => 'blue'])
Voir ma réservation
@endcomponent

Merci pour votre confiance,<br>
**L'équipe BookMi**

@endcomponent
