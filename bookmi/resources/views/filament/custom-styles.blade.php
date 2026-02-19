<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ============================================================
   BookMi Admin Theme — Charte graphique officielle
   Navy #1A2744 (Book) | Blue #2196F3 (Mi) | Orange #FF6B35 (CTA)
   Font: Nunito
   ============================================================ */

/* ── Typography ── */
*, body {
    font-family: 'Nunito', sans-serif !important;
}

/* ── Logo "Book" : navy par défaut (login blanc), blanc dans la sidebar ── */
.bookmi-logo-book {
    color: #1A2744;
}
.fi-sidebar .bookmi-logo-book {
    color: #ffffff !important;
}

/* ── Sidebar — fond Navy #1A2744 ── */
nav[class*="fi-sidebar"],
.fi-sidebar,
[class*="fi-sidebar"]:not([class*="item"]):not([class*="group"]):not([class*="label"]):not([class*="btn"]) {
    background-color: #1A2744 !important;
}

/* Header de la sidebar (logo zone) */
.fi-sidebar-header,
[class*="fi-sidebar-header"] {
    background-color: #1A2744 !important;
    border-bottom: 1px solid rgba(255,255,255,0.08) !important;
}

/* Nav items texte */
.fi-sidebar-item-button,
[class*="fi-sidebar-item-button"] {
    color: rgba(255,255,255,0.65) !important;
    border-radius: 0.5rem !important;
}

.fi-sidebar-item-button:hover {
    background-color: rgba(255,255,255,0.06) !important;
    color: #fff !important;
}

/* Item actif */
.fi-active .fi-sidebar-item-button,
.fi-sidebar-item-button[aria-current],
.fi-active > .fi-sidebar-item-button {
    background-color: rgba(33,150,243,0.18) !important;
    color: #ffffff !important;
}

/* Icônes sidebar */
.fi-sidebar-item-icon {
    color: rgba(255,255,255,0.45) !important;
}

.fi-active .fi-sidebar-item-icon,
.fi-active > .fi-sidebar-item-button .fi-sidebar-item-icon {
    color: #2196F3 !important;
}

/* Labels de groupe */
.fi-sidebar-group-label,
[class*="fi-sidebar-group-label"] {
    color: rgba(255,255,255,0.40) !important;
    font-size: 0.65rem !important;
    font-weight: 700 !important;
    letter-spacing: 0.08em !important;
    text-transform: uppercase !important;
}

/* Chevron collapse sidebar */
.fi-sidebar-group-collapse-button svg,
[class*="fi-sidebar-group"] button svg {
    color: rgba(255,255,255,0.35) !important;
}

/* ── Topbar / Header ── */
.fi-topbar,
[class*="fi-topbar"] {
    border-bottom: 1px solid #e5e7eb !important;
    box-shadow: 0 1px 4px rgba(26,39,68,0.06) !important;
}

/* ── Page titles ── */
.fi-header-heading,
[class*="fi-header-heading"] {
    font-weight: 800 !important;
    color: #1A2744 !important;
}

.fi-header-subheading {
    color: #6b7280 !important;
}

/* ── Login page — fond dégradé Navy ── */
.fi-body.fi-panel-admin:has(.fi-simple-layout) {
    background-color: #1A2744 !important;
}

.fi-simple-layout,
[class*="fi-simple-layout"] {
    background: linear-gradient(135deg, #1A2744 0%, #0F3460 100%) !important;
    background-color: #1A2744 !important;
    min-height: 100vh !important;
}

/* Container qui entoure la card — doit être transparent pour laisser voir le fond navy */
.fi-simple-main-ctn,
[class*="fi-simple-main-ctn"] {
    background-color: transparent !important;
}

.fi-simple-main,
[class*="fi-simple-main"]:not([class*="fi-simple-main-ctn"]) {
    background: #ffffff !important;
    border-radius: 1rem !important;
    box-shadow: 0 20px 60px rgba(26,39,68,0.3) !important;
}

/* ── Boutons primaires ── */
.fi-btn-color-primary {
    background-color: #2196F3 !important;
    border-color: #2196F3 !important;
}
.fi-btn-color-primary:hover {
    background-color: #1976D2 !important;
    border-color: #1976D2 !important;
}

/* ── Tables ── */
.fi-ta-row:hover > td,
.fi-ta-row:hover > th {
    background-color: #f0f7ff !important;
}

/* ── Cards / Stats ── */
.fi-wi-stats-overview-stat {
    border-radius: 0.75rem !important;
}

/* ── Inputs focus ring ── */
.fi-input:focus,
[class*="fi-input"]:focus {
    border-color: #2196F3 !important;
    --tw-ring-color: rgba(33,150,243,0.25) !important;
}

/* ── Scrollbar sidebar ── */
.fi-sidebar::-webkit-scrollbar { width: 4px; }
.fi-sidebar::-webkit-scrollbar-track { background: transparent; }
.fi-sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.15);
    border-radius: 2px;
}
</style>
