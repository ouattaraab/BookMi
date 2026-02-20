<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'BookMi - Réservez vos talents')</title>
    <meta name="description" content="@yield('meta_description', 'BookMi - La plateforme de réservation de talents en Côte d\'Ivoire')">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @yield('head')
</head>
<body class="bg-white min-h-screen flex flex-col">

    {{-- Navigation publique --}}
    <nav style="background: linear-gradient(180deg,#1A2744 0%,#0F1E3A 100%);" class="sticky top-0 z-50 border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-1">
                <span class="font-extrabold text-2xl text-white tracking-tight leading-none">Book</span>
                <span class="font-extrabold text-2xl tracking-tight leading-none" style="color:#64B5F6">Mi</span>
            </a>

            <div class="hidden md:flex items-center gap-6 text-sm font-medium text-white/75">
                <a href="{{ route('talents.index') }}" class="hover:text-white transition-colors">Découvrir les talents</a>
                <a href="#how-it-works" class="hover:text-white transition-colors">Comment ça marche</a>
            </div>

            <div class="flex items-center gap-3">
                @auth
                    @php $user = auth()->user(); @endphp
                    @if($user->hasRole('client'))
                        <a href="{{ route('client.dashboard') }}" class="px-4 py-2 rounded-xl text-sm font-semibold text-white transition-colors hover:bg-white/10">Mon espace</a>
                    @elseif($user->hasRole('talent'))
                        <a href="{{ route('talent.dashboard') }}" class="px-4 py-2 rounded-xl text-sm font-semibold text-white transition-colors hover:bg-white/10">Mon espace</a>
                    @elseif($user->hasRole('manager'))
                        <a href="{{ route('manager.dashboard') }}" class="px-4 py-2 rounded-xl text-sm font-semibold text-white transition-colors hover:bg-white/10">Mon espace</a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl text-sm font-medium text-white/80 hover:text-white transition-colors">Connexion</a>
                    <a href="{{ route('register') }}" class="px-4 py-2 rounded-xl text-sm font-semibold text-white" style="background: #FF6B35;">Inscription</a>
                @endauth
            </div>
        </div>
    </nav>

    {{-- Contenu principal --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer style="background: #0F1E3A;" class="text-white/60 text-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <div class="flex items-center gap-1 mb-3">
                        <span class="font-extrabold text-xl text-white">Book</span>
                        <span class="font-extrabold text-xl" style="color:#64B5F6">Mi</span>
                    </div>
                    <p class="text-sm text-white/50">La plateforme de réservation de talents en Côte d'Ivoire.</p>
                </div>
                <div>
                    <p class="font-semibold text-white mb-3">Liens rapides</p>
                    <ul class="space-y-2">
                        <li><a href="{{ route('talents.index') }}" class="hover:text-white transition-colors">Découvrir les talents</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-white transition-colors">Créer un compte</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-white transition-colors">Se connecter</a></li>
                    </ul>
                </div>
                <div>
                    <p class="font-semibold text-white mb-3">Contact</p>
                    <p class="text-white/50">support@bookmi.ci</p>
                </div>
            </div>
            <div class="border-t border-white/10 mt-8 pt-6 text-center text-white/30 text-xs">
                © {{ date('Y') }} BookMi. Tous droits réservés.
            </div>
        </div>
    </footer>

    @livewireScripts
    @yield('scripts')
</body>
</html>
