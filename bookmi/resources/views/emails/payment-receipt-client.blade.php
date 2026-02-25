@component('mail::message')

# Votre reçu de paiement 🧾

Bonjour **{{ $clientName }}**,

Merci pour votre paiement ! Votre réservation est désormais **confirmée**. Veuillez trouver ci-joint votre reçu de paiement.

@component('mail::table')
| | |
|:--|--:|
| **Prestataire** | {{ $talentName }} |
| **Prestation** | {{ $packageName }} |
| **Date de l'événement** | {{ $eventDate }} |
| **Montant payé** | **{{ $totalAmount }} XOF** |
| **Référence** | `{{ $reference }}` |
@endcomponent

@component('mail::panel')
📋 **Votre reçu est joint à cet e-mail**

Vous pouvez également télécharger votre reçu directement depuis l'application BookMi, dans le détail de votre réservation.
@endcomponent

@component('mail::button', ['url' => $actionUrl, 'color' => 'blue'])
Voir la réservation
@endcomponent

À bientôt,<br>
**L'équipe BookMi**

@endcomponent
