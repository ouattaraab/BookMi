<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'BookMi - Réservez vos talents')</title>
    <meta name="description" content="@yield('meta_description', 'BookMi - La plateforme N°1 de réservation de talents en Côte d\'Ivoire')">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @yield('head')
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body, button, input, select, textarea { font-family: 'Nunito', sans-serif; }
        html { scroll-behavior: smooth; }
    </style>
</head>
<body style="background:#0D1117; margin:0; padding:0;">

    {{-- ═══════════════════ NAVBAR ═══════════════════ --}}
    <nav x-data="{ open: false }"
         style="background: rgba(13,17,23,0.95); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255,255,255,0.06); position: sticky; top: 0; z-index: 50;">
        <div style="max-width:1200px; margin:0 auto; padding:0 1.5rem; height:64px; display:flex; align-items:center; justify-content:space-between; gap:2rem;">

            {{-- Logo --}}
            <a href="{{ route('home') }}" style="display:flex; align-items:center; text-decoration:none; flex-shrink:0;">
                <span style="font-weight:900; font-size:1.4rem; letter-spacing:-0.03em; line-height:1; font-family:'Nunito',sans-serif;">
                    <span style="color:white;">Book</span><span style="color:#2196F3;">Mi</span>
                </span>
            </a>

            {{-- Liens centre (desktop) --}}
            <div class="hidden md:flex" style="gap:2.5rem; flex:1; justify-content:center;">
                <a href="{{ route('home') }}"       style="color:rgba(255,255,255,0.75); text-decoration:none; font-size:0.875rem; font-weight:600; transition:color 0.15s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.75)'">Accueil</a>
                <a href="{{ route('talents.index') }}" style="color:rgba(255,255,255,0.75); text-decoration:none; font-size:0.875rem; font-weight:600; transition:color 0.15s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.75)'">Talents</a>
                <a href="#clients" style="color:rgba(255,255,255,0.75); text-decoration:none; font-size:0.875rem; font-weight:600; transition:color 0.15s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.75)'">Clients</a>
            </div>

            {{-- Boutons auth (desktop) --}}
            <div class="hidden md:flex" style="align-items:center; gap:0.75rem; flex-shrink:0;">
                @auth
                    @php $u = auth()->user(); @endphp
                    @if($u->hasRole('client'))
                        <a href="{{ route('client.dashboard') }}" style="color:white; text-decoration:none; font-size:0.875rem; font-weight:700; padding:8px 18px; border-radius:100px; border:1.5px solid rgba(255,255,255,0.25);">Mon espace</a>
                    @elseif($u->hasRole('talent'))
                        <a href="{{ route('talent.dashboard') }}" style="color:white; text-decoration:none; font-size:0.875rem; font-weight:700; padding:8px 18px; border-radius:100px; border:1.5px solid rgba(255,255,255,0.25);">Mon espace</a>
                    @elseif($u->hasRole('manager'))
                        <a href="{{ route('manager.dashboard') }}" style="color:white; text-decoration:none; font-size:0.875rem; font-weight:700; padding:8px 18px; border-radius:100px; border:1.5px solid rgba(255,255,255,0.25);">Mon espace</a>
                    @endif
                @else
                    <a href="{{ route('login') }}"
                       style="color:rgba(255,255,255,0.85); text-decoration:none; font-size:0.875rem; font-weight:700; padding:8px 18px; border-radius:100px; border:1.5px solid rgba(255,255,255,0.2); display:flex; align-items:center; gap:6px; transition:border-color 0.15s;"
                       onmouseover="this.style.borderColor='rgba(255,255,255,0.5)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.2)'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Connexion
                    </a>
                    <a href="{{ route('register') }}"
                       style="color:#0D1117; background:white; text-decoration:none; font-size:0.875rem; font-weight:800; padding:8px 20px; border-radius:100px; transition:transform 0.15s, box-shadow 0.15s;"
                       onmouseover="this.style.transform='scale(1.03)'; this.style.boxShadow='0 4px 16px rgba(255,255,255,0.2)'"
                       onmouseout="this.style.transform=''; this.style.boxShadow=''">
                        Inscription
                    </a>
                @endauth
            </div>

            {{-- Hamburger mobile --}}
            <button @click="open=!open" class="md:hidden" style="background:none; border:none; color:rgba(255,255,255,0.8); cursor:pointer; padding:4px;">
                <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                <svg x-show="open"  xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        {{-- Menu mobile --}}
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-end="opacity-0 -translate-y-2"
             style="padding:1rem 1.5rem 1.5rem; border-top:1px solid rgba(255,255,255,0.06);">
            <div style="display:flex; flex-direction:column; gap:0.5rem; margin-bottom:1rem;">
                <a href="{{ route('home') }}"        @click="open=false" style="color:rgba(255,255,255,0.75); text-decoration:none; font-size:0.9rem; font-weight:600; padding:0.6rem 0;">Accueil</a>
                <a href="{{ route('talents.index') }}" @click="open=false" style="color:rgba(255,255,255,0.75); text-decoration:none; font-size:0.9rem; font-weight:600; padding:0.6rem 0;">Talents</a>
                <a href="#clients"                  @click="open=false" style="color:rgba(255,255,255,0.75); text-decoration:none; font-size:0.9rem; font-weight:600; padding:0.6rem 0;">Clients</a>
            </div>
            @guest
                <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                    <a href="{{ route('login') }}"    style="color:white; text-decoration:none; font-size:0.875rem; font-weight:700; padding:8px 18px; border-radius:100px; border:1.5px solid rgba(255,255,255,0.25);">Connexion</a>
                    <a href="{{ route('register') }}" style="color:#0D1117; background:white; text-decoration:none; font-size:0.875rem; font-weight:800; padding:8px 20px; border-radius:100px;">Inscription</a>
                </div>
            @endguest
        </div>
    </nav>

    {{-- Contenu --}}
    <main>
        @yield('content')
    </main>

    {{-- ═══════════════════ FOOTER ═══════════════════ --}}
    <footer class="footer-bg" style="color:white;">
        <div style="max-width:1200px; margin:0 auto; padding:4rem 1.5rem 2rem;">
            <div style="display:grid; grid-template-columns:1.6fr 1fr 1fr 1.4fr; gap:2.5rem; margin-bottom:3rem;"
                 class="footer-grid">

                {{-- Col 1 : logo + desc + réseaux --}}
                <div>
                    <a href="{{ route('home') }}" style="display:inline-flex; align-items:center; text-decoration:none; margin-bottom:1rem;">
                        <span style="font-weight:900; font-size:1.3rem; letter-spacing:-0.03em; font-family:'Nunito',sans-serif;">
                            <span style="color:white;">Book</span><span style="color:#2196F3;">Mi</span>
                        </span>
                    </a>
                    <p style="color:rgba(255,255,255,0.4); font-size:0.85rem; line-height:1.65; margin-bottom:1.5rem; max-width:220px;">
                        La plateforme N°1 en Côte d'Ivoire qui révolutionne le booking artistique. Simple, Sécurisé, Pro.
                    </p>
                    <div style="display:flex; gap:0.6rem;">
                        @foreach([
                            ['label'=>'Facebook',  'path'=>'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z'],
                            ['label'=>'TikTok',    'path'=>'M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.28 8.28 0 004.83 1.54V6.79a4.85 4.85 0 01-1.06-.1z'],
                            ['label'=>'Instagram', 'path'=>'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z'],
                            ['label'=>'YouTube',   'path'=>'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z'],
                        ] as $soc)
                        <a href="#" aria-label="{{ $soc['label'] }}"
                           style="width:34px; height:34px; border-radius:8px; background:rgba(255,255,255,0.08); display:flex; align-items:center; justify-content:center; transition:background 0.15s;"
                           onmouseover="this.style.background='rgba(255,255,255,0.16)'" onmouseout="this.style.background='rgba(255,255,255,0.08)'">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="rgba(255,255,255,0.7)" viewBox="0 0 24 24"><path d="{{ $soc['path'] }}"/></svg>
                        </a>
                        @endforeach
                    </div>
                </div>

                {{-- Col 2 : Liens Rapides --}}
                <div>
                    <p style="font-weight:800; font-size:0.8rem; color:white; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:1.2rem;">Liens Rapides</p>
                    <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.75rem;">
                        @foreach(['Trouver un talent'=>route('talents.index'), 'Devenir talent'=>route('register'), 'Connexion'=>route('login'), 'À propos'=>'#'] as $label => $href)
                        <li><a href="{{ $href }}" style="color:rgba(255,255,255,0.45); text-decoration:none; font-size:0.875rem; font-weight:500; transition:color 0.15s;" onmouseover="this.style.color='rgba(255,255,255,0.85)'" onmouseout="this.style.color='rgba(255,255,255,0.45)'">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>

                {{-- Col 3 : Légal --}}
                <div>
                    <p style="font-weight:800; font-size:0.8rem; color:white; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:1.2rem;">Légal</p>
                    <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.75rem;">
                        @foreach(["Conditions d'utilisation", 'FAQ', 'Confidentialité'] as $label)
                        <li><a href="#" style="color:rgba(255,255,255,0.45); text-decoration:none; font-size:0.875rem; font-weight:500; transition:color 0.15s;" onmouseover="this.style.color='rgba(255,255,255,0.85)'" onmouseout="this.style.color='rgba(255,255,255,0.45)'">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>

                {{-- Col 4 : Contact --}}
                <div>
                    <p style="font-weight:800; font-size:0.8rem; color:white; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:1.2rem;">Contactez-nous</p>
                    <div style="display:flex; flex-direction:column; gap:0.9rem;">
                        <div style="display:flex; align-items:center; gap:10px; background:rgba(255,255,255,0.05); border-radius:10px; padding:10px 14px;">
                            <div style="width:30px; height:30px; border-radius:8px; background:rgba(255,107,53,0.15); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="#FF6B35" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </div>
                            <div>
                                <p style="color:rgba(255,255,255,0.35); font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; margin:0 0 2px;">Email</p>
                                <p style="color:rgba(255,255,255,0.75); font-size:0.82rem; font-weight:600; margin:0;">contact@bookmi.ci</p>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px; background:rgba(255,255,255,0.05); border-radius:10px; padding:10px 14px;">
                            <div style="width:30px; height:30px; border-radius:8px; background:rgba(255,107,53,0.15); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="#FF6B35" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.05 6.05l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 17z"/></svg>
                            </div>
                            <div>
                                <p style="color:rgba(255,255,255,0.35); font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; margin:0 0 2px;">Téléphone</p>
                                <p style="color:rgba(255,255,255,0.75); font-size:0.82rem; font-weight:600; margin:0;">+225 07 00 00 00</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bottom bar --}}
            <div style="border-top:1px solid rgba(255,255,255,0.07); padding-top:1.5rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
                <p style="color:rgba(255,255,255,0.2); font-size:0.78rem; font-weight:500; margin:0;">
                    © {{ date('Y') }} BookMi. Fait avec passion à Abidjan.
                </p>
                <div style="display:flex; gap:0.5rem; align-items:center;">
                    <span style="background:#FF6B35; color:white; font-size:0.7rem; font-weight:800; padding:4px 10px; border-radius:6px;">Orange Money</span>
                    <span style="background:#FFCC00; color:#1a1a1a; font-size:0.7rem; font-weight:800; padding:4px 10px; border-radius:6px;">MTN MoMo</span>
                    <span style="background:#1565C0; color:white; font-size:0.7rem; font-weight:800; padding:4px 10px; border-radius:6px;">Wave</span>
                </div>
            </div>
        </div>
    </footer>

    <style>
        /* ─── FOOTER : pattern africain/musical ─── */
        .footer-bg {
            background-color: #060912;
            /* Couche 1 : séparateur dégradé en haut pour distinguer du bloc précédent */
            /* Couche 2 : notes de musique flottantes (160×80) */
            /* Couche 3 : diamants kente / adinkra (60×60) */
            background-image:
                linear-gradient(to bottom, rgba(26,39,68,0.85) 0%, transparent 70px),
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='80'%3E%3Cline x1='44' y1='18' x2='44' y2='50' stroke='rgba(255,107,53,0.07)' stroke-width='1.5'/%3E%3Cellipse cx='40' cy='52' rx='6' ry='4' transform='rotate(-20 40 52)' fill='rgba(255,107,53,0.08)'/%3E%3Cpath d='M44 18 Q64 12 57 28' fill='none' stroke='rgba(255,107,53,0.07)' stroke-width='1.5'/%3E%3Cline x1='122' y1='28' x2='122' y2='60' stroke='rgba(255,107,53,0.055)' stroke-width='1.5'/%3E%3Cellipse cx='118' cy='62' rx='6' ry='4' transform='rotate(-20 118 62)' fill='rgba(255,107,53,0.065)'/%3E%3Cpath d='M122 28 Q142 22 135 38' fill='none' stroke='rgba(255,107,53,0.055)' stroke-width='1.5'/%3E%3C/svg%3E"),
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60'%3E%3Cpath d='M30 3 L57 30 L30 57 L3 30 Z' fill='none' stroke='rgba(255,140,50,0.07)' stroke-width='1'/%3E%3Cpath d='M30 18 L42 30 L30 42 L18 30 Z' fill='rgba(255,107,53,0.03)'/%3E%3Cline x1='30' y1='3' x2='30' y2='57' stroke='rgba(255,140,50,0.035)' stroke-width='0.5'/%3E%3Cline x1='3' y1='30' x2='57' y2='30' stroke='rgba(255,140,50,0.035)' stroke-width='0.5'/%3E%3Ccircle cx='30' cy='3' r='2' fill='rgba(255,180,50,0.1)'/%3E%3Ccircle cx='57' cy='30' r='2' fill='rgba(255,180,50,0.1)'/%3E%3Ccircle cx='30' cy='57' r='2' fill='rgba(255,180,50,0.1)'/%3E%3Ccircle cx='3' cy='30' r='2' fill='rgba(255,180,50,0.1)'/%3E%3C/svg%3E");
            background-size: 100% 70px, 160px 80px, 60px 60px;
            background-repeat: no-repeat, repeat, repeat;
            background-position: top center, 20px 30px, 0 0;
        }

        @media (max-width: 768px) {
            .footer-grid { grid-template-columns: 1fr 1fr !important; }
        }
        @media (max-width: 480px) {
            .footer-grid { grid-template-columns: 1fr !important; }
        }
    </style>

    @livewireScripts
    @yield('scripts')
</body>
</html>
