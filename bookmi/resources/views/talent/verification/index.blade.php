@extends('layouts.talent')

@section('title', 'Vérification identité — BookMi Talent')

@section('content')
<div class="space-y-6 max-w-2xl">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-800 text-sm font-medium">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-800 text-sm font-medium">{{ session('error') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-800 text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-black text-gray-900">Vérification d'identité</h1>
        <p class="text-sm text-gray-400 mt-0.5 font-semibold">Soumettez un document officiel pour obtenir le badge vérifié</p>
    </div>

    {{-- Statut global --}}
    @php
        $approved = $verifications->firstWhere(fn($v) => ($v->verification_status instanceof \BackedEnum ? $v->verification_status->value : (string) $v->verification_status) === 'approved');
        $pending  = $verifications->firstWhere(fn($v) => ($v->verification_status instanceof \BackedEnum ? $v->verification_status->value : (string) $v->verification_status) === 'pending');
    @endphp

    @if($approved)
    <div class="rounded-2xl p-6 flex items-center gap-4" style="background:#f0fdf4; border:1px solid #bbf7d0">
        <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0" style="background:#4CAF50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        </div>
        <div>
            <p class="font-bold text-green-900">Identité vérifiée !</p>
            <p class="text-sm text-green-700 mt-0.5">Votre document a été validé. Vous bénéficiez du badge "Vérifié" sur votre profil.</p>
        </div>
    </div>
    @elseif($pending)
    <div class="rounded-2xl p-6 flex items-center gap-4" style="background:#fffbeb; border:1px solid #fde68a">
        <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0" style="background:#FF9800">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="font-bold text-yellow-900">Vérification en cours</p>
            <p class="text-sm text-yellow-700 mt-0.5">Votre document est en cours d'examen. Délai : 24-48h.</p>
        </div>
    </div>
    @else
    {{-- Formulaire soumission --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-black text-gray-900">Soumettre un document</h2>
            <p class="text-xs text-gray-400 mt-1">Formats acceptés : JPG, PNG, PDF — max 10 Mo</p>
        </div>
        <form method="POST" action="{{ route('talent.verification.submit') }}" enctype="multipart/form-data" class="p-6 space-y-5">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Type de document *</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach([
                        ['value' => 'id_card',        'label' => "Carte d'identité",    'icon' => 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2'],
                        ['value' => 'passport',       'label' => 'Passeport',            'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ['value' => 'driver_license', 'label' => 'Permis de conduire',   'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                    ] as $doc)
                    <label class="relative border-2 rounded-xl p-4 cursor-pointer text-center hover:border-orange-400 transition-colors"
                           x-data="{ selected: false }"
                           :class="$refs.docInput{{ $loop->index }}.checked ? 'border-orange-400 bg-orange-50' : 'border-gray-200'">
                        <input type="radio" name="document_type" value="{{ $doc['value'] }}" required
                               class="sr-only peer"
                               x-ref="docInput{{ $loop->index }}">
                        <div class="peer-checked:block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto mb-2 text-gray-400 peer-checked:text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $doc['icon'] }}"/>
                            </svg>
                            <span class="text-sm font-medium text-gray-700">{{ $doc['label'] }}</span>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Document *</label>
                <div class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:border-orange-300 transition-colors"
                     x-data="{ fileName: '' }">
                    <input type="file" name="document" required accept=".jpg,.jpeg,.png,.pdf" id="doc-file"
                           class="hidden"
                           @change="fileName = $event.target.files[0]?.name || ''">
                    <label for="doc-file" class="cursor-pointer block">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span x-text="fileName || 'Cliquez pour sélectionner'" class="text-sm text-gray-500"></span>
                        <p class="text-xs text-gray-400 mt-1">JPG, PNG ou PDF — max 10 Mo</p>
                    </label>
                </div>
            </div>

            <div class="p-4 rounded-xl text-xs text-orange-800" style="background:#fff3e0">
                <p class="font-semibold mb-1">Information confidentialité</p>
                <p>Vos documents sont stockés de manière sécurisée et ne seront utilisés qu'à des fins de vérification d'identité.</p>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl text-sm font-semibold text-white hover:opacity-90 transition-opacity"
                        style="background:#FF6B35">
                    Soumettre le document
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- Historique vérifications --}}
    @if($verifications->count() > 0)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-black text-gray-900">Historique des soumissions</h2>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($verifications as $v)
                @php
                    $vs = $v->verification_status instanceof \BackedEnum ? $v->verification_status->value : (string) $v->verification_status;
                    $vc = ['approved' => '#4CAF50', 'pending' => '#FF9800', 'rejected' => '#f44336'][$vs] ?? '#6b7280';
                    $vl = ['approved' => 'Approuvé', 'pending' => 'En attente', 'rejected' => 'Rejeté'][$vs] ?? $vs;
                    $dtLabel = ['id_card' => "Carte d'identité", 'passport' => 'Passeport', 'driver_license' => 'Permis de conduire'][$v->document_type] ?? $v->document_type;
                @endphp
                <div class="px-6 py-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $dtLabel }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">Soumis le {{ $v->created_at->format('d/m/Y à H:i') }}</p>
                        @if($v->rejection_reason && $vs === 'rejected')
                            <p class="text-xs text-red-600 mt-1">Motif : {{ $v->rejection_reason }}</p>
                        @endif
                        @if($v->reviewed_at && $vs !== 'pending')
                            <p class="text-xs text-gray-400">Examiné le {{ $v->reviewed_at->format('d/m/Y') }}</p>
                        @endif
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full flex-shrink-0" style="background:{{ $vc }}20; color:{{ $vc }}">{{ $vl }}</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
