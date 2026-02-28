@extends('layouts.public')

@section('title', 'Découvrir les Talents — BookMi')

@section('head')
<style>
/* ─── RESET & VARIABLES ─────────────────────────────────────────────────── */
:root {
    --orange:       #1AB3FF;
    --orange-glow:  rgba(26,179,255,0.35);
    --orange-dim:   rgba(26,179,255,0.12);
    --navy:         #1A2744;
    --navy-dim:     rgba(26,39,68,0.6);
    --bg:           #0D1117;
    --glass-bg:     rgba(255,255,255,0.04);
    --glass-border: rgba(255,255,255,0.10);
    --glass-hover:  rgba(255,255,255,0.08);
    --text-muted:   rgba(255,255,255,0.50);
    --spring:       cubic-bezier(0.16,1,0.3,1);
}

/* ─── PAGE BASE ─────────────────────────────────────────────────────────── */
.disc-page {
    min-height: 100vh;
    background: var(--bg);
    position: relative;
    font-family: 'Nunito', sans-serif;
}

/* Noise grain overlay */
.disc-page::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
    pointer-events: none;
    z-index: 0;
    opacity: 0.6;
}

/* ─── HERO ──────────────────────────────────────────────────────────────── */
.disc-hero {
    position: relative;
    z-index: 1;
    padding: 4rem 1rem 3rem;
    text-align: center;
    overflow: hidden;
}
.disc-hero::after {
    content: '';
    position: absolute;
    bottom: -80px; left: 50%;
    transform: translateX(-50%);
    width: 700px; height: 300px;
    background: radial-gradient(ellipse, rgba(26,179,255,0.18) 0%, transparent 70%);
    pointer-events: none;
}

.disc-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--orange-dim);
    border: 1px solid rgba(26,179,255,0.25);
    border-radius: 100px;
    padding: 4px 14px;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--orange);
    margin-bottom: 1.2rem;
}

.disc-title {
    font-size: clamp(2.2rem, 5.5vw, 3.6rem);
    font-weight: 900;
    line-height: 1.06;
    letter-spacing: -0.03em;
    color: #fff;
    margin-bottom: 0.5rem;
}
.disc-title span { color: var(--orange); }

.disc-subtitle {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-muted);
    margin-bottom: 2rem;
}

/* Search glass */
.disc-search-wrap {
    max-width: 580px;
    margin: 0 auto 2rem;
    position: relative;
}
.disc-search {
    width: 100%;
    background: rgba(255,255,255,0.07);
    backdrop-filter: blur(24px) saturate(180%);
    -webkit-backdrop-filter: blur(24px) saturate(180%);
    border: 1px solid var(--glass-border);
    border-radius: 18px;
    padding: 0.9rem 1.2rem 0.9rem 3.2rem;
    color: #fff;
    font-size: 0.95rem;
    font-weight: 600;
    font-family: 'Nunito', sans-serif;
    outline: none;
    transition: border-color 0.25s, box-shadow 0.25s, background 0.25s;
    box-shadow: 0 4px 24px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.08);
}
.disc-search::placeholder { color: rgba(255,255,255,0.35); font-weight: 600; }
.disc-search:focus {
    border-color: rgba(26,179,255,0.5);
    background: rgba(255,255,255,0.10);
    box-shadow: 0 0 0 3px var(--orange-dim), 0 4px 24px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.12);
}
.disc-search-icon {
    position: absolute;
    left: 1rem; top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    opacity: 0.5;
}

/* Category scroll pills */
.disc-cats {
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
    padding-bottom: 4px;
    scrollbar-width: none;
    justify-content: center;
    flex-wrap: wrap;
}
.disc-cats::-webkit-scrollbar { display: none; }

.disc-cat-pill {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 0.4rem 1rem;
    border-radius: 100px;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s var(--spring);
    text-decoration: none;
    white-space: nowrap;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.10);
    color: rgba(255,255,255,0.65);
}
.disc-cat-pill:hover {
    background: rgba(255,255,255,0.12);
    border-color: rgba(255,255,255,0.20);
    color: #fff;
    transform: translateY(-1px);
}
.disc-cat-pill.active {
    background: var(--orange);
    border-color: var(--orange);
    color: #fff;
    box-shadow: 0 4px 16px var(--orange-glow);
}

