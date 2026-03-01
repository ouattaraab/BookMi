@extends('layouts.client')

@section('title', 'Vérification identité — BookMi Client')

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
        <p class="text-sm text-gray-400 mt-0.5 font-semibold">Soumettez un document officiel pour obtenir le badge "Client vérifié"</p>
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
            <p class="text-sm text-green-700 mt-0.5">Votre document a été validé. Vous bénéficiez du badge "Client vérifié" sur BookMi.</p>
        </div>
    </div>
    @elseif($pending)
    <div class="rounded-2xl p-6 flex items-center gap-4" style="background:#fffbeb; border:1px solid #fde68a">
        <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0" style="background:#FF9800">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="font-bold text-yellow-900">Vérification en cours</p>
            <p class="text-sm text-yellow-700 mt-0.5">Votre document est en cours d'examen. Délai estimé : 24-48h.</p>
        </div>
    </div>
    @else
    {{-- Formulaire soumission --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-black text-gray-900">Soumettre un document</h2>
            <p class="text-xs text-gray-400 mt-1">Formats acceptés : JPG, PNG, PDF — max 10 Mo</p>
        </div>
        <form method="POST" action="{{ route('client.verification.submit') }}" enctype="multipart/form-data" class="p-6 space-y-5">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Type de document *</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach([
                        ['value' => 'id_card',        'label' => "Carte d'identité"],
                        ['value' => 'passport',       'label' => 'Passeport'],
                        ['value' => 'driver_license', 'label' => 'Permis de conduire'],
                    ] as $doc)
                    <label class="relative border-2 rounded-xl p-4 cursor-pointer text-center hover:border-blue-400 transition-colors has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                        <input type="radio" name="document_type" value="{{ $doc['value'] }}" required class="sr-only">
                        <span class="text-sm font-semibold text-gray-700">{{ $doc['label'] }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Document *</label>
                <input type="file" name="document" accept=".jpg,.jpeg,.png,.pdf" required
                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <p class="text-xs text-gray-400 mt-1.5">Photo lisible, sans reflet. L'image doit être récente et non expirée.</p>
            </div>
            <button type="submit" class="w-full py-3 rounded-xl font-bold text-white text-sm transition-opacity hover:opacity-90" style="background:#2196F3">
                Soumettre le document
            </button>
        </form>
    </div>
    @endif

    {{-- Historique --}}
    @if($verifications->isNotEmpty())
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-black text-gray-900">Historique des soumissions</h2>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($verifications as $v)
            @php
                $vs = $v->verification_status instanceof \BackedEnum ? $v->verification_status->value : (string) $v->verification_status;
                $stColors = ['pending'=>['bg'=>'#fffbeb','text'=>'#92400e','label'=>'En attente'], 'approved'=>['bg'=>'#f0fdf4','text'=>'#166534','label'=>'Approuvé'], 'rejected'=>['bg'=>'#fef2f2','text'=>'#991b1b','label'=>'Rejeté']];
                $sc = $stColors[$vs] ?? ['bg'=>'#f9fafb','text'=>'#374151','label'=>$vs];
                $docLabels = ['id_card'=>"Carte d'identité",'passport'=>'Passeport','driver_license'=>'Permis de conduire'];
            @endphp
            <div class="px-6 py-4 flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ $docLabels[$v->document_type] ?? $v->document_type }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $v->created_at?->format('d/m/Y à H:i') }}</p>
                    @if($v->rejection_reason)
                        <p class="text-xs text-red-600 mt-1">Motif : {{ $v->rejection_reason }}</p>
                    @endif
                </div>
                <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold" style="background:{{ $sc['bg'] }};color:{{ $sc['text'] }}">
                    {{ $sc['label'] }}
                </span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Avantages --}}
    <div class="rounded-2xl p-5" style="background:#EFF6FF;border:1px solid #BFDBFE">
        <h3 class="text-sm font-bold text-blue-900 mb-3">Avantages du badge "Client vérifié"</h3>
        <ul class="space-y-2 text-sm text-blue-800">
            <li class="flex items-start gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Badge visible sur vos réservations</li>
            <li class="flex items-start gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Confiance renforcée auprès des talents</li>
            <li class="flex items-start gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Priorité de traitement en cas de litige</li>
        </ul>
    </div>
</div>
@endsection
