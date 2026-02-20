<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>Akwaba sur BookMi !</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { background-color: #0D1117; font-family: 'Nunito', Arial, sans-serif; -webkit-text-size-adjust: 100%; }
  .email-wrapper { background-color: #0D1117; padding: 32px 16px; }
  .email-container { max-width: 620px; margin: 0 auto; }

  /* Header — orange dominant pour les talents */
  .header { background: linear-gradient(135deg, #1A2744 0%, #2d1a0e 60%, #0D1117 100%); border-radius: 20px 20px 0 0; padding: 40px 40px 32px; text-align: center; border-bottom: 3px solid #FF6B35; position: relative; overflow: hidden; }
  .header-glow { position: absolute; top: -40px; left: 50%; transform: translateX(-50%); width: 400px; height: 250px; background: radial-gradient(ellipse, rgba(255,107,53,0.22) 0%, transparent 70%); pointer-events: none; }
  .logo { font-size: 32px; font-weight: 900; letter-spacing: -1px; }
  .logo-book { color: #FFFFFF; }
  .logo-mi { color: #FF6B35; }
  .logo-badge { display: inline-block; background: linear-gradient(135deg, #FF6B35, #ff4500); border-radius: 8px; padding: 4px 12px; margin-left: 8px; font-size: 11px; font-weight: 700; color: #FFFFFF; letter-spacing: 1px; text-transform: uppercase; vertical-align: middle; }
  .header-emoji { font-size: 52px; margin: 20px 0 12px; display: block; }
  .header-title { font-size: 26px; font-weight: 900; color: #FFFFFF; line-height: 1.2; margin-bottom: 8px; }
  .header-title span { color: #FF6B35; }
  .header-subtitle { font-size: 15px; color: rgba(255,255,255,0.6); font-weight: 400; }
  .header-stars { margin-top: 12px; font-size: 18px; letter-spacing: 4px; }

  /* Body */
  .body { background: #FFFFFF; padding: 40px; }

  /* Akwaba section */
  .akwaba-section { background: linear-gradient(135deg, #FFF5F0 0%, #FFF9F7 100%); border-left: 4px solid #FF6B35; border-radius: 0 12px 12px 0; padding: 20px 24px; margin-bottom: 32px; }
  .akwaba-text { font-size: 16px; color: #1A2744; font-weight: 600; line-height: 1.6; }
  .akwaba-text strong { color: #FF6B35; }

  /* Highlight stat bar */
  .stat-bar { display: table; width: 100%; background: linear-gradient(135deg, #1A2744, #243660); border-radius: 16px; padding: 20px; margin-bottom: 32px; }
  .stat-bar-inner { display: table-row; }
  .stat-item { display: table-cell; text-align: center; padding: 0 8px; border-right: 1px solid rgba(255,255,255,0.1); }
  .stat-item:last-child { border-right: none; }
  .stat-number { font-size: 22px; font-weight: 900; color: #FF6B35; line-height: 1; }
  .stat-label { font-size: 11px; color: rgba(255,255,255,0.6); margin-top: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

  /* Section title */
  .section-title { font-size: 18px; font-weight: 800; color: #1A2744; margin-bottom: 20px; }
  .section-title::before { content: ''; display: inline-block; width: 4px; height: 20px; background: #FF6B35; border-radius: 2px; margin-right: 10px; vertical-align: middle; }

  /* Steps */
  .steps { margin-bottom: 36px; }
  .step { display: flex; align-items: flex-start; margin-bottom: 20px; }
  .step-number { flex-shrink: 0; width: 40px; height: 40px; background: linear-gradient(135deg, #FF6B35, #ff4500); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 900; color: #FFFFFF; margin-right: 16px; box-shadow: 0 4px 12px rgba(255,107,53,0.35); }
  .step-content {}
  .step-title { font-size: 15px; font-weight: 800; color: #1A2744; margin-bottom: 4px; }
  .step-desc { font-size: 14px; color: #6B7280; line-height: 1.5; }

  /* Features */
  .features { margin-bottom: 36px; }
  .feature-card { background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 12px; padding: 16px; }
  .feature-icon { font-size: 24px; margin-bottom: 8px; display: block; }
  .feature-title { font-size: 13px; font-weight: 800; color: #1A2744; margin-bottom: 4px; }
  .feature-desc { font-size: 12px; color: #6B7280; line-height: 1.4; }

  /* Action urgency box */
  .action-box { background: linear-gradient(135deg, #FFF5F0, #FFEDE6); border: 2px solid rgba(255,107,53,0.3); border-radius: 16px; padding: 24px; margin-bottom: 32px; text-align: center; }
  .action-box-title { font-size: 16px; font-weight: 800; color: #1A2744; margin-bottom: 8px; }
  .action-box-text { font-size: 14px; color: #4B5563; margin-bottom: 20px; line-height: 1.5; }

  /* CTA */
  .cta-button { display: inline-block; background: linear-gradient(135deg, #FF6B35 0%, #ff4500 100%); color: #FFFFFF !important; text-decoration: none; font-size: 16px; font-weight: 800; padding: 16px 40px; border-radius: 50px; box-shadow: 0 6px 20px rgba(255,107,53,0.4); letter-spacing: 0.3px; }
  .cta-secondary { display: inline-block; color: #FF6B35 !important; text-decoration: none; font-size: 14px; font-weight: 700; margin-top: 12px; }

  /* Divider */
  .divider { border: none; border-top: 1px solid #E5E7EB; margin: 32px 0; }

  /* Signature */
  .signature-text { font-size: 15px; color: #374151; line-height: 1.7; }
  .signature-name { font-size: 16px; font-weight: 800; color: #1A2744; margin-top: 12px; }
  .signature-title { font-size: 13px; color: #FF6B35; font-weight: 600; }

  /* Footer */
  .footer { background: #1A2744; border-radius: 0 0 20px 20px; padding: 24px 40px; text-align: center; }
  .footer-logo span:first-child { color: #FFFFFF; font-size: 20px; font-weight: 900; }
  .footer-logo span:last-child { color: #FF6B35; font-size: 20px; font-weight: 900; }
  .footer-tagline { font-size: 12px; color: rgba(255,255,255,0.5); margin: 6px 0 16px; }
  .footer-links { font-size: 12px; color: rgba(255,255,255,0.4); }
  .footer-links a { color: rgba(255,255,255,0.5); text-decoration: none; }
  .footer-dot { margin: 0 8px; }
  .footer-badge { display: inline-flex; align-items: center; gap: 6px; background: rgba(255,107,53,0.1); border: 1px solid rgba(255,107,53,0.2); border-radius: 20px; padding: 4px 12px; font-size: 11px; color: rgba(255,255,255,0.6); margin-top: 12px; }
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
        <span class="logo-badge">✨ Talent</span>
      </div>
      <span class="header-emoji">🎤</span>
      <div class="header-title">
        Akwaba sur BookMi, <span>{{ $user->first_name }}</span> !
      </div>
      <div class="header-subtitle">La scène t'appartient. Côte d'Ivoire 🇨🇮</div>
      <div class="header-stars">⭐⭐⭐⭐⭐</div>
    </div>

    <!-- BODY -->
    <div class="body">

      <!-- Akwaba message -->
      <div class="akwaba-section">
        <div class="akwaba-text">
          Félicitations <strong>{{ $user->first_name }} {{ $user->last_name }}</strong> ! 🎉<br><br>
          Tu viens de rejoindre la <strong>première plateforme de mise en relation entre talents et clients</strong> en Côte d'Ivoire. BookMi va t'aider à <strong>booster ta carrière</strong>, trouver de nouveaux clients et être payé(e) en toute sécurité pour chaque prestation.
        </div>
      </div>

      <!-- Stats -->
      <table class="stat-bar" cellpadding="0" cellspacing="0">
        <tr class="stat-bar-inner">
          <td class="stat-item">
            <div class="stat-number">100%</div>
            <div class="stat-label">Paiements sécurisés</div>
          </td>
          <td class="stat-item">
            <div class="stat-number">7j/7</div>
            <div class="stat-label">Support dédié</div>
          </td>
          <td class="stat-item">
            <div class="stat-number">0 fcfa</div>
            <div class="stat-label">Pour démarrer</div>
          </td>
          <td class="stat-item">
            <div class="stat-number">🇨🇮</div>
            <div class="stat-label">Toute la CI</div>
          </td>
        </tr>
      </table>

      <!-- Comment ça marche -->
      <div class="steps">
        <div class="section-title">Comment démarrer sur BookMi ?</div>

        <div class="step">
          <div class="step-number">1</div>
          <div class="step-content">
            <div class="step-title">🎨 Complète ton profil</div>
            <div class="step-desc">Ajoute ta bio, tes photos, vidéos et tes forfaits de service. Un profil complet attire 3x plus de clients.</div>
          </div>
        </div>

        <div class="step">
          <div class="step-number">2</div>
          <div class="step-content">
            <div class="step-title">✅ Fais vérifier ton profil</div>
            <div class="step-desc">Soumets tes documents pour la vérification d'identité. Le badge vérifié inspire confiance et booste ta visibilité.</div>
          </div>
        </div>

        <div class="step">
          <div class="step-number">3</div>
          <div class="step-content">
            <div class="step-title">📬 Reçois des demandes de réservation</div>
            <div class="step-desc">Les clients te contactent directement. Tu acceptes ou refuses les demandes selon ton calendrier.</div>
          </div>
        </div>

        <div class="step">
          <div class="step-number">4</div>
          <div class="step-content">
            <div class="step-title">💰 Sois payé(e) en toute sécurité</div>
            <div class="step-desc">Le paiement du client est sécurisé à l'avance. Après ta prestation, les fonds sont libérés directement sur ton Mobile Money.</div>
          </div>
        </div>
      </div>

      <!-- Fonctionnalités talent -->
      <div class="features">
        <div class="section-title">Ce que BookMi t'offre</div>
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td width="50%" style="padding: 6px 6px 6px 0; vertical-align: top;">
              <div class="feature-card">
                <span class="feature-icon">📊</span>
                <div class="feature-title">Tableau de bord analytics</div>
                <div class="feature-desc">Suis tes performances, tes revenus et tes statistiques de réservation en temps réel.</div>
              </div>
            </td>
            <td width="50%" style="padding: 6px 0 6px 6px; vertical-align: top;">
              <div class="feature-card">
                <span class="feature-icon">📜</span>
                <div class="feature-title">Certificat de prestation</div>
                <div class="feature-desc">Obtiens un certificat officiel BookMi après chaque prestation complétée. Parfait pour ton portfolio.</div>
              </div>
            </td>
          </tr>
          <tr>
            <td width="50%" style="padding: 6px 6px 6px 0; vertical-align: top;">
              <div class="feature-card">
                <span class="feature-icon">🗓️</span>
                <div class="feature-title">Calendrier intégré</div>
                <div class="feature-desc">Gère tes disponibilités facilement. Bloque des dates, évite les doubles réservations.</div>
              </div>
            </td>
            <td width="50%" style="padding: 6px 0 6px 6px; vertical-align: top;">
              <div class="feature-card">
                <span class="feature-icon">🌍</span>
                <div class="feature-title">Visibilité nationale</div>
                <div class="feature-desc">Sois visible auprès de milliers de clients à Abidjan, Bouaké, San-Pédro et partout en CI.</div>
              </div>
            </td>
          </tr>
        </table>
      </div>

      <!-- Action urgency -->
      <div class="action-box">
        <div class="action-box-title">🚀 Lance-toi maintenant !</div>
        <div class="action-box-text">
          Ton profil est créé, mais il est encore vide. Complète-le dès aujourd'hui pour commencer à recevoir des demandes de réservation. Plus ton profil est riche, plus tu attires de clients !
        </div>
        <a href="{{ config('app.url') }}/talent/profile" class="cta-button">
          ✏️ Compléter mon profil →
        </a><br>
        <a href="{{ config('app.url') }}/talent/dashboard" class="cta-secondary">
          Accéder à mon espace talent →
        </a>
      </div>

      <hr class="divider">

      <!-- Signature -->
      <div>
        <div class="signature-text">
          Tu rejoins une communauté de talents exceptionnels. 🌟<br><br>
          Chez BookMi, nous croyons en toi et en ton talent. Notre mission est de te donner la <strong>visibilité que tu mérites</strong> et de te garantir des revenus <strong>sécurisés et réguliers</strong>.<br><br>
          N'hésite pas à nous contacter si tu as la moindre question — nous sommes là pour toi.
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
        <a href="{{ config('app.url') }}/talent/dashboard">Mon espace</a>
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