/* ─── STICKY FILTER BAR ─────────────────────────────────────────────────── */
.disc-filterbar {
    position: sticky;
    top: 0;
    z-index: 40;
    background: rgba(13,17,23,0.80);
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border-bottom: 1px solid rgba(255,255,255,0.07);
    padding: 0.75rem 1rem;
    box-shadow: 0 4px 24px rgba(0,0,0,0.4);
}
.disc-filterbar-inner {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    flex-wrap: wrap;
}

.disc-select {
    appearance: none;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 12px;
    padding: 0.5rem 2rem 0.5rem 0.9rem;
    color: rgba(255,255,255,0.80);
    font-size: 0.82rem;
    font-weight: 700;
    font-family: 'Nunito', sans-serif;
    cursor: pointer;
    outline: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.5)' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.6rem center;
    transition: all 0.2s;
}
.disc-select:focus, .disc-select:hover {
    border-color: rgba(26,179,255,0.4);
    background-color: rgba(255,255,255,0.09);
    color: #fff;
}
.disc-select option { background: #1a1f2e; color: #fff; }

.disc-filter-btn {
    padding: 0.5rem 1.2rem;
    border-radius: 12px;
    border: none;
    background: var(--orange);
    color: #fff;
    font-size: 0.82rem;
    font-weight: 800;
    font-family: 'Nunito', sans-serif;
    cursor: pointer;
    transition: all 0.2s var(--spring);
    box-shadow: 0 3px 12px var(--orange-glow);
}
.disc-filter-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px var(--orange-glow);
}

.disc-reset {
    font-size: 0.78rem;
    font-weight: 700;
    color: rgba(255,255,255,0.35);
    text-decoration: none;
    transition: color 0.2s;
    padding: 0.5rem 0.4rem;
}
.disc-reset:hover { color: rgba(255,255,255,0.65); }

.disc-count-badge {
    margin-left: auto;
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--text-muted);
    white-space: nowrap;
    padding: 0.35rem 0.85rem;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 100px;
}
.disc-count-badge strong { color: var(--orange); }

/* ─── GRID SECTION ──────────────────────────────────────────────────────── */
.disc-grid-section {
    position: relative;
    z-index: 1;
    max-width: 1200px;
    margin: 0 auto;
    padding: 2.5rem 1.25rem 5rem;
}

.disc-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}
@media (max-width: 900px) { .disc-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 560px) { .disc-grid { grid-template-columns: 1fr; } }

/* ─── TALENT CARD ───────────────────────────────────────────────────────── */
.tc {
    position: relative;
    border-radius: 22px;
    overflow: hidden;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    backdrop-filter: blur(16px) saturate(180%);
    -webkit-backdrop-filter: blur(16px) saturate(180%);
    display: flex;
    flex-direction: column;
    box-shadow:
        0 4px 24px rgba(0,0,0,0.35),
        inset 0 1px 0 rgba(255,255,255,0.08);
    transition:
        transform 0.42s var(--spring),
        box-shadow 0.42s var(--spring),
        border-color 0.3s;
    will-change: transform, box-shadow;
    text-decoration: none;
    color: inherit;
}
.tc:hover {
    transform: translateY(-10px) scale(1.015);
    border-color: rgba(26,179,255,0.35);
    box-shadow:
        0 24px 60px rgba(0,0,0,0.5),
        0 0 0 1px rgba(26,179,255,0.2),
        0 8px 32px rgba(26,179,255,0.22),
        inset 0 1px 0 rgba(255,255,255,0.12);
}

/* Photo area */
.tc-photo {
    position: relative;
    aspect-ratio: 4/3;
    overflow: hidden;
    background: linear-gradient(135deg, #1a2035 0%, #0d1117 100%);
    flex-shrink: 0;
}
.tc-photo img {
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform 0.6s var(--spring), filter 0.4s;
}
.tc:hover .tc-photo img {
    transform: scale(1.07);
    filter: brightness(1.08);
}

/* Shimmer on hover */
.tc-photo::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(105deg,
        transparent 30%,
        rgba(255,255,255,0.08) 50%,
        transparent 70%
    );
    transform: translateX(-100%);
    transition: transform 0s;
    pointer-events: none;
}
.tc:hover .tc-photo::after {
    transform: translateX(100%);
    transition: transform 0.6s ease;
}

