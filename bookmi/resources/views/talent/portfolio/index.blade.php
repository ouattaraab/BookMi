@extends('layouts.talent')

@section('title', 'Portfolio — BookMi Talent')

@section('content')
<div class="space-y-6" x-data="{ tab: 'upload' }">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-800 text-sm font-medium">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-800 text-sm font-medium">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-black text-gray-900">Portfolio</h1>
        <p class="text-sm text-gray-400 mt-0.5 font-semibold">Présentez votre travail aux clients</p>
    </div>

    {{-- Formulaires ajout --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        {{-- Tabs --}}
        <div class="flex border-b border-gray-100">
            <button
                @click="tab = 'upload'"
                class="flex-1 px-4 py-3 text-sm font-semibold transition-colors"
                :class="tab === 'upload' ? 'text-orange-600 border-b-2 border-orange-500 bg-orange-50/50' : 'text-gray-500 hover:text-gray-700'">
                <svg xmlns="http://www.w3.org/2000/svg" class="inline h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                Uploader un fichier
            </button>
            <button
                @click="tab = 'link'"
                class="flex-1 px-4 py-3 text-sm font-semibold transition-colors"
                :class="tab === 'link' ? 'text-orange-600 border-b-2 border-orange-500 bg-orange-50/50' : 'text-gray-500 hover:text-gray-700'">
                <svg xmlns="http://www.w3.org/2000/svg" class="inline h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                Ajouter un lien
            </button>
        </div>

        {{-- Form upload --}}
        <div x-show="tab === 'upload'" class="p-6">
            <form method="POST" action="{{ route('talent.portfolio.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Fichier (image ou vidéo) *</label>
                        <div class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:border-orange-300 transition-colors"
                             x-data="{ fileName: '' }">
                            <input type="file" name="file" required accept=".jpg,.jpeg,.png,.gif,.mp4,.mov" id="portfolio-file"
                                   class="hidden"
                                   @change="fileName = $event.target.files[0]?.name || ''">
                            <label for="portfolio-file" class="cursor-pointer block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span x-text="fileName || 'Cliquez pour sélectionner'" class="text-sm text-gray-500"></span>
                                <p class="text-xs text-gray-400 mt-1">JPG, PNG, GIF, MP4, MOV — max 50 Mo</p>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Légende (optionnel)</label>
                        <input type="text" name="caption" maxlength="255" placeholder="Décrivez ce média..."
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                    </div>
                    <div class="flex justify-end">
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white hover:opacity-90 transition-opacity"
                                style="background:#FF6B35">
                            Uploader
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Form lien --}}
        <div x-show="tab === 'link'" x-cloak class="p-6">
            <form method="POST" action="{{ route('talent.portfolio.link') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">URL du lien *</label>
                        <input type="url" name="link_url" required maxlength="500" placeholder="https://youtube.com/watch?v=..."
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Plateforme</label>
                        <select name="link_platform"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 bg-white">
                            <option value="other">Autre</option>
                            <option value="youtube">YouTube</option>
                            <option value="instagram">Instagram</option>
                            <option value="tiktok">TikTok</option>
                            <option value="facebook">Facebook</option>
                            <option value="soundcloud">SoundCloud</option>
                            <option value="spotify">Spotify</option>
                            <option value="vimeo">Vimeo</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Légende (optionnel)</label>
                        <input type="text" name="caption" maxlength="255" placeholder="Décrivez ce lien..."
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                    </div>
                    <div class="flex justify-end">
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white hover:opacity-90 transition-opacity"
                                style="background:#FF6B35">
                            Ajouter le lien
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Grille portfolio --}}
    @if($items->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-center bg-white rounded-2xl border border-gray-100">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background:#fff3e0">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" stroke="#FF6B35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
                </svg>
            </div>
            <p class="text-gray-700 font-semibold mb-1">Portfolio vide</p>
            <p class="text-gray-400 text-sm">Ajoutez des photos, vidéos ou liens pour présenter votre travail.</p>
        </div>
    @else
        <div>
            <h2 class="text-base font-black text-gray-900 mb-3">Vos médias ({{ $items->count() }})</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                @foreach($items as $item)
                    @php
                        $mt = $item->media_type instanceof \BackedEnum ? $item->media_type->value : (string) $item->media_type;
                    @endphp
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden group relative">
                        {{-- Aperçu --}}
                        @if($mt === 'image' && $item->original_path)
                            <div class="aspect-square overflow-hidden bg-gray-100">
                                <img src="{{ Storage::url($item->original_path) }}" alt="{{ $item->caption }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            </div>
                        @elseif($mt === 'video' && $item->original_path)
                            <div class="aspect-square overflow-hidden bg-gray-900 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @elseif($mt === 'link')
                            @php
                                $platformColors = ['youtube' => '#FF0000', 'instagram' => '#E1306C', 'tiktok' => '#000000', 'facebook' => '#1877F2', 'soundcloud' => '#FF3300', 'spotify' => '#1DB954', 'vimeo' => '#1AB7EA', 'other' => '#6b7280'];
                                $lp = $item->link_platform ?? 'other';
                                $pc = $platformColors[$lp] ?? '#6b7280';
                            @endphp
                            <a href="{{ $item->link_url }}" target="_blank" rel="noopener">
                                <div class="aspect-square flex flex-col items-center justify-center" style="background:{{ $pc }}15">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center mb-2" style="background:{{ $pc }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </div>
                                    <span class="text-xs font-semibold capitalize" style="color:{{ $pc }}">{{ $lp }}</span>
                                </div>
                            </a>
                        @endif

                        {{-- Caption --}}
                        @if($item->caption)
                        <div class="px-3 py-2">
                            <p class="text-xs text-gray-500 truncate">{{ $item->caption }}</p>
                        </div>
                        @endif

                        {{-- Bouton supprimer --}}
                        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <form method="POST" action="{{ route('talent.portfolio.destroy', $item->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-7 h-7 rounded-full bg-red-500 text-white flex items-center justify-center shadow-md hover:bg-red-600 transition-colors"
                                        onclick="return confirm('Supprimer cet élément ?')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </form>
                        </div>

                        {{-- Badge type --}}
                        <div class="absolute top-2 left-2">
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full text-white"
                                  style="background:{{ $mt === 'image' ? '#4CAF50' : ($mt === 'video' ? '#2196F3' : '#FF6B35') }}">
                                {{ strtoupper($mt) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
