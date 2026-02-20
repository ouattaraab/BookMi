<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'BookMi - Réservez vos talents')</title>
    <meta name="description" content="@yield('meta_description', 'BookMi - La plateforme de réservation de talents en Côte d\'Ivoire')">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @yield('head')
    <style>
        body, button, input, select, textarea { font-family: 'Nunito', sans-serif; }
        html { scroll-behavior: smooth; }
    </style>
</head>
<body class="bg-white min-h-screen flex flex-col">

    {{-- ═══════════ NAVBAR ═══════════ --}}
    <nav x-data="{ open: false }"
         style="background: linear-gradient(180deg,#1A2744 0%,#0F1E3A 100%);"
         class="sticky top-0 z-50 border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-6">

            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2 flex-shrink-0">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center font-black text-white text-sm flex-shrink-0"
                     style="background: linear-gradient(135deg, #FF6B35, #E55A2B)">B</div>
                <span class="font-extrabold text-xl tracking-tight leading-none">
                    <span class="text-white">Book</span><span style="color:#64B5F6">Mi</span>
                </span>
            </a>

            {{-- Liens onepage (desktop) --}}
            <div class="hidden md:flex items-center gap-8 text-sm font-semibold flex-1 justify-center">
                <a href="#talents"
                   class="text-white/70 hover:text-white transition-colors pb-0.5"
                   style="border-bottom: 2px solid transparent;"
                   onmouseover="this.style.borderBottomColor='#FF6B35'"
                   onmouseout="this.style.borderBottomColor='transparent'">
                    Talents
                </a>
                <a href="#pourquoi-bookmi"
                   class="text-white/70 hover:text-white transition-colors pb-0.5"
                   style="border-bottom: 2px solid transparent;"
                   onmouseover="this.style.borderBottomColor='#FF6B35'"
                   onmouseout="this.style.borderBottomColor='transparent'">
                    Pourquoi BookMi&nbsp;?
                </a>
                <a href="#clients"
                   class="text-white/70 hover:text-white transition-colors pb-0.5"
                   style="border-bottom: 2px solid transparent;"
                   onmouseover="this.style.borderBottomColor='#FF6B35'"
                   onmouseout="this.style.borderBottomColor='transparent'">
                    Clients
                </a>
            </div>

            {{-- Boutons auth (desktop) --}}
            <div class="hidden md:flex items-center gap-3 flex-shrink-0">
                @auth
                    @php $user = auth()->user(); @endphp
                    @if($user->hasRole('client'))
                        <a href="{{ route('client.dashboard') }}"
                           class="px-4 py-2 rounded-xl text-sm font-semibold text-white hover:bg-white/10 transition-colors">
                            Mon espace
                        </a>
                    @elseif($user->hasRole('talent'))
                        <a href="{{ route('talent.dashboard') }}"
                           class="px-4 py-2 rounded-xl text-sm font-semibold text-white hover:bg-white/10 transition-colors">
                            Mon espace
                        </a>
                    @elseif($user->hasRole('manager'))
                        <a href="{{ route('manager.dashboard') }}"
                           class="px-4 py-2 rounded-xl text-sm font-semibold text-white hover:bg-white/10 transition-colors">
                            Mon espace
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}"
                       class="px-4 py-2 rounded-xl text-sm font-semibold text-white/75 hover:text-white transition-colors">
                        Connexion
                    </a>
                    <a href="{{ route('register') }}"
                       class="px-5 py-2 rounded-2xl text-sm font-extrabold text-white transition-all hover:scale-105"
                       style="background: linear-gradient(135deg, #FF6B35 0%, #E55A2B 100%); box-shadow: 0 4px 16px rgba(255,107,53,0.45);">
                        Inscription
                    </a>
                @endauth
            </div>

            {{-- Hamburger (mobile) --}}
            <button @click="open = !open" class="md:hidden p-1 text-white/80 hover:text-white transition-colors">
                <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none"
                     stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
                <svg x-show="open" xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none"
                     stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        {{-- Menu mobile --}}
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="md:hidden px-4 pb-5 pt-2 space-y-1" style="border-top:1px solid rgba(255,255,255,0.08)">
            <a href="#talents"    @click="open=false" class="block py-3 text-white/75 font-semibold text-sm hover:text-white transition-colors">Talents</a>
            <a href="#pourquoi-bookmi" @click="open=false" class="block py-3 text-white/75 font-semibold text-sm hover:text-white transition-colors">Pourquoi BookMi ?</a>
            <a href="#clients"    @click="open=false" class="block py-3 text-white/75 font-semibold text-sm hover:text-white transition-colors">Clients</a>
            <div class="pt-2 border-t" style="border-color:rgba(255,255,255,0.1)">
                @guest
                    <a href="{{ route('login') }}"    class="block py-3 text-white/75 font-semibold text-sm hover:text-white transition-colors">Connexion</a>
                    <a href="{{ route('register') }}" class="block mt-1 py-3 text-center rounded-2xl font-extrabold text-white text-sm"
                       style="background: linear-gradient(135deg, #FF6B35, #E55A2B)">Inscription</a>
                @endguest
            </div>
        </div>
    </nav>

    {{-- Contenu principal --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- ═══════════ FOOTER ═══════════ --}}
    <footer style="background: #0B1628;" class="text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-8">

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-10 mb-12">

                {{-- Colonne 1 : Logo + desc + réseaux --}}
                <div class="sm:col-span-2 md:col-span-1">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center font-black text-white text-sm"
                             style="background: linear-gradient(135deg, #FF6B35, #E55A2B)">B</div>
                        <span class="font-extrabold text-xl">
                            <span class="text-white">Book</span><span style="color:#64B5F6">Mi</span>
                        </span>
                    </div>
                    <p class="text-white/45 text-sm leading-relaxed mb-6">
                        La plateforme de réservation de talents en Côte d'Ivoire.
                    </p>
                    <div class="flex gap-2.5">
                        {{-- Facebook --}}
                        <a href="#" aria-label="Facebook"
                           class="w-9 h-9 rounded-xl flex items-center justify-center bg-white/10 hover:bg-white/20 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="white" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        {{-- Instagram --}}
                        <a href="#" aria-label="Instagram"
                           class="w-9 h-9 rounded-xl flex items-center justify-center bg-white/10 hover:bg-white/20 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="white" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                            </svg>
                        </a>
                        {{-- TikTok --}}
                        <a href="#" aria-label="TikTok"
                           class="w-9 h-9 rounded-xl flex items-center justify-center bg-white/10 hover:bg-white/20 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="white" viewBox="0 0 24 24">
                                <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.28 8.28 0 004.83 1.54V6.79a4.85 4.85 0 01-1.06-.1z"/>
                            </svg>
                        </a>
                    </div>
                </div>

                {{-- Colonne 2 : Plateforme --}}
                <div>
                    <p class="font-extrabold text-white text-xs uppercase tracking-widest mb-5">Plateforme</p>
                    <ul class="space-y-3">
                        <li><a href="#talents"              class="text-white/50 hover:text-white text-sm font-medium transition-colors">Trouver un talent</a></li>
                        <li><a href="{{ route('register') }}" class="text-white/50 hover:text-white text-sm font-medium transition-colors">Devenir talent</a></li>
                        <li><a href="{{ route('register') }}" class="text-white/50 hover:text-white text-sm font-medium transition-colors">S'inscrire comme client</a></li>
                        <li><a href="{{ route('login') }}"   class="text-white/50 hover:text-white text-sm font-medium transition-colors">Se connecter</a></li>
                    </ul>
                </div>

                {{-- Colonne 3 : Catégories --}}
                <div>
                    <p class="font-extrabold text-white text-xs uppercase tracking-widest mb-5">Catégories</p>
                    <ul class="space-y-3">
                        @foreach(['DJ', 'Musicien', 'Chanteur', 'Animateur', 'Danseur', 'Comédien'] as $cat)
                        <li>
                            <a href="{{ route('talents.index', ['category' => $cat]) }}"
                               class="text-white/50 hover:text-white text-sm font-medium transition-colors">{{ $cat }}</a>
                        </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Colonne 4 : Contact + paiements --}}
                <div>
                    <p class="font-extrabold text-white text-xs uppercase tracking-widest mb-5">Contact</p>
                    <ul class="space-y-3 mb-8">
                        <li class="text-white/50 text-sm font-medium">support@bookmi.ci</li>
                        <li class="text-white/50 text-sm font-medium">Abidjan, Côte d'Ivoire</li>
                    </ul>

                    <p class="text-white/30 text-xs uppercase tracking-widest mb-3">Paiements acceptés</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-3 py-1.5 rounded-lg text-xs font-bold text-white"
                              style="background:#FF6B35">Orange Money</span>
                        <span class="px-3 py-1.5 rounded-lg text-xs font-bold"
                              style="background:#FFCC00; color:#333">MTN MoMo</span>
                        <span class="px-3 py-1.5 rounded-lg text-xs font-bold text-white"
                              style="background:#1565C0">Wave</span>
                    </div>
                </div>
            </div>

            {{-- Barre du bas --}}
            <div class="border-t pt-6 flex flex-col sm:flex-row items-center justify-between gap-4"
                 style="border-color:rgba(255,255,255,0.07)">
                <p class="text-white/25 text-xs font-medium">© {{ date('Y') }} BookMi. Tous droits réservés.</p>
                <div class="flex gap-6">
                    <a href="#" class="text-white/25 hover:text-white/50 text-xs font-medium transition-colors">Confidentialité</a>
                    <a href="#" class="text-white/25 hover:text-white/50 text-xs font-medium transition-colors">Conditions d'utilisation</a>
                    <a href="#" class="text-white/25 hover:text-white/50 text-xs font-medium transition-colors">Support</a>
                </div>
            </div>
        </div>
    </footer>

    @livewireScripts
    @yield('scripts')
</body>
</html>
