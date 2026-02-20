@component('mail::message')

# Réservation annulée

Bonjour **{{ $recipientName }}**,

Nous vous informons que la réservation suivante a été annulée par **{{ $cancelledByLabel }}**.

@component('mail::table')
| | |
|:--|--:|
| **Prestation** | {{ $packageName }} |
| **Date prévue** | {{ $eventDate }} |
| **Annulée par** | {{ $cancelledByLabel }} |
@endcomponent

@if($refundInfo)
@component('mail::panel')
💳 **Remboursement**

{{ $refundInfo }}
@endcomponent
@endif

@component('mail::button', ['url' => $actionUrl, 'color' => 'blue'])
Voir les détails
@endcomponent

Si vous avez des questions, n'hésitez pas à contacter notre support ou à trouver un autre talent sur BookMi.

Cordialement,<br>
**L'équipe BookMi**

@endcomponent
