<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification téléphone — BookMi</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: 'Nunito', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background-color: #070B14;
            background-image:
                radial-gradient(ellipse 140% 55% at 50% 115%, rgba(255,107,53,0.26) 0%, rgba(200,60,20,0.10) 45%, transparent 68%),
                radial-gradient(ellipse 110% 55% at 50% -8%, rgba(20,35,70,0.98) 0%, transparent 62%),
                radial-gradient(ellipse 55% 38% at 96% 2%, rgba(33,150,243,0.07) 0%, transparent 55%);
            overflow-x: hidden;
        }
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.72' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.032'/%3E%3C/svg%3E");
            background-size: 200px;
            pointer-events: none;
            z-index: 0;
        }
        .auth-wrapper { position: relative; z-index: 1; width: 100%; max-width: 400px; }

        .auth-logo { text-align: center; margin-bottom: 2rem; }
        .auth-logo a { display: inline-flex; align-items: center; gap: 1px; text-decoration: none; }
        .auth-logo .logo-book { font-weight: 900; font-size: 2.1rem; color: #fff; letter-spacing: -0.02em; line-height: 1; }
        .auth-logo .logo-mi   { font-weight: 900; font-size: 2.1rem; color: #2196F3; letter-spacing: -0.02em; line-height: 1; }

        @keyframes authCardIn {
            from { opacity: 0; transform: translateY(38px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .auth-card {
            background: #fff;
            border-radius: 22px;
            padding: 2.5rem 2.25rem 2rem;
            box-shadow: 0 48px 110px rgba(0,0,0,0.60), 0 0 0 1px rgba(255,255,255,0.06);
            animation: authCardIn 0.78s cubic-bezier(0.16,1,0.3,1) 0.06s both;
            text-align: center;
        }

        .icon-ring {
            width: 64px; height: 64px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.25rem;
            background: linear-gradient(135deg, #FFF4EF, #FFE8D6);
            box-shadow: 0 0 0 8px rgba(255,107,53,0.08);
        }
        .auth-card h2 { font-size: 1.2rem; font-weight: 900; color: #111827; margin-bottom: 0.5rem; }
        .auth-card .desc { font-size: 0.8125rem; color: #9CA3AF; font-weight: 600; line-height: 1.55; margin-bottom: 1.75rem; }
        .auth-card .desc strong { color: #374151; font-weight: 800; }

        .auth-label { display: block; font-size: 0.78rem; font-weight: 800; color: #374151; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.04em; }

        .otp-input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #E9EAEC;
            border-radius: 14px;
            font-size: 2.25rem;
            font-family: 'Courier New', 'Lucida Console', monospace;
            font-weight: 900;
            color: #111827;
            background: #F9FAFB;
            text-align: center;
            letter-spacing: 0.55em;
            transition: border-color 0.18s, box-shadow 0.18s, background 0.18s;
            outline: none;
            -webkit-appearance: none;
        }
        .otp-input:focus {
            border-color: #FF6B35;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(255,107,53,0.12);
        }

        .auth-btn {
            width: 100%;
            padding: 0.9rem;
            border: none;
            border-radius: 13px;
            font-size: 1rem;
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            color: #fff;
            cursor: pointer;
            margin-top: 1.25rem;
            transition: opacity 0.15s, transform 0.15s;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #FF6B35 0%, #C85A20 100%);
            letter-spacing: 0.01em;
        }
        .auth-btn::before {
            content: '';
            position: absolute;
            inset: 0 0 50% 0;
            background: linear-gradient(180deg, rgba(255,255,255,0.14) 0%, transparent 100%);
            pointer-events: none;
        }
        .auth-btn:hover { opacity: 0.91; transform: translateY(-1px); }
        .auth-btn:active { transform: translateY(0); }

        .resend-btn {
            display: block;
            width: 100%;
            padding: 0.75rem;
            border: 1.5px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            background: transparent;
            color: rgba(255,255,255,0.45);
            font-size: 0.875rem;
            font-family: 'Nunito', sans-serif;
            font-weight: 700;
            cursor: pointer;
            margin-top: 0.875rem;
            transition: border-color 0.15s, color 0.15s, background 0.15s;
        }
        .resend-btn:hover { border-color: rgba(255,255,255,0.30); color: rgba(255,255,255,0.75); background: rgba(255,255,255,0.04); }

        .auth-alert { padding: 0.75rem 1rem; border-radius: 10px; font-size: 0.825rem; font-weight: 700; margin-bottom: 1.25rem; text-align: left; }
        .auth-alert.success { background: #D1FAE5; border: 1.5px solid #6EE7B7; color: #065F46; }
        .auth-alert.error   { background: #FEE2E2; border: 1.5px solid #FCA5A5; color: #991B1B; }
    </style>
</head>
<body>

    <div class="auth-wrapper">
        <div class="auth-logo">
            <a href="{{ route('home') }}">
                <span class="logo-book">Book</span><span class="logo-mi">Mi</span>
            </a>
        </div>

        <div class="auth-card">
            <div class="icon-ring">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#FF6B35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.45A2 2 0 0 1 3.58 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.56a16 16 0 0 0 6.27 6.27l1.06-1.06a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <h2>Vérifiez votre téléphone</h2>
            <p class="desc">
                Nous avons envoyé un code SMS au <strong>{{ $phone ?? 'votre numéro' }}</strong>.<br>
                Entrez le code à 6 chiffres ci-dessous.
            </p>

            @if(session('success'))
                <div class="auth-alert success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="auth-alert error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('auth.verify-phone') }}">
                @csrf
                <label class="auth-label">Code OTP reçu par SMS</label>
                <input
                    type="text"
                    name="code"
                    inputmode="numeric"
                    maxlength="6"
                    required
                    autofocus
                    autocomplete="one-time-code"
                    class="otp-input"
                    placeholder="000000"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6)"
                >
                <button type="submit" class="auth-btn">Vérifier mon numéro</button>
            </form>

            <form method="POST" action="{{ route('auth.verify-phone.resend') }}">
                @csrf
                <button type="submit" class="resend-btn">Renvoyer le code</button>
            </form>
        </div>
    </div>

</body>
</html>
