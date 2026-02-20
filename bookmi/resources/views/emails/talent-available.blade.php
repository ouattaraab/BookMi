@component('mail::message')
# {{ $talentName }} est maintenant disponible sur BookMi !

Bonne nouvelle ! L'artiste que vous recherchiez — **{{ $searchQuery }}** — vient de rejoindre la plateforme BookMi.

@if($category || $city)
**{{ $talentName }}** est {{ $category ? 'un(e) '.$category : 'un artiste' }}{{ $city ? ' basé(e) à '.$city : '' }}.
@endif

Vous pouvez dès maintenant consulter son profil et effectuer une réservation en toute sécurité.

@component('mail::button', ['url' => $talentUrl, 'color' => 'primary'])
Voir le profil de {{ $talentName }} →
@endcomponent

---

*Paiement sécurisé Mobile Money · Escrow BookMi · Support 7j/7*

Merci de faire confiance à BookMi,<br>
**L'équipe BookMi — Côte d'Ivoire**
@endcomponent
