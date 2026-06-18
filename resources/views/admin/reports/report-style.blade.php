<style>
    @import url('https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Outfit:wght@100..900&display=swap');

    /* ====================================================
       INK DARK EDITORIAL - BASE TOKENS
       ==================================================== */
    :root {
        --onyx: #0f172a;
        --emerald: #10b981;
        --blue-accent: #3b82f6;
        --emerald-glow: rgba(16, 185, 129, 0.12);
        --white-5: rgba(255, 255, 255, 0.05);
        --white-10: rgba(255, 255, 255, 0.1);
        --glass-border: rgba(255, 255, 255, 0.08);
    }

    /* THEME-AWARE BASE TOKENS */
    .hub-label {
        font-size: 8px;
        font-weight: 850;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        margin-bottom: 0.25rem;
        opacity: 0.8;
    }

    .hub-value {
        font-size: 12px;
        font-weight: 900;
        letter-spacing: -0.01em;
    }

    html.dark .hub-value {
        color: white;
    }

    html:not(.dark) .hub-value {
        color: #0f172a;
    }

    .bl-text {
        color: #2563eb;
    }

    html.dark .bl-text {
        color: #3b82f6;
    }

    .em-text {
        color: #059669;
    }

    html.dark .em-text {
        color: #10b981;
    }

    .go-text {
        color: #b45309;
    }

    html.dark .go-text {
        color: #f59e0b;
    }

    .bl-icon {
        background: rgba(59, 130, 246, 0.1);
        color: #2563eb;
        border: 1px solid rgba(59, 130, 246, 0.2);
    }

    html.dark .bl-icon {
        color: #3b82f6;
    }

    .em-icon {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    html.dark .em-icon {
        color: #10b981;
    }

    .go-icon {
        background: rgba(245, 158, 11, 0.1);
        color: #b45309;
        border: 1px solid rgba(245, 158, 11, 0.2);
    }

    html.dark .go-icon {
        color: #f59e0b;
    }

    .hub-icon-box {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .editorial-title {
        font-family: 'Playfair Display', serif;
        letter-spacing: -0.02em;
        background: linear-gradient(to bottom, #fff 40%, rgba(255, 255, 255, 0.6));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .font-mono-numbers {
        font-family: 'Space Mono', monospace !important;
        font-variant-numeric: tabular-nums;
        line-height: 1 !important;
    }

    /* ====================================================
       ATOMIC EFFICIENCY ANIMATIONS
       ==================================================== */
    @keyframes pulse-critical {

        0%,
        100% {
            opacity: 1;
            text-shadow: 0 0 10px rgba(239, 68, 68, 0.5);
        }

        50% {
            opacity: 0.6;
            text-shadow: none;
        }
    }

    @keyframes pulse-warning {

        0%,
        100% {
            opacity: 1;
            filter: brightness(1);
        }

        50% {
            opacity: 0.8;
            filter: brightness(1.3);
        }
    }

    @keyframes tactical-success-glow {
        0% {
            background-color: rgba(16, 185, 129, 0.4);
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.3);
        }

        100% {
            background-color: transparent;
            box-shadow: none;
        }
    }

    .animate-pulse-critical {
        animation: pulse-critical 2s infinite ease-in-out;
    }

    .animate-pulse-warning {
        animation: pulse-warning 3s infinite ease-in-out;
    }

    .animate-success-flash {
        animation: tactical-success-glow 0.8s ease-out;
    }

    /* ====================================================
       REFINED GLASSMORPHISM
       ==================================================== */
    .glass-pill {
        background: rgba(15, 23, 42, 0.7) !important;
        backdrop-filter: blur(16px) saturate(180%);
        -webkit-backdrop-filter: blur(16px) saturate(180%);
        border: 1px solid var(--glass-border) !important;
        border-top: 1px solid rgba(255, 255, 255, 0.15) !important;
        /* Light source simulation */
        border-radius: 9999px;
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
    }

    .glass-panel {
        background: rgba(15, 23, 42, 0.3) !important;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid var(--white-5) !important;
        box-shadow: 0 4px 20px -5px rgba(0, 0, 0, 0.3);
    }

    /* ====================================================
       EDITORIAL METRIC CAPSULES (THE PILLS)
       ==================================================== */
    .metric-capsule {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.65rem;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--white-10);
        border-radius: 6px;
        font-size: 9px;
        font-family: 'Space Mono', monospace;
        letter-spacing: 0.05em;
        line-height: 1;
        transition: all 0.2s ease;
    }

    .metric-label-xs {
        font-size: 7px;
        text-transform: uppercase;
        opacity: 0.4;
        font-weight: 700;
        letter-spacing: 0.1em;
    }

    .pill-emerald {
        background: rgba(16, 185, 129, 0.08) !important;
        border-color: rgba(16, 185, 129, 0.2) !important;
        color: var(--emerald) !important;
    }

    .pill-blue {
        background: rgba(59, 130, 246, 0.08) !important;
        border-color: rgba(59, 130, 246, 0.2) !important;
        color: var(--blue-accent) !important;
    }

    .pill-onyx {
        background: rgba(255, 255, 255, 0.05) !important;
        border-color: var(--white-10) !important;
        color: rgba(255, 255, 255, 0.6) !important;
    }

    @media (max-width: 640px) {
        .mobile-metric-bar {
            overflow-x: auto;
            white-space: nowrap;
            display: flex;
            gap: 0.5rem;
            padding: 0.25rem 0.1rem;
            scrollbar-width: none;
        }

        .mobile-metric-bar::-webkit-scrollbar {
            display: none;
        }

        .box-header {
            padding: 1rem 0.75rem !important;
        }
    }

    /* ====================================================
       4. HYBRID HORIZONTAL-RESPONSIVE (HHR) TOOLBAR
       ==================================================== */
    .hhr-toolbar {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 0.75rem;
        background: rgba(15, 23, 42, 0.05) !important;
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
        border-radius: 0.75rem;
        overflow-x: auto;
        white-space: nowrap;
        scrollbar-width: none;
        -ms-overflow-style: none;
        transition: all 0.3s ease;
    }

    @media (max-width: 640px) {
        .hhr-toolbar {
            flex-wrap: wrap;
            white-space: normal;
            overflow-x: visible;
            padding: 0.75rem;
            gap: 0.5rem;
        }

        .hhr-group {
            flex: 1 1 auto;
            justify-content: flex-start;
        }

        /* Search + Actions MUST share the SAME row on mobile */
        .hhr-search-kinetic {
            flex: 1 1 0;
            min-width: 0;
            max-width: calc(100% - 130px); /* Reserve space for 3 action buttons */
            order: 10;
        }

        .hhr-action-group {
            margin-left: auto;
            flex-shrink: 0;
            justify-content: flex-end;
            order: 11;
        }
    }

    .hhr-toolbar::-webkit-scrollbar {
        display: none;
    }

    /* ====================================================
       TRINITY HUB (HHR STRATEGY) - GLOBAL
       ==================================================== */
    .triple-threat-hub {
        display: flex !important;
        flex-wrap: nowrap !important;
        overflow-x: auto !important;
        gap: 1.25rem !important;
        margin-bottom: 0 !important;
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
        padding-bottom: 0 !important;
        width: 100% !important;
        position: relative !important;
        z-index: 10 !important;
    }

    .triple-threat-hub::-webkit-scrollbar {
        display: none !important;
    }

    .triple-threat-hub .hub-block {
        flex: 0 0 320px !important;
        /* Forced Fixed Width for Horizontal Scrolling */
        min-width: 320px !important;
    }

    .hub-block {
        position: relative;
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 1.25rem;
        padding: 1.5rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    html.dark .hub-block {
        background: rgba(15, 23, 42, 0.4) !important;
        border-color: rgba(255, 255, 255, 0.05) !important;
        box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.4);
    }

    .emerald-style {
        background: #f0fdf4 !important;
        border-color: #bcf0da !important;
    }

    .blue-style {
        background: #eff6ff !important;
        border-color: #bfdbfe !important;
    }

    .gold-style {
        background: #fffbeb !important;
        border-color: #fef3c7 !important;
    }

    html.dark .emerald-style,
    html.dark .blue-style,
    html.dark .gold-style {
        background: rgba(15, 23, 42, 0.4) !important;
        border-color: rgba(255, 255, 255, 0.05) !important;
    }

    .hub-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .metric-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .metric-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .metric-label {
        font-size: 8px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: #64748b;
    }

    .metric-value {
        font-size: 14px;
        font-weight: 950;
        font-family: 'Space Mono', monospace;
    }

    html.dark .metric-value {
        color: white;
    }

    html:not(.dark) .metric-value {
        color: #0f172a;
    }

    @media (min-width: 1024px) {
        .triple-threat-hub .hub-block {
            flex: 1 !important;
            /* Equal width on desktop */
            min-width: 0 !important;
        }
    }

    html.dark .hhr-toolbar {
        background: rgba(255, 255, 255, 0.02) !important;
        border: 1px solid var(--white-10) !important;
        backdrop-filter: blur(10px);
    }

    .hhr-label-ghost {
        font-family: 'Space Mono', monospace;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        opacity: 0.4;
        font-weight: 600;
        margin-right: 0.25rem;
        line-height: 1 !important;
        display: flex;
        align-items: center;
        height: 32px;
    }

    .hhr-group {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        flex-shrink: 0;
    }

    .hhr-search-kinetic {
        flex-grow: 1;
        min-width: 120px;
        max-width: 400px;
        display: flex;
        align-items: center;
    }

    .hhr-action-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
        margin-left: auto;
    }

    .hhr-toolbar .form-select,
    .hhr-toolbar .form-input {
        background: white !important;
        border: 1px solid rgba(150, 150, 150, 0.2) !important;
        border-radius: 6px !important;
        padding: 0 10px !important;
        font-size: 13px !important;
        height: 32px !important;
        line-height: normal !important;
        box-sizing: border-box !important;
        transition: all 0.2s ease;
    }

    html.dark .hhr-toolbar .form-select,
    html.dark .hhr-toolbar .form-input {
        background-color: var(--onyx) !important;
        border-color: var(--white-10) !important;
        color: rgba(255, 255, 255, 0.9) !important;
    }

    .hhr-search-kinetic .relative {
        display: flex !important;
        align-items: center !important;
        height: 32px !important;
    }

    .hhr-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px !important;
        height: 32px !important;
        min-width: 32px !important;
        border-radius: 6px;
        border: 1px solid var(--white-10);
        background: rgba(255, 255, 255, 0.05);
        color: white;
        padding: 0 !important;
        transition: all 0.2s ease;
        box-sizing: border-box !important;
    }

    .hhr-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: var(--white-10);
    }

    .hhr-btn-excel {
        width: auto !important;
        min-width: 44px !important;
        padding: 0 10px !important;
        gap: 6px;
        background: var(--emerald) !important;
        color: white !important;
        border: none !important;
        font-weight: 600;
        font-size: 12px;
    }

    /* Fix search alignment */
    .hhr-search-kinetic .relative {
        width: 100%;
    }

    .hhr-toolbar .form-input.pl-8 {
        padding-left: 2.2rem !important;
        height: 32px !important;
    }

    .hhr-search-kinetic .absolute {
        display: flex;
        align-items: center;
        height: 100%;
        line-height: 1;
    }

    /* Style tambahan untuk Laporan */
    .text-center {
        text-align: center !important;
    }

    .text-right {
        text-align: right !important;
    }

    /* Narrow Columns for 'Per Tanggal' Mode */
    .col-date {
        width: 38px !important;
        min-width: 38px !important;
        max-width: 38px !important;
        padding-left: 2px !important;
        padding-right: 2px !important;
        font-size: 11px !important;
    }

    /* ====================================================
       HIGH-DENSITY TABLE ENFORCEMENT
       ==================================================== */
    .pos-table-manifest th {
        padding: 0.45rem 0.35rem !important;
        /* COMPACT horizontal padding */
        font-size: 13px !important;
    }

    .pos-table-manifest td {
        padding: 0.35rem 0.35rem !important;
        /* COMPACT horizontal padding */
        font-size: 13px !important;
    }

    /* MOBILE DENSITY OVERRIDE */
    @media (max-width: 640px) {

        .pos-table-manifest th,
        .pos-table-manifest td {
            padding-left: 0.2rem !important;
            padding-right: 0.2rem !important;
            font-size: 12px !important;
            /* Adjusted for better mobile readability */
        }

        .pos-table-manifest {
            width: auto !important;
            /* Allow collapse to content */
            min-width: unset !important;
            /* Let standard scroll-port handle it */
        }
    }

    .pos-table-manifest .sticky-col-no {
        width: 38px !important;
        min-width: 38px !important;
    }

    .pos-table-manifest .sticky-col-name {
        left: 38px !important;
    }

    /* NUCLEAR WIDTH OVERRIDE - FORCE FULL SCREEN ON DESKTOP */
    @media (min-width: 1024px) {

        .layout-page,
        .layout-content,
        .container,
        .grid {
            max-width: none !important;
            width: 100% !important;
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        .main {
            width: 100% !important;
            max-width: none !important;
        }
    }
    /* ====================================================
       NUMBERING BADGES (Producer/Product)
       ==================================================== */
    .num-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-family: 'Space Mono', monospace;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        line-height: 1;
        flex-shrink: 0;
        border-radius: 50%;
    }
    .num-badge-producer {
        width: 18px;
        height: 18px;
        font-size: 9px;
        background: rgba(139, 92, 246, 0.15);
        color: #a78bfa;
        border: 1px solid rgba(139, 92, 246, 0.25);
    }
    .num-badge-product {
        width: 15px;
        height: 15px;
        font-size: 8px;
        background: rgba(16, 185, 129, 0.1);
        color: #6ee7b7;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
</style>