<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#F3F4F6;font-family:'Segoe UI',Helvetica,Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F3F4F6;padding:32px 16px;">
  <tr>
    <td align="center">
      <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">

        {{-- Header --}}
        <tr>
          <td style="background:linear-gradient(135deg,#1a2744 0%,#0D1117 100%);border-radius:16px 16px 0 0;padding:32px 40px 24px;text-align:center;">
            <p style="margin:0 0 4px;font-size:20px;font-weight:900;color:white;">Book<span style="color:#1AB3FF;">Mi</span></p>
            <p style="margin:0;font-size:11px;font-weight:700;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.1em;">Nouveauté de votre talent suivi</p>
          </td>
        </tr>

        {{-- Body --}}
        <tr>
          <td style="background:white;padding:32px 40px;">

            {{-- Avatar initiale --}}
            <div style="text-align:center;margin-bottom:20px;">
              <div style="display:inline-flex;width:60px;height:60px;border-radius:16px;background:linear-gradient(135deg,#1AB3FF,#0090E8);align-items:center;justify-content:center;font-weight:900;font-size:1.5rem;color:white;">
                {{ strtoupper(substr($stageName, 0, 1)) }}
              </div>
            </div>

            <p style="margin:0 0 8px;font-size:15px;color:#374151;line-height:1.6;text-align:center;">
              Bonjour <strong>{{ $clientName }}</strong>,
            </p>
            <p style="margin:0 0 24px;font-size:15px;color:#374151;line-height:1.6;text-align:center;">
              <strong>{{ $stageName }}</strong> vient de publier {{ $contentLabel }}&nbsp;!
            </p>

            {{-- Contenu --}}
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;">
              <tr>
                <td style="background:#F0F9FF;border-left:4px solid #1AB3FF;border-radius:0 12px 12px 0;padding:16px 20px;">
                  <p style="margin:0 0 4px;font-size:13px;font-weight:800;color:#0369A1;text-transform:uppercase;letter-spacing:0.06em;">{{ $title }}</p>
                  <p style="margin:0;font-size:13px;color:#4B5563;line-height:1.6;">{{ $body }}</p>
                </td>
              </tr>
            </table>

            {{-- CTA --}}
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
              <tr>
                <td align="center">
                  <a href="{{ $profileUrl }}"
                     style="display:inline-block;background:linear-gradient(135deg,#1AB3FF,#0090E8);color:white;font-size:14px;font-weight:800;text-decoration:none;padding:14px 36px;border-radius:100px;letter-spacing:0.02em;">
                    Voir le profil de {{ $stageName }}
                  </a>
                </td>
              </tr>
            </table>

            {{-- Gestion abonnements --}}
            <p style="margin:0;font-size:11px;color:#9CA3AF;text-align:center;line-height:1.6;">
              Vous recevez cet email car vous suivez <strong>{{ $stageName }}</strong> sur BookMi.<br>
              <a href="{{ url('/client/subscriptions') }}" style="color:#1AB3FF;text-decoration:none;">Gérer mes abonnements</a>
            </p>

          </td>
        </tr>

        {{-- Footer --}}
        <tr>
          <td style="background:#F9FAFB;border-radius:0 0 16px 16px;padding:20px 40px;text-align:center;border-top:1px solid #E5E7EB;">
            <p style="margin:0;font-size:11px;color:#9CA3AF;">
              © {{ date('Y') }} BookMi · Abidjan, Côte d'Ivoire
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
