<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'BookMi - Espace client')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        :root {
            --bg: #0D1117;
            --navy: #1A2744;
            --orange: #FF6B35;
            --blue: #2196F3;
            --blue-light: #64B5F6;
            --glass-bg: rgba(255,255,255,0.04);
            --glass-border: rgba(255,255,255,0.09);
            --glass-bg-hover: rgba(255,255,255,0.07);
            --text: rgba(255,255,255,0.92);
            --text-muted: rgba(255,255,255,0.50);
            --text-faint: rgba(255,255,255,0.30);
        }
        body, button, input, select, textarea { font-family: 'Nunito', sans-serif; }
        body { background: var(--bg); color: var(--text); }
        @keyframes pageIn {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .page-content { animation: pageIn 0.52s cubic-bezier(0.16,1,0.3,1) both; }

        /* ─── Shared dark glass card ─── */
        .glass-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 1rem;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        .glass-card-hover:hover {
            background: var(--glass-bg-hover);
            border-color: rgba(100,181,246,0.22);
            box-shadow: 0 8px 32px rgba(0,0,0,0.35), 0 0 0 1px rgba(100,181,246,0.12);
            transform: translateY(-2px);
        }
        /* ─── Dark inputs ─── */
        .dark-input {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.10);
            color: rgba(255,255,255,0.90);
            border-radius: 0.75rem;
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
            outline: none;
            transition: border-color 0.15s;
            width: 100%;
        }
        .dark-input::placeholder { color: rgba(255,255,255,0.30); }
        .dark-input:focus { border-color: var(--blue-light); box-shadow: 0 0 0 3px rgba(100,181,246,0.12); }
        /* ─── Status badges ─── */
        .badge-status {
            font-size: 0.7rem; font-weight: 700;
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            letter-spacing: 0.02em;
        }
        /* ─── Section header ─── */
        .section-title { font-size: 1.4rem; font-weight: 900; color: var(--text); }
        .section-sub { font-size: 0.8rem; color: var(--text-muted); font-weight: 600; margin-top: 0.15rem; }
    </style>
    @yield('head')
</head>
<body>
@php
    /** @var \App\Models\User $user */
    $user = auth()->user();
    $initials = mb_strtoupper(mb_substr($user->first_name, 0, 1) . mb_substr($user->last_name, 0, 1));
    $displayName = $user->first_name . ' ' . $user->last_name;
    $currentPath = request()->path();
@endphp

<div
    class="flex h-screen"
    style="background: #0D1117"
    x-data="{ sidebarOpen: false }"
