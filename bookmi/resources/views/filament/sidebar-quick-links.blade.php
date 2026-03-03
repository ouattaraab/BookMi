@auth
@php
    $isMaintenance = false;
    try {
        $isMaintenance = \App\Models\PlatformSetting::bool('maintenance_enabled');
    } catch (\Throwable) {}
@endphp

<div class="bm-quick-links">
    <div class="bm-ql-label">ACCÈS RAPIDE</div>

    {{-- Mode maintenance --}}
    <a href="{{ filament()->getUrl() }}/maintenance" class="bm-ql-item {{ $isMaintenance ? 'bm-ql-danger' : '' }}">
        <span class="bm-ql-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/>
            </svg>
        </span>
        <span class="bm-ql-text">
            Mode maintenance
            @if($isMaintenance)
                <span class="bm-ql-badge">ACTIF</span>
            @endif
        </span>
    </a>

    {{-- Version app --}}
    <a href="{{ filament()->getUrl() }}/app-version" class="bm-ql-item">
        <span class="bm-ql-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                <path d="M12 18h.01"/>
            </svg>
        </span>
        <span class="bm-ql-text">Version app mobile</span>
    </a>

    <div class="bm-ql-divider"></div>
</div>

<style>
.bm-quick-links {
    padding: 0.75rem 0.75rem 0;
    font-family: 'Nunito', sans-serif;
}

.bm-ql-label {
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    color: rgba(255,255,255,0.25);
    padding: 0 0.25rem 0.4rem;
}

.bm-ql-item {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.45rem 0.5rem;
    border-radius: 0.45rem;
    text-decoration: none;
    transition: background 120ms ease;
    margin-bottom: 0.2rem;
    color: rgba(255,255,255,0.65);
}

.bm-ql-item:hover {
    background: rgba(255,255,255,0.08);
    color: #ffffff;
}

.bm-ql-item.bm-ql-danger {
    color: rgba(252, 165, 165, 0.85);
    background: rgba(239, 68, 68, 0.08);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.bm-ql-item.bm-ql-danger:hover {
    background: rgba(239, 68, 68, 0.14);
}

.bm-ql-icon {
    flex-shrink: 0;
    width: 1.1rem;
    height: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bm-ql-icon svg {
    width: 1rem;
    height: 1rem;
}

.bm-ql-text {
    font-size: 0.78rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    line-height: 1;
}

.bm-ql-badge {
    font-size: 0.55rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.35);
    border-radius: 0.25rem;
    padding: 0.1rem 0.35rem;
}

.bm-ql-divider {
    height: 1px;
    background: rgba(255,255,255,0.07);
    margin: 0.6rem 0.25rem 0;
}
</style>
@endauth
