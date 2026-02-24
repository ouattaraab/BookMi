@component('mail::message')

# Demande de réservation refusée

Bonjour **{{ $clientName }}**,

Votre demande de réservation auprès de **{{ $talentName }}** n'a pas pu être acceptée.

@component('mail::table')
| | |
|:--|--:|
| **Prestation** | {{ $packageName }} |
| **Date souhaitée** | {{ $eventDate }} |
| **Lieu** | {{ $eventLocation }} |
@endcomponent

@if(!empty($rejectReason))
@component('mail::panel')
**Motif communiqué par le talent :**

{{ $rejectReason }}
@endcomponent
@endif

Pas d'inquiétude — de nombreux autres talents sont disponibles sur BookMi pour votre événement.

@component('mail::button', ['url' => $actionUrl, 'color' => 'blue'])
Trouver un autre talent
@endcomponent

Cordialement,<br>
**L'équipe BookMi**

@endcomponent
