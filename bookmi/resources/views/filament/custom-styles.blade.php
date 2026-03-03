<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap" rel="stylesheet">

<style>
/* ============================================================
   BookMi Admin — Clarté Alpine
   Design validé : light editorial, Nunito, navy sidebar
   Fond #EEF2FA · Cartes blanches · Ombres douces
   ============================================================ */

/* ── Variables ── */
:root {
    --bm-bg:          #EEF2FA;
    --bm-bg2:         #E4EAF8;
    --bm-card:        #FFFFFF;
    --bm-card2:       #F7FAFF;
    --bm-border:      #DDE4F0;
    --bm-sidebar:     #1A2744;
    --bm-blue:        #2196F3;
    --bm-blue-d:      #1565C0;
    --bm-blue-bg:     #EBF5FE;
    --bm-green-bg:    #F0FDF4;
    --bm-red-bg:      #FEF2F2;
    --bm-amber-bg:    #FFFBEB;
    --bm-text:        #1A2744;
    --bm-text2:       #374151;
    --bm-muted:       #6B7280;
    --bm-muted2:      #94A3B8;
    --bm-shadow:      0 1px 3px rgba(0,0,0,0.07), 0 4px 16px rgba(0,0,0,0.04);
    --bm-shadow2:     0 2px 8px rgba(0,0,0,0.08), 0 8px 32px rgba(0,0,0,0.06);
    --bm-radius:      0.875rem;
}

/* ── Typographie globale — Nunito ── */
*, body, input, button, select, textarea {
    font-family: 'Nunito', sans-serif !important;
}

