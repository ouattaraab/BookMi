<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification téléphone — BookMi</title>
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
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
            <div class="text-center mb-6">
                <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-3" style="background:#fff3e0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#FF6B35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.45A2 2 0 0 1 3.58 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.56a16 16 0 0 0 6.27 6.27l1.06-1.06a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </div>
                <h1 class="text-lg font-bold text-gray-900">Vérifiez votre téléphone</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Nous avons envoyé un code SMS au <span class="font-semibold">{{ $phone ?? 'votre numéro' }}</span>
                </p>
            </div>

            @if(session('success'))
                <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('auth.verify-phone') }}" class="space-y-5">
                @csrf
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Code OTP</label>
                    <input
                        type="text"
                        name="code"
                        inputmode="numeric"
                        maxlength="6"
                        required
                        autofocus
                        autocomplete="one-time-code"
                        class="w-full px-3 py-4 border border-gray-200 rounded-xl text-center text-2xl tracking-[0.5em] font-mono font-bold focus:outline-none focus:ring-2 focus:ring-orange-400"
                        placeholder="000000"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6)"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full py-3 rounded-xl text-sm font-semibold text-white transition-all hover:opacity-90"
                    style="background:linear-gradient(135deg,#FF6B35,#C85A20)"
                >
                    Vérifier mon numéro
                </button>
            </form>

            <form method="POST" action="{{ route('auth.verify-phone.resend') }}" class="mt-3">
                @csrf
                <button type="submit" class="w-full text-sm text-gray-500 hover:text-gray-700 py-2">
                    Renvoyer le code
                </button>
            </form>
        </div>
    </div>

</body>
</html>
