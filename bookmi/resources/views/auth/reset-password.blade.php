<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe — BookMi</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center" style="background:linear-gradient(135deg,#dbeafe 0%,#e8e4ff 30%,#ddf4ff 65%,#d1fae5 100%)">

    <div class="w-full max-w-sm mx-4">
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1">
                <span class="font-extrabold text-3xl tracking-tight" style="color:#1A2744">Book</span>
                <span class="font-extrabold text-3xl tracking-tight" style="color:#FF6B35">Mi</span>
            </a>
            <p class="text-gray-500 text-sm mt-2">Choisissez un nouveau mot de passe</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">

            @if($errors->any())
                <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Adresse email</label>
                    <input type="email" name="email" value="{{ request()->query('email', old('email')) }}" required
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="vous@exemple.com">
                </div>

                <div class="space-y-1" x-data="{ show: false }">
                    <label class="text-sm font-medium text-gray-700">Nouveau mot de passe</label>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" name="password" required minlength="8"
                            class="w-full px-3 py-2.5 pr-10 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="8 caractères minimum">
                        <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg x-show="show" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="2" x2="22" y1="2" y2="22"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/></svg>
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
                    style="background:linear-gradient(135deg,#1A2744,#2563eb)"
                >
                    Réinitialiser le mot de passe
                </button>
            </form>
        </div>
    </div>

</body>
</html>
