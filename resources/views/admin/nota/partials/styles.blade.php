@php
    $palettes = [
        [ // TEMA CYAN (Fokus menguras tinta Cyan)
            'base' => '#002f3b', // Cyan gelap (Hampir hitam)
            'danger' => '#c2185b', // Pink/Magenta tua
            'payout_bg' => 'rgba(2, 132, 199, 0.08)',
            'payout_border' => 'rgba(2, 132, 199, 0.3)',
            'payout_text' => '#0369a1',
        ],
        [ // TEMA MAGENTA (Fokus menguras tinta Magenta)
            'base' => '#3b0024', // Magenta gelap (Hampir hitam)
            'danger' => '#e11d48', // Merah mawar
            'payout_bg' => 'rgba(192, 38, 211, 0.08)',
            'payout_border' => 'rgba(192, 38, 211, 0.3)',
            'payout_text' => '#a21caf',
        ],
        [ // TEMA YELLOW/GREEN (Fokus menguras tinta Yellow/Cyan)
            'base' => '#0f3814', // Hijau/Kuning gelap (Hampir hitam)
            'danger' => '#d97706', // Amber / Oranye tua
            'payout_bg' => 'rgba(5, 150, 105, 0.08)',
            'payout_border' => 'rgba(5, 150, 105, 0.3)',
            'payout_text' => '#047857',
        ],
        [ // TEMA BLUE (Fokus menguras tinta Cyan/Magenta)
            'base' => '#141a45', // Biru gelap (Hampir hitam)
            'danger' => '#f43f5e', // Merah muda terang
            'payout_bg' => 'rgba(79, 70, 229, 0.08)',
            'payout_border' => 'rgba(79, 70, 229, 0.3)',
            'payout_text' => '#4338ca',
        ],
        [ // TEMA RED/BROWN (Fokus menguras tinta Magenta/Yellow)
            'base' => '#3d1607', // Cokelat/Merah gelap (Hampir hitam)
            'danger' => '#dc2626', // Merah pekat
            'payout_bg' => 'rgba(234, 88, 12, 0.08)',
            'payout_border' => 'rgba(234, 88, 12, 0.3)',
            'payout_text' => '#c2410c',
        ]
    ];
    $defaultBlackTheme = [
        'base' => '#121212',
        'danger' => '#dc2626',
        'payout_bg' => 'rgba(5, 150, 105, 0.05)',
        'payout_border' => 'rgba(5, 150, 105, 0.2)',
        'payout_text' => '#065f46',
    ];

    $isRandomColor = request()->has('date') ? request()->has('randomize_color') : true;

    if ($isRandomColor) {
        $dayOfYear = date('z', strtotime($date ?? now()));
        $theme = $palettes[$dayOfYear % count($palettes)];
    } else {
        $theme = $defaultBlackTheme;
    }

    $notaColor = $theme['base'];
    \Illuminate\Support\Facades\View::share('theme', $theme);
    \Illuminate\Support\Facades\View::share('notaColor', $notaColor);
