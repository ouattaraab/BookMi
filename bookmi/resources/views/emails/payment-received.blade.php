@component('mail::message')

# Paiement reçu et sécurisé 💰

Bonjour **{{ $talentName }}**,

Le paiement pour votre prestation a été reçu et placé en **séquestre sécurisé**. Les fonds vous seront versés après confirmation de la prestation par le client.

@component('mail::table')
| | |
|:--|--:|
| **Client** | {{ $clientName }} |
| **Prestation** | {{ $packageName }} |
| **Date de l'événement** | {{ $eventDate }} |
| **Montant en séquestre** | **{{ $escrowAmount }} XOF** |
| **Référence** | `{{ $reference }}` |
@endcomponent

@component('mail::panel')
📅 **Versement automatique**

Si le client ne confirme pas la prestation dans les 48 heures suivant la date de l'événement, le montant vous sera automatiquement versé.
@endcomponent

@component('mail::button', ['url' => $actionUrl, 'color' => 'green'])
Voir la réservation
@endcomponent

Merci pour votre professionnalisme,<br>
**L'équipe BookMi**

@endcomponent
