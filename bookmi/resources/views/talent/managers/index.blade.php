@extends('layouts.talent')

@section('title', 'Mes managers — BookMi')

@section('content')
@php
    $accepted = $invitations->filter(fn($i) => $i->status->value === 'accepted');
    $pending  = $invitations->filter(fn($i) => $i->status->value === 'pending');
    $rejected = $invitations->filter(fn($i) => $i->status->value === 'rejected');
@endphp

<div class="max-w-2xl mx-auto space-y-6">

    {{-- Page header --}}
    <div>
        <h1 class="text-xl font-bold text-gray-900">Mes managers</h1>
        <p class="text-sm text-gray-500 mt-1">
            Un manager peut gérer vos réservations, répondre aux clients en votre nom et piloter votre calendrier.
        </p>
    </div>

    {{-- Info banner --}}
    <div class="rounded-xl p-4 text-sm" style="background:rgba(108,94,207,0.07);border:1px solid rgba(108,94,207,0.18);color:#5448a8">
        <strong>Note :</strong> Le manager accepte votre invitation par email. Il n'a pas accès à vos données financières.
    </div>

    {{-- Invite form --}}
    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
        <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-4">Inviter un manager</h2>
        <form method="POST" action="{{ route('talent.managers.invite') }}" class="flex gap-3">
            @csrf
            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="Adresse email du manager"
                required
                class="flex-1 rounded-lg border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2"
                style="focus:ring-color:#FF6B35"
            >
            <button
                type="submit"
                class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white transition-all"
                style="background:linear-gradient(135deg,#FF6B35,#C85A20)"
                onmouseover="this.style.opacity='0.9'"
                onmouseout="this.style.opacity='1'"
            >
                Inviter
            </button>
        </form>
        @error('email')
            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Accepted managers --}}
    <div>
        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">
            Managers actifs
            @if($accepted->isNotEmpty())
                <span class="ml-1 text-green-600">({{ $accepted->count() }})</span>
            @endif
        </h2>

        @if($accepted->isEmpty())
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 text-center text-sm text-gray-400">
                Aucun manager actif pour l'instant.
            </div>
        @else
            <div class="space-y-3">
                @foreach($accepted as $inv)
                    @php
                        $mgr = $inv->manager;
                        $name = $mgr ? trim(($mgr->first_name ?? '') . ' ' . ($mgr->last_name ?? '')) : null;
                        $name = ($name && $name !== '') ? $name : $inv->manager_email;
                        $email = $mgr?->email ?? $inv->manager_email;
                    @endphp
                    <div class="bg-white rounded-xl border shadow-sm p-4 flex items-center gap-4" style="border-color:#bbf7d0">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold text-white" style="background:linear-gradient(135deg,#22c55e,#16a34a)">
                            {{ mb_strtoupper(mb_substr($name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $email }}</p>
                            <span class="inline-block mt-1 text-xs font-medium px-2 py-0.5 rounded-full" style="background:#dcfce7;color:#15803d">Actif</span>
                        </div>
                        <form method="POST" action="{{ route('talent.managers.remove', $inv->id) }}" onsubmit="return confirm('Retirer {{ addslashes($name) }} de votre équipe ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-medium text-red-500 hover:text-red-700 transition-colors px-3 py-1.5 rounded-lg hover:bg-red-50">
                                Retirer
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Pending invitations --}}
    @if($pending->isNotEmpty())
    <div>
        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">
            Invitations en attente
            <span class="ml-1 text-yellow-600">({{ $pending->count() }})</span>
        </h2>
        <div class="space-y-3">
            @foreach($pending as $inv)
                <div class="bg-white rounded-xl border shadow-sm p-4 flex items-center gap-4" style="border-color:#fde68a">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0" style="background:#fef3c7">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $inv->manager_email }}</p>
                        <p class="text-xs text-gray-400">Invité le {{ $inv->invited_at->format('d/m/Y') }}</p>
                        <span class="inline-block mt-1 text-xs font-medium px-2 py-0.5 rounded-full" style="background:#fef9c3;color:#a16207">En attente</span>
                    </div>
                    <form method="POST" action="{{ route('talent.managers.invitations.cancel', $inv->id) }}" onsubmit="return confirm('Annuler cette invitation ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs font-medium text-gray-500 hover:text-gray-700 transition-colors px-3 py-1.5 rounded-lg hover:bg-gray-100">
                            Annuler
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Rejected invitations --}}
    @if($rejected->isNotEmpty())
    <div>
        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">
            Invitations refusées
            <span class="ml-1 text-red-400">({{ $rejected->count() }})</span>
        </h2>
        <div class="space-y-3">
            @foreach($rejected as $inv)
                <div class="bg-white rounded-xl border shadow-sm p-4 flex items-start gap-4 opacity-70" style="border-color:#fecaca">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0" style="background:#fee2e2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $inv->manager_email }}</p>
                        <p class="text-xs text-gray-400">Invité le {{ $inv->invited_at->format('d/m/Y') }}</p>
                        @if($inv->manager_comment)
                            <p class="mt-1 text-xs text-gray-500 italic">"{{ $inv->manager_comment }}"</p>
                        @endif
                        <span class="inline-block mt-1 text-xs font-medium px-2 py-0.5 rounded-full" style="background:#fee2e2;color:#b91c1c">Refusée</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