/* Photo gradient overlay */
.tc-photo-overlay {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 65%;
    background: linear-gradient(to top, rgba(13,17,23,0.92) 0%, rgba(13,17,23,0.3) 60%, transparent 100%);
    pointer-events: none;
}

/* Photo placeholder */
.tc-photo-placeholder {
    width: 100%; height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg,
        rgba(26,39,68,0.8) 0%,
        rgba(13,17,23,0.9) 100%
    );
}
.tc-photo-monogram {
    width: 64px; height: 64px;
    border-radius: 50%;
    background: rgba(26,179,255,0.12);
    border: 1.5px solid rgba(26,179,255,0.25);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
    font-weight: 900;
    color: var(--orange);
    letter-spacing: -0.02em;
}

/* Badges on photo */
.tc-badge-cat {
    position: absolute;
    bottom: 10px; left: 10px;
    padding: 3px 10px;
    border-radius: 100px;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid;
}
.tc-badge-verified {
    position: absolute;
    top: 10px; right: 10px;
    display: flex; align-items: center; gap: 3px;
    background: rgba(13,17,23,0.75);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 100px;
    padding: 3px 8px;
    font-size: 0.67rem;
    font-weight: 800;
    color: #4ade80;
}

/* Card body */
.tc-body {
    padding: 1.1rem 1.2rem 1.2rem;
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
    flex: 1;
}

.tc-name {
    font-size: 1rem;
    font-weight: 900;
    color: #fff;
    letter-spacing: -0.02em;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: color 0.2s;
}
.tc:hover .tc-name { color: var(--orange); }

.tc-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.tc-city {
    display: flex; align-items: center; gap: 4px;
    font-size: 0.76rem;
    font-weight: 700;
    color: var(--text-muted);
}

.tc-rating {
    display: flex; align-items: center; gap: 3px;
    font-size: 0.78rem;
    font-weight: 800;
    color: #fff;
}
.tc-rating-stars {
    display: flex; gap: 1px;
}

/* Divider */
.tc-divider {
    height: 1px;
    background: linear-gradient(to right, rgba(255,255,255,0.06), rgba(255,255,255,0.10), rgba(255,255,255,0.06));
}

.tc-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}
.tc-price {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--text-muted);
    line-height: 1.2;
}
.tc-price strong {
    display: block;
    font-size: 1rem;
    font-weight: 900;
    color: #fff;
    letter-spacing: -0.02em;
}

.tc-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 0.48rem 1.1rem;
    border-radius: 100px;
    font-size: 0.78rem;
    font-weight: 800;
    color: #fff;
    background: var(--orange);
    border: none;
    cursor: pointer;
    transition: all 0.25s var(--spring);
    text-decoration: none;
    white-space: nowrap;
    box-shadow: 0 3px 14px rgba(26,179,255,0.38);
    letter-spacing: 0.01em;
}
.tc:hover .tc-btn {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(26,179,255,0.5);
}

