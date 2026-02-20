@extends('layouts.manager')

@section('title', 'Réservation #' . $booking->id . ' — BookMi Manager')

@section('content')
@php
    $sk = $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status;
    $sc = ['pending'=>'#FF9800','accepted'=>'#2196F3','paid'=>'#00BCD4','confirmed'=>'#4CAF50','completed'=>'#9C27B0','cancelled'=>'#f44336','disputed'=>'#FF5722'][$sk] ?? '#6b7280';
    $sl = ['pending'=>'En attente','accepted'=>'Acceptée','paid'=>'Payée','confirmed'=>'Confirmée','completed'=>'Terminée','cancelled'=>'Annulée','disputed'=>'En litige'][$sk] ?? $sk;
@endphp

<div class="space-y-6">

    {{-- Retour + titre --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('manager.bookings') }}" class="inline-flex items-center gap-2 text-sm font-medium hover:underline" style="color:#2196F3">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Retour
            </a>
            <span class="text-gray-300">/</span>
            <h1 class="text-xl font-bold text-gray-900">Réservation #{{ $booking->id }}</h1>
        </div>
        <span class="inline-flex px-3 py-1.5 rounded-full text-sm font-semibold text-white" style="background:{{ $sc }}">
            {{ $sl }}
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Colonne principale --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Talent --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Talent</h2>
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center text-xl font-bold text-white flex-shrink-0"
                         style="background:linear-gradient(135deg,#1A2744,#2196F3)">
                        {{ mb_strtoupper(mb_substr($booking->talentProfile?->stage_name ?? $booking->talentProfile?->user?->first_name ?? '?', 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 text-lg">
                            {{ $booking->talentProfile?->stage_name ?? ($booking->talentProfile?->user?->first_name . ' ' . $booking->talentProfile?->user?->last_name) }}
                        </p>
                        <p class="text-sm text-gray-500">{{ $booking->talentProfile?->user?->email }}</p>
                        @if($booking->talentProfile?->city)
                            <p class="text-sm text-gray-400">{{ $booking->talentProfile->city }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Client --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Client</h2>
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center text-xl font-bold text-white flex-shrink-0"
                         style="background:linear-gradient(135deg,#6b7280,#9ca3af)">
                        {{ mb_strtoupper(mb_substr($booking->client?->first_name ?? '?', 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 text-lg">
                            {{ $booking->client?->first_name }} {{ $booking->client?->last_name }}
                        </p>
                        <p class="text-sm text-gray-500">{{ $booking->client?->email }}</p>
                        @if($booking->client?->phone)
                            <p class="text-sm text-gray-400">{{ $booking->client->phone }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Détails événement --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Détails de l'événement</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Date de l'événement</dt>
                        <dd class="text-base font-semibold text-gray-900">
                            {{ $booking->event_date ? \Carbon\Carbon::parse($booking->event_date)->format('d/m/Y') : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Lieu</dt>
                        <dd class="text-base font-semibold text-gray-900">
                            {{ $booking->event_location ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Package</dt>
                        <dd class="text-base font-semibold text-gray-900">
                            {{ $booking->servicePackage?->name ?? $booking->servicePackage?->title ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Date de demande</dt>
                        <dd class="text-base font-semibold text-gray-900">
                            {{ $booking->created_at?->format('d/m/Y à H:i') ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Colonne montants --}}
        <div class="space-y-5">

            {{-- Récapitulatif financier --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Récapitulatif financier</h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Cachet artiste</span>
                        <span class="font-semibold text-gray-900">
                            {{ $booking->cachet_amount ? number_format($booking->cachet_amount, 0, ',', ' ') . ' XOF' : '—' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Commission platform</span>
                        <span class="font-semibold text-gray-900">
                            @php
                                $commission = null;
                                if ($booking->total_amount && $booking->cachet_amount) {
                                    $commission = $booking->total_amount - $booking->cachet_amount;
                                }
                            @endphp
                            {{ $commission !== null ? number_format($commission, 0, ',', ' ') . ' XOF' : '—' }}
                        </span>
                    </div>
                    <div class="border-t border-gray-100 pt-3 flex justify-between items-center">
                        <span class="text-base font-bold text-gray-900">Total</span>
                        <span class="text-lg font-extrabold" style="color:#1A2744">
                            {{ $booking->total_amount ? number_format($booking->total_amount, 0, ',', ' ') . ' XOF' : '—' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Statut card --}}
            <div class="rounded-2xl p-6" style="background:{{ $sc }}1a;border:1px solid {{ $sc }}33">
                <h2 class="text-sm font-semibold uppercase tracking-wider mb-2" style="color:{{ $sc }}">Statut</h2>
                <p class="text-2xl font-extrabold" style="color:{{ $sc }}">{{ $sl }}</p>
                @if($booking->updated_at)
                    <p class="text-xs mt-2" style="color:{{ $sc }}bb">
                        Mis à jour le {{ $booking->updated_at->format('d/m/Y à H:i') }}
                    </p>
                @endif
            </div>

            {{-- Liens rapides --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Actions rapides</h2>
                <div class="space-y-2">
                    <a href="{{ route('manager.talents.show', $booking->talent_profile_id) }}"
                       class="flex items-center gap-2 text-sm font-medium hover:underline" style="color:#2196F3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        Voir le profil du talent
                    </a>
                    <a href="{{ route('manager.messages') }}"
                       class="flex items-center gap-2 text-sm font-medium hover:underline" style="color:#2196F3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Messagerie
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
