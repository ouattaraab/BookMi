@extends('layouts.client')

@section('title', 'Réservation #' . $booking->id . ' — BookMi Client')

@section('head')
<style>
/* ── Detail row ── */
.detail-row {
    display: flex;
    align-items: flex-start;
    gap: 0.875rem;
    padding: 0.75rem 0;
}
.detail-row + .detail-row { border-top: 1px solid var(--glass-border); }
.detail-icon {
    width: 2.25rem; height: 2.25rem;
    border-radius: 0.625rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    background: rgba(33,150,243,0.10);
    border: 1px solid rgba(33,150,243,0.18);
}
/* ── Finance row ── */
.finance-block {
    background: rgba(255,255,255,0.025);
    border: 1px solid var(--glass-border);
    border-radius: 0.875rem;
    padding: 1rem 1.25rem;
}
/* ── Back button ── */
.back-btn {
    display: inline-flex; align-items: center; gap: 0.5rem;
    padding: 0.5rem 0.875rem;
    border-radius: 0.75rem;
    font-size: 0.8rem; font-weight: 700;
    background: var(--glass-bg); border: 1px solid var(--glass-border);
    color: var(--text-muted);
    text-decoration: none;
    transition: all 0.2s;
}
.back-btn:hover { color: var(--text); border-color: rgba(255,255,255,0.18); }
/* ── Action btns ── */
.btn-pay {
    flex: 1; text-align: center;
    padding: 0.875rem 1.5rem;
    border-radius: 0.875rem;
    font-size: 0.875rem; font-weight: 800;
    color: white; text-decoration: none;
    background: linear-gradient(135deg,#4CAF50,#66BB6A);
    box-shadow: 0 4px 16px rgba(76,175,80,0.35);
    transition: transform 0.2s, box-shadow 0.2s;
    display: block;
}
.btn-pay:hover { transform: translateY(-2px); box-shadow: 0 6px 22px rgba(76,175,80,0.45); }
.btn-cancel {
    flex: 1; width: 100%;
    padding: 0.875rem 1.5rem;
    border-radius: 0.875rem;
    font-size: 0.875rem; font-weight: 700;
    background: rgba(244,67,54,0.07);
    border: 1px solid rgba(244,67,54,0.25);
    color: rgba(252,165,165,0.9);
    transition: background 0.2s;
    cursor: pointer;
}
.btn-cancel:hover { background: rgba(244,67,54,0.14); }
/* ── Reveal ── */
.reveal-item { opacity:0; transform:translateY(16px); transition:opacity 0.45s cubic-bezier(0.16,1,0.3,1), transform 0.45s cubic-bezier(0.16,1,0.3,1); }
.reveal-item.visible { opacity:1; transform:none; }
</style>
@endsection

@section('content')
<div class="space-y-6 max-w-3xl">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="p-3 rounded-xl text-sm font-medium" style="background:rgba(76,175,80,0.12);border:1px solid rgba(76,175,80,0.25);color:rgba(134,239,172,0.95)">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="p-3 rounded-xl text-sm font-medium" style="background:rgba(244,67,54,0.12);border:1px solid rgba(244,67,54,0.25);color:rgba(252,165,165,0.95)">{{ session('error') }}</div>
    @endif
    @if(session('info'))
    <div class="p-3 rounded-xl text-sm font-medium" style="background:rgba(33,150,243,0.12);border:1px solid rgba(33,150,243,0.25);color:rgba(147,197,253,0.95)">{{ session('info') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex items-center gap-3 reveal-item">
        <a href="{{ route('client.bookings') }}" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 19l-7-7 7-7"/></svg>
            Retour
        </a>
        <div>
            <h1 class="section-title">Réservation <span style="color:var(--blue-light)">#{{ $booking->id }}</span></h1>
            <p class="section-sub">Détails de votre demande de prestation</p>
        </div>
    </div>

    @php
        $sk = $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status;
        $sc = ['pending'=>'#FF9800','accepted'=>'#2196F3','paid'=>'#00BCD4','confirmed'=>'#4CAF50','completed'=>'#9C27B0','cancelled'=>'#f44336','disputed'=>'#FF5722'][$sk] ?? '#6b7280';
        $sl = ['pending'=>'En attente','accepted'=>'Acceptée','paid'=>'Payée','confirmed'=>'Confirmée','completed'=>'Terminée','cancelled'=>'Annulée','disputed'=>'En litige'][$sk] ?? $sk;
        $talentName = $booking->talentProfile->stage_name
            ?? trim(($booking->talentProfile->user->first_name ?? '') . ' ' . ($booking->talentProfile->user->last_name ?? ''))
            ?: '?';
    @endphp

    {{-- Main card --}}
    <div class="glass-card overflow-hidden reveal-item" style="transition-delay:0.06s">

        {{-- Talent header --}}
        <div class="p-5 flex items-center gap-4" style="border-bottom:1px solid var(--glass-border)">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white font-black text-xl flex-shrink-0"
                 style="background:linear-gradient(135deg,{{ $sc }}99,{{ $sc }}55);box-shadow:0 0 20px {{ $sc }}40">
                {{ strtoupper(substr($talentName, 0, 1)) }}
            </div>
            <div class="flex-1">
                <h2 class="text-lg font-black" style="color:var(--text)">{{ $talentName }}</h2>
                <p class="text-sm" style="color:var(--text-muted)">{{ $booking->talentProfile->category->name ?? '—' }}</p>
            </div>
            <span class="badge-status" style="background:{{ $sc }}18;color:{{ $sc }};border:1px solid {{ $sc }}40;font-size:0.75rem;padding:0.35rem 0.875rem">{{ $sl }}</span>
        </div>

        {{-- Detail rows --}}
        <div class="p-5 space-y-0">

            {{-- Date --}}
            <div class="detail-row">
                <div class="detail-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#64B5F6" stroke-width="2"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide mb-0.5" style="color:var(--text-faint)">Date de l'événement</p>
                    <p class="font-bold text-sm" style="color:var(--text)">{{ \Carbon\Carbon::parse($booking->event_date)->translatedFormat('l d F Y') }}</p>
                </div>
            </div>

            {{-- Lieu --}}
            @if($booking->event_location)
            <div class="detail-row">
                <div class="detail-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#64B5F6" stroke-width="2"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide mb-0.5" style="color:var(--text-faint)">Lieu</p>
                    <p class="font-bold text-sm" style="color:var(--text)">{{ $booking->event_location }}</p>
                </div>
            </div>
            @endif

            {{-- Package --}}
            @if($booking->servicePackage)
            <div class="detail-row">
                <div class="detail-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#64B5F6" stroke-width="2"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide mb-0.5" style="color:var(--text-faint)">Package</p>
                    <p class="font-bold text-sm" style="color:var(--text)">{{ $booking->servicePackage->name }}</p>
                    @if($booking->servicePackage->duration_minutes)
                        <p class="text-xs mt-0.5" style="color:var(--text-muted)">{{ $booking->servicePackage->duration_minutes }} min</p>
                    @endif
                </div>
            </div>
            @endif

            {{-- Message --}}
            @if($booking->message)
            <div class="detail-row">
                <div class="detail-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#64B5F6" stroke-width="2"><path d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide mb-0.5" style="color:var(--text-faint)">Message</p>
                    <p class="text-sm leading-relaxed" style="color:var(--text-muted)">{{ $booking->message }}</p>
                </div>
            </div>
            @endif

        </div>

        {{-- Finance --}}
        <div class="px-5 pb-5">
            <div class="finance-block">
                <h3 class="text-xs font-bold uppercase tracking-wider mb-3" style="color:var(--text-muted)">Détail financier</h3>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span style="color:var(--text-muted)">Cachet talent</span>
                        <span class="font-semibold" style="color:var(--text)">{{ number_format($booking->cachet_amount, 0, ',', ' ') }} FCFA</span>
                    </div>
                    @if($booking->commission_amount)
                    <div class="flex justify-between text-sm">
                        <span style="color:var(--text-muted)">Commission BookMi</span>
                        <span class="font-semibold" style="color:var(--text)">{{ number_format($booking->commission_amount, 0, ',', ' ') }} FCFA</span>
                    </div>
                    @endif
                    <div class="flex justify-between pt-3" style="border-top:1px solid var(--glass-border)">
                        <span class="font-black" style="color:var(--text)">Total</span>
                        <span class="text-lg font-black" style="color:var(--blue-light)">{{ number_format($booking->total_amount ?? $booking->cachet_amount, 0, ',', ' ') }} FCFA</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        @if(in_array($sk, ['pending', 'accepted']))
        <div class="px-5 pb-5 flex gap-3 flex-wrap">
            @if($sk === 'accepted')
            <a href="{{ route('client.bookings.pay', $booking->id) }}" class="btn-pay">
                💳 Payer maintenant
            </a>
            @endif
            <form action="{{ route('client.bookings.cancel', $booking->id) }}" method="POST" class="flex-1"
                  x-data onsubmit="return confirm('Confirmer l\'annulation de cette réservation ?')">
                @csrf
                <button type="submit" class="btn-cancel">Annuler la réservation</button>
            </form>
        </div>
        @endif

    </div>

    {{-- Meta --}}
    <p class="text-xs text-center reveal-item" style="color:var(--text-faint);transition-delay:0.20s">
        Réservation créée le {{ $booking->created_at->format('d/m/Y à H:i') }}
    </p>

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
