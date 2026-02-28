<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — BookMi</title>
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
                radial-gradient(ellipse 140% 55% at 50% 115%, rgba(26,179,255,0.26) 0%, rgba(0,144,232,0.10) 45%, transparent 68%),
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
        .auth-logo p { color: rgba(255,255,255,0.40); font-size: 0.875rem; margin-top: 0.5rem; font-weight: 600; }

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
        .auth-card h2 { font-size: 1.25rem; font-weight: 900; color: #111827; margin-bottom: 0.25rem; }
        .auth-card .subtitle { font-size: 0.8125rem; color: #9CA3AF; margin-bottom: 1.5rem; font-weight: 600; }

        .form-group { margin-bottom: 1.125rem; }
        .auth-label { display: flex; align-items: center; justify-content: space-between; font-size: 0.8rem; font-weight: 800; color: #374151; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .auth-label a { font-weight: 700; color: #1AB3FF; text-decoration: none; text-transform: none; letter-spacing: 0; font-size: 0.8125rem; }
        .auth-label a:hover { text-decoration: underline; }

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
        .input-wrap { position: relative; }
        .input-wrap .auth-input { padding-right: 3rem; }
        .eye-btn {
            position: absolute; right: 0.9rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; color: #9CA3AF; padding: 0;
            display: flex; align-items: center; transition: color 0.15s;
        }
        .eye-btn:hover { color: #374151; }

        .remember-row { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1.5rem; }
        .remember-row input[type=checkbox] { width: 16px; height: 16px; border-radius: 4px; accent-color: #2196F3; cursor: pointer; }
        .remember-row label { font-size: 0.875rem; font-weight: 600; color: #6B7280; cursor: pointer; }

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

        .auth-footer { text-align: center; font-size: 0.875rem; color: #9CA3AF; margin-top: 1.25rem; font-weight: 600; }
        .auth-footer a { font-weight: 800; color: #1AB3FF; text-decoration: none; }
        .auth-footer a:hover { text-decoration: underline; }

        .terms-row { display: flex; align-items: flex-start; gap: 0.6rem; margin-bottom: 1.5rem; }
        .terms-row input[type=checkbox] { width: 17px; height: 17px; min-width: 17px; margin-top: 2px; border-radius: 4px; accent-color: #2196F3; cursor: pointer; }
        .terms-row label { font-size: 0.825rem; font-weight: 600; color: #6B7280; cursor: pointer; line-height: 1.5; }
        .terms-row a { color: #2196F3; text-decoration: none; font-weight: 700; }
        .terms-row a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="auth-wrapper">
        <div class="auth-logo">
            <a href="{{ route('home') }}">
                <span class="logo-book">Book</span><span class="logo-mi">Mi</span>
            </a>
            <p>Connectez-vous à votre compte</p>
        </div>

        <div class="auth-card" x-data="{ show: false }">
            <h2>Bon retour</h2>
            <p class="subtitle">Entrez vos identifiants pour continuer</p>

            @if(session('success'))
                <div class="auth-alert success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="auth-alert error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
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

                <div class="form-group">
                    <label class="auth-label">
                        Mot de passe
                        <a href="{{ route('password.request') }}">Mot de passe oublié ?</a>
                    </label>
                    <div class="input-wrap">
                        <input
                            :type="show ? 'text' : 'password'"
                            name="password"
                            required
                            class="auth-input"
                            placeholder="••••••••"
                        >
                        <button type="button" class="eye-btn" @click="show = !show">
                            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg x-show="show" xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                        </button>
                    </div>
                </div>

                <div class="remember-row">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember">Se souvenir de moi</label>
                </div>

                <div class="terms-row">
                    <input type="checkbox" name="terms" id="terms" required>
                    <label for="terms">
                        J'accepte les <a href="{{ route('legal.conditions') }}" target="_blank">Conditions d'utilisation</a>
                        et la <a href="{{ route('legal.privacy') }}" target="_blank">Politique de confidentialité</a>
                    </label>
                </div>

                <button type="submit" class="auth-btn">Se connecter</button>
            </form>

            <p class="auth-footer">
                Pas encore de compte ? <a href="{{ route('register') }}">Créer un compte</a>
            </p>
        </div>
    </div>

</body>
</html>
