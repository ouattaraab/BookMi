<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation manager — BookMi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body, button, input, textarea { font-family: 'Nunito', sans-serif; }</style>
</head>
<body style="background:linear-gradient(135deg,#1A2744 0%,#0F1E3A 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1.5rem">

@php
    $talent = $invitation->talentProfile;
    $talentName = $talent?->stage_name ?? trim(($talent?->user?->first_name ?? '') . ' ' . ($talent?->user?->last_name ?? '')) ?: 'Un talent';
@endphp

<div class="w-full max-w-lg">

    {{-- Logo --}}
    <div class="text-center mb-8">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-0.5">
            <span class="font-extrabold text-2xl text-white tracking-tight">Book</span><span class="font-extrabold text-2xl tracking-tight" style="color:#2196F3">Mi</span>
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-2xl p-8">

        @if(session('success'))
        <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm">
            {{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        {{-- Talent card --}}
        <div class="flex items-center gap-4 mb-6 p-4 rounded-xl" style="background:#f0f6ff">
            <div class="w-14 h-14 rounded-xl flex items-center justify-center text-xl font-bold text-white flex-shrink-0"
                 style="background:linear-gradient(135deg,#1A2744,#2196F3)">
                {{ mb_strtoupper(mb_substr($talentName, 0, 1)) }}
            </div>
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-0.5">Invitation de</p>
                <p class="font-bold text-gray-900 text-lg leading-tight">{{ $talentName }}</p>
                @if($talent?->category)
                    <p class="text-sm text-gray-500">{{ $talent->category->name }}</p>
                @endif
            </div>
        </div>

        <h1 class="text-xl font-bold text-gray-900 mb-2">Invitation à devenir manager</h1>
        <p class="text-gray-500 text-sm mb-6">
            <strong>{{ $talentName }}</strong> vous invite à gérer son profil sur BookMi. En acceptant, vous pourrez gérer ses réservations, son calendrier et suivre ses statistiques.
        </p>

        <form method="POST" action="{{ route('manager.invitations.respond', $invitation->token) }}" x-data="{ action: '' }">
            @csrf

            {{-- Accept / Reject toggle --}}
            <div class="grid grid-cols-2 gap-3 mb-5">
                <label class="cursor-pointer">
                    <input type="radio" name="action" value="accept" class="sr-only" x-model="action" required>
                    <div :class="action === 'accept' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'"
                         class="rounded-xl border-2 p-4 text-center transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-1" :style="action === 'accept' ? 'color:#4CAF50' : 'color:#9ca3af'"><polyline points="20 6 9 17 4 12"/></svg>
                        <p class="text-sm font-semibold" :class="action === 'accept' ? 'text-green-700' : 'text-gray-500'">Accepter</p>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="action" value="reject" class="sr-only" x-model="action" required>
                    <div :class="action === 'reject' ? 'border-red-400 bg-red-50' : 'border-gray-200 hover:border-gray-300'"
                         class="rounded-xl border-2 p-4 text-center transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-1" :style="action === 'reject' ? 'color:#f44336' : 'color:#9ca3af'"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        <p class="text-sm font-semibold" :class="action === 'reject' ? 'text-red-600' : 'text-gray-500'">Refuser</p>
                    </div>
                </label>
            </div>

            {{-- Comment --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Commentaire
                    <span x-show="action === 'reject'" class="text-red-500"> *</span>
                    <span x-show="action !== 'reject'" class="text-gray-400">(optionnel)</span>
                </label>
                <textarea name="comment" rows="3" maxlength="500" :required="action === 'reject'"
                          class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-300 resize-none"
                          placeholder="Ajoutez un message…"></textarea>
            </div>

            <button type="submit" :disabled="!action"
                    class="w-full px-4 py-3 rounded-xl text-sm font-bold text-white transition-all disabled:opacity-50"
                    :style="action === 'reject' ? 'background:#f44336' : 'background:#2196F3'">
                Confirmer ma réponse
            </button>
        </form>

    </div>

    <p class="text-center text-xs mt-4" style="color:rgba(255,255,255,0.4)">
        BookMi — La plateforme de réservation d'artistes
    </p>
</div>

@if(!isset($alpineLoaded))
<script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endif

</body>
</html>
