<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification 2FA — BookMi</title>
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
            <p class="text-gray-500 text-sm mt-2">Vérification en deux étapes</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">

            <div class="text-center mb-6">
                <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-3" style="background:#dbeafe">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#1A2744" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                </div>
                <h1 class="text-lg font-bold text-gray-900">Code de vérification</h1>
                @if($method === 'email')
                    <p class="text-sm text-gray-500 mt-1">Un code a été envoyé à votre adresse email. Valable 10 minutes.</p>
                @else
                    <p class="text-sm text-gray-500 mt-1">Entrez le code depuis votre application d'authentification.</p>
                @endif
            </div>

            @if($errors->any())
                <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('auth.2fa.verify') }}" class="space-y-5">
                @csrf

                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">
                        {{ $method === 'email' ? 'Code reçu par email' : 'Code de l\'application' }}
                    </label>
                    <input
                        type="text"
                        name="code"
                        inputmode="numeric"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        required
                        autofocus
                        autocomplete="one-time-code"
                        class="w-full px-3 py-4 border border-gray-200 rounded-xl text-center text-2xl tracking-[0.5em] font-mono font-bold focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="000000"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6)"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full py-3 rounded-xl text-sm font-semibold text-white transition-all hover:opacity-90"
                    style="background:linear-gradient(135deg,#1A2744,#2563eb)"
                >
                    Vérifier
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
