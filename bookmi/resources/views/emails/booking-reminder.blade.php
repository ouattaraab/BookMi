@component('mail::message')
# Rappel {{ $label }} — Prestation à venir

Bonjour,

Votre prestation BookMi approche. Voici un rappel des informations clés.

@component('mail::table')
| Détail | Information |
|:-------|:------------|
| Avec | {{ $otherParty }} |
| Prestation | {{ $packageName }} |
| Date | {{ $eventDate }} |
| Lieu | {{ $location }} |
@endcomponent

@component('mail::button', ['url' => $actionUrl, 'color' => 'primary'])
Voir la réservation
@endcomponent

Bonne prestation !

Merci,<br>
**L'équipe BookMi**
@endcomponent
