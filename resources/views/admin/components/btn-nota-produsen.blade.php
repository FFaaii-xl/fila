{{-- 
    REUSABLE NOTA PRODUSEN BUTTON + MODAL
    Usage: @include('admin.components.btn-nota-produsen', ['produsenName' => 'SURANTO APM', 'date' => '2026-05-10'])
    
    Requirements:
    - $produsenName: string - Nama produsen (digunakan sebagai filter search)
    - $date: string - Tanggal nota (format Y-m-d)
    
    Optional:
    - $size: 'xs' | 'sm' (default: 'xs') - Ukuran tombol
    - $label: string (default: null) - Label teks, jika null hanya ikon
--}}

@php
    $notaUrl = url('/admin/print-nota') . '?' . http_build_query([
        'date' => $date,
        'search' => $produsenName,
    ]);
    $btnSize = $size ?? 'xs';
    $btnLabel = $label ?? null;
    $footerNote = $footerNote ?? null;
    $footerAmount = $footerAmount ?? 0;
    $modalId = 'nota_' . md5($produsenName . $date);
@endphp

<div x-data="{ showNota: false, loaded: false }" class="inline-flex" x-id="['nota-modal']">
    {{-- TRIGGER BUTTON --}}
    <button type="button"
       @click.stop="showNota = true; loaded = true;"
       title="Lihat Nota {{ $produsenName }} — {{ date('d M Y', strtotime($date)) }}"
       class="nota-produsen-btn nota-btn-{{ $btnSize }} group/nota">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="nota-btn-icon">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
        </svg>
        @if($btnLabel)
            <span class="nota-btn-text">{{ $btnLabel }}</span>
        @endif
    </button>

    {{-- MODAL OVERLAY (Teleported to body) --}}
    <template x-teleport="body">
        <div x-show="showNota" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="nota-modal-overlay"
             @click.self="showNota = false"
             @keydown.escape.window="showNota = false"
             style="display: none;">
            
            <div class="nota-modal-container" @click.stop>
                {{-- Modal Header --}}
                <div class="nota-modal-header">
                    <div class="flex items-center gap-3">
                        <div class="nota-modal-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="nota-modal-title">{{ strtoupper($produsenName) }}</h3>
                            <p class="nota-modal-subtitle">{{ date('d M Y', strtotime($date)) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        {{-- Print from iframe --}}
                        <button @click="$refs.notaFrame_{{ $modalId }}.contentWindow.print()" 
                                class="nota-modal-action" title="Cetak Nota">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2m-2 4H8v-7h8v7Z"/>
                            </svg>
                            <span>Cetak</span>
                        </button>
                        {{-- Open in new tab --}}
                        <a href="{{ $notaUrl }}" target="_blank" rel="noopener"
                           class="nota-modal-action nota-modal-action-ghost" title="Buka di Tab Baru">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                            </svg>
                        </a>
                        {{-- Close --}}
                        <button @click="showNota = false" class="nota-modal-close" title="Tutup">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Modal Body (iframe) --}}
                <div class="nota-modal-body">
                    <template x-if="loaded">
                        <iframe x-ref="notaFrame_{{ $modalId }}" 
                                src="{{ $notaUrl }}" 
                                class="nota-modal-iframe"
                                frameborder="0"></iframe>
                    </template>
                </div>

                @if(!empty($footerNote))
                    <div class="nota-modal-footer">
                        <div class="nota-modal-footer-label">Lain-lain</div>
                        <div class="nota-modal-footer-note">{{ $footerNote }}</div>
                        @if((float) $footerAmount !== 0.0)
                            <div class="nota-modal-footer-amount">
                                {{ $footerAmount >= 0 ? '+' : '' }}Rp {{ number_format((float) $footerAmount, 0, ',', '.') }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </template>
</div>

@once
<style>
    /* ====== TRIGGER BUTTON ====== */
    .nota-produsen-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        border-radius: 5px;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none !important;
        flex-shrink: 0;
        opacity: 0.35;
        color: inherit;
        background: transparent;
        border: none;
        padding: 0;
    }
    .nota-produsen-btn:hover {
        opacity: 1;
        background: rgba(139, 92, 246, 0.15);
        color: #a78bfa !important;
        transform: scale(1.08);
    }
    .nota-btn-xs { padding: 2px 5px; height: 20px; }
    .nota-btn-xs .nota-btn-icon { width: 12px; height: 12px; }
    .nota-btn-xs .nota-btn-text { font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; }
    .nota-btn-sm { padding: 3px 8px; height: 24px; border: 1px solid rgba(255,255,255,0.08) !important; }
    .nota-btn-sm .nota-btn-icon { width: 14px; height: 14px; }
    .nota-btn-sm .nota-btn-text { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; }

    /* ====== MODAL OVERLAY ====== */
    .nota-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        padding: 1rem;
    }

    /* ====== MODAL CONTAINER ====== */
    .nota-modal-container {
        width: 100%;
        max-width: 960px;
        height: 90vh;
        max-height: 90vh;
        background: #111827;
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 16px;
        display: grid;
        grid-template-rows: auto minmax(0, 1fr) auto;
        overflow: hidden;
        box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.6);
    }

    /* ====== MODAL HEADER ====== */
    .nota-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 20px;
        border-bottom: 1px solid rgba(255,255,255,0.06);
        background: rgba(255,255,255,0.02);
        flex-shrink: 0;
    }
    .nota-modal-icon {
        width: 32px; height: 32px;
        border-radius: 8px;
        background: rgba(139, 92, 246, 0.12);
        border: 1px solid rgba(139, 92, 246, 0.25);
        color: #a78bfa;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .nota-modal-title {
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.06em;
        color: rgba(255,255,255,0.9);
        line-height: 1.2;
    }
    .nota-modal-subtitle {
        font-size: 10px;
        color: rgba(255,255,255,0.4);
        font-family: 'Space Mono', monospace;
        margin-top: 1px;
    }

    /* ====== HEADER ACTIONS ====== */
    .nota-modal-action {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        background: rgba(139, 92, 246, 0.15);
        color: #a78bfa;
        border: 1px solid rgba(139, 92, 246, 0.25);
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none !important;
    }
    .nota-modal-action:hover {
        background: rgba(139, 92, 246, 0.25);
        color: #c4b5fd;
        transform: translateY(-1px);
    }
    .nota-modal-action-ghost {
        background: transparent;
        border-color: rgba(255,255,255,0.1);
        color: rgba(255,255,255,0.5);
    }
    .nota-modal-action-ghost:hover {
        background: rgba(255,255,255,0.05);
        color: rgba(255,255,255,0.8);
    }
    .nota-modal-close {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 28px; height: 28px;
        border-radius: 6px;
        border: none;
        background: transparent;
        color: rgba(255,255,255,0.3);
        cursor: pointer;
        transition: all 0.15s ease;
        margin-left: 4px;
    }
    .nota-modal-close:hover {
        background: rgba(239, 68, 68, 0.15);
        color: #f87171;
    }

    /* ====== MODAL BODY ====== */
    .nota-modal-body {
        min-height: 0;
        overflow: hidden;
        background: white;
    }
    .nota-modal-iframe {
        width: 100%;
        height: 100%;
        border: none;
        background: white;
    }

    /* ====== MODAL FOOTER ====== */
    .nota-modal-footer {
        position: sticky;
        bottom: 0;
        padding: 12px 16px 14px;
        border-top: 1px solid rgba(255,255,255,0.06);
        background: rgba(15, 23, 42, 0.96);
        color: rgba(255,255,255,0.9);
        box-shadow: 0 -8px 24px rgba(0, 0, 0, 0.18);
    }
    .nota-modal-footer-label {
        font-size: 9px;
        font-weight: 800;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: #fda4af;
        margin-bottom: 4px;
    }
    .nota-modal-footer-note {
        font-size: 12px;
        font-weight: 700;
        line-height: 1.4;
        color: rgba(255,255,255,0.86);
        word-break: break-word;
    }
    .nota-modal-footer-amount {
        margin-top: 4px;
        font-size: 11px;
        font-weight: 800;
        color: #fda4af;
        font-family: 'Space Mono', monospace;
    }
</style>
@endonce
