<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ============================================================
   BookMi Admin Theme — iOS 26 Glassmorphism
   Navy #1A2744 (Book) | Blue #2196F3 (Mi) | Orange #FF6B35 (CTA)
   Font: Nunito
   ============================================================ */

/* ── Typography ── */
*, body {
    font-family: 'Nunito', sans-serif !important;
}

/* ── Logo "Book" : navy en contexte clair, blanc dans la sidebar ── */
.bookmi-logo-book { color: #1A2744; }
.fi-sidebar .bookmi-logo-book { color: #ffffff !important; }

/* ================================================================
   SIDEBAR — Navy glassmorphism
   ================================================================ */

.fi-sidebar,
nav[class*="fi-sidebar"] {
    background: linear-gradient(180deg, #1A2744 0%, #0F1E3A 100%) !important;
    border-right: 1px solid rgba(255,255,255,0.06) !important;
    display: flex !important;
    flex-direction: column !important;
}

.fi-sidebar-header,
[class*="fi-sidebar-header"] {
    background: transparent !important;
    border-bottom: 1px solid rgba(255,255,255,0.07) !important;
}

/* Navigation wrapper — doit pousser le footer vers le bas */
.fi-sidebar-nav,
.fi-sidebar > nav,
.fi-sidebar-group {
    flex: 1;
}

/* ── Textes nav — BLANC ── */
.fi-sidebar-item-button span,
.fi-sidebar-item-button,
[class*="fi-sidebar-item"] span {
    color: rgba(255,255,255,0.88) !important;
}

.fi-sidebar-item-button {
    border-radius: 0.5rem !important;
    transition: all 150ms ease !important;
}

.fi-sidebar-item-button:hover {
    background-color: rgba(255,255,255,0.08) !important;
    color: #ffffff !important;
}

.fi-sidebar-item-button:hover span {
    color: #ffffff !important;
}

/* ── Item ACTIF — Filament v3 utilise .fi-sidebar-item-active sur le LI parent ── */
.fi-sidebar-item-active .fi-sidebar-item-button {
    background: linear-gradient(90deg, rgba(33,150,243,0.28), rgba(33,150,243,0.10)) !important;
    border-left: 3px solid #2196F3 !important;
    padding-left: calc(0.75rem - 3px) !important;
}

.fi-sidebar-item-active .fi-sidebar-item-button span {
    color: #ffffff !important;
    font-weight: 700 !important;
}

/* Icône de l'item actif uniquement */
.fi-sidebar-item-active .fi-sidebar-item-button .fi-sidebar-item-icon {
    color: #2196F3 !important;
}

/* Icônes des items inactifs */
.fi-sidebar-item-icon {
    color: rgba(255,255,255,0.45) !important;
}

/* Items non-actifs — reset (y compris ceux dans un groupe actif) */
.fi-sidebar-item:not(.fi-sidebar-item-active) .fi-sidebar-item-button {
    background: transparent !important;
    border-left: none !important;
    padding-left: 0.75rem !important;
}

.fi-sidebar-item:not(.fi-sidebar-item-active) .fi-sidebar-item-button span {
    color: rgba(255,255,255,0.88) !important;
    font-weight: normal !important;
}

.fi-sidebar-item:not(.fi-sidebar-item-active) .fi-sidebar-item-button .fi-sidebar-item-icon {
    color: rgba(255,255,255,0.45) !important;
}

/* Labels groupes — uppercase petits */
.fi-sidebar-group-label,
[class*="fi-sidebar-group-label"] {
    color: rgba(255,255,255,0.38) !important;
    font-size: 0.62rem !important;
    font-weight: 700 !important;
    letter-spacing: 0.1em !important;
    text-transform: uppercase !important;
}

/* Chevrons collapse */
.fi-sidebar-group-collapse-button,
.fi-sidebar-group-collapse-button svg {
    color: rgba(255,255,255,0.35) !important;
}

/* Scrollbar sidebar */
.fi-sidebar::-webkit-scrollbar { width: 3px; }
.fi-sidebar::-webkit-scrollbar-track { background: transparent; }
.fi-sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.12);
    border-radius: 2px;
}

/* ================================================================
   TOPBAR
   ================================================================ */

.fi-topbar {
    background: rgba(255,255,255,0.92) !important;
    backdrop-filter: blur(20px) saturate(180%) !important;
    -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
    border-bottom: 1px solid rgba(26,39,68,0.08) !important;
    box-shadow: 0 1px 8px rgba(26,39,68,0.06) !important;
}

/* ================================================================
   MAIN CONTENT — fond dégradé pour glassmorphism visible
   ================================================================ */

.fi-main,
main.fi-main {
    background: linear-gradient(135deg,
        #dbeafe 0%,
        #e8e4ff 25%,
        #f0ebff 50%,
        #ddf4ff 75%,
        #d1fae5 100%
    ) !important;
    min-height: 100vh !important;
}

/* Page header */
.fi-header-heading {
    font-weight: 800 !important;
    color: #1A2744 !important;
}
.fi-header-subheading { color: #6b7280 !important; }

/* ================================================================
   GLASSMORPHISM — Cartes iOS 26 style
   ================================================================ */

/* Cards/Sections principales */
.fi-section,
.fi-wi-stats-overview,
.fi-card,
[class*="fi-section"]:not([class*="fi-section-header"]):not([class*="fi-section-content"]) {
    background: rgba(255,255,255,0.72) !important;
    backdrop-filter: blur(20px) saturate(180%) !important;
    -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
    border: 1px solid rgba(255,255,255,0.85) !important;
    border-radius: 1rem !important;
    box-shadow:
        0 4px 24px rgba(26,39,68,0.08),
        0 1px 4px rgba(26,39,68,0.04),
        inset 0 1px 0 rgba(255,255,255,0.9) !important;
}

/* Stats overview widgets */
.fi-wi-stats-overview-stat {
    background: rgba(255,255,255,0.70) !important;
    backdrop-filter: blur(16px) saturate(160%) !important;
    -webkit-backdrop-filter: blur(16px) saturate(160%) !important;
    border: 1px solid rgba(255,255,255,0.82) !important;
    border-radius: 0.875rem !important;
    box-shadow:
        0 4px 20px rgba(26,39,68,0.07),
        inset 0 1px 0 rgba(255,255,255,0.95) !important;
    transition: transform 150ms ease, box-shadow 150ms ease !important;
}

.fi-wi-stats-overview-stat:hover {
    transform: translateY(-2px) !important;
    box-shadow:
        0 8px 32px rgba(26,39,68,0.12),
        inset 0 1px 0 rgba(255,255,255,0.95) !important;
}

/* Account widget (carte "Bonjour") */
.fi-wi-account {
    background: rgba(255,255,255,0.75) !important;
    backdrop-filter: blur(20px) !important;
    -webkit-backdrop-filter: blur(20px) !important;
    border: 1px solid rgba(255,255,255,0.85) !important;
    border-radius: 1rem !important;
}

/* Tables */
.fi-ta-ctn,
.fi-ta-table-ctn {
    background: rgba(255,255,255,0.72) !important;
    backdrop-filter: blur(16px) !important;
    -webkit-backdrop-filter: blur(16px) !important;
    border: 1px solid rgba(255,255,255,0.82) !important;
    border-radius: 1rem !important;
    overflow: hidden !important;
}

/* Header de tableau */
.fi-ta-header {
    background: rgba(248,250,252,0.8) !important;
    backdrop-filter: blur(8px) !important;
}

/* Hover sur les lignes de tableau */
.fi-ta-row:hover > td,
.fi-ta-row:hover > th {
    background-color: rgba(33,150,243,0.05) !important;
}

/* ================================================================
   BOUTONS — Brand Blue #2196F3
   ================================================================ */

.fi-btn-color-primary {
    background: linear-gradient(135deg, #2196F3, #1976D2) !important;
    border-color: #2196F3 !important;
    box-shadow: 0 2px 8px rgba(33,150,243,0.3) !important;
}
.fi-btn-color-primary:hover {
    background: linear-gradient(135deg, #1976D2, #1565C0) !important;
    box-shadow: 0 4px 16px rgba(33,150,243,0.4) !important;
    transform: translateY(-1px) !important;
}

/* ================================================================
   INPUTS
   ================================================================ */

.fi-input:focus,
[class*="fi-input"]:focus {
    border-color: #2196F3 !important;
    --tw-ring-color: rgba(33,150,243,0.2) !important;
}

/* ================================================================
   LOGIN PAGE — fond navy dégradé + card glassmorphism
   ================================================================ */

.fi-body.fi-panel-admin:has(.fi-simple-layout) {
    background-color: #1A2744 !important;
}

.fi-simple-layout {
    background: linear-gradient(135deg, #1A2744 0%, #0F3460 100%) !important;
    background-color: #1A2744 !important;
    min-height: 100vh !important;
}

.fi-simple-main-ctn { background-color: transparent !important; }

.fi-simple-main:not([class*="fi-simple-main-ctn"]) {
    background: rgba(255,255,255,0.95) !important;
    backdrop-filter: blur(40px) saturate(200%) !important;
    -webkit-backdrop-filter: blur(40px) saturate(200%) !important;
    border: 1px solid rgba(255,255,255,0.8) !important;
    border-radius: 1.25rem !important;
    box-shadow:
        0 24px 64px rgba(0,0,0,0.3),
        inset 0 1px 0 rgba(255,255,255,0.9) !important;
}

/* ================================================================
   BADGES
   ================================================================ */

.fi-badge-color-primary {
    background: rgba(33,150,243,0.12) !important;
    color: #1565C0 !important;
}

/* ================================================================
   RESPONSIVE — Grilles adaptatives
   ================================================================ */

/* Widgets grid — responsive sur mobile */
@media (max-width: 640px) {
    .fi-wi-stats-overview-stats-ctn {
        grid-template-columns: 1fr !important;
    }
    .fi-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 0.75rem !important;
    }
    .fi-main-ctn {
        padding: 1rem !important;
    }
    .fi-ta-ctn {
        overflow-x: auto !important;
    }
}

@media (min-width: 641px) and (max-width: 1024px) {
    .fi-wi-stats-overview-stats-ctn {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

/* Sidebar responsive — s'effondre sur mobile */
@media (max-width: 1024px) {
    .fi-sidebar {
        position: fixed !important;
        z-index: 50 !important;
        height: 100vh !important;
        overflow-y: auto !important;
    }
}

/* Tables responsive */
.fi-ta-table-ctn {
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch !important;
}

/* Formulaires responsive */
@media (max-width: 768px) {
    .fi-fo-section-ctn {
        grid-template-columns: 1fr !important;
    }
    .fi-fo-component-ctn {
        grid-template-columns: 1fr !important;
    }
}
</style>
