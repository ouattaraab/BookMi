<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié — BookMi</title>
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
            <p class="text-gray-500 text-sm mt-2">Réinitialisez votre mot de passe</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">

            @if(session('success'))
                <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">{{ $errors->first() }}</div>
            @endif

            <p class="text-sm text-gray-600 mb-5">
                Entrez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.
            </p>

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Adresse email</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="vous@exemple.com"
                    >
                </div>
                <button
                    type="submit"
                    class="w-full py-3 rounded-xl text-sm font-semibold text-white transition-all hover:opacity-90"
                    style="background:linear-gradient(135deg,#1A2744,#2563eb)"
                >
                    Envoyer le lien
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="{{ route('login') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center justify-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                    Retour à la connexion
                </a>
            </div>
        </div>
    </div>

</body>
</html>
