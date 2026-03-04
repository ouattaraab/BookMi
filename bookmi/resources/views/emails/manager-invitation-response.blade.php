@component('mail::message')
# Réponse à votre invitation manager

Bonjour,

Le manager **{{ $managerName }}** a **{{ $statusLabel }}** votre invitation à gérer le profil **{{ $talentName }}** sur BookMi.

@if($accepted)
**{{ $managerName }}** peut maintenant accéder à votre espace manager et gérer vos réservations.
@else
Vous pouvez inviter un autre manager depuis votre profil BookMi.
@endif

@if($comment)
**Commentaire du manager :**
> {{ $comment }}
@endif

Cordialement,
L'équipe **BookMi**
@endcomponent
