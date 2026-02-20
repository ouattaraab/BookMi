@extends('layouts.client')

@section('title', 'Mes réservations — BookMi Client')

@section('head')
<style>
/* ── Status tabs ── */
.status-tab {
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    font-size: 0.8rem;
    font-weight: 700;
    border: 1px solid var(--glass-border);
    background: var(--glass-bg);
    color: var(--text-muted);
    text-decoration: none;
    transition: all 0.2s cubic-bezier(0.16,1,0.3,1);
    white-space: nowrap;
}
.status-tab:hover {
    color: var(--blue-light);
    border-color: rgba(100,181,246,0.25);
    background: rgba(100,181,246,0.07);
}
.status-tab.active {
    color: white;
    background: linear-gradient(135deg,var(--navy),var(--blue));
    border-color: rgba(100,181,246,0.4);
    box-shadow: 0 0 14px rgba(33,150,243,0.30);
}
/* ── Booking row card ── */
.booking-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-radius: 1rem;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    text-decoration: none;
    transition: background 0.2s, border-color 0.2s, transform 0.25s cubic-bezier(0.16,1,0.3,1), box-shadow 0.25s;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}
.booking-card:hover {
    background: rgba(255,255,255,0.065);
    border-color: rgba(100,181,246,0.22);
    transform: translateY(-2px) translateX(2px);
    box-shadow: 0 8px 28px rgba(0,0,0,0.35), 0 0 0 1px rgba(100,181,246,0.10);
}
/* ── Reveal ── */
.reveal-item { opacity:0; transform:translateY(16px); transition:opacity 0.45s cubic-bezier(0.16,1,0.3,1), transform 0.45s cubic-bezier(0.16,1,0.3,1); }
.reveal-item.visible { opacity:1; transform:none; }
</style>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="p-3 rounded-xl text-sm font-medium reveal-item" style="background:rgba(76,175,80,0.12);border:1px solid rgba(76,175,80,0.25);color:rgba(134,239,172,0.95)">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="p-3 rounded-xl text-sm font-medium reveal-item" style="background:rgba(244,67,54,0.12);border:1px solid rgba(244,67,54,0.25);color:rgba(252,165,165,0.95)">{{ session('error') }}</div>
    @endif
    @if(session('info'))
    <div class="p-3 rounded-xl text-sm font-medium reveal-item" style="background:rgba(33,150,243,0.12);border:1px solid rgba(33,150,243,0.25);color:rgba(147,197,253,0.95)">{{ session('info') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between reveal-item">
        <div>
            <h1 class="section-title">Mes réservations</h1>
            <p class="section-sub">Gérez toutes vos demandes de prestation</p>
        </div>
        <a href="{{ route('talents.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold text-white transition-all hover:scale-105 active:scale-95"
           style="background:linear-gradient(135deg,var(--orange),#ff8c5a);box-shadow:0 4px 14px rgba(255,107,53,0.32)">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 4v16m8-8H4"/></svg>
            Réserver
        </a>
    </div>

    {{-- Status tabs --}}
    @php
        $tabs = ['' => 'Toutes', 'pending' => 'En attente', 'accepted' => 'Acceptées', 'paid' => 'Payées', 'confirmed' => 'Confirmées', 'completed' => 'Terminées', 'cancelled' => 'Annulées'];
        $currentStatus = request('status', '');
    @endphp
    <div class="flex gap-2 overflow-x-auto pb-1 reveal-item" style="transition-delay:0.05s;scrollbar-width:none">
        @foreach($tabs as $value => $label)
            <a href="{{ route('client.bookings', $value ? ['status' => $value] : []) }}"
               class="status-tab {{ $currentStatus === $value ? 'active' : '' }} flex-shrink-0">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Booking list --}}
    @if($bookings->isEmpty())
        <div class="glass-card flex flex-col items-center justify-center py-20 text-center reveal-item" style="transition-delay:0.10s">
            <div class="w-16 h-16 rounded-2xl mb-5 flex items-center justify-center" style="background:rgba(255,107,53,0.08);border:1px solid rgba(255,107,53,0.15)">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#FF6B35" stroke-width="1.5"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <p class="font-bold text-base mb-1" style="color:var(--text)">Aucune réservation trouvée</p>
            <p class="text-sm mb-6" style="color:var(--text-muted)">
                {{ $currentStatus ? 'Aucune réservation avec ce statut.' : 'Commencez par réserver un talent !' }}
            </p>
            <a href="{{ route('talents.index') }}"
               class="px-6 py-2.5 rounded-xl text-sm font-bold text-white"
               style="background:var(--orange);box-shadow:0 4px 14px rgba(255,107,53,0.35)">
                Découvrir les talents
            </a>
        </div>
    @else
        <div class="space-y-3">
            @foreach($bookings as $i => $booking)
            @php
                $sk = $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status;
                $sc = ['pending'=>'#FF9800','accepted'=>'#2196F3','paid'=>'#00BCD4','confirmed'=>'#4CAF50','completed'=>'#9C27B0','cancelled'=>'#f44336','disputed'=>'#FF5722'][$sk] ?? '#6b7280';
                $sl = ['pending'=>'En attente','accepted'=>'Acceptée','paid'=>'Payée','confirmed'=>'Confirmée','completed'=>'Terminée','cancelled'=>'Annulée','disputed'=>'En litige'][$sk] ?? $sk;
                $talentName = $booking->talentProfile->stage_name
                    ?? trim(($booking->talentProfile->user->first_name ?? '') . ' ' . ($booking->talentProfile->user->last_name ?? ''))
                    ?: '?';
            @endphp
            <a href="{{ route('client.bookings.show', $booking->id) }}"
               class="booking-card reveal-item"
               style="transition-delay:{{ min($i * 0.06, 0.3) }}s">
                {{-- Avatar --}}
                <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg"
                     style="background:linear-gradient(135deg,{{ $sc }}90,{{ $sc }}50)">
                    {{ strtoupper(substr($talentName, 0, 1)) }}
                </div>
                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-0.5">
                        <span class="font-bold text-sm truncate" style="color:var(--text)">{{ $talentName }}</span>
                        @if($booking->talentProfile->category)
                        <span class="text-xs flex-shrink-0" style="color:var(--text-faint)">· {{ $booking->talentProfile->category->name ?? '' }}</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-3 text-xs" style="color:var(--text-muted)">
                        <span>{{ \Carbon\Carbon::parse($booking->event_date)->format('d/m/Y') }}</span>
                        @if($booking->servicePackage)
                            <span style="color:var(--text-faint)">|</span>
                            <span class="truncate">{{ $booking->servicePackage->name }}</span>
                        @endif
                    </div>
                </div>
                {{-- Amount + badge --}}
                <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
                    <span class="font-black text-sm" style="color:var(--text)">{{ number_format($booking->cachet_amount, 0, ',', ' ') }} <span style="color:var(--text-muted);font-weight:600">FCFA</span></span>
                    <span class="badge-status" style="background:{{ $sc }}18;color:{{ $sc }};border:1px solid {{ $sc }}35">{{ $sl }}</span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:var(--text-faint);flex-shrink:0"><path d="M9 5l7 7-7 7"/></svg>
            </a>
            @endforeach
        </div>

        @if($bookings->hasPages())
            <div class="mt-6 flex justify-center gap-2">
                {{ $bookings->links() }}
            </div>
        @endif
    @endif

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const io = new IntersectionObserver(entries => {
        entries.forEach(e => { if(e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); } });
    }, { threshold: 0.04 });
    document.querySelectorAll('.reveal-item').forEach(el => io.observe(el));
});
</script>
@endsection
