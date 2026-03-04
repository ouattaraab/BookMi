@extends('layouts.manager')

@section('title', 'Réservation #' . $booking->id . ' — BookMi Manager')

@section('head')
<style>
main.page-content { background: #F2EFE9 !important; }
</style>
@endsection

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
            @if($booking->cachet_amount || $booking->total_amount)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Récapitulatif financier</h2>
                <dl class="space-y-3">
                    @if($booking->cachet_amount)
                    <div class="flex justify-between items-center">
                        <dt class="text-sm text-gray-500">Cachet artiste</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ number_format($booking->cachet_amount, 0, ',', ' ') }} XOF</dd>
                    </div>
                    @endif
                    @if($booking->commission_amount)
                    <div class="flex justify-between items-center">
                        <dt class="text-sm text-gray-500">Commission BookMi</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ number_format($booking->commission_amount, 0, ',', ' ') }} XOF</dd>
                    </div>
                    @endif
                    @if($booking->total_amount)
                    <div class="flex justify-between items-center pt-2 border-t border-gray-100">
                        <dt class="text-sm font-semibold text-gray-700">Total client</dt>
                        <dd class="text-base font-bold text-gray-900">{{ number_format($booking->total_amount, 0, ',', ' ') }} XOF</dd>
                    </div>
                    @endif
                    @if($booking->escrowHold)
                    <div class="flex justify-between items-center pt-1">
                        <dt class="text-sm text-gray-400">Escrow</dt>
                        <dd class="text-xs font-medium px-2 py-0.5 rounded-full" style="background:#e8f5e9;color:#2e7d32">
                            {{ ucfirst($booking->escrowHold->status ?? 'En attente') }}
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>
            @endif

            {{-- Actions accept/reject pour réservations pending --}}
            @if($sk === 'pending')
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Actions</h2>
                <div class="space-y-3">
                    <form method="POST" action="{{ route('manager.bookings.accept', $booking->id) }}">
                        @csrf
                        <button type="submit"
                                class="w-full px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition-all"
                                style="background:#4CAF50"
                                onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                            Accepter la réservation
                        </button>
                    </form>
                    <button type="button"
                            onclick="document.getElementById('reject-modal').classList.remove('hidden')"
                            class="w-full px-4 py-2.5 rounded-xl text-sm font-semibold transition-all"
                            style="color:#f44336;border:1.5px solid #f44336;background:transparent"
                            onmouseover="this.style.background='#fff5f5'" onmouseout="this.style.background='transparent'">
                        Refuser la réservation
                    </button>
                </div>
            </div>

            {{-- Reject modal --}}
            <div id="reject-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,0.5)">
                <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md mx-4">
                    <h3 class="text-base font-bold text-gray-900 mb-3">Refuser la réservation</h3>
                    <form method="POST" action="{{ route('manager.bookings.reject', $booking->id) }}">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Motif du refus <span class="text-red-500">*</span></label>
                            <textarea name="reason" rows="3" required maxlength="500"
                                      class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-red-300"
                                      placeholder="Expliquez pourquoi vous refusez cette réservation…"></textarea>
                        </div>
                        <div class="flex gap-3">
                            <button type="button" onclick="document.getElementById('reject-modal').classList.add('hidden')"
                                    class="flex-1 px-4 py-2 rounded-xl text-sm font-semibold text-gray-600 border border-gray-200 hover:bg-gray-50">
                                Annuler
                            </button>
                            <button type="submit"
                                    class="flex-1 px-4 py-2 rounded-xl text-sm font-semibold text-white"
                                    style="background:#f44336">
                                Confirmer le refus
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endif

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