@endphp
<style>
    /* SHARED STYLES - MERCANTILE LEDGER AESTHETIC (ULTRA COMPACT) */
    .nota-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.5mm;
        width: 100%;
        margin-bottom: 2mm;
    }

    .page-container {
        padding-top: 0;
    }

    @media (min-width: 768px) {
        .nota-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (min-width: 1280px) {
        .nota-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }

    @media print {
        @page {
            size: 325mm 212mm landscape;
            margin: 0 !important;
        }

        body {
            margin: 0;
            padding: 0;
            background: white !important;
        }

        .page-container {
            page-break-after: always;
            padding-top: 6.5mm !important;
            width: 100%;
        }

        .nota-grid {
            display: grid !important;
            grid-template-columns: repeat(4, 23.4%) !important;
            gap: 1% !important;
            width: 100% !important;
            padding: 0 1.7% !important;
        }

        .no-print {
            display: none !important;
        }
    }

    .nota-card {
        border: 0.4pt solid
            {{ $notaColor }}
        ;
        padding: 0.5mm;
        background: #fff;
        color:
            {{ $notaColor }}
            !important;
        display: flex;
        flex-direction: column;
        width: 100% !important;
        min-width: 0;
        min-height: 80mm;
        page-break-inside: avoid;
        break-inside: avoid;
    }

    .nota-header {
        border-bottom: 0.8pt solid
            {{ $notaColor }}
        ;
        margin-bottom: 0.5mm;
        padding-bottom: 0.5mm;
    }

    .flex-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .uppercase {
        text-transform: uppercase;
    }

    .border-box {
        border: 0.8pt solid
            {{ $notaColor }}
        ;
        padding: 0 3px;
        font-size: 12px;
        background-color: #f0f0f0;
        font-weight: 800;
        line-height: 1;
    }

    .header-small-badge {
        border: 0.4pt solid
            {{ $notaColor }}
        ;
        padding: 0 2px;
        font-size: 9px;
        display: inline-block;
        line-height: 1.1;
    }

    .nota-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0;
        font-family: 'Outfit', sans-serif;
        font-variant-numeric: tabular-nums;
        line-height: 1.0;
        table-layout: fixed;
    }

    .nota-table th {
        background-color: #f0f0f0;
        border: 0.4pt solid
            {{ $notaColor }}
        ;
        padding: 0.2mm 0.4mm;
        font-size: 10px;
        font-weight: 700;
    }

    .nota-table td {
        border: 0.4pt solid
            {{ $notaColor }}
        ;
        padding: 0.2mm 0.4mm;
        text-align: center;
        font-size: 11px;
    }

    .nota-table tbody td {
        padding-top: 1px;
        padding-bottom: 1px;
    }

    .nota-table td:nth-child(2) {
        font-family: 'Outfit', sans-serif;
        text-align: left;
        font-weight: 400;
        font-size: 11px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 15mm;
    }

    .nota-table td:last-child {
        text-align: right;
        padding-right: 1px;
    }

    .nota-table tbody tr:nth-child(even) {
        background-color: #f2f2f2 !important;
        /* Dipertegas agar muncul saat diprint */
    }

    .fontWeightBold,
    .b {
        font-weight: 700;
    }

    .r {
        color:
            {{ $theme['danger'] }}
            !important;
    }

    .m {
        font-family: 'Outfit', sans-serif;
        font-variant-numeric: tabular-nums;
    }

    .s9 {
        font-size: 11px;
    }

    .tr-lh {
        line-height: 0.95;
    }

    .nota-title {
        font-size: 12px;
        margin: 0;
        max-width: 30mm;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .nota-time {
        font-size: 11px;
    }

    .nota-p-name {
        font-size: 10px;
        font-weight: 800;
    }

    .nota-disclaimer {
        font-size: 8.5px;
        text-align: center;
        margin-top: 2px;
        text-transform: uppercase;
    }

    .nota-table tfoot tr {
        background-color: #e5e5e5;
        font-weight: 800;
    }

    .nota-table tfoot td {
        font-family: 'Outfit', sans-serif !important;
        font-variant-numeric: tabular-nums;
        font-size: 11px !important;
        font-weight: 700 !important;
        padding: 1px 0px !important;
        text-align: center !important;
    }

    .nota-summary {
        display: flex;
        border: 0.8pt solid
            {{ $notaColor }}
        ;
        margin-top: 2px;
        font-family: 'Outfit', sans-serif;
        font-variant-numeric: tabular-nums;
        font-size: 10px;
    }

    .summary-left,
    .summary-right {
        flex: 1;
        padding: 1px 3px;
    }

    .summary-left {
        border-right: 0.8pt solid
            {{ $notaColor }}
        ;
    }

    .row-flex {
        display: flex;
        justify-content: space-between;
        border-bottom: 0.1pt solid #ccc;
        margin-bottom: 0px;
    }

    .row-flex:last-child {
        border-bottom: none;
    }

    .uang-hari-ini {
        text-align: center;
        font-weight: 800;
        padding: 0.5px 0;
        border: 1pt solid
            {{ $notaColor }}
        ;
        background-color: #f0f0f0;
        margin-top: 2px;
        font-size: 13px;
        letter-spacing: 0em;
    }

    /* --- 7. MERCANTILE INSIGHT DRAWER (Interactive) --- */
    .fab-btn {
        background:
            {{ $notaColor }}
        ;
        color: #10b981;
        /* Emerald */
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        width: 48px;
        height: 48px;
        cursor: pointer;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .fab-btn:hover {
        transform: scale(1.1) rotate(5deg);
        background: #1a1a1a;
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.6);
    }

    .drawer-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(8px);
        z-index: 10000;
    }

    .drawer-content {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        width: 300px;
        background: #0f1113;
        color: #e5e7eb;
        padding: 16px;
        box-shadow: -10px 0 35px rgba(0, 0, 0, 0.7);
        display: flex;
        flex-direction: column;
        font-family: 'Outfit', sans-serif;
    }

    .drawer-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid rgba(255, 255, 255, 0.05);
        padding-bottom: 8px;
        margin-bottom: 15px;
        flex-shrink: 0;
    }

    .drawer-header h2 {
        font-size: 11px;
    }

    .drawer-body {
        flex: 1;
        overflow-y: auto;
        padding-right: 2px;
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.1) transparent;
    }

    .drawer-body::-webkit-scrollbar {
        width: 3px;
    }

    .drawer-body::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
    }

    .close-btn {
        background: none;
        border: none;
        color: #6b7280;
        font-size: 24px;
        cursor: pointer;
        transition: color 0.2s;
    }

    .close-btn:hover {
        color: #f3f4f6;
    }

    .stat-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 10px;
        padding: 12px;
        transition: border 0.3s;
    }

    .stat-card:hover {
        border-color: rgba(16, 185, 129, 0.3);
    }

    .stat-label {
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: #9ca3af;
        display: block;
    }

    .stat-value {
        font-size: 24px;
        font-weight: 800;
        margin: 2px 0;
        font-family: 'Outfit', sans-serif;
        font-variant-numeric: tabular-nums;
    }

    .stat-meta {
        font-size: 10px;
        color: #6b7280;
    }

    .pulse-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 2px 0;
    }

    .rank-row {
        display: flex;
        justify-content: space-between;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        padding: 6px 0;
    }

    .rank-name {
        font-weight: 500;
        color: #d1d5db;
        font-size: 10px;
    }

    .rank-data {
        font-size: 10px;
        color: #10b981;
    }

    .toggle-container {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 6px;
        padding: 2px;
        display: flex;
        gap: 2px;
    }

    .toggle-container button {
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 8px;
        text-transform: uppercase;
        border: none;
        background: none;
        color: #9ca3af;
        cursor: pointer;
        transition: all 0.2s;
    }

    .toggle-container button.toggle-active {
        background: #10b981;
        color:
            {{ $notaColor }}
        ;
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
    }

    [x-cloak] {
        display: none !important;
    }

    .nota-footer {
        text-align: center;
        font-weight: 700;
        padding: 1px;
        border: 0.4pt solid
            {{ $notaColor }}
        ;
        margin-top: 1px;
        font-size: 8px;
        letter-spacing: 0.02em;
        background: #fdfdfd;
        white-space: nowrap;
        overflow: hidden;
    }
</style>