@extends('layouts.talent')

@section('title', 'Packages — BookMi Talent')

@section('content')
<div class="space-y-6" x-data="{ showCreate: false, editId: null, editData: {} }">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-800 text-sm font-medium">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-800 text-sm font-medium">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Packages & Offres</h1>
            <p class="text-sm text-gray-500 mt-1">Gérez vos prestations et tarifs</p>
        </div>
        <button
            @click="showCreate = !showCreate"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
            style="background:#FF6B35">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nouveau package
        </button>
    </div>

    {{-- Form création inline --}}
    <div x-show="showCreate" x-cloak x-transition class="bg-white rounded-2xl border border-orange-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-orange-100 flex items-center justify-between" style="background:#fff8f5">
            <h2 class="text-base font-bold text-gray-900">Nouveau package</h2>
            <button type="button" @click="showCreate = false" class="text-gray-400 hover:text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('talent.packages.store') }}" class="p-6">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Nom du package *</label>
                    <input type="text" name="name" required maxlength="150" placeholder="Ex: Prestation 2h + DJ"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent"
                           value="{{ old('name') }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Description</label>
                    <textarea name="description" rows="3" maxlength="1000" placeholder="Décrivez ce que comprend ce package..."
                              class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent resize-none">{{ old('description') }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Cachet (FCFA) *</label>
                    <input type="number" name="cachet_amount" required min="0" placeholder="0"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent"
                           value="{{ old('cachet_amount') }}">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Durée (minutes)</label>
                    <input type="number" name="duration_minutes" min="0" placeholder="120"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent"
                           value="{{ old('duration_minutes') }}">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Type</label>
                    <input type="text" name="type" maxlength="50" placeholder="Ex: concert, mariage, corporate"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent"
                           value="{{ old('type') }}">
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-5">
                <button type="button" @click="showCreate = false"
                        class="px-5 py-2.5 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">
                    Annuler
                </button>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white hover:opacity-90 transition-opacity"
                        style="background:#FF6B35">
                    Créer le package
                </button>
            </div>
        </form>
    </div>

    {{-- Liste packages --}}
    @if($packages->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-center bg-white rounded-2xl border border-gray-100">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background:#fff3e0">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" stroke="#FF6B35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                </svg>
            </div>
            <p class="text-gray-700 font-semibold mb-1">Aucun package créé</p>
            <p class="text-gray-400 text-sm mb-5">Créez votre premier package pour proposer vos prestations.</p>
            <button @click="showCreate = true"
                    class="px-5 py-2 rounded-xl text-sm font-semibold text-white" style="background:#FF6B35">
                Créer un package
            </button>
        </div>
    @else
        <div class="space-y-3">
            @foreach($packages as $package)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 flex flex-col sm:flex-row sm:items-start gap-4">
                    {{-- Icône --}}
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center" style="background:#fff3e0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="#FF6B35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        </svg>
                    </div>

                    {{-- Infos --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-bold text-gray-900">{{ $package->name }}</h3>
                            @if(!$package->is_active)
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">Inactif</span>
                            @endif
                            @if($package->type)
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full text-orange-700" style="background:#fff3e0">{{ $package->type }}</span>
                            @endif
                        </div>
                        @if($package->description)
                            <p class="text-sm text-gray-500 mt-1 leading-relaxed">{{ $package->description }}</p>
                        @endif
                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                            @if($package->duration_minutes)
                                <span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="inline h-3.5 w-3.5 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    {{ $package->duration_minutes }} min
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Prix + actions --}}
                    <div class="flex flex-col items-start sm:items-end gap-3">
                        <span class="font-bold text-xl" style="color:#FF6B35">{{ number_format($package->cachet_amount, 0, ',', ' ') }} FCFA</span>
                        <div class="flex items-center gap-2">
                            <button
                                @click="editId = {{ $package->id }}; editData = {
                                    name: {{ json_encode($package->name) }},
                                    description: {{ json_encode($package->description ?? '') }},
                                    cachet_amount: {{ $package->cachet_amount }},
                                    duration_minutes: {{ $package->duration_minutes ?? 'null' }},
                                    is_active: {{ $package->is_active ? 'true' : 'false' }}
                                }"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium border border-gray-200 text-gray-600 hover:border-orange-300 hover:text-orange-600 transition-colors">
                                Modifier
                            </button>
                            <form method="POST" action="{{ route('talent.packages.destroy', $package->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="px-3 py-1.5 rounded-lg text-xs font-medium border border-red-100 text-red-500 hover:bg-red-50 transition-colors"
                                        onclick="return confirm('Supprimer ce package ?')">
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif

    {{-- Modal edition Alpine.js --}}
    <div
        x-show="editId !== null"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center"
        style="background:rgba(0,0,0,0.4)"
        @click.self="editId = null"
    >
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden" @click.stop>
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-gray-900">Modifier le package</h3>
                <button type="button" @click="editId = null" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form method="POST" :action="'/talent/packages/' + editId" class="p-6">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Nom du package *</label>
                        <input type="text" name="name" required maxlength="150"
                               x-model="editData.name"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Description</label>
                        <textarea name="description" rows="3" maxlength="1000"
                                  x-model="editData.description"
                                  class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 resize-none"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Cachet (FCFA) *</label>
                            <input type="number" name="cachet_amount" required min="0"
                                   x-model="editData.cachet_amount"
                                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Durée (min)</label>
                            <input type="number" name="duration_minutes" min="0"
                                   x-model="editData.duration_minutes"
                                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="is_active" id="is_active_edit" value="1"
                               x-model="editData.is_active"
                               class="w-4 h-4 rounded accent-orange-500">
                        <label for="is_active_edit" class="text-sm font-medium text-gray-700">Package actif</label>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-5">
                    <button type="button" @click="editId = null"
                            class="px-5 py-2.5 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">
                        Annuler
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white hover:opacity-90"
                            style="background:#FF6B35">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
