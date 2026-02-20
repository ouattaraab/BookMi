@component('mail::message')

# Nouvelle demande de réservation 🎉

Bonjour **{{ $talentName }}**,

Un client vient de faire une demande de prestation sur votre profil. Connectez-vous pour l'accepter ou la refuser.

@component('mail::table')
| | |
|:--|--:|
| **Client** | {{ $clientName }} |
| **Prestation** | {{ $packageName }} |
| **Date de l'événement** | {{ $eventDate }} |
| **Lieu** | {{ $eventLocation }} |
| **Montant** | {{ $amount }} XOF |
@endcomponent

@if($message)
> **Message du client :** {{ $message }}
@endif

@component('mail::button', ['url' => $actionUrl, 'color' => 'blue'])
Voir la demande
@endcomponent

Vous avez **48 heures** pour répondre à cette demande. Passé ce délai, elle sera automatiquement refusée.

Merci de votre réactivité,<br>
**L'équipe BookMi**

@endcomponent