>
    {{-- Mobile overlay --}}
    <div
        x-show="sidebarOpen"
        @click="sidebarOpen = false"
        class="fixed inset-0 z-40 md:hidden"
        style="background: rgba(0,0,0,0.45)"
        x-cloak
    ></div>

    {{-- Sidebar --}}
    <aside
        class="fixed md:relative z-50 md:z-auto w-64 flex flex-col h-full transition-transform duration-300 ease-in-out"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
        style="background: linear-gradient(180deg,#1A2744 0%,#0F1E3A 100%); border-right: 1px solid rgba(255,255,255,0.06)"
    >
        {{-- Logo --}}
        <div class="px-6 py-5 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-0.5">
                <span class="font-extrabold text-xl text-white tracking-tight leading-none">Book</span><span class="font-extrabold text-xl tracking-tight leading-none" style="color:#64B5F6">Mi</span>
            </a>
            <button class="md:hidden text-white/70 hover:text-white" @click="sidebarOpen = false">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <div style="height:1px; background:rgba(255,255,255,0.10); margin:0 1rem"></div>

        {{-- Espace client badge --}}
        <div class="px-6 py-2">
            <span class="text-xs font-semibold uppercase tracking-widest" style="color:rgba(100,181,246,0.75)">Espace client</span>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
            @php
            $navItems = [
                ['route' => 'client.dashboard',  'label' => 'Tableau de bord', 'icon' => 'squares-2x2'],
                ['href'  => '/talents',           'label' => 'Découvrir',       'icon' => 'magnifying-glass'],
                ['route' => 'client.bookings',    'label' => 'Mes réservations','icon' => 'book-open'],
                ['route' => 'client.favorites',   'label' => 'Favoris',         'icon' => 'heart'],
                ['route' => 'client.messages',    'label' => 'Messages',        'icon' => 'chat-bubble-left'],
                ['route' => 'client.settings',    'label' => 'Paramètres',      'icon' => 'cog'],
            ];
            @endphp

            @foreach($navItems as $item)
                @php
                    $href = isset($item['route']) ? route($item['route']) : $item['href'];
                    $isActive = isset($item['route'])
                        ? request()->routeIs($item['route']) || request()->routeIs($item['route'] . '.*')
                        : request()->is(ltrim($item['href'], '/'));
                @endphp
                <a
                    href="{{ $href }}"
                    @click="sidebarOpen = false"
                    class="flex items-center gap-3 rounded-lg text-sm font-medium transition-all duration-150 {{ $isActive ? 'text-white font-semibold' : 'text-white/65 hover:text-white' }}"
                    style="{{ $isActive
                        ? 'background:linear-gradient(90deg,rgba(100,181,246,0.22),rgba(100,181,246,0.08));border-left:3px solid #64B5F6;padding:0.625rem 0.75rem 0.625rem calc(0.75rem - 3px)'
                        : 'padding:0.625rem 0.75rem' }}"
                    @if(!$isActive)
                    onmouseover="this.style.background='rgba(255,255,255,0.06)'"
                    onmouseout="this.style.background=''"
                    @endif
                >
                    @include('partials.icons.nav', ['icon' => $item['icon'], 'active' => $isActive, 'color' => '#64B5F6'])
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div style="height:1px; background:rgba(255,255,255,0.10); margin:0 1rem 0.75rem"></div>

        {{-- User + Logout --}}
        <div class="p-4 space-y-3">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                     style="background:rgba(100,181,246,0.25);border:1.5px solid rgba(100,181,246,0.4)">
                    {{ $initials }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white truncate">{{ $displayName }}</p>
                    <p class="text-xs truncate" style="color:rgba(255,255,255,0.45)">Client</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all"
                    style="color:rgba(252,165,165,0.85);border:1px solid rgba(239,68,68,0.25);background:rgba(239,68,68,0.06)"
                    onmouseover="this.style.background='rgba(239,68,68,0.14)'"
                    onmouseout="this.style.background='rgba(239,68,68,0.06)'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                    Déconnexion
                </button>
            </form>
        </div>
    </aside>

    {{-- Main content --}}
    <div class="flex-1 flex flex-col overflow-hidden min-w-0">
        {{-- Header --}}
        <header
            class="flex-shrink-0 px-4 md:px-8 py-4 flex items-center justify-between"
            style="background:rgba(13,17,23,0.80);backdrop-filter:blur(20px) saturate(180%);-webkit-backdrop-filter:blur(20px) saturate(180%);border-bottom:1px solid rgba(255,255,255,0.07);box-shadow:0 1px 16px rgba(0,0,0,0.3)"
        >
            <div class="flex items-center gap-3">
                <button class="md:hidden p-1" style="color:rgba(255,255,255,0.65)" @click="sidebarOpen = true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                </button>
                <a href="{{ route('home') }}"
                   class="flex items-center gap-1.5 text-xs font-semibold rounded-full transition-all duration-150"
                   style="color:rgba(100,181,246,0.85);background:rgba(100,181,246,0.08);border:1px solid rgba(100,181,246,0.20);padding:0.35rem 0.85rem"
                   onmouseover="this.style.background='rgba(100,181,246,0.16)';this.style.borderColor='rgba(100,181,246,0.40)'"
                   onmouseout="this.style.background='rgba(100,181,246,0.08)';this.style.borderColor='rgba(100,181,246,0.20)'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Accueil
                </a>
                <div class="hidden md:block">
                    <h2 class="text-sm" style="color:rgba(255,255,255,0.45)">
                        Espace client — <span class="font-semibold" style="color:rgba(255,255,255,0.90)">{{ $displayName }}</span>
                    </h2>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <livewire:shared.notification-bell accent-color="#2196F3" />
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white"
                     style="background:linear-gradient(135deg,#1A2744,#64B5F6)">
                    {{ $initials }}
                </div>
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 overflow-auto p-4 md:p-8 page-content" style="background:#0D1117">
            @if(session('success'))
                <div class="mb-4 p-3 rounded-xl text-sm font-medium" style="background:rgba(76,175,80,0.12);border:1px solid rgba(76,175,80,0.25);color:rgba(134,239,172,0.95)">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-3 rounded-xl text-sm font-medium" style="background:rgba(244,67,54,0.12);border:1px solid rgba(244,67,54,0.25);color:rgba(252,165,165,0.95)">
                    {{ session('error') }}
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>

@livewireScripts
@yield('scripts')
</body>
</html>
