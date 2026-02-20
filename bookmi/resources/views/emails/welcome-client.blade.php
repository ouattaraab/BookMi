<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>Akwaba sur BookMi !</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<!--[if mso]>
<noscript>
<xml>
<o:OfficeDocumentSettings>
<o:PixelsPerInch>96</o:PixelsPerInch>
</o:OfficeDocumentSettings>
</xml>
</noscript>
<![endif]-->
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { background-color: #0D1117; font-family: 'Nunito', Arial, sans-serif; -webkit-text-size-adjust: 100%; }
  .email-wrapper { background-color: #0D1117; padding: 32px 16px; }
  .email-container { max-width: 620px; margin: 0 auto; }

  /* Header */
  .header { background: linear-gradient(135deg, #1A2744 0%, #0D1117 100%); border-radius: 20px 20px 0 0; padding: 40px 40px 32px; text-align: center; border-bottom: 3px solid #FF6B35; position: relative; overflow: hidden; }
  .header-glow { position: absolute; top: -60px; left: 50%; transform: translateX(-50%); width: 300px; height: 200px; background: radial-gradient(ellipse, rgba(255,107,53,0.18) 0%, transparent 70%); pointer-events: none; }
  .logo { font-size: 32px; font-weight: 900; letter-spacing: -1px; }
  .logo-book { color: #FFFFFF; }
  .logo-mi { color: #FF6B35; }
  .logo-badge { display: inline-block; background: rgba(255,107,53,0.15); border: 1px solid rgba(255,107,53,0.3); border-radius: 8px; padding: 4px 12px; margin-left: 8px; font-size: 11px; font-weight: 700; color: #FF6B35; letter-spacing: 1px; text-transform: uppercase; vertical-align: middle; }
  .header-emoji { font-size: 48px; margin: 20px 0 12px; display: block; }
  .header-title { font-size: 26px; font-weight: 900; color: #FFFFFF; line-height: 1.2; margin-bottom: 8px; }
  .header-title span { color: #FF6B35; }
  .header-subtitle { font-size: 15px; color: rgba(255,255,255,0.6); font-weight: 400; }

  /* Body */
  .body { background: #FFFFFF; padding: 40px; }

  /* Akwaba section */
  .akwaba-section { background: linear-gradient(135deg, #FFF5F0 0%, #FFF9F7 100%); border-left: 4px solid #FF6B35; border-radius: 0 12px 12px 0; padding: 20px 24px; margin-bottom: 32px; }
  .akwaba-text { font-size: 16px; color: #1A2744; font-weight: 600; line-height: 1.6; }
  .akwaba-text strong { color: #FF6B35; }

  /* Section title */
  .section-title { font-size: 18px; font-weight: 800; color: #1A2744; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
  .section-title::before { content: ''; display: inline-block; width: 4px; height: 20px; background: #FF6B35; border-radius: 2px; margin-right: 4px; }

  /* Steps */
  .steps { margin-bottom: 36px; }
  .step { display: flex; align-items: flex-start; margin-bottom: 20px; }
  .step-number { flex-shrink: 0; width: 40px; height: 40px; background: linear-gradient(135deg, #FF6B35, #ff8c5a); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 900; color: #FFFFFF; margin-right: 16px; box-shadow: 0 4px 12px rgba(255,107,53,0.35); }
  .step-content {}
  .step-title { font-size: 15px; font-weight: 800; color: #1A2744; margin-bottom: 4px; }
  .step-desc { font-size: 14px; color: #6B7280; line-height: 1.5; }

  /* Features grid */
  .features { margin-bottom: 36px; }
  .features-grid { display: table; width: 100%; border-collapse: separate; border-spacing: 12px; margin: -12px; }
  .features-row { display: table-row; }
  .feature-cell { display: table-cell; width: 50%; vertical-align: top; padding: 12px; }
  .feature-card { background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 12px; padding: 16px; }
  .feature-icon { font-size: 24px; margin-bottom: 8px; display: block; }
  .feature-title { font-size: 13px; font-weight: 800; color: #1A2744; margin-bottom: 4px; }
  .feature-desc { font-size: 12px; color: #6B7280; line-height: 1.4; }

  /* CTA */
  .cta-section { text-align: center; margin-bottom: 36px; }
  .cta-text { font-size: 15px; color: #374151; margin-bottom: 20px; line-height: 1.6; }
  .cta-button { display: inline-block; background: linear-gradient(135deg, #FF6B35 0%, #ff4500 100%); color: #FFFFFF !important; text-decoration: none; font-size: 16px; font-weight: 800; padding: 16px 40px; border-radius: 50px; box-shadow: 0 6px 20px rgba(255,107,53,0.4); letter-spacing: 0.3px; }

  /* Divider */
  .divider { border: none; border-top: 1px solid #E5E7EB; margin: 32px 0; }

  /* Signature */
  .signature { margin-bottom: 24px; }
  .signature-text { font-size: 15px; color: #374151; line-height: 1.7; }
  .signature-name { font-size: 16px; font-weight: 800; color: #1A2744; margin-top: 12px; }
  .signature-title { font-size: 13px; color: #FF6B35; font-weight: 600; }

  /* Footer */
  .footer { background: #1A2744; border-radius: 0 0 20px 20px; padding: 24px 40px; text-align: center; }
  .footer-logo { font-size: 20px; font-weight: 900; margin-bottom: 8px; }
  .footer-logo span:first-child { color: #FFFFFF; }
  .footer-logo span:last-child { color: #FF6B35; }
  .footer-tagline { font-size: 12px; color: rgba(255,255,255,0.5); margin-bottom: 16px; }
  .footer-links { font-size: 12px; color: rgba(255,255,255,0.4); }
  .footer-links a { color: rgba(255,255,255,0.5); text-decoration: none; }
  .footer-dot { margin: 0 8px; }
  .footer-badge { display: inline-flex; align-items: center; gap: 6px; background: rgba(255,107,53,0.1); border: 1px solid rgba(255,107,53,0.2); border-radius: 20px; padding: 4px 12px; font-size: 11px; color: rgba(255,255,255,0.6); margin-top: 12px; }

  @media only screen and (max-width: 600px) {
    .header, .body, .footer { padding: 24px 20px !important; }
    .header-title { font-size: 22px !important; }
    .feature-cell { display: block !important; width: 100% !important; }
  }
</style>
</head>
<body>
<div class="email-wrapper">
  <div class="email-container">

    <!-- HEADER -->
    <div class="header">
      <div class="header-glow"></div>
      <div class="logo">
        <span class="logo-book">Book</span><span class="logo-mi">Mi</span>
        <span class="logo-badge">Client</span>
      </div>
      <span class="header-emoji">🎊</span>
      <div class="header-title">
        Akwaba sur BookMi, <span>{{ $user->first_name }}</span> !
      </div>
      <div class="header-subtitle">Bienvenue dans la famille BookMi — Côte d'Ivoire 🇨🇮</div>
    </div>

    <!-- BODY -->
    <div class="body">

      <!-- Akwaba message -->
      <div class="akwaba-section">
        <div class="akwaba-text">
          Bonjour <strong>{{ $user->first_name }} {{ $user->last_name }}</strong> !<br><br>
          Nous sommes <strong>vraiment contents</strong> de te compter parmi nous. 🙏<br>
          BookMi est la plateforme qui te connecte aux <strong>meilleurs talents de Côte d'Ivoire</strong> — musiciens, DJs, danseurs, photographes et bien plus encore — pour rendre chacun de tes événements <strong>inoubliable</strong>.
        </div>
      </div>

      <!-- Comment ça marche -->
      <div class="steps">
        <div class="section-title">Comment réserver un talent ?</div>

        <div class="step">
          <div class="step-number">1</div>
          <div class="step-content">
            <div class="step-title">🔍 Explore les talents</div>
            <div class="step-desc">Parcours notre catalogue de talents vérifiés. Filtre par catégorie, ville ou budget pour trouver l'artiste idéal pour ton événement.</div>
          </div>
        </div>

        <div class="step">
          <div class="step-number">2</div>
          <div class="step-content">
            <div class="step-title">📅 Envoie ta demande de réservation</div>
            <div class="step-desc">Indique la date, le lieu et tes besoins. Le talent reçoit ta demande et te confirme sa disponibilité dans les 24h.</div>
          </div>
        </div>

        <div class="step">
          <div class="step-number">3</div>
          <div class="step-content">
            <div class="step-title">💳 Paie en toute sécurité</div>
            <div class="step-desc">Règle via Mobile Money (MTN, Orange, Wave) ou carte bancaire. Ton argent est sécurisé en séquestre jusqu'à la prestation.</div>
          </div>
        </div>

        <div class="step">
          <div class="step-number">4</div>
          <div class="step-content">
            <div class="step-title">🎉 Profite du spectacle !</div>
            <div class="step-desc">Le talent se produit, tu valides la prestation, et le paiement lui est libéré. Laisse un avis pour aider la communauté.</div>
          </div>
        </div>
      </div>

      <!-- Fonctionnalités -->
      <div class="features">
        <div class="section-title">Pourquoi choisir BookMi ?</div>
        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: separate; border-spacing: 0;">
          <tr>
            <td width="50%" style="padding: 6px 6px 6px 0; vertical-align: top;">
              <div class="feature-card">
                <span class="feature-icon">🛡️</span>
                <div class="feature-title">Séquestre sécurisé</div>
                <div class="feature-desc">Ton paiement est protégé. L'argent n'est libéré qu'après confirmation de la prestation.</div>
              </div>
            </td>
            <td width="50%" style="padding: 6px 0 6px 6px; vertical-align: top;">
              <div class="feature-card">
                <span class="feature-icon">✅</span>
                <div class="feature-title">Talents vérifiés</div>
                <div class="feature-desc">Chaque artiste est contrôlé par notre équipe. Profils, avis et certifications — tout est authentique.</div>
              </div>
            </td>
          </tr>
          <tr>
            <td width="50%" style="padding: 6px 6px 6px 0; vertical-align: top;">
              <div class="feature-card">
                <span class="feature-icon">📱</span>
                <div class="feature-title">Mobile Money</div>
                <div class="feature-desc">Paie comme tu veux : MTN MoMo, Orange Money, Wave. Rapide, simple, ivoirien.</div>
              </div>
            </td>
            <td width="50%" style="padding: 6px 0 6px 6px; vertical-align: top;">
              <div class="feature-card">
                <span class="feature-icon">💬</span>
                <div class="feature-title">Support 7j/7</div>
                <div class="feature-desc">Notre équipe est disponible 7j/7 pour t'accompagner avant, pendant et après ta réservation.</div>
              </div>
            </td>
          </tr>
        </table>
      </div>

      <!-- CTA -->
      <div class="cta-section">
        <div class="cta-text">
          Prêt(e) à rendre ton prochain événement mémorable ?<br>
          Des dizaines de talents t'attendent en ce moment même. 🎵
        </div>
        <a href="{{ config('app.url') }}/talents" class="cta-button">
          🎭 Découvrir les talents →
        </a>
      </div>

      <hr class="divider">

      <!-- Signature -->
      <div class="signature">
        <div class="signature-text">
          Avec toute notre chaleur ivoirienne, 🤝<br><br>
          Sache que chez BookMi, chaque événement compte.<br>
          Nous mettons tout en œuvre pour que ta prochaine fête, cérémonie ou soirée soit <strong>parfaite</strong>.
        </div>
        <div class="signature-name">Charles Ouattara</div>
        <div class="signature-title">CEO & Fondateur — BookMi Côte d'Ivoire</div>
      </div>

    </div>

    <!-- FOOTER -->
    <div class="footer">
      <div class="footer-logo">
        <span>Book</span><span>Mi</span>
      </div>
      <div class="footer-tagline">La plateforme de réservation de talents en Côte d'Ivoire</div>
      <div class="footer-links">
        <a href="{{ config('app.url') }}">bookmi.click</a>
        <span class="footer-dot">·</span>
        <a href="{{ config('app.url') }}/talents">Talents</a>
        <span class="footer-dot">·</span>
        <a href="mailto:support@bookmi.click">Support</a>
      </div>
      <div>
        <span class="footer-badge">🔒 Paiement sécurisé · Escrow BookMi · Support 7j/7</span>
      </div>
      <div style="margin-top: 16px; font-size: 11px; color: rgba(255,255,255,0.3);">
        © {{ date('Y') }} BookMi. Tous droits réservés.<br>
        Abidjan, Côte d'Ivoire 🇨🇮
      </div>
    </div>

  </div>
</div>
</body>
</html>