/* ─── CATEGORY BADGE COLORS ─────────────────────────────────────────────── */
.cat-dj        { background: rgba(96,165,250,0.18); border-color: rgba(96,165,250,0.35); color: #93c5fd; }
.cat-musicien  { background: rgba(167,139,250,0.18); border-color: rgba(167,139,250,0.35); color: #c4b5fd; }
.cat-danseur   { background: rgba(249,168,212,0.18); border-color: rgba(249,168,212,0.35); color: #f9a8d4; }
.cat-chanteur  { background: rgba(110,231,183,0.18); border-color: rgba(110,231,183,0.35); color: #6ee7b7; }
.cat-animateur { background: rgba(253,186,116,0.18); border-color: rgba(253,186,116,0.35); color: #fbbf24; }
.cat-comedien  { background: rgba(248,113,113,0.18); border-color: rgba(248,113,113,0.35); color: #fca5a5; }
.cat-default   { background: rgba(26,179,255,0.15);  border-color: rgba(26,179,255,0.30);  color: #1AB3FF; }

/* ─── SKELETON LOADER ───────────────────────────────────────────────────── */
@keyframes shimmerSkeleton {
    0%   { background-position: -600px 0; }
    100% { background-position: 600px 0; }
}
.disc-skeleton {
    border-radius: 22px;
    overflow: hidden;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.07);
}
.disc-skeleton-photo {
    aspect-ratio: 4/3;
    background: linear-gradient(90deg, rgba(255,255,255,0.04) 25%, rgba(255,255,255,0.09) 50%, rgba(255,255,255,0.04) 75%);
    background-size: 600px 100%;
    animation: shimmerSkeleton 1.4s infinite linear;
}
.disc-skeleton-body { padding: 1.1rem 1.2rem 1.2rem; display: flex; flex-direction: column; gap: 0.7rem; }
.disc-skeleton-line {
    border-radius: 8px;
    background: linear-gradient(90deg, rgba(255,255,255,0.04) 25%, rgba(255,255,255,0.09) 50%, rgba(255,255,255,0.04) 75%);
    background-size: 600px 100%;
    animation: shimmerSkeleton 1.4s infinite linear;
}

/* ─── EMPTY STATE ───────────────────────────────────────────────────────── */
.disc-empty {
    grid-column: 1/-1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 6rem 2rem;
    text-align: center;
}
.disc-empty-orb {
    width: 96px; height: 96px;
    border-radius: 50%;
    background: rgba(26,179,255,0.08);
    border: 1px solid rgba(26,179,255,0.15);
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 1.5rem;
    box-shadow: 0 0 40px rgba(26,179,255,0.08);
}
.disc-empty h3 {
    font-size: 1.3rem;
    font-weight: 900;
    color: #fff;
    margin-bottom: 0.4rem;
}
.disc-empty p {
    font-size: 0.9rem;
    color: var(--text-muted);
    font-weight: 600;
    margin-bottom: 1.5rem;
}
.disc-empty-btn {
    padding: 0.65rem 1.8rem;
    border-radius: 100px;
    background: var(--orange);
    color: #fff;
    font-weight: 800;
    font-size: 0.88rem;
    text-decoration: none;
    box-shadow: 0 4px 16px var(--orange-glow);
    transition: all 0.25s var(--spring);
}
.disc-empty-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px var(--orange-glow);
}

/* ─── NOTIFICATION FORM ──────────────────────────────────────────────────── */
.notif-card {
    width: 100%;
    max-width: 440px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(26,179,255,0.25);
    border-radius: 16px;
    padding: 1.25rem 1.5rem 1rem;
    margin-top: 1.25rem;
    backdrop-filter: blur(12px);
}
.notif-toggle {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}
.notif-toggle button {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    padding: 0.5rem 1rem;
    border-radius: 100px;
    border: 1px solid rgba(255,255,255,0.12);
    background: transparent;
    color: rgba(255,255,255,0.5);
    font-family: 'Nunito', sans-serif;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}
.notif-toggle button:hover { color: #fff; border-color: rgba(255,255,255,0.3); }
.notif-toggle-active {
    background: rgba(26,179,255,0.18) !important;
    border-color: rgba(26,179,255,0.6) !important;
    color: #1AB3FF !important;
}
.notif-input-row {
    display: flex;
    gap: 0.5rem;
    align-items: stretch;
}
.notif-input {
    flex: 1;
    padding: 0.65rem 1rem;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.15);
    background: rgba(255,255,255,0.06);
    color: #fff;
    font-family: 'Nunito', sans-serif;
    font-size: 0.88rem;
    outline: none;
    transition: border-color 0.2s;
    min-width: 0;
}
.notif-input::placeholder { color: rgba(255,255,255,0.35); }
.notif-input:focus { border-color: rgba(26,179,255,0.6); }
.notif-btn {
    padding: 0.65rem 1.2rem;
    border-radius: 10px;
    border: none;
    background: var(--orange);
    color: #fff;
    font-family: 'Nunito', sans-serif;
    font-size: 0.85rem;
    font-weight: 800;
    cursor: pointer;
    white-space: nowrap;
    box-shadow: 0 4px 14px var(--orange-glow);
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 100px;
}
.notif-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 18px var(--orange-glow); }
.notif-btn:disabled { opacity: 0.6; cursor: wait; }
.notif-spinner {
    width: 16px; height: 16px;
    border: 2px solid rgba(255,255,255,0.4);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
    display: inline-block;
}
@keyframes spin { to { transform: rotate(360deg); } }
.notif-error {
    color: #f87171;
    font-size: 0.78rem;
    margin-top: 0.6rem;
    font-weight: 600;
}
.notif-hint {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    color: rgba(255,255,255,0.3);
    font-size: 0.73rem;
    margin-top: 0.75rem;
}
.notif-success {
    width: 100%;
    max-width: 440px;
    text-align: center;
    padding: 1.5rem;
    background: rgba(74,222,128,0.07);
    border: 1px solid rgba(74,222,128,0.25);
    border-radius: 16px;
    margin-top: 1.25rem;
}
.notif-success-icon {
    width: 56px; height: 56px;
    border-radius: 50%;
    background: rgba(74,222,128,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.75rem;
}
.notif-success h4 {
    color: #4ade80;
    font-size: 1rem;
    font-weight: 800;
    margin: 0 0 0.4rem;
}
.notif-success p {
    color: rgba(255,255,255,0.6);
    font-size: 0.85rem;
    margin: 0;
}

/* ─── PAGINATION ────────────────────────────────────────────────────────── */
.disc-pagination {
    display: flex;
    justify-content: center;
    margin-top: 3rem;
    gap: 0.4rem;
}
/* Override Laravel default pagination */
.disc-page nav { display: flex; }
nav[aria-label="Pagination"] svg { display: none; }

/* ─── CARD REVEAL ANIMATION ─────────────────────────────────────────────── */
.tc-reveal {
    opacity: 0;
    transform: translateY(36px) scale(0.97);
    transition:
        opacity 0.65s var(--spring),
        transform 0.65s var(--spring);
    will-change: transform, opacity;
}
.tc-reveal.is-visible { opacity: 1; transform: translateY(0) scale(1); }

/* Stagger per column */
.td-0 { transition-delay: 0.00s; }
.td-1 { transition-delay: 0.08s; }
.td-2 { transition-delay: 0.16s; }

/* ─── HERO ANIMATIONS ───────────────────────────────────────────────────── */
@keyframes heroUp {
    from { opacity: 0; transform: translateY(28px); }
    to   { opacity: 1; transform: translateY(0); }
}
.ha-0 { animation: heroUp 0.8s var(--spring) 0.05s both; }
.ha-1 { animation: heroUp 0.8s var(--spring) 0.18s both; }
.ha-2 { animation: heroUp 0.8s var(--spring) 0.30s both; }
.ha-3 { animation: heroUp 0.8s var(--spring) 0.44s both; }
.ha-4 { animation: heroUp 0.7s var(--spring) 0.58s both; }

/* ─── AMBIENT LIGHT ORBS ────────────────────────────────────────────────── */
.disc-orb {
    position: fixed;
    border-radius: 50%;
    pointer-events: none;
    filter: blur(80px);
    z-index: 0;
    opacity: 0.5;
}
.disc-orb-1 {
    width: 500px; height: 500px;
    top: -150px; right: -100px;
    background: radial-gradient(circle, rgba(26,179,255,0.12) 0%, transparent 70%);
}
.disc-orb-2 {
    width: 600px; height: 400px;
    bottom: 200px; left: -150px;
    background: radial-gradient(circle, rgba(26,39,68,0.6) 0%, transparent 70%);
}
</style>
@endsection

@section('content')
<div class="disc-page">

    {{-- Ambient orbs --}}
    <div class="disc-orb disc-orb-1"></div>
    <div class="disc-orb disc-orb-2"></div>

    {{-- ══════════════════════════════════════════════
         HERO
    ════════════════════════════════════════════════ --}}
    <section class="disc-hero">
        <p class="disc-eyebrow ha-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="#1AB3FF" viewBox="0 0 24 24">
                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
            </svg>
            Talents Certifiés · Côte d'Ivoire
        </p>

        <h1 class="disc-title ha-1">
            Découvrez les<br><span>Talents</span>
        </h1>

        <p class="disc-subtitle ha-2">
            {{ $talents->total() }} artiste{{ $talents->total() > 1 ? 's' : '' }} disponible{{ $talents->total() > 1 ? 's' : '' }} · Réservation instantanée
        </p>

        {{-- Search bar --}}
        <form method="GET" action="{{ route('talents.index') }}" class="disc-search-wrap ha-3">
            <svg class="disc-search-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                 fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" name="search" value="{{ request('search') }}"
                   class="disc-search" placeholder="DJ, musicien, animateur, ville…">
            @foreach(request()->except('search', 'page') as $k => $v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach
        </form>

        {{-- Category pills — dynamiques depuis la DB --}}
        <div class="disc-cats ha-4">
            <a href="{{ route('talents.index', request()->except('category','page')) }}"
               class="disc-cat-pill {{ !request('category') ? 'active' : '' }}">
                ✦ Tous
            </a>
            @foreach($categories as $cat)
            <a href="{{ route('talents.index', ['category' => $cat->name] + request()->except('category','page')) }}"
               class="disc-cat-pill {{ request('category') === $cat->name ? 'active' : '' }}">
                {{ $cat->name }}
            </a>
            @endforeach
        </div>
    </section>

    {{-- ══════════════════════════════════════════════
         STICKY FILTER BAR
    ════════════════════════════════════════════════ --}}
    <form method="GET" action="{{ route('talents.index') }}" id="filterForm">
        <div class="disc-filterbar">
            <div class="disc-filterbar-inner">

                {{-- Preserve search --}}
                @if(request('search'))
                <input type="hidden" name="search" value="{{ request('search') }}">
                @endif

                {{-- Ville --}}
                <select name="city" class="disc-select" onchange="document.getElementById('filterForm').submit()">
                    <option value="">📍 Toutes les villes</option>
                    @foreach(['Abidjan','Bouaké','Daloa','Korhogo','Yamoussoukro','San Pedro','Gagnoa','Man'] as $city)
                    <option value="{{ $city }}" {{ request('city') === $city ? 'selected' : '' }}>{{ $city }}</option>
                    @endforeach
                </select>

                {{-- Catégorie --}}
                <select name="category" class="disc-select" onchange="document.getElementById('filterForm').submit()">
                    <option value="">🎭 Toutes catégories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->name }}" {{ request('category') === $cat->name ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>

                {{-- Tri --}}
                <select name="sort" class="disc-select" onchange="document.getElementById('filterForm').submit()">
                    <option value="recent"     {{ request('sort','recent') === 'recent'     ? 'selected' : '' }}>🕐 Plus récents</option>
                    <option value="price_asc"  {{ request('sort') === 'price_asc'           ? 'selected' : '' }}>↑ Prix croissant</option>
                    <option value="price_desc" {{ request('sort') === 'price_desc'          ? 'selected' : '' }}>↓ Prix décroissant</option>
                </select>

                <button type="submit" class="disc-filter-btn">
                    Filtrer
                </button>

                @if(request()->hasAny(['search','category','city','sort']))
                <a href="{{ route('talents.index') }}" class="disc-reset">✕ Effacer</a>
                @endif

                <span class="disc-count-badge">
                    <strong>{{ $talents->total() }}</strong> talent{{ $talents->total() > 1 ? 's' : '' }}
                </span>

            </div>
        </div>
    </form>

    {{-- ══════════════════════════════════════════════
         GRID
    ════════════════════════════════════════════════ --}}
    <div class="disc-grid-section">
        <div class="disc-grid">

            @if($talents->isEmpty())
            @php $searchQuery = request('search', ''); @endphp
            <div class="disc-empty"
                 x-data="{
                    contact: 'email',
                    email: '',
                    phone: '',
                    loading: false,
                    success: false,
                    error: '',
                    async submit() {
                        this.error = '';
                        if (this.contact === 'email' && !this.email) { this.error = 'Veuillez saisir votre adresse email.'; return; }
                        if (this.contact === 'phone' && !this.phone) { this.error = 'Veuillez saisir votre numéro de téléphone.'; return; }
                        this.loading = true;
                        try {
                            const body = new FormData();
                            body.append('_token', document.querySelector('meta[name=csrf-token]').content);
                            body.append('search_query', '{{ addslashes($searchQuery) }}');
                            if (this.contact === 'email') body.append('email', this.email);
                            else body.append('phone', this.phone);
                            const res = await fetch('{{ route('talents.notify') }}', { method: 'POST', body });
                            const json = await res.json();
                            if (json.success) { this.success = true; }
                            else { this.error = json.message || 'Une erreur est survenue.'; }
                        } catch(e) { this.error = 'Une erreur réseau est survenue.'; }
                        this.loading = false;
                    }
                 }">

                {{-- Icône et titre --}}
                <div class="disc-empty-orb">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36"
                         fill="none" stroke="#1AB3FF" stroke-width="1.5" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                    </svg>
                </div>

                @if($searchQuery)
                    <h3>« {{ $searchQuery }} » n'est pas encore sur BookMi</h3>
                    <p>Cet artiste n'est pas encore disponible sur la plateforme.<br>
                       Laissez votre contact et nous vous préviendrons dès qu'il rejoint BookMi !</p>
                @else
                    <h3>Aucun talent trouvé</h3>
                    <p>Essayez d'autres filtres ou explorez tous nos artistes.</p>
                @endif

                @if($searchQuery)
                {{-- ── Formulaire de notification ── --}}
                <div class="notif-card" x-show="!success">
                    <div class="notif-toggle">
                        <button type="button"
                                :class="contact === 'email' ? 'notif-toggle-active' : ''"
                                @click="contact = 'email'">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/></svg>
                            Email
                        </button>
                        <button type="button"
                                :class="contact === 'phone' ? 'notif-toggle-active' : ''"
                                @click="contact = 'phone'">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            Téléphone
                        </button>
                    </div>

                    <div class="notif-input-row">
                        <div x-show="contact === 'email'" style="width:100%">
                            <input type="email" x-model="email"
                                   class="notif-input"
                                   placeholder="votre@email.com"
                                   @keydown.enter="submit()">
                        </div>
                        <div x-show="contact === 'phone'" style="width:100%">
                            <input type="tel" x-model="phone"
                                   class="notif-input"
                                   placeholder="+225 07 00 00 00 00"
                                   @keydown.enter="submit()">
                        </div>
                        <button type="button" class="notif-btn" @click="submit()" :disabled="loading">
                            <span x-show="!loading">Me notifier</span>
                            <span x-show="loading" class="notif-spinner"></span>
                        </button>
                    </div>

                    <p x-show="error" x-text="error" class="notif-error"></p>
                    <p class="notif-hint">
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Vos données ne seront jamais partagées avec des tiers.
                    </p>
                </div>

                {{-- ── État succès ── --}}
                <div class="notif-success" x-show="success" x-cloak>
                    <div class="notif-success-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" stroke="#4ade80" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <h4>Parfait, vous êtes sur la liste !</h4>
                    <p>Nous vous contacterons dès que <strong>{{ $searchQuery }}</strong> rejoint BookMi.</p>
                </div>
                @endif

                <a href="{{ route('talents.index') }}" class="disc-empty-btn" style="margin-top:1.5rem;">
                    Voir tous les talents →
                </a>
            </div>

            @else
            @foreach($talents as $talent)
            @php
                $catName  = $talent->category?->name ?? 'Artiste';
                $catClass = 'cat-' . strtolower(str_replace(['é','è','ê'], 'e', preg_replace('/[^a-zA-Zéèê]/', '', $catName)));
                if (!in_array($catClass, ['cat-dj','cat-musicien','cat-danseur','cat-chanteur','cat-animateur','cat-comedien'])) {
                    $catClass = 'cat-default';
                }
                $colDelay = 'td-' . ($loop->index % 3);
                $initials = strtoupper(mb_substr($talent->stage_name ?? 'A', 0, 2));
                $price = $talent->servicePackages?->min('price') ?? $talent->cachet_amount ?? 0;
                $rating = (float) ($talent->average_rating ?? 0);
                $stars = min(5, max(0, round($rating)));
                $slugOrId = $talent->slug ?? $talent->id;
            @endphp

            <div class="tc tc-reveal {{ $colDelay }}">
                {{-- Photo --}}
                <a href="{{ route('talent.show', $slugOrId) }}" style="display:block;text-decoration:none;">
                    <div class="tc-photo">
                        @if($talent->cover_photo_url ?? false)
                            <img src="{{ $talent->cover_photo_url }}"
                                 alt="{{ $talent->stage_name }}"
                                 loading="lazy">
                        @else
                            <div class="tc-photo-placeholder">
                                <div class="tc-photo-monogram">{{ $initials }}</div>
                            </div>
                        @endif
                        <div class="tc-photo-overlay"></div>

                        {{-- Category badge --}}
                        <span class="tc-badge-cat {{ $catClass }}">{{ $catName }}</span>

                        {{-- Verified --}}
                        @if($talent->is_verified)
                        <span class="tc-badge-verified">
                            <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="#4ade80">
                                <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/>
                                <path fill="none" stroke="#0d1117" stroke-width="3" stroke-linecap="round" d="m9 12 2 2 4-4"/>
                            </svg>
                            Vérifié
                        </span>
                        @endif
                    </div>
                </a>

                {{-- Body --}}
                <div class="tc-body">
                    <a href="{{ route('talent.show', $slugOrId) }}" style="text-decoration:none;">
                        <div class="tc-name">{{ $talent->stage_name }}</div>
                    </a>

                    <div class="tc-meta">
                        {{-- City --}}
                        <span class="tc-city">
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none"
                                 stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M20 10c0 6-8 13-8 13s-8-7-8-13a8 8 0 0 1 16 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            {{ $talent->city ?? '—' }}
                        </span>

                        {{-- Stars --}}
                        <span class="tc-rating">
                            <span class="tc-rating-stars">
                                @for($s = 1; $s <= 5; $s++)
                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"
                                     fill="{{ $s <= $stars ? '#FF9800' : 'rgba(255,255,255,0.15)' }}"
                                     viewBox="0 0 24 24">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                                @endfor
                            </span>
                            {{ number_format($rating, 1) }}
                        </span>
                    </div>

                    <div class="tc-divider"></div>

                    <div class="tc-footer">
                        @if($price > 0)
                        <div class="tc-price">
                            à partir de
                            <strong>{{ number_format($price, 0, ',', ' ') }} F</strong>
                        </div>
                        @else
                        <div class="tc-price"><strong style="color:rgba(255,255,255,0.4)">Sur devis</strong></div>
                        @endif

                        <a href="{{ route('talent.show', $slugOrId) }}" class="tc-btn">
                            Réserver
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none"
                                 stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
            @endif

        </div>

        {{-- Pagination --}}
        @if($talents->hasPages())
        <div class="disc-pagination">
            {{ $talents->appends(request()->query())->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

@section('scripts')
<script>
(function () {
    'use strict';

    /* ── Card reveal via IntersectionObserver ─────────────────────────── */
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });

    document.querySelectorAll('.tc-reveal').forEach(function (el) {
        observer.observe(el);
    });

    /* ── Search on Enter (hero search) ───────────────────────────────── */
    var heroSearch = document.querySelector('.disc-search');
    if (heroSearch) {
        heroSearch.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.target.closest('form').submit(); }
        });
    }

    /* ── Subtle parallax on ambient orbs ─────────────────────────────── */
    var orb1 = document.querySelector('.disc-orb-1');
    var orb2 = document.querySelector('.disc-orb-2');
    if (orb1 && orb2 && window.matchMedia('(min-width:768px)').matches) {
        window.addEventListener('mousemove', function (e) {
            var mx = (e.clientX / window.innerWidth - 0.5) * 30;
            var my = (e.clientY / window.innerHeight - 0.5) * 20;
            orb1.style.transform = 'translate(' + mx + 'px,' + my + 'px)';
            orb2.style.transform = 'translate(' + (-mx * 0.5) + 'px,' + (-my * 0.5) + 'px)';
        }, { passive: true });
    }

})();
</script>
@endsection
