<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Votre billet Meet &amp; Greet — BookMi</title>
</head>
<body style="margin:0;padding:0;background:#F3F4F6;font-family:'Segoe UI',Helvetica,Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F3F4F6;padding:32px 16px;">
  <tr>
    <td align="center">

      {{-- Container principal --}}
      <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">

        {{-- ── En-tête BookMi ── --}}
        <tr>
          <td style="background:linear-gradient(135deg,#4C1D95 0%,#6D28D9 50%,#7C3AED 100%);border-radius:16px 16px 0 0;padding:36px 40px 28px;text-align:center;">
            <p style="margin:0 0 16px;font-size:13px;font-weight:800;color:rgba(255,255,255,0.6);text-transform:uppercase;letter-spacing:0.12em;">BookMi · Expériences exclusives</p>
            <div style="display:inline-block;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.25);border-radius:100px;padding:6px 18px;margin-bottom:20px;">
              <span style="font-size:11px;font-weight:800;color:rgba(255,255,255,0.9);text-transform:uppercase;letter-spacing:0.1em;">&#9733; Meet &amp; Greet</span>
            </div>
            <h1 style="margin:0;font-size:26px;font-weight:900;color:white;line-height:1.25;">{{ $eventTitle }}</h1>
            <p style="margin:10px 0 0;font-size:15px;color:rgba(255,255,255,0.75);font-weight:600;">avec <strong style="color:white;">{{ $talentName }}</strong></p>
          </td>
        </tr>

        {{-- ── Bande de découpe (effet billet) ── --}}
        <tr>
          <td style="background:white;position:relative;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td width="24" style="background:#F3F4F6;border-radius:0 12px 12px 0;">&nbsp;</td>
                <td style="border-top:2px dashed #E5E7EB;height:2px;">&nbsp;</td>
                <td width="24" style="background:#F3F4F6;border-radius:12px 0 0 12px;">&nbsp;</td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- ── Corps du billet ── --}}
        <tr>
          <td style="background:white;padding:32px 40px;">

            {{-- Bandeau "À présenter à l'entrée" --}}
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;">
              <tr>
                <td style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:10px;padding:12px 18px;text-align:center;">
                  <p style="margin:0;font-size:12px;font-weight:800;color:#92400E;text-transform:uppercase;letter-spacing:0.08em;">
                    &#128507; À présenter à l'entrée le jour de l'événement
                  </p>
                </td>
              </tr>
            </table>

            {{-- Salutation --}}
            <p style="margin:0 0 24px;font-size:15px;color:#374151;line-height:1.6;">
              Bonjour <strong>{{ $clientName }}</strong>,<br>
              votre inscription au Meet &amp; Greet est bien enregistrée. Conservez ce billet précieusement — vous devrez le présenter à l'accueil.
            </p>

            {{-- Infos événement --}}
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
              <tr>
                {{-- Date & Heure --}}
                <td width="50%" style="padding-right:8px;vertical-align:top;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F7F3FF;border:1px solid #DDD6FE;border-radius:12px;padding:16px 18px;">
                    <tr>
                      <td>
                        <p style="margin:0 0 4px;font-size:10px;font-weight:800;color:#7C3AED;text-transform:uppercase;letter-spacing:0.1em;">Date &amp; Heure</p>
                        <p style="margin:0;font-size:15px;font-weight:800;color:#1E1B4B;line-height:1.4;">{{ $eventDate }}</p>
                        <p style="margin:2px 0 0;font-size:13px;font-weight:700;color:#6D28D9;">{{ $eventTime }}</p>
                      </td>
                    </tr>
                  </table>
                </td>
                {{-- Lieu --}}
                <td width="50%" style="padding-left:8px;vertical-align:top;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F7F3FF;border:1px solid #DDD6FE;border-radius:12px;padding:16px 18px;">
                    <tr>
                      <td>
                        <p style="margin:0 0 4px;font-size:10px;font-weight:800;color:#7C3AED;text-transform:uppercase;letter-spacing:0.1em;">Lieu</p>
                        @if($venueAddress)
                          <p style="margin:0;font-size:14px;font-weight:700;color:#1E1B4B;line-height:1.4;">{{ $venueAddress }}</p>
                        @else
                          <p style="margin:0;font-size:13px;font-weight:600;color:#9CA3AF;font-style:italic;">Révélé prochainement</p>
                        @endif
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            {{-- Places & Montant --}}
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
              <tr>
                {{-- Nombre de places --}}
                <td width="50%" style="padding-right:8px;vertical-align:top;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:12px;padding:16px 18px;">
                    <tr>
                      <td>
                        <p style="margin:0 0 4px;font-size:10px;font-weight:800;color:#059669;text-transform:uppercase;letter-spacing:0.1em;">Places réservées</p>
                        <p style="margin:0;font-size:28px;font-weight:900;color:#065F46;line-height:1;">{{ $seatsCount }}</p>
                        <p style="margin:2px 0 0;font-size:12px;color:#059669;font-weight:700;">{{ $pricePerSeat }} FCFA / pers.</p>
                      </td>
                    </tr>
                  </table>
                </td>
                {{-- Montant total --}}
                <td width="50%" style="padding-left:8px;vertical-align:top;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#EDE9FE;border:1px solid #C4B5FD;border-radius:12px;padding:16px 18px;">
                    <tr>
                      <td>
                        <p style="margin:0 0 4px;font-size:10px;font-weight:800;color:#6D28D9;text-transform:uppercase;letter-spacing:0.1em;">Montant total</p>
                        <p style="margin:0;font-size:24px;font-weight:900;color:#4C1D95;line-height:1;">{{ $totalAmount }}</p>
                        <p style="margin:2px 0 0;font-size:12px;color:#6D28D9;font-weight:700;">FCFA</p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            {{-- Référence de réservation --}}
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;">
              <tr>
                <td style="background:#1E1B4B;border-radius:12px;padding:18px 24px;text-align:center;">
                  <p style="margin:0 0 6px;font-size:10px;font-weight:800;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:0.15em;">Référence de réservation</p>
                  <p style="margin:0;font-size:22px;font-weight:900;color:white;letter-spacing:0.18em;font-family:'Courier New',monospace;">{{ $reference }}</p>
                  <p style="margin:8px 0 0;font-size:11px;color:rgba(255,255,255,0.4);font-weight:600;">En attente de confirmation BookMi</p>
                </td>
              </tr>
            </table>

            {{-- Bouton CTA --}}
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
              <tr>
                <td align="center">
                  <a href="{{ $detailUrl }}"
                     style="display:inline-block;background:linear-gradient(135deg,#6D28D9,#7C3AED);color:white;font-size:14px;font-weight:800;text-decoration:none;padding:14px 36px;border-radius:100px;letter-spacing:0.02em;">
                    Voir mon inscription en ligne
                  </a>
                </td>
              </tr>
            </table>

            {{-- Note info --}}
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="background:#F9FAFB;border-radius:10px;padding:16px 18px;">
                  <p style="margin:0;font-size:12px;color:#6B7280;line-height:1.7;">
                    <strong style="color:#374151;">Comment ca se passe ?</strong><br>
                    L'équipe BookMi vous contactera pour confirmer votre inscription et vous communiquer les détails pratiques.
                    En cas de question, écrivez-nous à <a href="mailto:contact@bookmi.ci" style="color:#6D28D9;text-decoration:none;">contact@bookmi.ci</a>.
                  </p>
                </td>
              </tr>
            </table>

          </td>
        </tr>

        {{-- ── Bande de découpe bas (effet billet) ── --}}
        <tr>
          <td style="background:white;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td width="24" style="background:#F3F4F6;border-radius:0 12px 12px 0;">&nbsp;</td>
                <td style="border-top:2px dashed #E5E7EB;height:2px;">&nbsp;</td>
                <td width="24" style="background:#F3F4F6;border-radius:12px 0 0 12px;">&nbsp;</td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- ── Pied de page ── --}}
        <tr>
          <td style="background:#F7F3FF;border-radius:0 0 16px 16px;padding:24px 40px;text-align:center;">
            <p style="margin:0 0 6px;font-size:16px;font-weight:900;color:#4C1D95;">
              Book<span style="color:#7C3AED;">Mi</span>
            </p>
            <p style="margin:0;font-size:11px;color:#9CA3AF;line-height:1.6;">
              La plateforme N°1 pour réserver les talents ivoiriens.<br>
              Paiement sécurisé · Support 7j/7 · Abidjan, Côte d'Ivoire
            </p>
          </td>
        </tr>

      </table>
      {{-- /Container --}}

    </td>
  </tr>
</table>

</body>
</html>
