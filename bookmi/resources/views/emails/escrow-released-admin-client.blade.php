@component('mail::message')

# Vos fonds ont été libérés ✅

Bonjour **{{ $clientName }}**,

Suite à l'intervention de l'équipe BookMi, les fonds bloqués en séquestre pour votre réservation avec **{{ $talentName }}** ont été **libérés manuellement par l'administrateur**.

@component('mail::table')
| | |
|:--|--:|
| **Talent** | {{ $talentName }} |
| **Date de l'événement** | {{ $eventDate }} |
| **Montant libéré** | **{{ $amount }} XOF** |
@endcomponent

@component('mail::panel')
ℹ️ **Que s'est-il passé ?**

Les fonds qui étaient en attente de libération ont été débloqués par notre équipe d'administration. Le talent recevra son paiement conformément aux conditions de la réservation.
@endcomponent

Si vous avez des questions concernant cette décision, n'hésitez pas à contacter notre support.

@component('mail::button', ['url' => $actionUrl, 'color' => 'blue'])
Voir ma réservation
@endcomponent

À bientôt,<br>
**L'équipe BookMi**

@endcomponent
