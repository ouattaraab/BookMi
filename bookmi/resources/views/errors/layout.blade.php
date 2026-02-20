<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Erreur') — BookMi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Nunito', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1A2744 0%, #0F1E3A 60%, #0a1628 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            border-radius: 28px;
            padding: 3rem 2.5rem;
            max-width: 480px;
            width: 100%;
        }
        .logo { display: flex; align-items: center; gap: 8px; justify-content: center; margin-bottom: 2rem; text-decoration: none; }
        .logo-icon {
            width: 36px; height: 36px; border-radius: 10px;
            background: linear-gradient(135deg, #FF6B35, #E55A2B);
            display: flex; align-items: center; justify-content: center;
            font-weight: 900; font-size: 14px; color: white;
        }
        .logo-text { font-size: 22px; font-weight: 900; color: white; letter-spacing: -0.02em; }
        .logo-text span { color: #64B5F6; }
        .code {
            font-size: clamp(5rem, 15vw, 8rem);
            font-weight: 900;
            line-height: 1;
            letter-spacing: -0.04em;
            margin-bottom: 0.5rem;
        }
        .title { font-size: 1.4rem; font-weight: 800; margin-bottom: 0.75rem; }
        .desc { color: rgba(255,255,255,0.55); font-size: 0.95rem; font-weight: 500; line-height: 1.6; margin-bottom: 2rem; }
        .btn {
            display: inline-block;
            padding: 0.85rem 2.2rem;
            border-radius: 16px;
            font-weight: 800;
            font-size: 0.9rem;
            text-decoration: none;
            transition: transform 0.15s, box-shadow 0.15s;
            cursor: pointer;
        }
        .btn:hover { transform: scale(1.04); }
        .btn-primary {
            background: linear-gradient(135deg, #FF6B35, #E55A2B);
            color: white;
            box-shadow: 0 6px 20px rgba(255,107,53,0.4);
        }
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.8);
            border: 1px solid rgba(255,255,255,0.2);
            margin-left: 0.75rem;
        }
        .btn-secondary:hover { background: rgba(255,255,255,0.18); }
        .decoration {
            position: fixed; border-radius: 50%; pointer-events: none;
            background: rgba(255,107,53,0.08);
        }
        .deco1 { width: 400px; height: 400px; top: -100px; right: -100px; }
        .deco2 { width: 300px; height: 300px; bottom: -80px; left: -80px; background: rgba(33,150,243,0.08); }
    </style>
</head>
<body>
    <div class="decoration deco1"></div>
    <div class="decoration deco2"></div>

    <div class="card">
        <a href="/" class="logo">
            <div class="logo-icon">B</div>
            <div class="logo-text">Book<span>Mi</span></div>
        </a>

        <div class="code" style="color:@yield('code-color', '#FF6B35')">@yield('code')</div>
        <div class="title">@yield('title')</div>
        <div class="desc">@yield('description')</div>

        <div>
            <a href="/" class="btn btn-primary">Retour à l'accueil</a>
            <a href="javascript:history.back()" class="btn btn-secondary">← Retour</a>
        </div>
    </div>
</body>
</html>
