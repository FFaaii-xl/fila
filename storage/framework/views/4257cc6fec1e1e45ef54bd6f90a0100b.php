<style>
    /* ========================================
       GLASSMORPHISM DESIGN SYSTEM
       For Merchant & Producer Sales Pages
       ======================================== */
    
    /* Font imports */
    @import url('https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Playfair+Display:wght@700;900&family=Outfit:wght@400;500;600;700&display=swap');
    
    /* Base Glass Effect */
    .glass-panel {
        background: rgba(30, 41, 59, 0.6);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
    }
    
    .glass-pill {
        background: rgba(30, 41, 59, 0.75);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.06);
    }
    
    .glass-card {
        background: rgba(30, 41, 59, 0.5);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 12px;
    }
    
    /* Metric Capsules */
    .metric-capsule {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 600;
        font-family: 'Space Mono', monospace;
        white-space: nowrap;
    }
    
    .pill-onyx {
        background: rgba(0, 0, 0, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #cbd5e1;
    }
    
    .pill-blue { background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
    .pill-emerald { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .pill-amber { background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
    .pill-purple { background: rgba(168, 85, 247, 0.2); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
    .pill-rose { background: rgba(244, 63, 94, 0.2); color: #fb7185; border: 1px solid rgba(244, 63, 94, 0.3); }
    
    /* Number Badges */
    .num-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        border-radius: 6px;
        font-size: 9px;
        font-weight: 700;
        font-family: 'Space Mono', monospace;
    }
    
    .num-badge-producer { background: rgba(168, 85, 247, 0.25); color: #c084fc; }
    .num-badge-product { background: rgba(56, 189, 248, 0.25); color: #38bdf8; }
    .num-badge-merchant { background: rgba(16, 185, 129, 0.25); color: #34d399; }
    
    /* Toolbar Styling */
    .hhr-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        padding: 14px 16px;
        background: rgba(30, 41, 59, 0.5);
        border-radius: 14px;
        margin-bottom: 16px;
    }
    
    .hhr-group {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .hhr-label-ghost {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: rgba(255, 255, 255, 0.35);
        font-weight: 700;
        font-family: 'Outfit', sans-serif;
    }
    
    .hhr-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 7px 12px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 600;
        font-family: 'Outfit', sans-serif;
        background: rgba(255, 255, 255, 0.06);
        color: #94a3b8;
        border: 1px solid rgba(255, 255, 255, 0.1);
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .hhr-btn:hover {
        background: rgba(255, 255, 255, 0.12);
        color: #e2e8f0;
        border-color: rgba(255, 255, 255, 0.2);
    }
    
    .hhr-btn-excel {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
        border-color: rgba(16, 185, 129, 0.3);
    }
    
    .hhr-btn-excel:hover {
        background: rgba(16, 185, 129, 0.25);
        color: #34d399;
    }
    
    /* Group Boxes */
    .group-box {
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 14px;
        overflow: hidden;
        margin-bottom: 12px;
    }
    
    .box-header {
        background: rgba(255, 255, 255, 0.02);
        padding: 12px 16px;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .box-header:hover {
        background: rgba(255, 255, 255, 0.05);
    }
    
    .box-title {
        font-family: 'Outfit', sans-serif;
        font-size: 12px;
        font-weight: 700;
        color: #e2e8f0;
        letter-spacing: 0.02em;
    }
    
    /* Summary Cards */
    .summary-card {
        padding: 14px 18px;
        border-radius: 14px;
        min-width: 150px;
        flex-shrink: 0;
    }
    
    .summary-label {
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        opacity: 0.7;
        margin-bottom: 4px;
    }
    
    .summary-value {
        font-family: 'Space Mono', monospace;
        font-size: 18px;
        font-weight: 700;
    }
    
    /* Heatmap Colors for Percentage */
    .heatmap-cell {
        font-weight: 700;
        font-family: 'Space Mono', monospace;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
        padding: 4px 8px;
        border-radius: 6px;
    }
    
    /* Enhanced Table */
    .glass-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .glass-table thead {
        background: rgba(255, 255, 255, 0.03);
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .glass-table th {
        padding: 10px 12px;
        text-align: center;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        white-space: nowrap;
    }
    
    .glass-table th:first-child { text-align: center; width: 40px; }
    .glass-table th:nth-child(2) { text-align: left; }
    
    .glass-table td {
        padding: 10px 12px;
        font-size: 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        transition: background 0.15s;
    }
    
    .glass-table tbody tr:hover td {
        background: rgba(255, 255, 255, 0.02);
    }
    
    .glass-table tfoot {
        background: rgba(255, 255, 255, 0.04);
        font-weight: 700;
    }
    
    .glass-table tfoot td {
        padding: 12px;
        border-top: 2px solid rgba(255, 255, 255, 0.1);
    }
    
    /* Floating Ribbon */
    .floating-ribbon {
        position: sticky;
        top: 60px;
        z-index: 20;
        padding: 10px 20px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 16px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
    }
    
    /* Mobile Horizontal Scroll */
    .snap-scroll {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding-bottom: 8px;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
    }
    
    .snap-scroll::-webkit-scrollbar {
        height: 4px;
    }
    
    .snap-scroll::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 2px;
    }
    
    .snap-scroll::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.15);
        border-radius: 2px;
    }
    
    .snap-item {
        scroll-snap-align: start;
        flex-shrink: 0;
    }
    
    /* Alert Styling */
    .glass-alert {
        padding: 14px 16px;
        border-radius: 12px;
        border-left: 4px solid;
        margin-bottom: 16px;
    }
    
    .glass-alert-warning {
        background: rgba(245, 158, 11, 0.1);
        border-color: #f59e0b;
    }
    
    /* Form Elements */
    .glass-select, .glass-input {
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 12px;
        color: #e2e8f0;
        transition: all 0.2s;
    }
    
    .glass-select:focus, .glass-input:focus {
        outline: none;
        border-color: rgba(59, 130, 246, 0.5);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .glass-select option {
        background: #1e293b;
        color: #e2e8f0;
    }
    
    /* Mode Tabs */
    .mode-tab {
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid transparent;
    }
    
    .mode-tab-active {
        background: rgba(59, 130, 246, 0.2);
        color: #60a5fa;
        border-color: rgba(59, 130, 246, 0.3);
    }
    
    .mode-tab-inactive {
        background: rgba(255, 255, 255, 0.05);
        color: #94a3b8;
    }
    
    .mode-tab-inactive:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #cbd5e1;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 48px 24px;
        opacity: 0.5;
    }
    
    .empty-state-icon {
        width: 48px;
        height: 48px;
        margin: 0 auto 16px;
        opacity: 0.3;
    }
    
    .empty-state-title {
        font-size: 14px;
        font-weight: 600;
        color: #94a3b8;
        margin-bottom: 4px;
    }
    
    .empty-state-text {
        font-size: 12px;
        color: #64748b;
    }
    
    /* Animations */
    @keyframes pulse-glow {
        0%, 100% { box-shadow: 0 0 8px rgba(16, 185, 129, 0.3); }
        50% { box-shadow: 0 0 16px rgba(16, 185, 129, 0.5); }
    }
    
    .live-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #10b981;
        animation: pulse-glow 2s ease-in-out infinite;
    }
    
    /* Typography */
    .font-mono { font-family: 'Space Mono', monospace; }
    .font-display { font-family: 'Playfair Display', serif; }
    .font-sans { font-family: 'Outfit', sans-serif; }
</style>
<?php /**PATH D:\www\fila\resources\views/filament/components/report-styles.blade.php ENDPATH**/ ?>