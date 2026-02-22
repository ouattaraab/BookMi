@component('mail::message')

# Votre réservation est acceptée ✅

Bonjour **{{ $clientName }}**,

Bonne nouvelle ! **{{ $talentName }}** a accepté votre demande de prestation. Finalisez votre réservation en effectuant le paiement sécurisé.

@component('mail::table')
| | |
|:--|--:|
| **Talent** | {{ $talentName }} |
| **Prestation** | {{ $packageName }} |
| **Date de l'événement** | {{ $eventDate }} |
| **Cachet artiste** | {{ $artistFee }} XOF |
| **Commission plateforme** | {{ $commission }} XOF |
| **Total à payer** | **{{ $total }} XOF** |
@endcomponent

@if(!empty($talentComment))
@component('mail::panel')
💬 **Message de {{ $talentName }}**

{{ $talentComment }}
@endcomponent

@endif
@component('mail::panel')
🔒 **Paiement sécurisé par séquestre**

Votre paiement est protégé : les fonds sont placés en séquestre et ne sont versés au talent qu'après confirmation de la prestation.
@endcomponent

@component('mail::button', ['url' => $actionUrl, 'color' => 'blue'])
Procéder au paiement
@endcomponent

Ce lien de paiement est valable **7 jours**. Passé ce délai, la réservation sera annulée automatiquement.

À très bientôt,<br>
**L'équipe BookMi**

@endcomponent
