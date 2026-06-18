@include('admin.reports.report-style')

<style>
    /* REPLICATING TRADING/SUBMISSION HUB STYLES */
    /* TRIPLE HUB HHR (SWIPEABLE ON MOBILE) */
    .triple-threat-hub {
        display: flex !important;
        gap: 0.9rem !important;
        overflow-x: auto !important;
        padding-bottom: 0 !important;
        scrollbar-width: none;
        -ms-overflow-style: none;
        width: 100% !important;
        align-items: stretch !important;
        margin-bottom: 0 !important;
    }

    .triple-threat-hub::-webkit-scrollbar {
        display: none !important;
    }

    .hub-block {
        flex: 1 1 0% !important;
        min-width: 280px !important;
        /* Ensures readability on mobile */
    }

    @media (min-width: 1024px) {
        .triple-threat-hub {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 0.9rem !important;
            overflow-x: visible !important;
        }

        .hub-block {
            min-width: 0 !important;
        }
    }

    .hub-block {
        border-radius: 0.5rem !important;
        padding: 1rem !important;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        position: relative;
        overflow: visible;
        backdrop-filter: blur(12px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .hub-block:hover {
        border-color: rgba(255, 255, 255, 0.08) !important;
        box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255,255,255,0.03) !important;
        transform: translateY(-1px);
    }

    /* FORCE THEME COLORS - SIMPLE ONYX EDITION */
    .dark .hub-block {
        background: #0b1121 !important;
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
        box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.3) !important;
        color: #ffffff !important;
    }

    .hub-block {
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
        color: #0f172a !important;
    }

    .hub-header {
        display: flex !important;
        align-items: center !important;
        gap: 0.6rem !important;
        margin-bottom: 0.5rem !important;
    }

    .hub-icon-box {
        width: 2.25rem !important;
        height: 2.25rem !important;
        border-radius: 0.75rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        border: 1px solid !important;
    }

    .em-icon {
        background: rgba(16, 185, 129, 0.08) !important;
        color: #10b981 !important;
        border-color: rgba(16, 185, 129, 0.1) !important;
    }

    .bl-icon {
        background: rgba(59, 130, 246, 0.08) !important;
        color: #3b82f6 !important;
        border-color: rgba(59, 130, 246, 0.1) !important;
    }

    .gd-icon {
        background: rgba(250, 204, 21, 0.08) !important;
        color: #facc15 !important;
        border-color: rgba(250, 204, 21, 0.1) !important;
    }

    .pr-icon {
        background: rgba(139, 92, 246, 0.08) !important;
        color: #8b5cf6 !important;
        border-color: rgba(139, 92, 246, 0.1) !important;
    }

    .rs-icon {
        background: rgba(244, 63, 94, 0.08) !important;
        color: #f43f5e !important;
        border-color: rgba(244, 63, 94, 0.1) !important;
    }

    .hub-label {
        font-size: 9px !important;
        font-weight: 900 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.18em !important;
    }

    .hub-value {
        font-family: ui-monospace, SFMono-Regular, monospace !important;
        font-size: 11px !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
        margin-top: 2px !important;
    }

    .hub-content {
        margin-top: 0.25rem !important;
    }

    .dark .hub-label {
        color: rgba(255, 255, 255, 0.4) !important;
    }

    .hub-label {
        color: #0f172a !important;
    }

    .dark .hub-value {
        color: #ffffff !important;
    }

    .hub-value {
        color: #0f172a !important;
    }

    /* MATRIX TABLE REFINEMENT - EDITORIAL STANDARDS */
    .matrix-container {
        border-radius: 0.5rem !important;
        overflow: hidden !important;
        backdrop-filter: blur(12px) !important;
        display: flex !important;
        flex-direction: column !important;
        width: 100% !important;
        max-width: 100vw !important;
        padding-bottom: 0 !important;
        margin-top: 0 !important;
    }

    .dark .matrix-container {
        background: rgba(15, 23, 42, 0.4) !important;
        border: 1px solid rgba(255, 255, 255, 0.03) !important;
    }

    .matrix-container {
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
    }

    /* SCROLL PORT - THE CORE OF HORIZONTAL MOVEMENT */
    .scroll-port {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        position: relative;
    }

    .pos-table-manifest {
        width: max-content !important;
        min-width: 100%;
        /* Ensure it spans at least full container for aesthetic, but columns will stay tight */
    }

    .pos-table-manifest th {
        font-size: 9px !important;
        font-weight: 900 !important;
        text-transform: uppercase !important;
        text-align: center !important;
        letter-spacing: 0.2em !important;
        opacity: 0.4;
        border-bottom: 1px solid rgba(150, 150, 150, 0.1) !important;
    }

    .dark .pos-table-manifest th {
        color: #fff !important;
    }

    .pos-table-manifest th {
        color: #0f172a !important;
    }

    .pos-table-manifest td {
        vertical-align: middle !important;
    }

    /* Nuclear Freeze Columns */
    .pos-table-manifest {
        border-collapse: separate !important;
        border-spacing: 0 !important;
    }

    .pos-table-manifest .sticky-col-no {
        position: sticky !important;
        left: 0 !important;
        z-index: 35 !important;
        background-color: #ffffff !important;
        width: 48px !important;
        min-width: 48px !important;
    }

    .dark .pos-table-manifest .sticky-col-no {
        background-color: #1a1c23 !important;
    }

    .pos-table-manifest .sticky-col-name {
        position: sticky !important;
        left: 48px !important;
        z-index: 35 !important;
        background-color: #ffffff !important;
        border-right: 1px solid rgba(0, 0, 0, 0.05) !important;
    }

    .dark .pos-table-manifest .sticky-col-name {
        background-color: #1a1c23 !important;
        border-right: 1px solid rgba(255, 255, 255, 0.05) !important;
    }

    .pos-table-manifest thead th.sticky-col-no,
    .pos-table-manifest thead th.sticky-col-name {
        z-index: 45 !important;
        /* Higher than body sticky cells */
    }

    .pos-table-manifest .shadow-sep {
        box-shadow: 4px 0 8px -4px rgba(0, 0, 0, 0.15) !important;
    }

    .pos-table-manifest tbody tr {
        border-bottom: 1px solid rgba(150, 150, 150, 0.03) !important;
    }

    .pos-table-manifest tbody tr:hover {
        background: rgba(255, 255, 255, 0.02) !important;
    }

    /* HEATMAP CELL STYLE */
    .heatmap-cell {
        width: 48px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 800;
        color: white;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .no-scrollbar::-webkit-scrollbar {
        display: none !important;
    }

    .kinetic-search-results { 
        position: absolute; 
        left: 0; 
        right: 0; 
        top: 110%; 
        background: #0d121f !important; 
        border: 1px solid rgba(16, 185, 129, 0.2) !important; 
        border-radius: 1.25rem !important; 
        z-index: 1000 !important; 
        max-height: 350px !important; 
        overflow-y: auto !important; 
        box-shadow: 0 0 50px rgba(0,0,0,0.8) !important; 
        opacity: 1 !important;
    }
    .result-item { padding: 0.75rem 1.25rem; transition: all 0.2s; cursor: pointer; }
    .result-item:hover { background: rgba(255,255,255,0.05); }
    .active-result { background: #10b981 !important; color: black !important; padding: 0.75rem 1.25rem; cursor: pointer; border-radius: 1rem; }
    .active-result * { color: black !important; opacity: 1 !important; }

    .custom-scrollbar::-webkit-scrollbar {
        width: 4px !important;
        height: 4px !important;
    }

    .dark .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05) !important;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.05) !important;
    }

    .dark .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1) !important;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.1) !important;
        border-radius: 10px !important;
    }

    .btn-hub {
        border-radius: 0.75rem !important;
        padding: 0.65rem 1rem !important;
        font-size: 10px !important;
        font-weight: 900 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.1em !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .btn-blue {
        background: #3b82f6 !important;
        color: white !important;
        box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4) !important;
    }

    .btn-emerald {
        background: #10b981 !important;
        color: white !important;
        box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.4) !important;
    }

    .btn-rose {
        background: #f43f5e !important;
        color: white !important;
        box-shadow: 0 10px 15px -3px rgba(244, 63, 94, 0.4) !important;
    }

    .btn-hub:hover {
        transform: translateY(-2px) !important;
        filter: brightness(1.1) !important;
    }

    .btn-hub:active {
        transform: translateY(0) !important;
    }

    /* STATUS AVATAR STYLES */
    .status-avatar {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 900;
        position: relative;
        transition: all 0.3s ease;
        border: 2px solid rgba(255, 255, 255, 0.1);
    }

    .status-avatar-nalangi {
        background: linear-gradient(135deg, #f43f5e, #e11d48);
        color: white;
    }

    .status-avatar-ok {
        background: linear-gradient(135deg, #10b981, #059669);
        box-shadow: 0 0 8px rgba(16, 185, 129, 0.3);
        color: white;
    }

    /* Pulse animation removed - no more glowing effect */

    /* GLOBAL SVG & ICON CONSTRAINTS - Anti-Scaling Guard */
    .hub-block svg, 
    .btn-hub svg, 
    .btn-kinetic svg,
    .kinetic-wrapper svg {
        max-width: 100% !important;
        max-height: 100% !important;
        flex-shrink: 0 !important;
    }

    /* Force specific size for spinners if Tailwind utilities fail */
    svg.animate-spin {
        width: 14px !important;
        height: 14px !important;
        display: inline-block !important;
    }
</style>
