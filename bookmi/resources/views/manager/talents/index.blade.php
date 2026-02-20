@extends('layouts.manager')

@section('title', 'Mes talents — BookMi Manager')

@section('head')
<style>
main.page-content { background: #F2EFE9 !important; }
</style>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-gray-900">Mes talents</h1>
            <p class="text-gray-500 text-sm mt-1">{{ $talents->count() }} talent(s) sous votre gestion</p>
        </div>
    </div>

    {{-- Grille talents --}}
    @if($talents->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-16 text-center">
            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background:#dbeafe">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2196F3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-1">Aucun talent géré</h3>
            <p class="text-gray-500 text-sm">Vous n'avez pas encore de talents associés à votre compte manager.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($talents as $talent)
            @php
                $initials = mb_strtoupper(mb_substr($talent->stage_name ?? $talent->user?->first_name ?? '?', 0, 1));
            @endphp
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow overflow-hidden flex flex-col">

                {{-- Card header --}}
                <div class="p-6 flex items-center gap-4 flex-1">
                    {{-- Avatar --}}
                    <div class="w-14 h-14 rounded-full flex items-center justify-center text-lg font-bold text-white flex-shrink-0"
                         style="background:linear-gradient(135deg,#1A2744,#2196F3)">
                        {{ $initials }}
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-900 truncate">
                            {{ $talent->stage_name ?? ($talent->user?->first_name . ' ' . $talent->user?->last_name) }}
                        </p>
                        @if($talent->category)
                            <p class="text-sm text-gray-500 truncate">{{ $talent->category->name ?? $talent->category->label ?? '—' }}</p>
                        @endif
                        @if($talent->city)
                            <p class="text-xs text-gray-400 flex items-center gap-1 mt-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                {{ $talent->city }}
                            </p>
                        @endif
                    </div>

                    {{-- Badge vérifié --}}
                    <div class="flex-shrink-0">
                        @if($talent->is_verified)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold" style="background:#d1fae5;color:#065f46">
                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                Vérifié
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold" style="background:#fef3c7;color:#92400e">
                                En attente
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Cachet --}}
                @if($talent->cachet_amount)
                <div class="px-6 pb-2">
                    <p class="text-sm text-gray-500">Cachet : <span class="font-semibold text-gray-800">{{ number_format($talent->cachet_amount, 0, ',', ' ') }} XOF</span></p>
                </div>
                @endif

                {{-- Footer action --}}
                <div class="border-t border-gray-50 px-6 py-3">
                    <a href="{{ route('manager.talents.show', $talent->id) }}"
                       class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white transition-all duration-150"
                       style="background:linear-gradient(135deg,#1A2744,#2196F3)"
                       onmouseover="this.style.opacity='0.88'"
                       onmouseout="this.style.opacity='1'">
                        Voir détails
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
