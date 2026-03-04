@component('mail::message')
# Invitation à gérer un talent sur BookMi

Bonjour,

**{{ $talentName }}** vous invite à devenir son manager sur la plateforme BookMi.

En tant que manager, vous pourrez :
- Gérer ses réservations et son calendrier
- Suivre ses statistiques et revenus
- Communiquer avec ses clients

@component('mail::button', ['url' => $respondUrl, 'color' => 'primary'])
Répondre à l'invitation
@endcomponent

Si vous n'avez pas encore de compte BookMi, vous pourrez en créer un en cliquant sur le lien ci-dessus.

Ce lien est valable et vous permettra d'accepter ou refuser l'invitation.

Cordialement,
L'équipe **BookMi**
@endcomponent