/* ── Logo ── */
.bookmi-logo-book { color: #1A2744; }
.fi-sidebar .bookmi-logo-book { color: #ffffff !important; }

/* ================================================================
   FOND GÉNÉRAL — Bleu-gris clair
   ================================================================ */

.fi-body,
html body {
    background-color: var(--bm-bg) !important;
}

.fi-main,
main.fi-main {
    background-color: var(--bm-bg) !important;
    background-image: none !important;
    min-height: 100vh !important;
}

.fi-main-ctn {
    background-color: transparent !important;
}

/* ================================================================
   SIDEBAR — Navy raffinée
   ================================================================ */

.fi-sidebar,
nav[class*="fi-sidebar"] {
    background: linear-gradient(180deg, #1E2E54 0%, #1A2744 40%, #131E38 100%) !important;
    border-right: 1px solid rgba(255,255,255,0.07) !important;
    display: flex !important;
    flex-direction: column !important;
}

.fi-sidebar-header,
[class*="fi-sidebar-header"] {
    background: transparent !important;
    border-bottom: 1px solid rgba(255,255,255,0.08) !important;
}

/* Navigation wrapper */
.fi-sidebar-nav,
.fi-sidebar > nav,
.fi-sidebar-group {
    flex: 1;
}

/* ── Items nav ── */
.fi-sidebar-item-button span,
.fi-sidebar-item-button,
[class*="fi-sidebar-item"] span {
    color: rgba(255,255,255,0.65) !important;
    font-weight: 500 !important;
}

.fi-sidebar-item-button {
    border-radius: 0 0.5rem 0.5rem 0 !important;
    transition: all 140ms ease !important;
}

.fi-sidebar-item-button:hover {
    background-color: rgba(255,255,255,0.07) !important;
}
.fi-sidebar-item-button:hover span {
    color: rgba(255,255,255,0.92) !important;
}

/* Item actif — border-left bleue */
.fi-sidebar-item-active .fi-sidebar-item-button {
    background: rgba(33,150,243,0.16) !important;
    border-left: 3px solid #2196F3 !important;
    padding-left: calc(0.75rem - 3px) !important;
    box-shadow: none !important;
}
.fi-sidebar-item-active .fi-sidebar-item-button span {
    color: #ffffff !important;
    font-weight: 700 !important;
}
.fi-sidebar-item-active .fi-sidebar-item-button .fi-sidebar-item-icon {
    color: #64B5F6 !important;
}

/* Items inactifs */
.fi-sidebar-item:not(.fi-sidebar-item-active) .fi-sidebar-item-button {
    background: transparent !important;
    border-left: none !important;
    padding-left: 0.75rem !important;
}
.fi-sidebar-item:not(.fi-sidebar-item-active) .fi-sidebar-item-button span {
    color: rgba(255,255,255,0.65) !important;
    font-weight: 500 !important;
}
.fi-sidebar-item:not(.fi-sidebar-item-active) .fi-sidebar-item-button .fi-sidebar-item-icon {
    color: rgba(255,255,255,0.4) !important;
}

/* Labels de groupes */
.fi-sidebar-group-label,
[class*="fi-sidebar-group-label"] {
    color: rgba(255,255,255,0.3) !important;
    font-size: 0.6rem !important;
    font-weight: 800 !important;
    letter-spacing: 0.13em !important;
    text-transform: uppercase !important;
}

/* Chevrons */
.fi-sidebar-group-collapse-button,
.fi-sidebar-group-collapse-button svg {
    color: rgba(255,255,255,0.3) !important;
}

/* Scrollbar sidebar */
.fi-sidebar::-webkit-scrollbar { width: 3px; }
.fi-sidebar::-webkit-scrollbar-track { background: transparent; }
.fi-sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.1);
    border-radius: 2px;
}

/* ── Badges nav — textes lisibles ── */
.fi-sidebar-item .fi-sidebar-item-button .fi-badge.fi-color-warning,
.fi-sidebar-item .fi-sidebar-item-button .fi-badge.fi-color-warning span {
    color: #b45309 !important;
}
.fi-sidebar-item .fi-sidebar-item-button .fi-badge.fi-color-danger,
.fi-sidebar-item .fi-sidebar-item-button .fi-badge.fi-color-danger span {
    color: #b91c1c !important;
}
.fi-sidebar-item .fi-sidebar-item-button .fi-badge.fi-color-success,
.fi-sidebar-item .fi-sidebar-item-button .fi-badge.fi-color-success span {
    color: #065f46 !important;
}
.fi-sidebar-item .fi-sidebar-item-button .fi-badge.fi-color-info,
.fi-sidebar-item .fi-sidebar-item-button .fi-badge.fi-color-info span {
    color: #0369a1 !important;
}

/* ================================================================
   TOPBAR — Blanc propre
   ================================================================ */

.fi-topbar {
    background: #ffffff !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    border-bottom: 1px solid var(--bm-border) !important;
    box-shadow: 0 1px 0 var(--bm-border) !important;
}

/* ================================================================
   EN-TÊTES DE PAGE
   ================================================================ */

.fi-header-heading {
    font-weight: 900 !important;
    font-size: 1.6rem !important;
    letter-spacing: -0.02em !important;
    color: var(--bm-text) !important;
}
.fi-header-subheading {
    color: var(--bm-muted) !important;
    font-weight: 500 !important;
}

/* ================================================================
   CARTES & SECTIONS — Blanches, ombres douces
   ================================================================ */

.fi-section,
.fi-card,
[class*="fi-section"]:not([class*="fi-section-header"]):not([class*="fi-section-content"]):not([class*="fi-section-actions"]) {
    background: var(--bm-card) !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    border: 1px solid var(--bm-border) !important;
    border-radius: var(--bm-radius) !important;
    box-shadow: var(--bm-shadow) !important;
}

/* Section header */
.fi-section-header {
    background: var(--bm-card2) !important;
    border-bottom: 1px solid var(--bm-border) !important;
    border-radius: var(--bm-radius) var(--bm-radius) 0 0 !important;
}

/* Section header label */
.fi-section-header-heading {
    font-weight: 700 !important;
    color: var(--bm-text) !important;
    font-size: 0.875rem !important;
}

/* ================================================================
   WIDGETS STATS (KPI Cards)
   ================================================================ */

.fi-wi-stats-overview-stat {
    background: var(--bm-card) !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    border: 1px solid var(--bm-border) !important;
    border-radius: var(--bm-radius) !important;
    box-shadow: var(--bm-shadow) !important;
    transition: box-shadow 200ms ease, transform 200ms ease !important;
}
.fi-wi-stats-overview-stat:hover {
    box-shadow: var(--bm-shadow2) !important;
    transform: translateY(-2px) !important;
}

/* Valeur principale (chiffre) */
.fi-wi-stats-overview-stat-value {
    font-weight: 900 !important;
    font-size: 2rem !important;
    letter-spacing: -0.03em !important;
    color: var(--bm-text) !important;
}

/* Label sous la valeur */
.fi-wi-stats-overview-stat-label {
    font-weight: 500 !important;
    font-size: 0.8rem !important;
    color: var(--bm-muted) !important;
}

/* Description/trend */
.fi-wi-stats-overview-stat-description {
    font-weight: 600 !important;
    font-size: 0.78rem !important;
}

/* ================================================================
   TABLES
   ================================================================ */

.fi-ta-ctn,
.fi-ta-table-ctn {
    background: var(--bm-card) !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    border: 1px solid var(--bm-border) !important;
    border-radius: var(--bm-radius) !important;
    box-shadow: var(--bm-shadow) !important;
    overflow: hidden !important;
}

/* Header de tableau */
.fi-ta-header {
    background: var(--bm-card2) !important;
    backdrop-filter: none !important;
    border-bottom: 1px solid var(--bm-border) !important;
}

/* Colonnes header */
.fi-ta-header-cell-label {
    font-size: 0.7rem !important;
    font-weight: 700 !important;
    letter-spacing: 0.07em !important;
    text-transform: uppercase !important;
    color: var(--bm-muted2) !important;
}

/* Lignes */
.fi-ta-row > td,
.fi-ta-row > th {
    border-color: var(--bm-border) !important;
}
.fi-ta-row:hover > td,
.fi-ta-row:hover > th {
    background-color: #F8FAFE !important;
}

/* Cellules */
.fi-ta-cell-content {
    font-weight: 500 !important;
    color: var(--bm-text2) !important;
    font-size: 0.85rem !important;
}

/* Pagination */
.fi-ta-pagination {
    background: var(--bm-card2) !important;
    border-top: 1px solid var(--bm-border) !important;
}

/* ================================================================
   BOUTONS
   ================================================================ */

.fi-btn-color-primary {
    background: linear-gradient(135deg, #2196F3, #1976D2) !important;
    border-color: #2196F3 !important;
    box-shadow: 0 2px 8px rgba(33,150,243,0.28) !important;
    font-weight: 700 !important;
    border-radius: 0.625rem !important;
}
.fi-btn-color-primary:hover {
    background: linear-gradient(135deg, #1976D2, #1565C0) !important;
    box-shadow: 0 4px 16px rgba(33,150,243,0.38) !important;
    transform: translateY(-1px) !important;
}

/* Bouton secondaire */
.fi-btn-color-gray {
    background: var(--bm-card) !important;
    border-color: var(--bm-border) !important;
    color: var(--bm-text2) !important;
    font-weight: 600 !important;
    border-radius: 0.625rem !important;
}
.fi-btn-color-gray:hover {
    background: var(--bm-bg2) !important;
    border-color: #BFDBFE !important;
    color: var(--bm-blue) !important;
}

/* Bouton danger */
.fi-btn-color-danger {
    border-radius: 0.625rem !important;
    font-weight: 700 !important;
}

/* ================================================================
   INPUTS & FORMULAIRES
   ================================================================ */

.fi-input,
[class*="fi-fo-field-wrp"] input,
[class*="fi-fo-field-wrp"] textarea,
[class*="fi-fo-field-wrp"] select {
    background: var(--bm-card) !important;
    border-color: var(--bm-border) !important;
    border-radius: 0.625rem !important;
    font-weight: 500 !important;
    color: var(--bm-text) !important;
    transition: border-color 150ms, box-shadow 150ms !important;
}

.fi-input:focus,
[class*="fi-input"]:focus {
    border-color: #2196F3 !important;
    box-shadow: 0 0 0 3px rgba(33,150,243,0.1) !important;
    --tw-ring-color: rgba(33,150,243,0.15) !important;
}

/* Label champ */
.fi-fo-field-wrp-label label {
    font-weight: 700 !important;
    color: var(--bm-text) !important;
    font-size: 0.82rem !important;
}

/* Aide champ */
.fi-fo-field-wrp-hint {
    color: var(--bm-muted) !important;
    font-size: 0.78rem !important;
}

/* Toggle switch */
.fi-toggle-input:checked + .fi-toggle-track {
    background-color: var(--bm-blue) !important;
}

/* ================================================================
   DROPDOWN / MODALS
   ================================================================ */

.fi-dropdown-panel {
    background: var(--bm-card) !important;
    border: 1px solid var(--bm-border) !important;
    border-radius: var(--bm-radius) !important;
    box-shadow: var(--bm-shadow2) !important;
}

.fi-modal-content {
    background: var(--bm-card) !important;
    border: 1px solid var(--bm-border) !important;
    border-radius: calc(var(--bm-radius) + 0.25rem) !important;
    box-shadow: 0 8px 40px rgba(0,0,0,0.12) !important;
}

.fi-modal-header {
    border-bottom: 1px solid var(--bm-border) !important;
}
.fi-modal-header-heading {
    font-weight: 800 !important;
    color: var(--bm-text) !important;
}

.fi-modal-footer {
    background: var(--bm-card2) !important;
    border-top: 1px solid var(--bm-border) !important;
}

/* ================================================================
   BADGES
   ================================================================ */

.fi-badge {
    font-weight: 700 !important;
    font-size: 0.68rem !important;
    border-radius: 20px !important;
    letter-spacing: 0.02em !important;
}

.fi-badge-color-primary {
    background: var(--bm-blue-bg) !important;
    color: var(--bm-blue-d) !important;
    border: none !important;
}

/* ================================================================
   NOTIFICATIONS / TOASTS
   ================================================================ */

.fi-no-notification {
    background: var(--bm-card) !important;
    border: 1px solid var(--bm-border) !important;
    border-radius: var(--bm-radius) !important;
    box-shadow: var(--bm-shadow2) !important;
}

/* ================================================================
   INFOLIST (vue détail)
   ================================================================ */

.fi-in-section {
    background: var(--bm-card) !important;
    border: 1px solid var(--bm-border) !important;
    border-radius: var(--bm-radius) !important;
    box-shadow: var(--bm-shadow) !important;
}

.fi-in-entry-label {
    font-size: 0.72rem !important;
    font-weight: 700 !important;
    letter-spacing: 0.06em !important;
    text-transform: uppercase !important;
    color: var(--bm-muted2) !important;
}

.fi-in-entry-content {
    font-weight: 600 !important;
    color: var(--bm-text) !important;
}

/* ================================================================
   TABS
   ================================================================ */

.fi-tabs-tab {
    font-weight: 600 !important;
    color: var(--bm-muted) !important;
    border-radius: 0.5rem !important;
    transition: all 140ms !important;
}
.fi-tabs-tab:hover {
    color: var(--bm-blue) !important;
    background: var(--bm-blue-bg) !important;
}
.fi-tabs-tab-active,
.fi-tabs-tab[aria-selected="true"] {
    color: var(--bm-blue) !important;
    font-weight: 800 !important;
    background: var(--bm-blue-bg) !important;
}

/* ================================================================
   ACTION TABLE — Filtres/recherche
   ================================================================ */

.fi-ta-filters-form {
    background: var(--bm-card2) !important;
    border-bottom: 1px solid var(--bm-border) !important;
}

/* ================================================================
   LOGIN PAGE — Fond navy + card blanche
   ================================================================ */

.fi-simple-layout {
    background: linear-gradient(135deg, #1A2744 0%, #0F3460 100%) !important;
    min-height: 100vh !important;
}

.fi-simple-main-ctn { background-color: transparent !important; }

.fi-simple-main:not([class*="fi-simple-main-ctn"]) {
    background: #ffffff !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    border: 1px solid rgba(255,255,255,0.15) !important;
    border-radius: 1.25rem !important;
    box-shadow: 0 24px 64px rgba(0,0,0,0.3) !important;
}

/* ================================================================
   WIDGETS PERSO — Cohérence
   ================================================================ */

/* BookingsOverviewWidget, RevenueWidget, etc. */
.fi-wi-chart {
    background: var(--bm-card) !important;
    border: 1px solid var(--bm-border) !important;
    border-radius: var(--bm-radius) !important;
    box-shadow: var(--bm-shadow) !important;
}

/* AlertsWidget */
.fi-wi-alerts-list {
    background: var(--bm-card) !important;
    border-radius: var(--bm-radius) !important;
}

/* ================================================================
   BREADCRUMB & PAGE NAV
   ================================================================ */

.fi-breadcrumbs-item {
    font-weight: 500 !important;
    color: var(--bm-muted) !important;
}
.fi-breadcrumbs-item:last-child {
    font-weight: 700 !important;
    color: var(--bm-text) !important;
}

/* ================================================================
   ACCOUNT WIDGET (sidebar footer via render hook)
   ================================================================ */

.fi-wi-account {
    background: var(--bm-card) !important;
    border: 1px solid var(--bm-border) !important;
    border-radius: var(--bm-radius) !important;
    box-shadow: var(--bm-shadow) !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

/* ================================================================
   RESPONSIVE
   ================================================================ */

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

@media (max-width: 1024px) {
    .fi-sidebar {
        position: fixed !important;
        z-index: 50 !important;
        height: 100vh !important;
        overflow-y: auto !important;
    }
}

.fi-ta-table-ctn {
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch !important;
}

@media (max-width: 768px) {
    .fi-fo-section-ctn { grid-template-columns: 1fr !important; }
    .fi-fo-component-ctn { grid-template-columns: 1fr !important; }
}
</style>
