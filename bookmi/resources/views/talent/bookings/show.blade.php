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
        @if(in_array($sk, ['pending', 'accepted', 'paid', 'confirmed']))
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
            @elseif($sk === 'paid' && $booking->event_date->addDay()->lte(now()))
                {{-- Fallback talent : le client n'a pas confirmé dans les 24h suivant l'événement --}}
                <form method="POST" action="{{ route('talent.bookings.talent_confirm', $booking->id) }}">
                    @csrf
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
                            style="background:#FF6B35"
                            onclick="return confirm('Marquer l\'événement comme terminé et libérer le paiement ?')">
                        Marquer l'événement comme terminé
                    </button>
                </form>
            @elseif($sk === 'confirmed')
            {{-- Check-in jour J --}}
            @php
                $lastTracking = $booking->trackingEvents->sortByDesc('occurred_at')->first();
                $lastStatus   = $lastTracking?->status instanceof \App\Enums\TrackingStatus
                    ? $lastTracking->status->value
                    : ($lastTracking?->status ?? null);
            @endphp
            <div class="space-y-3">
                <p class="text-sm font-semibold text-gray-700">Suivi jour-J : indiquez votre avancement</p>
                @if(!$lastStatus)
                <form method="POST" action="{{ route('talent.bookings.checkin', $booking->id) }}">
                    @csrf <input type="hidden" name="status" value="preparing">
                    <button type="submit" class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90" style="background:#6366F1">
                        <span style="font-size:1.1rem;">🎒</span> Je me prépare
                    </button>
                </form>
                @elseif($lastStatus === 'preparing')
                <div class="flex items-center gap-2 text-sm text-indigo-600 font-medium mb-2">
                    <span>🎒</span> <span>En préparation</span>
                </div>
                <form method="POST" action="{{ route('talent.bookings.checkin', $booking->id) }}">
                    @csrf <input type="hidden" name="status" value="en_route">
                    <button type="submit" class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90" style="background:#0EA5E9">
                        <span style="font-size:1.1rem;">🚗</span> Je suis en route
                    </button>
                </form>
                @elseif($lastStatus === 'en_route')
                <div class="flex items-center gap-2 text-sm font-medium mb-2" style="color:#0EA5E9">
                    <span>🚗</span> <span>En route</span>
                </div>
                <form method="POST" action="{{ route('talent.bookings.checkin', $booking->id) }}">
                    @csrf <input type="hidden" name="status" value="arrived">
                    <button type="submit" class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90" style="background:#16A34A">
                        <span style="font-size:1.1rem;">✅</span> Je suis arrivé
                    </button>
                </form>
                @else
                {{-- arrived --}}
                <div class="flex items-center gap-3 p-4 rounded-xl" style="background:#f0fdf4; border:1px solid #bbf7d0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="#16A34A"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm font-semibold" style="color:#15803D">Arrivée confirmée ✓ Le client a été notifié.</p>
                </div>
                @if($booking->event_date->addDay()->lte(now()))
                <form method="POST" action="{{ route('talent.bookings.complete', $booking->id) }}" class="mt-3">
                    @csrf
                    <button type="submit"
                            class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
                            style="background:#FF6B35"
                            onclick="return confirm('Marquer cette réservation comme terminée ? Le paiement sera libéré.')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Marquer comme terminé
                    </button>
                </form>
                @endif
                @endif
            </div>
            @endif
        </div>
        @endif
    </div>

    {{-- ── Chronologie des statuts ── --}}
    @if($booking->statusLogs->isNotEmpty())
    @php
        $tlColors = [
            'pending'   => '#FF9800', 'accepted'  => '#2196F3', 'paid'      => '#9C27B0',
            'confirmed' => '#00BCD4', 'completed' => '#4CAF50', 'cancelled' => '#9E9E9E',
            'rejected'  => '#f44336', 'disputed'  => '#FF5722',
        ];
        $tlLabels = [
            'pending'   => 'En attente', 'accepted'  => 'Acceptée',  'paid'      => 'Payée',
            'confirmed' => 'Confirmée',  'completed' => 'Terminée',  'cancelled' => 'Annulée',
            'rejected'  => 'Rejetée',    'disputed'  => 'Litige',
        ];
    @endphp
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" stroke="#FF6B35" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <h2 class="text-base font-bold text-gray-900">Chronologie de la réservation</h2>
        </div>
        <div class="p-6">
            <ol class="relative border-l border-gray-200 ml-3">
                @foreach($booking->statusLogs as $log)
                @php
                    $dotColor  = $tlColors[$log->to_status] ?? '#9E9E9E';
                    $toLabel   = $tlLabels[$log->to_status] ?? ucfirst($log->to_status ?? '—');
                    $fromLabel = $log->from_status ? ($tlLabels[$log->from_status] ?? ucfirst($log->from_status)) : 'Création';
                @endphp
                <li class="mb-4 ml-5">
                    <span class="absolute flex items-center justify-center w-3 h-3 rounded-full -left-1.5 ring-2 ring-white" style="background:{{ $dotColor }}"></span>
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-xs font-semibold text-gray-800 leading-tight">{{ $fromLabel }} &rarr; {{ $toLabel }}</p>
                            @if($log->performer)
                                <p class="text-xs text-gray-500 mt-0.5">Par {{ $log->performer->first_name ?? '' }} {{ $log->performer->last_name ?? '' }}</p>
                            @else
                                <p class="text-xs text-gray-400 mt-0.5">Système automatique</p>
                            @endif
                        </div>
                        <time class="text-xs text-gray-400 whitespace-nowrap shrink-0">{{ $log->created_at?->format('d/m/Y H:i') ?? '—' }}</time>
                    </div>
                </li>
                @endforeach
            </ol>
        </div>
    </div>
    @endif

    {{-- ── Avis client sur cette réservation ── --}}
    @if(in_array($sk, ['confirmed', 'completed']))
    @php $clientReview = $booking->reviews->firstWhere('type', \App\Enums\ReviewType::ClientToTalent); @endphp
    @if($clientReview)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" stroke="#FF9800" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <h2 class="text-base font-bold text-gray-900">Avis du client</h2>
        </div>
        <div class="p-6 space-y-4">
            {{-- Note étoiles --}}
            <div style="display:flex; gap:3px;">
                @for($i = 1; $i <= 5; $i++)
                    <span style="font-size:1.15rem; color:{{ $i <= $clientReview->rating ? '#FF9800' : '#D1D5DB' }};">★</span>
                @endfor
                <span class="text-sm text-gray-500 ml-2 mt-0.5">{{ $clientReview->rating }}/5</span>
            </div>

            {{-- Commentaire --}}
            @if($clientReview->comment)
            <p class="text-gray-700 text-sm leading-relaxed">{{ $clientReview->comment }}</p>
            @else
            <p class="text-gray-400 text-sm italic">Aucun commentaire laissé.</p>
            @endif

            <p class="text-xs text-gray-400">Publié le {{ $clientReview->created_at->format('d/m/Y') }}</p>

            {{-- Réponse existante --}}
            @if($clientReview->reply)
            <div class="rounded-xl p-4" style="background:#fff3e0; border:1px solid #FFD0B0;">
                <div class="flex items-center gap-1.5 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" stroke="#C85A20" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>
                    <p class="text-xs font-bold uppercase tracking-wider" style="color:#C85A20;">Votre réponse</p>
                </div>
                <p class="text-sm text-gray-700 leading-relaxed">{{ $clientReview->reply }}</p>
                @if($clientReview->reply_at)
                <p class="text-xs text-gray-400 mt-1">{{ $clientReview->reply_at->format('d/m/Y') }}</p>
                @endif
            </div>

            @else
            {{-- Formulaire réponse --}}
            <form method="POST" action="{{ route('talent.bookings.review.reply', ['id' => $booking->id, 'reviewId' => $clientReview->id]) }}" class="space-y-3 pt-2 border-t border-gray-100">
                @csrf
                <label class="block text-sm font-semibold text-gray-700">Répondre à cet avis</label>
                <textarea name="reply"
                          rows="3"
                          required
                          maxlength="1000"
                          placeholder="Rédigez votre réponse publique à cet avis…"
                          class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-800 focus:outline-none focus:ring-2 resize-none"
                          style="--tw-ring-color:#FF6B35"></textarea>
                @error('reply')
                    <p class="text-xs" style="color:#f44336">{{ $message }}</p>
                @enderror
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
                        style="background:#FF6B35">
                    Publier la réponse
                </button>
            </form>
            @endif
        </div>
    </div>
    @endif
    @endif

    {{-- ── Évaluation talent → client ── --}}
    @if(in_array($sk, ['confirmed', 'completed']))
    @php $talentReview = $booking->reviews->firstWhere('type', \App\Enums\ReviewType::TalentToClient); @endphp
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <h2 class="text-base font-bold text-gray-900">Évaluer ce client</h2>
        </div>

        @if($talentReview)
        {{-- Already reviewed --}}
        <div class="p-6">
            <div class="flex items-center gap-2 mb-3">
                @for($i = 1; $i <= 5; $i++)
                    <span style="font-size:1.15rem; color:{{ $i <= $talentReview->rating ? '#2563EB' : '#D1D5DB' }};">★</span>
                @endfor
                <span class="text-sm text-gray-500 ml-1">{{ $talentReview->rating }}/5</span>
            </div>
            @if($talentReview->comment)
            <p class="text-sm text-gray-700 leading-relaxed">{{ $talentReview->comment }}</p>
            @endif
            <p class="text-xs text-gray-400 mt-2">Publié le {{ $talentReview->created_at->format('d/m/Y') }}</p>
        </div>
        @else
        {{-- Review form --}}
        <form method="POST" action="{{ route('talent.bookings.review.client', $booking->id) }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Note globale</label>
                <div class="flex gap-2" x-data="{ rating: {{ old('rating', 0) }} }">
                    @for($i = 1; $i <= 5; $i++)
                    <button type="button"
                            @click="rating = {{ $i }}"
                            :style="rating >= {{ $i }} ? 'color:#2563EB;font-size:1.6rem;' : 'color:#D1D5DB;font-size:1.6rem;'"
                            style="background:none;border:none;cursor:pointer;padding:0 2px;line-height:1;transition:color 0.1s;">★</button>
                    @endfor
                    <input type="hidden" name="rating" :value="rating">
                </div>
                @error('rating')<p class="text-xs mt-1" style="color:#f44336">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Commentaire <span class="text-gray-400 font-normal">(optionnel)</span></label>
                <textarea name="comment"
                          rows="3"
                          maxlength="1000"
                          placeholder="Ce client était ponctuel, bien organisé…"
                          class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-800 focus:outline-none focus:ring-2 resize-none"
                          style="--tw-ring-color:#2563EB">{{ old('comment') }}</textarea>
                @error('comment')<p class="text-xs mt-1" style="color:#f44336">{{ $message }}</p>@enderror
            </div>

            <button type="submit"
                    class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
                    style="background:#2563EB">
                Publier mon évaluation
            </button>
        </form>
        @endif
    </div>
    @endif

</div>
@endsection
