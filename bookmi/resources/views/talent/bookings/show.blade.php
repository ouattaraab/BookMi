@extends('layouts.talent')

@section('title', 'Détail réservation — BookMi Talent')

@section('content')
<div class="space-y-6 max-w-2xl">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-800 text-sm font-medium">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-800 text-sm font-medium">{{ session('error') }}</div>
    @endif

    {{-- Back --}}
    <div>
        <a href="{{ route('talent.bookings') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-orange-600 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Retour aux réservations
        </a>
    </div>

    @php
        $sk = $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status;
        $sc = ['pending'=>'#FF9800','accepted'=>'#2196F3','paid'=>'#00BCD4','confirmed'=>'#4CAF50','completed'=>'#9C27B0','cancelled'=>'#f44336','disputed'=>'#FF5722'][$sk] ?? '#6b7280';
        $sl = ['pending'=>'En attente','accepted'=>'Acceptée','paid'=>'Payée','confirmed'=>'Confirmée','completed'=>'Terminée','cancelled'=>'Annulée','disputed'=>'En litige'][$sk] ?? $sk;
    @endphp

    {{-- Card principale --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        {{-- Header card --}}
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-gray-900">Réservation #{{ $booking->id }}</h1>
                <p class="text-xs text-gray-400 mt-0.5">Créée le {{ $booking->created_at->format('d/m/Y à H:i') }}</p>
            </div>
            <span class="text-sm font-semibold px-3 py-1.5 rounded-full" style="background:{{ $sc }}20; color:{{ $sc }}">{{ $sl }}</span>
        </div>

        <div class="p-6 space-y-5">
            {{-- Client --}}
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg flex-shrink-0" style="background:#FF6B35">
                    {{ strtoupper(substr($booking->client->first_name ?? 'C', 0, 1)) }}
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-0.5">Client</p>
                    <p class="font-semibold text-gray-900">{{ $booking->client->first_name ?? '—' }} {{ $booking->client->last_name ?? '' }}</p>
                    <p class="text-sm text-gray-500">{{ $booking->client->email ?? '' }}</p>
                    @if($booking->client->phone)
                        <p class="text-sm text-gray-500">{{ $booking->client->phone }}</p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Date événement --}}
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Date de l'événement</p>
                    <p class="font-semibold text-gray-900">{{ \Carbon\Carbon::parse($booking->event_date)->format('d/m/Y') }}</p>
                    @if($booking->start_time)
                        <p class="text-sm font-bold mt-1" style="color:#FF6B35;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="inline h-3.5 w-3.5 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-width="2" stroke-linecap="round" d="M12 6v6l4 2"/></svg>
                            {{ \Carbon\Carbon::createFromTimeString($booking->start_time)->format('H\hi') }}
                        </p>
                    @endif
                </div>

                {{-- Lieu --}}
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Lieu</p>
                    <p class="font-semibold text-gray-900">{{ $booking->event_location ?: '—' }}</p>
                </div>

                {{-- Package --}}
                @if($booking->servicePackage)
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Package</p>
                    <p class="font-semibold text-gray-900">{{ $booking->servicePackage->name }}</p>
                </div>
                @endif

                {{-- Cachet --}}
                <div class="rounded-xl p-4" style="background:#fff3e0">
                    <p class="text-xs uppercase tracking-wider mb-1" style="color:#C85A20">Cachet</p>
                    <p class="font-bold text-lg" style="color:#FF6B35">{{ number_format($booking->cachet_amount, 0, ',', ' ') }} FCFA</p>
                </div>

                {{-- Total --}}
                @if($booking->total_amount)
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Total client</p>
                    <p class="font-semibold text-gray-900">{{ number_format($booking->total_amount, 0, ',', ' ') }} FCFA</p>
                </div>
                @endif

                {{-- Commission --}}
                @if($booking->commission_amount)
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Commission BookMi</p>
                    <p class="font-semibold text-gray-900">{{ number_format($booking->commission_amount, 0, ',', ' ') }} FCFA</p>
                </div>
                @endif
            </div>

            {{-- Message --}}
            @if($booking->message)
            <div class="bg-gray-50 rounded-xl p-4">
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Message du client</p>
                <p class="text-gray-700 text-sm leading-relaxed">{{ $booking->message }}</p>
            </div>
            @endif
        </div>

        {{-- Commentaire d'acceptation affiché si déjà acceptée --}}
        @if($sk !== 'pending' && $booking->accept_comment)
        <div class="px-6 pb-4">
            <div class="rounded-xl p-4" style="background:#f0fdf4; border:1px solid #bbf7d0">
                <p class="text-xs uppercase tracking-wider mb-2" style="color:#15803d">Votre commentaire d'acceptation</p>
                <p class="text-sm text-gray-700 leading-relaxed">{{ $booking->accept_comment }}</p>
            </div>
        </div>
        @endif

        {{-- Timeline suivi jour-J (lecture seule) --}}
        @if(in_array($sk, ['paid', 'confirmed', 'completed']) && $booking->trackingEvents->isNotEmpty())
        <div class="px-6 pb-4">
            <div style="background:#f9fafb;border-radius:14px;padding:16px 20px;">
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-4">Suivi de la prestation</p>
                @foreach($booking->trackingEvents as $event)
                @php $isLast = $loop->last; @endphp
                <div style="display:flex;gap:12px;{{ $isLast ? '' : 'margin-bottom:4px;' }}">
                    <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;">
                        <div style="width:10px;height:10px;border-radius:50%;background:{{ $isLast ? '#15803D' : '#FF6B35' }};flex-shrink:0;"></div>
                        @if(!$isLast)
                        <div style="width:2px;flex:1;background:#e5e7eb;margin:3px 0;min-height:24px;"></div>
                        @endif
                    </div>
                    <div style="padding-bottom:{{ $isLast ? '0' : '16px' }};">
                        <p class="text-sm font-semibold text-gray-900" style="margin:0 0 2px;">
                            {{ $event->status instanceof \App\Enums\TrackingStatus ? $event->status->label() : (string) $event->status }}
                        </p>
                        <p class="text-xs text-gray-400" style="margin:0;">{{ $event->occurred_at->format('d/m H:i') }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Actions --}}
        @if(in_array($sk, ['pending', 'accepted', 'confirmed']))
        <div class="px-6 py-4 border-t border-gray-100">
            @if($sk === 'pending')
                {{-- Formulaire acceptation avec commentaire obligatoire --}}
                <form method="POST" action="{{ route('talent.bookings.accept', $booking->id) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="accept_comment" class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Commentaire pour le client <span style="color:#f44336">*</span>
                        </label>
                        <textarea id="accept_comment"
                                  name="accept_comment"
                                  rows="3"
                                  required
                                  minlength="10"
                                  maxlength="1000"
                                  placeholder="Ex : Bonjour, je confirme ma disponibilité pour votre événement. Je serai présent à l'heure convenue avec tout le matériel nécessaire…"
                                  class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-800 focus:outline-none focus:ring-2 resize-none"
                                  style="focus:ring-color:#FF6B35">{{ old('accept_comment') }}</textarea>
                        @error('accept_comment')
                            <p class="text-xs mt-1" style="color:#f44336">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
                                style="background:#4CAF50">
                            ✓ Accepter la réservation
                        </button>
                        <button type="button"
                                class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
                                style="background:#f44336"
                                onclick="document.getElementById('reject-form').classList.toggle('hidden')">
                            ✗ Refuser
                        </button>
                    </div>
                </form>
                <form id="reject-form" method="POST" action="{{ route('talent.bookings.reject', $booking->id) }}" class="hidden mt-3">
                    @csrf
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
                            style="background:#f44336"
                            onclick="return confirm('Confirmer le refus de cette réservation ?')">
                        Confirmer le refus
                    </button>
                </form>
            @elseif($sk === 'accepted')
                <form method="POST" action="{{ route('talent.bookings.reject', $booking->id) }}">
                    @csrf
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
                            style="background:#f44336"
                            onclick="return confirm('Annuler cette réservation ?')">
                        Annuler
                    </button>
                </form>
            @elseif($sk === 'confirmed')
                <form method="POST" action="{{ route('talent.bookings.complete', $booking->id) }}">
                    @csrf
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
                            style="background:#9C27B0"
                            onclick="return confirm('Marquer comme terminée ?')">
                        Marquer comme terminée
                    </button>
                </form>
            @endif
        </div>
        @endif
    </div>

</div>
@endsection
