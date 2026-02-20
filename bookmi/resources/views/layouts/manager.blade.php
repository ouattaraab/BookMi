<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'BookMi - Espace manager')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        body, button, input, select, textarea { font-family: 'Nunito', sans-serif; }
        @keyframes pageIn {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .page-content { animation: pageIn 0.52s cubic-bezier(0.16,1,0.3,1) both; }
    </style>
    @yield('head')
</head>
<body>
@php
    /** @var \App\Models\User $user */
    $user = auth()->user();
    $initials = mb_strtoupper(mb_substr($user->first_name, 0, 1) . mb_substr($user->last_name, 0, 1));
    $displayName = $user->first_name . ' ' . $user->last_name;
@endphp

<div
    class="flex h-screen"
    style="background: #EFF3FA"
    x-data="{ sidebarOpen: false }"
>
    {{-- Mobile overlay --}}
    <div
        x-show="sidebarOpen"
        @click="sidebarOpen = false"
        class="fixed inset-0 z-40 md:hidden"
        style="background:rgba(0,0,0,0.45)"
        x-cloak
    ></div>

    {{-- Sidebar --}}
    <aside
        class="fixed md:relative z-50 md:z-auto w-64 flex flex-col h-full transition-transform duration-300 ease-in-out"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
        style="background:linear-gradient(180deg,#1A2744 0%,#0F1E3A 100%);border-right:1px solid rgba(255,255,255,0.06)"
    >
        {{-- Logo --}}
        <div class="px-6 py-5 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-0.5">
                <span class="font-extrabold text-xl text-white tracking-tight leading-none">Book</span><span class="font-extrabold text-xl tracking-tight leading-none" style="color:#2196F3">Mi</span>
            </a>
            <button class="md:hidden text-white/70 hover:text-white" @click="sidebarOpen = false">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <div style="height:1px;background:rgba(255,255,255,0.10);margin:0 1rem"></div>

        {{-- Manager badge --}}
        <div class="px-6 py-2">
            <span class="text-xs font-semibold uppercase tracking-widest" style="color:rgba(33,150,243,0.75)">Manager</span>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
            @php
            $navItems = [
                ['route' => 'manager.talents',  'label' => 'Mes talents',   'icon' => 'users'],
                ['route' => 'manager.bookings', 'label' => 'Réservations',  'icon' => 'book-open'],
                ['route' => 'manager.messages', 'label' => 'Messages',      'icon' => 'chat-bubble-left'],
            ];
            @endphp

            @foreach($navItems as $item)
                @php
                    $href = route($item['route']);
                    $isActive = request()->routeIs($item['route']) || request()->routeIs($item['route'] . '.*');
                @endphp
                <a
                    href="{{ $href }}"
                    @click="sidebarOpen = false"
                    class="flex items-center gap-3 rounded-lg text-sm font-medium transition-all duration-150 {{ $isActive ? 'text-white font-semibold' : 'text-white/65 hover:text-white' }}"
                    style="{{ $isActive
                        ? 'background:linear-gradient(90deg,rgba(33,150,243,0.22),rgba(33,150,243,0.08));border-left:3px solid #2196F3;padding:0.625rem 0.75rem 0.625rem calc(0.75rem - 3px)'
                        : 'padding:0.625rem 0.75rem' }}"
                    @if(!$isActive)
                    onmouseover="this.style.background='rgba(255,255,255,0.06)'"
                    onmouseout="this.style.background=''"
                    @endif
                >
                    @include('partials.icons.nav', ['icon' => $item['icon'], 'active' => $isActive, 'color' => '#2196F3'])
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div style="height:1px;background:rgba(255,255,255,0.10);margin:0 1rem 0.75rem"></div>

        {{-- User + Logout --}}
        <div class="p-4 space-y-3">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                     style="background:rgba(33,150,243,0.22);border:1.5px solid rgba(33,150,243,0.4)">
                    {{ $initials }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white truncate">{{ $displayName }}</p>
                    <p class="text-xs truncate" style="color:rgba(255,255,255,0.45)">Manager</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150"
                    style="color:rgba(252,165,165,0.85);border:1px solid rgba(239,68,68,0.25);background:rgba(239,68,68,0.06)"
                    onmouseover="this.style.background='rgba(239,68,68,0.14)';this.style.borderColor='rgba(239,68,68,0.45)'"
                    onmouseout="this.style.background='rgba(239,68,68,0.06)';this.style.borderColor='rgba(239,68,68,0.25)'"
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
            style="background:rgba(255,255,255,0.85);backdrop-filter:blur(20px) saturate(180%);-webkit-backdrop-filter:blur(20px) saturate(180%);border-bottom:1px solid rgba(26,39,68,0.08);box-shadow:0 1px 8px rgba(26,39,68,0.06)"
        >
            <div class="flex items-center gap-3">
                <button class="md:hidden text-gray-600 hover:text-gray-900 p-1" @click="sidebarOpen = true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                </button>
                <div>
                    <h2 class="text-sm text-gray-500">
                        Espace manager — <span class="font-semibold text-gray-900">{{ $displayName }}</span>
                    </h2>
                </div>
            </div>

            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white"
                 style="background:linear-gradient(135deg,#1A2744,#2196F3)">
                {{ $initials }}
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 overflow-auto p-4 md:p-8 page-content">
            @if(session('success'))
                <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
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
