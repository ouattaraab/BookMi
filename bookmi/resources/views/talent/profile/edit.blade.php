@extends('layouts.talent')

@section('title', 'Mon profil — BookMi Talent')

@section('content')
<div class="space-y-6 max-w-2xl">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-800 text-sm font-medium">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-800 text-sm font-medium">{{ session('error') }}</div>
    @endif
    @if(session('info'))
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-blue-800 text-sm font-medium">{{ session('info') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-800 text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-black text-gray-900">Mon profil</h1>
        <p class="text-sm text-gray-400 mt-0.5 font-semibold">Informations visibles par les clients</p>
    </div>

    {{-- Photo --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex items-center gap-5">
        {{-- Avatar / Photo actuelle --}}
        @if($profile?->cover_photo_url)
            <img src="{{ $profile->cover_photo_url }}" alt="Photo de profil"
                 class="w-20 h-20 rounded-2xl object-cover flex-shrink-0"
                 style="box-shadow:0 4px 16px rgba(255,107,53,0.25)">
        @else
            <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-white font-bold text-2xl flex-shrink-0"
                 style="background:linear-gradient(135deg,#FF6B35,#C85A20)">
                {{ strtoupper(substr($user->first_name ?? 'T', 0, 1)) }}
            </div>
        @endif

        <div class="flex-1 min-w-0">
            <p class="font-semibold text-gray-900">{{ $profile->stage_name ?? ($user->first_name . ' ' . $user->last_name) }}</p>
            <p class="text-sm text-gray-400 mb-3">{{ $user->email }}</p>
            <form method="POST" action="{{ route('talent.profile.photo') }}" enctype="multipart/form-data" class="flex items-center gap-3 flex-wrap">
                @csrf
                <label class="cursor-pointer px-4 py-2 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:border-orange-300 hover:text-orange-600 transition-colors">
                    Changer la photo
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="hidden"
                           onchange="this.closest('form').submit()">
                </label>
                <span class="text-xs text-gray-400">JPG, PNG ou WebP · max 4 Mo</span>
            </form>
        </div>
    </div>

    {{-- Formulaire principal --}}
    <form method="POST" action="{{ route('talent.profile.update') }}" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        @csrf

        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-black text-gray-900">Informations personnelles</h2>
        </div>

        <div class="p-6 space-y-5">
            {{-- Nom / Prénom --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Prénom</label>
                    <input type="text" name="first_name"
                           value="{{ old('first_name', $user->first_name) }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Nom</label>
                    <input type="text" name="last_name"
                           value="{{ old('last_name', $user->last_name) }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent">
                </div>
            </div>

            {{-- Nom de scène --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Nom de scène *</label>
                <input type="text" name="stage_name" required maxlength="100"
                       value="{{ old('stage_name', $profile->stage_name ?? '') }}"
                       placeholder="Votre nom artistique"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent">
                <p class="text-xs text-gray-400 mt-1">C'est ce nom qui sera affiché aux clients.</p>
            </div>

            {{-- Catégorie --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Catégorie *</label>
                <select name="category_id" required
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent bg-white">
                    <option value="">Choisir votre catégorie artistique…</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}"
                            {{ old('category_id', $profile->category_id ?? '') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Détermine dans quelle section vous apparaissez.</p>
            </div>

            {{-- Bio --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Biographie</label>
                <textarea name="bio" rows="5" maxlength="2000"
                          placeholder="Parlez de vous, de votre style, de votre expérience..."
                          class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent resize-none">{{ old('bio', $profile->bio ?? '') }}</textarea>
                <p class="text-xs text-gray-400 mt-1">{{ mb_strlen(old('bio', $profile->bio ?? '')) }}/2000 caractères</p>
            </div>

            {{-- Ville + Cachet --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Ville</label>
                    <input type="text" name="city" maxlength="100"
                           value="{{ old('city', $profile->city ?? '') }}"
                           placeholder="Abidjan, Dakar..."
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Cachet de base (FCFA)</label>
                    <div class="relative">
                        <input type="number" name="cachet_amount" min="0" max="99999999"
                               value="{{ old('cachet_amount', $profile->cachet_amount ?? 0) }}"
                               placeholder="0"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pr-16 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium">FCFA</span>
                    </div>
                </div>
            </div>

            {{-- Liens sociaux --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-2">Réseaux sociaux</label>
                <div class="space-y-3">
                    @foreach([
                        'instagram' => ['label'=>'Instagram','placeholder'=>'https://instagram.com/votre_compte','color'=>'#E1306C'],
                        'facebook'  => ['label'=>'Facebook', 'placeholder'=>'https://facebook.com/votre_page',  'color'=>'#1877F2'],
                        'youtube'   => ['label'=>'YouTube',  'placeholder'=>'https://youtube.com/@votre_chaine','color'=>'#FF0000'],
                        'tiktok'    => ['label'=>'TikTok',   'placeholder'=>'https://tiktok.com/@votre_compte', 'color'=>'#000000'],
                        'twitter'   => ['label'=>'X / Twitter','placeholder'=>'https://x.com/votre_compte',    'color'=>'#000000'],
                    ] as $key => $meta)
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold w-20 flex-shrink-0" style="color:{{ $meta['color'] }}">{{ $meta['label'] }}</span>
                        <input type="url" name="social_links[{{ $key }}]"
                               value="{{ old('social_links.'.$key, ($profile->social_links[$key] ?? '')) }}"
                               placeholder="{{ $meta['placeholder'] }}"
                               class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent">
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Email (readonly) --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Email</label>
                <input type="email" value="{{ $user->email }}" disabled
                       class="w-full border border-gray-100 rounded-xl px-4 py-2.5 text-sm bg-gray-50 text-gray-400 cursor-not-allowed">
                <p class="text-xs text-gray-400 mt-1">L'email ne peut pas être modifié ici.</p>
            </div>

            {{-- Téléphone (readonly) --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Téléphone</label>
                <input type="text" value="{{ $user->phone ?? '—' }}" disabled
                       class="w-full border border-gray-100 rounded-xl px-4 py-2.5 text-sm bg-gray-50 text-gray-400 cursor-not-allowed">
            </div>
        </div>

        {{-- Statut profil --}}
        @if($profile)
        <div class="px-6 pb-4">
            <div class="flex items-center gap-3 p-4 rounded-xl" style="background:#fff3e0">
                @if($profile->is_verified)
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="#4CAF50"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span class="text-sm font-medium text-green-800">Profil vérifié</span>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="#FF9800"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <div class="flex-1">
                        <span class="text-sm font-medium text-orange-800">Profil non vérifié</span>
                        <p class="text-xs text-orange-600 mt-0.5">
                            <a href="{{ route('talent.verification') }}" class="underline">Soumettre un document</a> pour obtenir la vérification.
                        </p>
                    </div>
                @endif
            </div>
        </div>
        @endif

        <div class="px-6 pb-6 flex justify-end">
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl text-sm font-semibold text-white hover:opacity-90 transition-opacity"
                    style="background:#FF6B35">
                Enregistrer les modifications
            </button>
        </div>
    </form>

</div>
@endsection
