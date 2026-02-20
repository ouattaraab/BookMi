<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — BookMi</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center py-8" style="background:linear-gradient(135deg,#dbeafe 0%,#e8e4ff 30%,#ddf4ff 65%,#d1fae5 100%)">

    <div class="w-full max-w-md mx-4">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1">
                <span class="font-extrabold text-3xl tracking-tight" style="color:#1A2744">Book</span>
                <span class="font-extrabold text-3xl tracking-tight" style="color:#FF6B35">Mi</span>
            </a>
            <p class="text-gray-500 text-sm mt-2">Créez votre compte BookMi</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100" x-data="{ role: '{{ old('role', 'client') }}', showPass: false }">

            @if($errors->any())
                <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
                    <ul class="space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Role selector --}}
            <div class="grid grid-cols-2 gap-3 mb-6">
                <button
                    type="button"
                    @click="role = 'client'"
                    :class="role === 'client' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'"
                    class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1A2744" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <div class="text-center">
                        <p class="font-semibold text-sm text-gray-800">Je suis client</p>
                        <p class="text-xs text-gray-500">Je cherche des talents</p>
                    </div>
                </button>
                <button
                    type="button"
                    @click="role = 'talent'"
                    :class="role === 'talent' ? 'border-orange-400 bg-orange-50' : 'border-gray-200 hover:border-gray-300'"
                    class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#FF6B35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <div class="text-center">
                        <p class="font-semibold text-sm text-gray-800">Je suis talent</p>
                        <p class="text-xs text-gray-500">Je propose mes services</p>
                    </div>
                </button>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="role" :value="role">

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-gray-700">Prénom</label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" required
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Kofi">
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-gray-700">Nom</label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" required
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Asante">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Adresse email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="vous@exemple.com">
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Numéro de téléphone</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" required
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="+225 07 00 00 00 00">
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Mot de passe</label>
                    <div class="relative">
                        <input :type="showPass ? 'text' : 'password'" name="password" required minlength="8"
                            class="w-full px-3 py-2.5 pr-10 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="8 caractères minimum">
                        <button type="button" @click="showPass = !showPass" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg x-show="!showPass" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg x-show="showPass" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                        </button>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Confirmer le mot de passe</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="••••••••">
                </div>

                <button
                    type="submit"
                    class="w-full py-3 rounded-xl text-sm font-semibold text-white transition-all hover:opacity-90"
                    :style="role === 'talent' ? 'background:linear-gradient(135deg,#FF6B35,#C85A20)' : 'background:linear-gradient(135deg,#1A2744,#2563eb)'"
                >
                    Créer mon compte
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-6">
                Déjà un compte ?
                <a href="{{ route('login') }}" class="font-semibold hover:underline" style="color:#FF6B35">Se connecter</a>
            </p>
        </div>
    </div>

</body>
</html>
