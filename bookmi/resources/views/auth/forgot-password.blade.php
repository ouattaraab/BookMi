<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié — BookMi</title>
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
        .auth-wrapper { position: relative; z-index: 1; width: 100%; max-width: 420px; }

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
            padding: 2.25rem 2.25rem 2rem;
            box-shadow: 0 48px 110px rgba(0,0,0,0.60), 0 0 0 1px rgba(255,255,255,0.06);
            animation: authCardIn 0.78s cubic-bezier(0.16,1,0.3,1) 0.06s both;
        }

        .icon-ring {
            width: 58px; height: 58px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.25rem;
            background: linear-gradient(135deg, #EFF8FF, #DBEAFE);
            box-shadow: 0 0 0 8px rgba(33,150,243,0.07);
        }
        .auth-card h2 { font-size: 1.2rem; font-weight: 900; color: #111827; text-align: center; margin-bottom: 0.375rem; }
        .auth-card p.desc { font-size: 0.8125rem; color: #9CA3AF; font-weight: 600; text-align: center; margin-bottom: 1.5rem; line-height: 1.55; }

        .form-group { margin-bottom: 1.25rem; }
        .auth-label { display: block; font-size: 0.78rem; font-weight: 800; color: #374151; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .auth-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid #E9EAEC;
            border-radius: 12px;
            font-size: 0.9375rem;
            font-family: 'Nunito', sans-serif;
            font-weight: 600;
            color: #111827;
            background: #F9FAFB;
            transition: border-color 0.18s, box-shadow 0.18s, background 0.18s;
            outline: none;
            -webkit-appearance: none;
        }
        .auth-input:focus {
            border-color: #2196F3;
            background: #fff;
            box-shadow: 0 0 0 3.5px rgba(33,150,243,0.13);
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
            transition: opacity 0.15s, transform 0.15s;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #1A2744 0%, #2563EB 100%);
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

        .auth-alert { padding: 0.75rem 1rem; border-radius: 10px; font-size: 0.825rem; font-weight: 700; margin-bottom: 1.25rem; }
        .auth-alert.success { background: #D1FAE5; border: 1.5px solid #6EE7B7; color: #065F46; }
        .auth-alert.error   { background: #FEE2E2; border: 1.5px solid #FCA5A5; color: #991B1B; }

        .back-link {
            display: flex; align-items: center; justify-content: center; gap: 0.375rem;
            font-size: 0.875rem; font-weight: 700;
            color: rgba(255,255,255,0.38);
            margin-top: 1.5rem;
            text-decoration: none;
            transition: color 0.15s;
        }
        .back-link:hover { color: rgba(255,255,255,0.75); }
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
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#2196F3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            </div>
            <h2>Mot de passe oublié ?</h2>
            <p class="desc">Entrez votre email et nous vous enverrons un lien pour réinitialiser votre mot de passe.</p>

            @if(session('success'))
                <div class="auth-alert success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="auth-alert error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="form-group">
                    <label class="auth-label">Adresse email</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="auth-input"
                        placeholder="vous@exemple.com"
                    >
                </div>
                <button type="submit" class="auth-btn">Envoyer le lien de réinitialisation</button>
            </form>
        </div>

        <a href="{{ route('login') }}" class="back-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            Retour à la connexion
        </a>
    </div>

</body>
</html>
