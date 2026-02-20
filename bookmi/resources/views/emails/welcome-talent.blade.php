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
        <!-- Logo + Badge Talent -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin-bottom:20px;">
          <tr>
            <td style="font-family:Arial,Helvetica,sans-serif;font-size:28px;font-weight:900;letter-spacing:-0.5px;vertical-align:middle;">
              <span style="color:#FFFFFF;">Book</span><span style="color:#FF6B35;">Mi</span>
            </td>
            <td width="10" style="vertical-align:middle;"></td>
            <td style="vertical-align:middle;background-color:#2d1e0f;border:1.5px solid #FF6B35;padding:3px 10px;font-size:10px;font-weight:700;color:#FF6B35;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.5px;text-transform:uppercase;">&#10022; TALENT</td>
          </tr>
        </table>
        <!-- Emoji -->
        <p style="font-size:44px;margin:0 0 10px 0;line-height:1;">&#127908;</p>
        <!-- Stars -->
        <p style="font-size:18px;margin:0 0 14px 0;letter-spacing:4px;color:#FF6B35;">&#9733;&#9733;&#9733;&#9733;&#9733;</p>
        <!-- Title -->
        <p style="font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:900;color:#FFFFFF;margin:0 0 8px 0;line-height:1.3;">
          La scène t'appartient, <span style="color:#FF6B35;">{{ $user->first_name }}</span>&nbsp;!
        </p>
        <!-- Subtitle -->
        <p style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#8892a4;font-weight:400;margin:0;">
          Rejoins la communauté des meilleurs talents de Côte d'Ivoire &#127464;&#127470;
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

        <!-- Félicitations block with left border -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;">
          <tr>
            <td width="4" bgcolor="#FF6B35" style="border-radius:2px;font-size:0;">&nbsp;</td>
            <td bgcolor="#FFF5F0" style="padding:16px 20px;">
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#1A2744;font-weight:600;line-height:1.7;margin:0;">
                Félicitations <strong style="color:#FF6B35;">{{ $user->first_name }} {{ $user->last_name }}</strong>&nbsp;! &#127881;<br><br>
                Tu viens de rejoindre la <strong>première plateforme de mise en relation entre talents et clients</strong> en Côte d'Ivoire. BookMi va t'aider à <strong>booster ta carrière</strong>, trouver de nouveaux clients et être payé(e) en toute sécurité pour chaque prestation.
              </p>
            </td>
          </tr>
        </table>

        <!-- Stats bar -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#1A2744" style="border-radius:10px;margin-bottom:32px;">
          <tr>
            <td width="25%" align="center" style="padding:16px 8px;border-right:1px solid #2d3f5e;">
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:17px;font-weight:900;color:#FF6B35;margin:0 0 3px 0;">100%</p>
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:10px;color:#8892a4;margin:0;line-height:1.3;">Paiements<br>sécurisés</p>
            </td>
            <td width="25%" align="center" style="padding:16px 8px;border-right:1px solid #2d3f5e;">
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:17px;font-weight:900;color:#FF6B35;margin:0 0 3px 0;">7j/7</p>
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:10px;color:#8892a4;margin:0;line-height:1.3;">Support<br>dédié</p>
            </td>
            <td width="25%" align="center" style="padding:16px 8px;border-right:1px solid #2d3f5e;">
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:17px;font-weight:900;color:#FF6B35;margin:0 0 3px 0;">0 fcfa</p>
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:10px;color:#8892a4;margin:0;line-height:1.3;">Pour<br>démarrer</p>
            </td>
            <td width="25%" align="center" style="padding:16px 8px;">
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:900;color:#FF6B35;margin:0 0 3px 0;">&#127464;&#127470;</p>
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:10px;color:#8892a4;margin:0;line-height:1.3;">Toute<br>la CI</p>
            </td>
          </tr>
        </table>

        <!-- Section title: Steps -->
        <p style="font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:800;color:#1A2744;margin:0 0 20px 0;padding-left:14px;border-left:4px solid #FF6B35;">
          Comment démarrer sur BookMi ?
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
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:800;color:#1A2744;margin:0 0 4px 0;">&#127917; Complète ton profil</p>
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#6B7280;line-height:1.5;margin:0;">Ajoute ta bio, tes photos, vidéos et tes forfaits de service. Un profil complet attire 3x plus de clients.</p>
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
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:800;color:#1A2744;margin:0 0 4px 0;">&#9989; Fais vérifier ton profil</p>
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#6B7280;line-height:1.5;margin:0;">Soumets tes documents pour la vérification d'identité. Le badge vérifié inspire confiance et booste ta visibilité.</p>
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
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:800;color:#1A2744;margin:0 0 4px 0;">&#128203; Reçois des demandes de réservation</p>
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#6B7280;line-height:1.5;margin:0;">Les clients te contactent directement. Tu acceptes ou refuses les demandes selon ton calendrier.</p>
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
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:800;color:#1A2744;margin:0 0 4px 0;">&#128176; Sois payé(e) en toute sécurité</p>
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#6B7280;line-height:1.5;margin:0;">Le paiement du client est sécurisé à l'avance. Après ta prestation, les fonds sont libérés directement sur ton Mobile Money.</p>
            </td>
          </tr>
        </table>

        <!-- Features section title -->
        <p style="font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:800;color:#1A2744;margin:0 0 16px 0;padding-left:14px;border-left:4px solid #FF6B35;">
          Ce que BookMi t'offre
        </p>

        <!-- Features 2x2 grid -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;">
          <tr>
            <td width="50%" valign="top" style="padding:0 6px 10px 0;">
              <table role="presentation" width="100%" cellpadding="14" cellspacing="0" border="0" bgcolor="#F9FAFB" style="border:1px solid #E5E7EB;border-radius:10px;">
                <tr><td>
                  <p style="font-size:22px;margin:0 0 7px 0;">&#128202;</p>
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:800;color:#1A2744;margin:0 0 4px 0;">Tableau de bord analytics</p>
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#6B7280;line-height:1.45;margin:0;">Suis tes performances, tes revenus et tes statistiques de réservation en temps réel.</p>
                </td></tr>
              </table>
            </td>
            <td width="50%" valign="top" style="padding:0 0 10px 6px;">
              <table role="presentation" width="100%" cellpadding="14" cellspacing="0" border="0" bgcolor="#F9FAFB" style="border:1px solid #E5E7EB;border-radius:10px;">
                <tr><td>
                  <p style="font-size:22px;margin:0 0 7px 0;">&#128220;</p>
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:800;color:#1A2744;margin:0 0 4px 0;">Certificat de prestation</p>
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#6B7280;line-height:1.45;margin:0;">Obtiens un certificat officiel BookMi après chaque prestation complétée. Parfait pour ton portfolio.</p>
                </td></tr>
              </table>
            </td>
          </tr>
          <tr>
            <td width="50%" valign="top" style="padding:0 6px 0 0;">
              <table role="presentation" width="100%" cellpadding="14" cellspacing="0" border="0" bgcolor="#F9FAFB" style="border:1px solid #E5E7EB;border-radius:10px;">
                <tr><td>
                  <p style="font-size:22px;margin:0 0 7px 0;">&#128197;</p>
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:800;color:#1A2744;margin:0 0 4px 0;">Calendrier intégré</p>
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#6B7280;line-height:1.45;margin:0;">Gère tes disponibilités facilement. Bloque des dates, évite les doubles réservations.</p>
                </td></tr>
              </table>
            </td>
            <td width="50%" valign="top" style="padding:0 0 0 6px;">
              <table role="presentation" width="100%" cellpadding="14" cellspacing="0" border="0" bgcolor="#F9FAFB" style="border:1px solid #E5E7EB;border-radius:10px;">
                <tr><td>
                  <p style="font-size:22px;margin:0 0 7px 0;">&#127758;</p>
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:800;color:#1A2744;margin:0 0 4px 0;">Visibilité nationale</p>
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#6B7280;line-height:1.45;margin:0;">Sois visible auprès de milliers de clients à Abidjan, Bouaké, San-Pédro et partout en CI.</p>
                </td></tr>
              </table>
            </td>
          </tr>
        </table>

        <!-- Action urgency box -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:32px;border:2px solid #FF6B35;border-radius:12px;">
          <tr>
            <td bgcolor="#FFF5F0" style="padding:24px;border-radius:10px;text-align:center;">
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:18px;font-weight:900;color:#1A2744;margin:0 0 8px 0;">&#128640; Lance-toi maintenant !</p>
              <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#6B7280;line-height:1.6;margin:0 0 20px 0;">
                Ton profil est créé, mais il est encore vide. Complète-le dès aujourd'hui pour commencer à recevoir des demandes de réservation. Plus ton profil est riche, plus tu attires de clients&nbsp;!
              </p>
              <a href="{{ config('app.url') }}/talent/profile" style="display:inline-block;background-color:#FF6B35;color:#FFFFFF;text-decoration:none;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:800;padding:12px 28px;border-radius:50px;">&#9998;&#65039; Compléter mon profil &rarr;</a>
              <br><br>
              <a href="{{ config('app.url') }}/talent/dashboard" style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#FF6B35;font-weight:600;text-decoration:none;">&#127968; Accéder à mon espace talent &rarr;</a>
            </td>
          </tr>
        </table>

        <!-- Divider -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
          <tr><td style="border-top:1px solid #E5E7EB;font-size:0;line-height:0;">&nbsp;</td></tr>
        </table>

        <!-- Signature -->
        <p style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#374151;line-height:1.7;margin:0 0 14px 0;">
          Tu rejoins une communauté de talents exceptionnels. &#127775;<br><br>
          Chez BookMi, nous croyons en ton talent et en l'impact que tu peux avoir sur chaque événement ivoirien.<br>
          On est là pour te voir <strong>briller</strong>.
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
