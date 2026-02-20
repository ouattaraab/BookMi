<!DOCTYPE html>
<html lang="fr" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>Akwaba sur BookMi, {{ $user->first_name }} !</title>
</head>
<body style="margin:0;padding:0;background-color:#0D1117;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#0D1117">
<tr>
<td align="center" style="padding:32px 16px;">

  <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">

    <!-- ===== HEADER ===== -->
    <tr>
      <td bgcolor="#1A2744" style="padding:40px 40px 32px;border-radius:20px 20px 0 0;text-align:center;">
        <!-- Logo + Badge -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin-bottom:20px;">
          <tr>
            <td style="font-family:Arial,Helvetica,sans-serif;font-size:28px;font-weight:900;letter-spacing:-0.5px;vertical-align:middle;">
              <span style="color:#FFFFFF;">Book</span><span style="color:#FF6B35;">Mi</span>
            </td>
            <td width="10" style="vertical-align:middle;"></td>
            <td style="vertical-align:middle;background-color:#2d1e0f;border:1.5px solid #FF6B35;padding:3px 10px;font-size:10px;font-weight:700;color:#FF6B35;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.5px;text-transform:uppercase;">CLIENT</td>
          </tr>
        </table>
        <!-- Emoji -->
        <p style="font-size:44px;margin:0 0 14px 0;line-height:1;">&#127882;</p>
        <!-- Title -->
        <p style="font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:900;color:#FFFFFF;margin:0 0 8px 0;line-height:1.3;">
          Akwaba sur BookMi, <span style="color:#FF6B35;">{{ $user->first_name }}</span>&nbsp;!
        </p>
        <!-- Subtitle -->
        <p style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#8892a4;font-weight:400;margin:0;">
          Bienvenue dans la famille BookMi — Côte d'Ivoire &#127464;&#127470;
        </p>
      </td>
    </tr>

    <!-- Orange accent bar -->
    <tr>
      <td bgcolor="#FF6B35" style="height:3px;font-size:0;line-height:0;"></td>
    </tr>

    <!-- ===== BODY ===== -->
    <tr>
      <td bgcolor="#FFFFFF" style="padding:40px;">

        <!-- Akwaba block with left border -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:32px;">
          <tr>
            <td width="4" bgcolor="#FF6B35" style="border-radius:2px;font-size:0;">&nbsp;</td>
            <td bgcolor="#FFF5F0" style="padding:16px 20px;">
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#1A2744;font-weight:600;line-height:1.7;margin:0;">
                Bonjour <strong style="color:#FF6B35;">{{ $user->first_name }} {{ $user->last_name }}</strong>&nbsp;!<br><br>
                Nous sommes <strong>vraiment contents</strong> de te compter parmi nous. &#128591;<br>
                BookMi est la plateforme qui te connecte aux <strong>meilleurs talents de Côte d'Ivoire</strong> — musiciens, DJs, danseurs, photographes et bien plus encore — pour rendre chacun de tes événements <strong>inoubliable</strong>.
              </p>
            </td>
          </tr>
        </table>

        <!-- Section title -->
        <p style="font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:800;color:#1A2744;margin:0 0 20px 0;padding-left:14px;border-left:4px solid #FF6B35;">
          Comment réserver un talent ?
        </p>

        <!-- Step 1 -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:16px;">
          <tr>
            <td width="50" valign="top">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td width="38" height="38" bgcolor="#FF6B35" align="center" style="border-radius:50%;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:900;color:#FFFFFF;line-height:38px;vertical-align:middle;">1</td>
                </tr>
              </table>
            </td>
            <td valign="top" style="padding-left:4px;">
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:800;color:#1A2744;margin:0 0 4px 0;">&#128269; Explore les talents</p>
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#6B7280;line-height:1.5;margin:0;">Parcours notre catalogue de talents vérifiés. Filtre par catégorie, ville ou budget pour trouver l'artiste idéal pour ton événement.</p>
            </td>
          </tr>
        </table>

        <!-- Step 2 -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:16px;">
          <tr>
            <td width="50" valign="top">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td width="38" height="38" bgcolor="#FF6B35" align="center" style="border-radius:50%;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:900;color:#FFFFFF;line-height:38px;vertical-align:middle;">2</td>
                </tr>
              </table>
            </td>
            <td valign="top" style="padding-left:4px;">
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:800;color:#1A2744;margin:0 0 4px 0;">&#128197; Envoie ta demande de réservation</p>
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#6B7280;line-height:1.5;margin:0;">Indique la date, le lieu et tes besoins. Le talent reçoit ta demande et te confirme sa disponibilité dans les 24h.</p>
            </td>
          </tr>
        </table>

        <!-- Step 3 -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:16px;">
          <tr>
            <td width="50" valign="top">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td width="38" height="38" bgcolor="#FF6B35" align="center" style="border-radius:50%;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:900;color:#FFFFFF;line-height:38px;vertical-align:middle;">3</td>
                </tr>
              </table>
            </td>
            <td valign="top" style="padding-left:4px;">
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:800;color:#1A2744;margin:0 0 4px 0;">&#128179; Paie en toute sécurité</p>
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#6B7280;line-height:1.5;margin:0;">Règle via Mobile Money (MTN, Orange, Wave) ou carte bancaire. Ton argent est sécurisé en séquestre jusqu'à la prestation.</p>
            </td>
          </tr>
        </table>

        <!-- Step 4 -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:36px;">
          <tr>
            <td width="50" valign="top">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td width="38" height="38" bgcolor="#FF6B35" align="center" style="border-radius:50%;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:900;color:#FFFFFF;line-height:38px;vertical-align:middle;">4</td>
                </tr>
              </table>
            </td>
            <td valign="top" style="padding-left:4px;">
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:800;color:#1A2744;margin:0 0 4px 0;">&#127881; Profite du spectacle !</p>
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#6B7280;line-height:1.5;margin:0;">Le talent se produit, tu valides la prestation, et le paiement lui est libéré. Laisse un avis pour aider la communauté.</p>
            </td>
          </tr>
        </table>

        <!-- Features section title -->
        <p style="font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:800;color:#1A2744;margin:0 0 16px 0;padding-left:14px;border-left:4px solid #FF6B35;">
          Pourquoi choisir BookMi ?
        </p>

        <!-- Features 2x2 grid -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:36px;">
          <tr>
            <td width="50%" valign="top" style="padding:0 6px 10px 0;">
              <table role="presentation" width="100%" cellpadding="14" cellspacing="0" border="0" bgcolor="#F9FAFB" style="border:1px solid #E5E7EB;border-radius:10px;">
                <tr><td>
                  <p style="font-size:22px;margin:0 0 7px 0;">&#128737;&#65039;</p>
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:800;color:#1A2744;margin:0 0 4px 0;">Séquestre sécurisé</p>
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#6B7280;line-height:1.45;margin:0;">Ton paiement est protégé. L'argent n'est libéré qu'après confirmation de la prestation.</p>
                </td></tr>
              </table>
            </td>
            <td width="50%" valign="top" style="padding:0 0 10px 6px;">
              <table role="presentation" width="100%" cellpadding="14" cellspacing="0" border="0" bgcolor="#F9FAFB" style="border:1px solid #E5E7EB;border-radius:10px;">
                <tr><td>
                  <p style="font-size:22px;margin:0 0 7px 0;">&#9989;</p>
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:800;color:#1A2744;margin:0 0 4px 0;">Talents vérifiés</p>
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#6B7280;line-height:1.45;margin:0;">Chaque artiste est contrôlé par notre équipe. Profils, avis et certifications — tout est authentique.</p>
                </td></tr>
              </table>
            </td>
          </tr>
          <tr>
            <td width="50%" valign="top" style="padding:0 6px 0 0;">
              <table role="presentation" width="100%" cellpadding="14" cellspacing="0" border="0" bgcolor="#F9FAFB" style="border:1px solid #E5E7EB;border-radius:10px;">
                <tr><td>
                  <p style="font-size:22px;margin:0 0 7px 0;">&#128241;</p>
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:800;color:#1A2744;margin:0 0 4px 0;">Mobile Money</p>
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#6B7280;line-height:1.45;margin:0;">Paie comme tu veux : MTN MoMo, Orange Money, Wave. Rapide, simple, ivoirien.</p>
                </td></tr>
              </table>
            </td>
            <td width="50%" valign="top" style="padding:0 0 0 6px;">
              <table role="presentation" width="100%" cellpadding="14" cellspacing="0" border="0" bgcolor="#F9FAFB" style="border:1px solid #E5E7EB;border-radius:10px;">
                <tr><td>
                  <p style="font-size:22px;margin:0 0 7px 0;">&#128172;</p>
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:800;color:#1A2744;margin:0 0 4px 0;">Support 7j/7</p>
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#6B7280;line-height:1.45;margin:0;">Notre équipe est disponible 7j/7 pour t'accompagner avant, pendant et après ta réservation.</p>
                </td></tr>
              </table>
            </td>
          </tr>
        </table>

        <!-- CTA -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:32px;">
          <tr>
            <td align="center">
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#374151;margin:0 0 18px 0;line-height:1.6;text-align:center;">
                Prêt(e) à rendre ton prochain événement mémorable ?<br>
                Des dizaines de talents t'attendent en ce moment même. &#127925;
              </p>
              <a href="{{ config('app.url') }}/talents" style="display:inline-block;background-color:#FF6B35;color:#FFFFFF;text-decoration:none;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:800;padding:14px 36px;border-radius:50px;">&#127917; Découvrir les talents &rarr;</a>
            </td>
          </tr>
        </table>

        <!-- Divider -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
          <tr><td style="border-top:1px solid #E5E7EB;font-size:0;line-height:0;">&nbsp;</td></tr>
        </table>

        <!-- Signature -->
        <p style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#374151;line-height:1.7;margin:0 0 14px 0;">
          Avec toute notre chaleur ivoirienne, &#129309;<br><br>
          Sache que chez BookMi, chaque événement compte.<br>
          Nous mettons tout en œuvre pour que ta prochaine fête, cérémonie ou soirée soit <strong>parfaite</strong>.
        </p>
        <p style="font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:900;color:#1A2744;margin:0 0 2px 0;">Charles Ouattara</p>
        <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#FF6B35;font-weight:600;margin:0;">CEO &amp; Fondateur — BookMi Côte d'Ivoire</p>

      </td>
    </tr>

    <!-- ===== FOOTER ===== -->
    <tr>
      <td bgcolor="#1A2744" style="padding:24px 40px;border-radius:0 0 20px 20px;text-align:center;">
        <p style="font-family:Arial,Helvetica,sans-serif;font-size:18px;font-weight:900;margin:0 0 6px 0;">
          <span style="color:#FFFFFF;">Book</span><span style="color:#FF6B35;">Mi</span>
        </p>
        <p style="font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#8892a4;margin:0 0 12px 0;">La plateforme de réservation de talents en Côte d'Ivoire</p>
        <p style="font-family:Arial,Helvetica,sans-serif;font-size:12px;margin:0 0 10px 0;">
          <a href="{{ config('app.url') }}" style="color:#8892a4;text-decoration:none;">bookmi.click</a>
          <span style="color:#4a5568;margin:0 6px;">&middot;</span>
          <a href="{{ config('app.url') }}/talents" style="color:#8892a4;text-decoration:none;">Talents</a>
          <span style="color:#4a5568;margin:0 6px;">&middot;</span>
          <a href="mailto:support@bookmi.click" style="color:#8892a4;text-decoration:none;">Support</a>
        </p>
        <p style="font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#8892a4;background-color:#1f2f4a;border:1px solid #2d4060;border-radius:20px;padding:5px 14px;display:inline-block;margin:0 0 12px 0;">&#128274; Paiement sécurisé &middot; Escrow BookMi &middot; Support 7j/7</p>
        <p style="font-family:Arial,Helvetica,sans-serif;font-size:10px;color:#4a5568;margin:0;">
          &copy; {{ date('Y') }} BookMi. Tous droits réservés.<br>
          Abidjan, Côte d'Ivoire &#127464;&#127470;
        </p>
      </td>
    </tr>

  </table>

</td>
</tr>
</table>

</body>
</html>
