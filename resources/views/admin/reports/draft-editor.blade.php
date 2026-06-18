@include('admin.reports.report-style')
@php
    // The logic for isSpecial is now passed from the Page, but we keep fallback just in case
    $isSpecial = $isSpecial ?? false;
@endphp

<div x-data="kineticDraft()" x-init="init()" x-cloak @keydown.window.slash.prevent.stop="openPalette()" class="space-y-6 relative">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    {{-- 1. TRINITY DASHBOARD (HHR STRATEGY) --}}
    @include('admin.upload.partials.editor-hub-cards', [
        'pedagang' => $pedagang,
        'downloadButton' => $downloadButton,
        'pullButton' => $pullButton,
        'currentVersion' => $currentVersion,
        'hasChanges' => $hasChanges,
        'isAdmin' => $isAdmin,
        'uploadForm' => $uploadForm,
        'isLocked' => $isLocked ?? false,
        'lockError' => $lockError ?? null
    ])

    {{-- 2. CONTROL HUB (FORCED SINGLE ROW) --}}
    <div class="kinetic-control-bar">
        <div class="search-wrapper">
            <div class="search-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input type="text" x-model="paletteSearch" @input="filterProducts(); showResults = true" @focus="showResults = true" @click.away="showResults = false"
                @keydown.down.prevent="activeIndex = (activeIndex + 1) % (filteredProducts.length || 1)"
                @keydown.up.prevent="activeIndex = (activeIndex - 1 + filteredProducts.length) % (filteredProducts.length || 1)"
                @keydown.enter.prevent="selectProduct()" @keydown.escape="showResults = false; paletteSearch = ''"
                :disabled="isLockedSession"
                :placeholder="isLockedSession ? 'SESSION LOCKED: PAID' : 'CARI PRODUK (KETIK NAMA UNTUK MENAMBAHKAN)...'" class="search-input"
                :class="isLockedSession ? 'opacity-50 cursor-not-allowed border-rose-500/20' : ''">
            
            <div x-show="showResults && filteredProducts.length > 0" class="search-results no-scrollbar">
                <template x-for="(prod, idx) in filteredProducts" :key="prod.id">
                    <div @click="addItem(prod); showResults = false" @mouseenter="activeIndex = idx" :class="idx === activeIndex ? 'active-result' : 'result-item'" class="px-4 py-3 cursor-pointer transition-colors flex justify-between items-center border-b border-white/5 last:border-0">
                        <div class="flex flex-col">
                            <span class="uppercase text-[10px] font-black tracking-normal" x-text="shortenName(prod.nama).toUpperCase()"></span>
                            <div class="flex items-center gap-2 mt-1 text-[9px] font-mono opacity-60">
                                <span x-text="'Rp ' + formatNumber(prod.harga_jual)"></span>
                                <span>•</span>
                                <span x-text="prod.produsen_nama"></span>
                            </div>
                        </div>
                        <template x-if="prod.isAdded">
                            <span class="px-1.5 py-0.5 bg-emerald-500/20 text-emerald-400 text-[7px] font-black rounded uppercase tracking-tighter">Sudah Ada</span>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <div class="action-buttons">
            <button @click="editMode = !editMode" :disabled="isLockedSession" :class="isLockedSession ? 'btn-edit-off opacity-30 cursor-not-allowed' : (editMode ? 'btn-edit-on shadow-[0_0_15px_rgba(16,185,129,0.3)] text-emerald-400' : 'btn-edit-off')" class="action-btn !h-[2.5rem] px-4">
                <div class="flex flex-col items-center justify-center leading-none">
                     <x-moonshine::icon x-show="editMode && !isLockedSession" icon="pencil-square" size="3" />
                     <x-moonshine::icon x-show="(!editMode || isLockedSession)" icon="lock-closed" size="3" />
                     <span class="text-[8px] font-black mt-0.5 uppercase tracking-tighter" x-text="isLockedSession ? 'PAID' : (editMode ? 'EDIT' : 'LOCK')"></span>
                </div>
            </button>

            <button x-show="editMode && !isLockedSession && items.length > 0" 
                    @click="lockDraft()" 
                    class="action-btn !h-[2.5rem] px-4 bg-rose-600 hover:bg-rose-700 text-white shadow-[0_0_20px_rgba(244,63,94,0.3)]">
                <div class="flex flex-col items-center justify-center leading-none">
                     <x-moonshine::icon icon="shield-check" size="3" />
                     <span class="text-[8px] font-black mt-0.5 uppercase tracking-tighter">KUNCI DRAFT</span>
                </div>
            </button>

            <div class="w-[1px] h-6 bg-white/10 mx-1"></div>

            <template x-if="!isOnline">
                <div class="px-3 py-1 bg-rose-500 text-white text-[9px] font-black rounded-lg animate-pulse uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 bg-white rounded-full"></span>
                    No Connection
                </div>
            </template>

            <!-- 2.1 KINETIC SORTING (SEGMENTED GLASS) -->
            <div class="kinetic-segmented-control">
                <div class="active-indicator" :class="{ 'pos-abc': sortMode === 'abc', 'pos-prod': sortMode === 'prod', 'pos-hot': sortMode === 'hot', 'pos-new': sortMode === 'new' }"></div>
                <button @click="setSortMode('abc')" class="segment-btn px-2" :class="sortMode === 'abc' ? 'active' : ''">
                    <span class="segment-label">ABC</span>
                    <span x-show="sortMode === 'abc'" class="text-[6px] ml-0.5 opacity-50" x-text="sortDesc ? '▼' : '▲'"></span>
                </button>
                <button @click="setSortMode('prod')" class="segment-btn px-2" :class="sortMode === 'prod' ? 'active' : ''">
                    <x-moonshine::icon icon="users" size="3" class="mb-0.5" />
                    <span class="segment-label">PROD</span>
                    <span x-show="sortMode === 'prod'" class="text-[6px] ml-0.5 opacity-50" x-text="sortDesc ? '▼' : '▲'"></span>
                </button>
                <button @click="setSortMode('hot')" class="segment-btn px-2" :class="sortMode === 'hot' ? 'active' : ''">
                    <x-moonshine::icon icon="bolt" size="3" class="mb-0.5" />
                    <span class="segment-label">HOT</span>
                    <span x-show="sortMode === 'hot'" class="text-[6px] ml-0.5 opacity-50" x-text="sortDesc ? '▼' : '▲'"></span>
                </button>
                <button @click="setSortMode('new')" class="segment-btn px-2" :class="sortMode === 'new' ? 'active' : ''">
                    <x-moonshine::icon icon="clock" size="3" class="mb-0.5" />
                    <span class="segment-label">NEW</span>
                    <span x-show="sortMode === 'new'" class="text-[6px] ml-0.5 opacity-50" x-text="sortDesc ? '▼' : '▲'"></span>
                </button>
            </div>





        </div>
    </div>

    <!-- 3. KINETIC MATRIX (UNIFIED ARCHITECTURE) -->
    <div class="matrix-container shadow-2xl hub-block !p-0 overflow-hidden flex flex-col w-full max-w-full" @selectstart.prevent.stop>
        <div class="scroll-port no-scrollbar">
            <table class="kinetic-table pos-table-manifest whitespace-nowrap w-full" onselectstart="return false">
                <thead class="sticky top-0 z-30">
                    <tr class="bg-slate-100 dark:bg-[#0f172a] backdrop-blur-md">
                        <th class="sticky-col-idx px-2 py-1.5 text-center border-r border-slate-300 dark:border-white/5 w-[40px] min-w-[40px] text-slate-600 dark:text-gray-400">#</th>
                        <th class="sticky-col-name px-2 py-1.5 text-left border-r border-slate-300 dark:border-white/5 min-w-[80px] w-auto text-primary">
                            <span class="hidden sm:inline">Daftar Produk</span>
                            <span class="sm:hidden">Produk</span>
                        </th>
                        <th class="px-0 py-1.5 text-center text-[9px] font-black uppercase w-[60px] min-w-[60px] border-l border-slate-300 dark:border-white/5 text-cyan-600 dark:text-cyan-400">TTP</th>
                        <th class="px-0 py-1.5 text-center text-[9px] font-black uppercase w-[60px] min-w-[60px] border-l border-slate-300 dark:border-white/5 text-rose-600 dark:text-rose-400">S.R</th>
                        <th class="px-0 py-1.5 text-center text-[9px] font-black uppercase w-[60px] min-w-[60px] border-l border-slate-300 dark:border-white/5 text-slate-600 dark:text-gray-400">S.J</th>
                        <th class="px-0 py-1.5 text-center text-[9px] font-black uppercase w-[60px] min-w-[60px] border-l border-slate-300 dark:border-white/5 text-emerald-600 dark:text-emerald-400">LK</th>
                        <th class="px-2 py-1.5 text-right text-[7px] font-black uppercase text-slate-600 dark:text-gray-300 opacity-80 dark:opacity-40 bg-slate-50 dark:bg-white/5 border-l border-slate-300 dark:border-white/5 w-[70px]">Bayar</th>
                        <th class="px-2 py-1.5 text-right text-[7px] font-black uppercase opacity-100 dark:opacity-40 bg-emerald-50 dark:bg-emerald-500/5 text-emerald-600 dark:text-emerald-400 border-l border-slate-300 dark:border-white/5 w-[80px]">Omset</th>
                        <th x-show="editMode" class="px-1 py-0.5 text-center text-[7px] font-black uppercase opacity-60 dark:opacity-40 text-slate-500 dark:text-gray-400 border border-slate-300 dark:border-white/10">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-white/5 no-scrollbar" id="matrix-body">
                    <template x-for="(item, index) in filteredItems()" :key="item.produk_id">
                        <tr class="group hover:bg-slate-100 dark:hover:bg-white/[0.04] even:bg-slate-50 dark:even:bg-white/[0.01] transition-colors kinetic-row">
                            <td class="sticky-col-idx px-1 text-center font-mono text-[8px] text-slate-500 dark:text-gray-300 opacity-80 dark:opacity-30 border border-slate-200 dark:border-white/10" x-text="index + 1"></td>
                            <td class="sticky-col-name border border-slate-200 dark:border-white/10 overflow-hidden" style="padding: 3px !important">
                                <div class="flex flex-col justify-center min-h-[22px] py-1 leading-tight">
                                    <span class="text-[8.5px] font-black uppercase text-slate-800 dark:text-gray-200 group-hover:text-black dark:group-hover:text-white truncate whitespace-nowrap block w-full mb-1" x-text="shortenName(item.nama).toUpperCase()"></span>
                                    
                                    <div class="flex items-center gap-1.5 opacity-80 font-mono font-bold text-slate-500 dark:text-gray-500 overflow-hidden" style="font-size: 8.5px !important">
                                        <span class="truncate max-w-[100px]" x-text="item.produsen_nama"></span>
                                        <span class="opacity-40 dark:opacity-20">|</span>
                                        <div class="flex items-center gap-0.5">
                                            <span x-text="formatNumber(item.harga_beli)"></span>
                                            <span class="opacity-50 dark:opacity-30">/</span>
                                            <span x-text="formatNumber(item.harga_jual)"></span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="p-0 border border-slate-200 dark:border-white/10 bg-cyan-50 dark:bg-cyan-500/[0.03] w-[60px] min-w-[60px]">
                                <input type="number" x-model.number="item.titip" 
                                    @input="recalcRow(index, 'titip')" 
                                    @keydown.prevent.enter="focusNext(index, 'sr')"
                                    @keydown.up="navigate(index, 'titip', $event)"
                                    @keydown.down="navigate(index, 'titip', $event)"
                                    @keydown.left="navigate(index, 'titip', $event)"
                                    @keydown.right="navigate(index, 'titip', $event)"
                                    @keydown.tab="navigate(index, 'titip', $event)"
                                    :id="'titip_' + index"
                                    :disabled="!editMode || item.status !== 'Draft'" 
                                    :class="{ 'animate-success-flash': item._saved }"
                                    style="padding: 3px !important"
                                    class="matrix-input em-input h-full text-center text-[10px] font-bold p-0 m-0 w-full border-none outline-none bg-transparent text-cyan-700 dark:text-cyan-400">
                            </td>
                            <td class="p-0 border border-slate-200 dark:border-white/10 bg-rose-50 dark:bg-rose-500/[0.03] w-[60px] min-w-[60px]">
                                <input type="number" x-model.number="item.sr" 
                                    @input="recalcRow(index, 'sr')" 
                                    @keydown.prevent.enter="focusNext(index, 'sj')"
                                    @keydown.up="navigate(index, 'sr', $event)"
                                    @keydown.down="navigate(index, 'sr', $event)"
                                    @keydown.left="navigate(index, 'sr', $event)"
                                    @keydown.right="navigate(index, 'sr', $event)"
                                    @keydown.tab="navigate(index, 'sr', $event)"
                                    :id="'sr_' + index"
                                    :disabled="!editMode || item.status !== 'Draft'" 
                                    :class="{ 'animate-success-flash': item._saved }"
                                    style="padding: 3px !important"
                                    class="matrix-input rs-input h-full text-center text-[10px] font-bold p-0 m-0 w-full border-none outline-none bg-transparent text-rose-700 dark:text-rose-400">
                            </td>
                            <td class="p-0 border border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-white/[0.02] w-[60px] min-w-[60px]">
                                <input type="number" x-model.number="item.sj" 
                                    @input="recalcRow(index, 'sj')" 
                                    @keydown.prevent.enter="focusNextRow(index)"
                                    @keydown.up="navigate(index, 'sj', $event)"
                                    @keydown.down="navigate(index, 'sj', $event)"
                                    @keydown.left="navigate(index, 'sj', $event)"
                                    @keydown.right="navigate(index, 'sj', $event)"
                                    @keydown.tab="navigate(index, 'sj', $event)"
                                    :id="'sj_' + index"
                                    :disabled="!editMode || item.status !== 'Draft'" 
                                    :class="{ 'animate-success-flash': item._saved }"
                                    style="padding: 3px !important"
                                    class="matrix-input bl-input h-full text-center text-[10px] font-bold p-0 m-0 w-full border-none outline-none bg-transparent text-slate-700 dark:text-gray-400">
                            </td>
 
                            <td class="text-center border border-slate-200 dark:border-white/10 bg-emerald-50 dark:bg-emerald-500/10 w-[60px] min-w-[60px]" style="padding: 3px !important">
                                <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 font-mono" x-text="item.laku"></span>
                            </td>
                            
                            
                            <td class="text-right font-mono text-[8px] font-bold text-slate-700 dark:text-gray-500 bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10" style="padding: 3px !important" x-text="formatNumber(item.bayar)"></td>
                            <td class="text-right font-mono text-[9px] font-black text-amber-600 dark:text-amber-500/80 bg-amber-50 dark:bg-amber-500/[0.05] border border-slate-200 dark:border-white/10" style="padding: 3px !important" x-text="formatNumber(item.rowOmset)"></td>
                            
                            <td x-show="editMode" class="px-3 text-center border border-slate-200 dark:border-white/10">
                                <button x-show="item.status === 'Draft'" @click="removeItem(index)" class="text-rose-500/60 dark:text-rose-500/30 hover:text-rose-600 dark:hover:text-rose-500 transition-colors">
                                    <x-moonshine::icon icon="trash" size="3" />
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot class="sticky bottom-0 z-30">
                    <tr class="bg-slate-200 dark:bg-[#0f172a] backdrop-blur-md h-[28px] border-t border-slate-300 dark:border-white/20">
                        <td class="sticky-col-idx px-1 border border-slate-300 dark:border-white/10"></td>
                        <td class="sticky-col-name px-2 border border-slate-300 dark:border-white/10 font-black text-[7px] uppercase text-slate-800 dark:text-white opacity-80 dark:opacity-60">Aggregate</td>
                        <td class="px-0 text-center text-cyan-600 dark:text-cyan-400 font-black font-mono text-[9px] border border-slate-300 dark:border-white/10 bg-cyan-100 dark:bg-cyan-500/10" x-text="totalTitip()"></td>
                        <td class="px-0 text-center text-rose-600 dark:text-rose-400 font-black font-mono text-[9px] border border-slate-300 dark:border-white/10 bg-rose-100 dark:bg-rose-500/10" x-text="totalSR()"></td>
                        <td class="px-0 text-center text-slate-600 dark:text-gray-400 font-black font-mono text-[9px] border border-slate-300 dark:border-white/10 bg-slate-100 dark:bg-white/5" x-text="totalSJ()"></td>
                        <td class="px-0 text-center text-emerald-600 dark:text-emerald-400 font-black font-mono text-[10px] border border-slate-300 dark:border-white/10 bg-emerald-100 dark:bg-emerald-500/10" x-text="totalLaku()"></td>
                        <td class="px-2 text-right text-slate-700 dark:text-gray-300 font-bold font-mono text-[8px] bg-slate-100 dark:bg-white/5 border border-slate-300 dark:border-white/10" x-text="formatNumber(totalBayar())"></td>
                        <td class="px-2 text-right text-amber-600 dark:text-amber-400 font-black font-mono text-[10px] bg-amber-100 dark:bg-amber-500/10 border border-slate-300 dark:border-white/10" x-text="formatNumber(totalOmset())"></td>
                        <td x-show="editMode" class="px-1 border border-slate-300 dark:border-white/10"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<style>
    /* GLOBAL LAYOUT OVERRIDE FOR TRUE FULL-WIDTH */
    body:has(.kinetic-wrapper) .layout-page {
        padding: 0 !important;
    }
    body:has(.kinetic-wrapper) .layout-content {
        padding: 0 !important;
        max-width: 100% !important;
    }
    body:has(.kinetic-wrapper) .layout-navigation {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
        padding-top: 0.5rem !important;
    }

    
    .kinetic-row {
        min-height: 23px;
    }
    
    .kinetic-table td, .kinetic-table th {
        padding: 3px !important;
        line-height: 1;
    }
    
    .matrix-input {
        width: 100% !important;
        height: 100% !important;
        padding: 3px !important;
        margin: 0 !important;
        display: block !important;
        border-radius: 0 !important;
        cursor: cell;
        transition: all 0.1s;
    }
    
    .matrix-input:focus {
        background: rgba(255, 255, 255, 0.8) !important;
        color: #000 !important;
        box-shadow: inset 0 0 0 1px #facc15 !important;
    }
    html.dark .matrix-input:focus {
        background: rgba(255, 255, 255, 0.15) !important;
        color: #fff !important;
    }
    
    .kinetic-table {
        border-collapse: collapse !important;
        width: 100%;
        border: 1px solid rgba(0, 0, 0, 0.1);
    }
    html.dark .kinetic-table { border-color: rgba(255, 255, 255, 0.1); }
    
    .kinetic-table th, .kinetic-table td {
        border: 1px solid rgba(0, 0, 0, 0.1);
    }
    html.dark .kinetic-table th, html.dark .kinetic-table td { border-color: rgba(255, 255, 255, 0.1); }
    
    .kinetic-table input::-webkit-outer-spin-button,
    .kinetic-table input::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }


    .scroll-port {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* CONTROL BAR FORCED ROW */
    .kinetic-control-bar {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: nowrap !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 0.75rem !important;
        background: white;
        backdrop-filter: blur(16px);
        border: 1px solid rgba(0, 0, 0, 0.1);
        padding: 0.5rem 0.75rem;
        border-radius: 1.25rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        position: relative !important;
        z-index: 100 !important;
    }
    html.dark .kinetic-control-bar {
        background: rgba(0, 0, 0, 0.6);
        border-color: rgba(255, 255, 255, 0.08);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
    }

    .search-wrapper { position: relative; flex-grow: 1; max-width: 500px; display: flex; align-items: center; }
    .search-icon { position: absolute; left: 1rem; display: flex; align-items: center; justify-content: center; color: #10b981; pointer-events: none; opacity: 0.9; width: 1.15rem; height: 1.15rem; }
    .search-input { width: 100%; height: 2.25rem; background: rgba(0, 0, 0, 0.02); border: 1px solid rgba(0, 0, 0, 0.1); border-radius: 0.85rem; padding-left: 2.75rem; font-size: 10px; font-weight: 800; color: #0f172a; text-transform: uppercase; outline: none; transition: all 0.3s; letter-spacing: 0.05em; }
    html.dark .search-input { background: rgba(255, 255, 255, 0.05); border-color: rgba(255, 255, 255, 0.1); color: white; }
    .search-input:focus { border-color: #10b981; background: rgba(0, 0, 0, 0.04); box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1); }
    html.dark .search-input:focus { background: rgba(255, 255, 255, 0.08); }
    .search-input::placeholder { color: #94a3b8; }
    
    .search-results { position: absolute; left: 0; right: 0; top: 120%; background: #0f172a; border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 1.25rem; shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); z-index: 60; max-height: 350px; overflow-y: auto; box-shadow: 0 0 50px rgba(0,0,0,0.8); }
    .result-item { padding: 0.75rem 1.25rem; transition: all 0.2s; }
    .active-result { background: #10b981; color: black !important; padding: 0.75rem 1.25rem; }
    .active-result * { color: black !important; opacity: 1 !important; }

    .action-buttons { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
    .action-btn { height: 2.25rem; padding: 0 1rem; border-radius: 0.85rem; font-size: 9px; font-weight: 900; text-transform: uppercase; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s; white-space: nowrap; letter-spacing: 0.05em; }
    .btn-edit-on { background: #10b981; color: black; }
    .btn-edit-off { background: rgba(0, 0, 0, 0.04); color: #64748b; border: 1px solid rgba(0, 0, 0, 0.1); }
    html.dark .btn-edit-off { background: rgba(255, 255, 255, 0.03); color: #94a3b8; border-color: rgba(255, 255, 255, 0.08); }
    .btn-edit-off:hover { background: rgba(0, 0, 0, 0.06); color: #1e293b; border-color: rgba(0, 0, 0, 0.15); }
    html.dark .btn-edit-off:hover { background: rgba(255, 255, 255, 0.06); color: white; border-color: rgba(255, 255, 255, 0.15); }
    .btn-primary { background: #facc15; color: black; }
    .btn-primary:hover { background: #fde047; transform: translateY(-1px); }
    .btn-primary:active { transform: translateY(0); }

    /* KINETIC SEGMENTED CONTROL */
    .kinetic-segmented-control {
        display: flex;
        background: rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 0.85rem;
        padding: 0.2rem;
        position: relative;
        height: 2.5rem;
        backdrop-filter: blur(8px);
    }
    html.dark .kinetic-segmented-control {
        background: rgba(255, 255, 255, 0.03);
        border-color: rgba(255, 255, 255, 0.08);
    }
    .matrix-container {
        border-radius: 0 !important;
    }
    .active-indicator {
        position: absolute;
        top: 0.2rem;
        bottom: 0.2rem;
        width: 24%;
        background: white;
        border-radius: 0.7rem;
        transition: all 0.4s cubic-bezier(0.19, 1, 0.22, 1);
        z-index: 10;
        border: 1px solid rgba(0, 0, 0, 0.1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    html.dark .active-indicator {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    }
    .pos-abc { left: 0.2rem; background: #3b82f6 !important; border-color: #2563eb !important; }
    .pos-prod { left: 25.5%; background: #f59e0b !important; border-color: #d97706 !important; }
    .pos-hot { left: 50.5%; background: #ef4444 !important; border-color: #dc2626 !important; }
    .pos-new { left: 75.5%; background: #10b981 !important; border-color: #059669 !important; }
    
    .segment-btn {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        padding: 0 0.75rem;
        min-width: 4rem;
        z-index: 20;
        transition: all 0.3s;
        border-radius: 0.7rem;
    }
    .segment-btn .segment-label {
        font-family: ui-monospace, SFMono-Regular, monospace;
        font-size: 8px;
        font-weight: 950;
        letter-spacing: 0.15em;
        color: #1e293b;
        transition: all 0.3s;
    }
    html.dark .segment-btn .segment-label { color: #94a3b8; }
    .segment-btn.active .segment-label,
    .segment-btn.active svg { color: white !important; opacity: 1 !important; }
    .segment-btn:hover:not(.active) .segment-label { color: #000; font-weight: 950; }
    html.dark .segment-btn:hover:not(.active) .segment-label { color: white; }

    /* KINETIC VISIBILITY CONTROL */
    .kinetic-visibility-control {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 0.85rem;
        padding: 0 0.85rem;
        height: 2.5rem;
        cursor: pointer;
        transition: all 0.3s;
    }
    .kinetic-visibility-control:hover { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.15); }
    .toggle-status { display: flex; align-items: center; gap: 0.4rem; transition: all 0.3s; }
    .status-visible { color: #64748b; }
    .status-hidden { color: #10b981; }
    .toggle-label { font-size: 8px; font-weight: 900; letter-spacing: 0.1em; }
    
    .toggle-track { width: 2.5rem; height: 1.15rem; background: rgba(0,0,0,0.4); border-radius: 1rem; padding: 0.15rem; display: flex; align-items: center; border: 1px solid rgba(255,255,255,0.05); }
    .toggle-thumb { width: 0.75rem; height: 0.75rem; border-radius: 50%; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }

    /* MATRIX TABLE (ONYX EDITION & LIGHT MODE) */
    .matrix-container { 
        background: var(--bg-secondary, #ffffff) !important; 
        border: 1px solid var(--border-color, #e2e8f0) !important; 
        border-radius: 1rem; 
        overflow: hidden; 
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1); 
    }
    .dark .matrix-container {
        background: rgba(15, 23, 42, 0.4) !important;
        border-color: rgba(255, 255, 255, 0.05) !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); 
    }
    .pos-table-manifest thead tr { background: rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.1); }
    .pos-table-manifest th { padding: 0.45rem 0.35rem; font-size: 9px; font-weight: 950; text-transform: uppercase; color: #64748b; text-align: center; letter-spacing: 0.1em; }
    .em-bg-lite { background: rgba(16, 185, 129, 0.05); }
    .pr-bg-lite { background: rgba(250, 204, 21, 0.05); }
 
    /* FORCE HIGH CONTRAST ON ALL KINETIC INPUTS */
    .kinetic-table input.matrix-input,
    .kinetic-table input.matrix-input:disabled,
    .kinetic-table input.matrix-input[disabled] {
        color: #000000 !important;
        -webkit-text-fill-color: #000000 !important;
        opacity: 1 !important;
        background-color: #ffffff !important;
        font-weight: 900 !important;
        text-align: center !important;
    }
    html.dark .kinetic-table input.matrix-input {
        color: #f8fafc !important;
        -webkit-text-fill-color: #f8fafc !important;
        background-color: transparent !important;
    }

    .active-input { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.4); }
    .disabled-input { cursor: not-allowed; } 
    .em-input:focus { outline: none; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.5); }
    .rs-input:focus { outline: none; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.5); }
    .bl-input:focus { outline: none; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5); }

    .trash-btn { color: rgba(239, 68, 68, 0.4); padding: 0.5rem; border-radius: 0.75rem; transition: all 0.2s; }
    .trash-btn:hover { color: #ef4444; background: rgba(239, 68, 68, 0.1); }

    .sticky-col-idx { position: sticky; left: 0; z-index: 10; background: #f8fafc; min-width: 40px; }
    .sticky-col-name { position: sticky; left: 40px; z-index: 10; background: #f8fafc; box-shadow: 2px 0 5px rgba(0,0,0,0.05); }
    th.sticky-col-idx, th.sticky-col-name { background: #f1f5f9 !important; }

    .dark .sticky-col-idx { background: #0c1221; }
    .dark .sticky-col-name { background: #0c1221; box-shadow: 2px 0 5px rgba(0,0,0,0.2); }
    .dark th.sticky-col-idx, .dark th.sticky-col-name { background: rgba(30, 41, 59, 1) !important; }
    
    .overflow-x-auto { overscroll-behavior-x: contain; }
    


    @media (max-width: 640px) {
        /* FORCE FLUSH TO EDGES ON MOBILE */
        :root { --main-padding: 0 !important; }
        .layout-page { padding-left: 0 !important; padding-right: 0 !important; }
        .layout-container { padding-left: 0 !important; padding-right: 0 !important; }
        main.layout-content { padding-left: 0 !important; padding-right: 0 !important; }
        
        .matrix-container { border-radius: 0 !important; border-left: none !important; border-right: none !important; }
        .hub-block { border-radius: 0 !important; padding: 0 !important; margin-left: 0 !important; margin-right: 0 !important; }
        
        /* Mobile Touch Target & Layout Tuning */
        .kinetic-control-bar { 
            flex-direction: column !important; 
            align-items: stretch !important; 
            padding: 0.5rem !important; 
            border-radius: 0 !important;
            margin-bottom: 0px !important;
            border-left: none !important;
            border-right: none !important;
        }
        .search-wrapper { max-width: 100%; width: 100%; margin-bottom: 0.75rem; }
        .action-buttons { width: 100%; justify-content: space-between; }
        .action-btn { flex-grow: 1; justify-content: center; }
        .matrix-input { min-height: 44px; font-size: 14px; } /* Minimum touch target iOS */
        .pos-table-manifest tbody tr td { padding-top: 0.6rem; padding-bottom: 0.6rem; }
        .matrix-container { touch-action: pan-x pan-y; border-radius: 1rem; }
        .pos-table-manifest th, .pos-table-manifest td { white-space: nowrap; user-select: none !important; -webkit-user-select: none !important; }
    }
    
    .kinetic-table { border: 1px solid #e2e8f0; }
    .kinetic-table td { border: 1px solid #e2e8f0; }
    .dark .kinetic-table { border: 1px solid rgba(255,255,255,0.05); }
    .dark .kinetic-table td { border: 1px solid rgba(255,255,255,0.05); }
    
    .pos-table-manifest, .pos-table-manifest * { user-select: none !important; -webkit-user-select: none !important; -moz-user-select: none !important; -ms-user-select: none !important; }
    .matrix-input { user-select: none !important; -webkit-user-select: none !important; }
    
    /* Ultimate deterrent: prevent interaction with text-only cells */
    .pos-table-manifest td:not(:has(input)), .pos-table-manifest th { pointer-events: none; }
    .pos-table-manifest td:has(input) { pointer-events: auto; }

    /* Make any accidental selection invisible */
    .pos-table-manifest ::selection { background: transparent !important; color: inherit !important; }
    .pos-table-manifest ::-moz-selection { background: transparent !important; color: inherit !important; }
    
    @media (max-width: 640px) {
        .matrix-input { min-height: 48px !important; margin: 0 !important; border-radius: 0 !important; }
    }
</style>

<script>
function kineticDraft() {
    return {
        items: @json($initialItems ?? []),
        allProducts: @json($products ?? []),
        paletteSearch: '',
        filteredProducts: [],
        activeIndex: 0,
        isSpecial: @json($isSpecial ?? false),
        editMode: false,
        showResults: false,
        selectedDate: @json($selectedDate ?? date('Y-m-d')),
        pedagangId: @json($pedagang->id ?? null),
        saving: false,
        saveTimeout: null,
        isOnline: navigator.onLine,
        sortMode: 'abc', 
        sortDesc: false,
        hideEmpty: false,
        get isLockedSession() {
            return this.isLockedFromServer || (this.items || []).some(i => 
                ['ok', 'pending'].includes(i.status?.toLowerCase()) || 
                (i.status?.toLowerCase() === 'draft' && i.keterangan === 'Locked')
            );
        },

        init() {
            if (this.isLockedSession) {
                this.editMode = false;
            }
            window.addEventListener('online', () => { 
                this.isOnline = true; 
                if (window.MoonShine && MoonShine.ui) MoonShine.ui.toast('Online Kembali', 'success');
            });
            window.addEventListener('offline', () => { 
                this.isOnline = false; 
                if (window.MoonShine && MoonShine.ui) MoonShine.ui.toast('Koneksi Terputus!', 'error');
            });

            this.items.forEach((item, idx) => {
                if (item.laku === undefined) item.laku = Math.max(0, (item.titip || 0) - (item.sisa_jual || 0));
                this.recalcRow(idx, false);
            });
            this.$nextTick(() => {
                const el = document.getElementById('matrix-body');
                if (el) {
                    Sortable.create(el, { handle: '.handle', animation: 200, ghostClass: 'bg-primary/10',
                        onEnd: (evt) => {
                            const movedItem = this.items.splice(evt.oldIndex, 1)[0];
                            this.items.splice(evt.newIndex, 0, movedItem);
                            this.saveSortOrder();
                        }
                    });
                }
            });
            this.$watch('paletteSearch', () => { this.activeIndex = 0; });
        },

        formatNumber(val) { return new Intl.NumberFormat('id-ID').format(Math.round(val || 0)); },
        shortenName(name) {
            if (!name) return '';
            let words = name.trim().split(/\s+/);
            if (words.length <= 2) return name;
            
            let lastTwo = words.slice(-2);
            let initialWords = words.slice(0, -2);
            
            let processedInitial = initialWords.map(w => {
                if (w.length <= 1) return w;
                return w[0] + w.slice(1).replace(/[aiueoAIUEO]/g, '');
            });
            
            return [...processedInitial, ...lastTwo].join(' ');
        },
        filterProducts() {
            if (!this.paletteSearch) { this.filteredProducts = []; return; }
            const low = this.paletteSearch.toLowerCase();
            const existingIds = this.items.map(i => i.produk_id);
            this.filteredProducts = this.allProducts
                .filter(p => (p.nama || '').toLowerCase().includes(low))
                .map(p => ({ ...p, isAdded: existingIds.includes(p.id) }))
                .slice(0, 10);
        },

        addItem(p) {
            if (this.isLockedSession) return;

            // Check if already exists -> Jump instead of Add
            const existingIndex = this.items.findIndex(i => i.produk_id === p.id);
            if (existingIndex !== -1) {
                this.paletteSearch = '';
                this.showResults = false;
                this.jumpToProduct(p.id);
                return;
            }

            const newItem = { 
                produk_id: p.id, nama: p.nama, titip: 0, sr: 0, sj: 0, laku: 0, bayar: 0, rowOmset: 0, 
                harga_beli: parseFloat(p.harga_beli), harga_jual: parseFloat(p.harga_jual), 
                produsen_nama: p.produsen_nama || 'N/A',
                status: 'Draft', isNew: true, _saving: false, _saved: false, _dirty: false 
            };
            this.items.unshift(newItem); this.paletteSearch = ''; this.filteredProducts = []; this.editMode = true; this.showResults = false;
            this.autoSaveRow(0); this.$nextTick(() => { this.jumpToProduct(p.id); });
        },

        jumpToProduct(productId) {
            const index = this.items.findIndex(i => i.produk_id === productId);
            if (index !== -1) {
                const el = document.getElementById('titip_' + index);
                if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); setTimeout(() => { el.focus(); el.select(); }, 400); }
            }
        },

        focusNext(idx, field) {
            const el = document.getElementById(`${field}_${idx}`);
            if (el) { el.focus(); el.select(); }
        },

        focusNextRow(idx) {
            const nextIdx = idx + 1;
            const el = document.getElementById(`titip_${nextIdx}`);
            if (el) { el.focus(); el.select(); } else { this.paletteSearch = ''; document.querySelector('.search-input').focus(); }
        },

        navigate(idx, field, e) {
            const fields = ['titip', 'sr', 'sj'];
            const fIdx = fields.indexOf(field);
            if (e.key === 'ArrowUp' && idx > 0) { e.preventDefault(); this.focusNext(idx - 1, field); }
            else if (e.key === 'ArrowDown' && idx < this.items.length - 1) { e.preventDefault(); this.focusNext(idx + 1, field); }
            else if (e.key === 'ArrowLeft' && fIdx > 0) { e.preventDefault(); this.focusNext(idx, fields[fIdx - 1]); }
            else if (e.key === 'ArrowRight' && fIdx < fields.length - 1) { e.preventDefault(); this.focusNext(idx, fields[fIdx + 1]); }
            
            // Tab handling for sequential navigation
            if (e.key === 'Tab' && !e.shiftKey) {
                e.preventDefault();
                if (field === 'sj') {
                    this.focusNextRow(idx);
                } else {
                    this.focusNext(idx, fields[fIdx + 1]);
                }
            }
        },

        removeItem(idx) { this.items.splice(idx, 1); },

        async lockDraft(force = false) {
            let msg = 'KUNCI LAPORAN?\n\nSetelah dikunci, Anda tidak dapat mengedit data ini lagi dan admin akan segera memproses laporan Anda.\n\nLanjutkan?';
            if (force) msg = 'KONFIRMASI ULANG: LANJUTKAN KUNCI LAPORAN?';
            
            if (!confirm(msg)) return;
            
            this.saving = true;
            try {
                const response = await fetch("{{ route('admin.penjualan.lock-draft') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ 
                        pedagang_id: this.pedagangId, 
                        tanggal: this.selectedDate,
                        force: force
                    })
                });
                const data = await response.json();

                if (data.is_warning) {
                    const productsStr = data.unique_products.join('\n- ');
                    if (confirm(data.message + '\n\n' + data.details + '\n\nProduk Terisolasi:\n- ' + productsStr + '\n\nTetap kunci laporan ini?')) {
                        this.lockDraft(true);
                    }
                    return;
                }

                if (data.success) {
                    if (window.MoonShine && MoonShine.ui) MoonShine.ui.toast(data.message, 'success');
                    this.items.forEach(i => {
                        i.status = 'Draft';
                        i.keterangan = 'Locked';
                    });
                    this.editMode = false;
                } else {
                    if (window.MoonShine && MoonShine.ui) MoonShine.ui.toast(data.message, 'error');
                }
            } catch (e) {
                if (window.MoonShine && MoonShine.ui) MoonShine.ui.toast('Terjadi kesalahan sistem.', 'error');
            } finally {
                this.saving = false;
            }
        },

        recalcRow(idx, source = 'titip', shouldSave = true) {
            if (this.isLockedSession) return;
            this.editMode = true;
            
            const item = this.items[idx];
            let titip = Math.max(0, parseInt(item.titip) || 0);
            let sr = Math.max(0, parseInt(item.sr) || 0);
            let sj = Math.max(0, parseInt(item.sj) || 0);
            
            // Validation: Return + Sisa Jual cannot exceed Titip
            if (sr + sj > titip) {
                if (source === 'sr') {
                    item.sr = Math.max(0, titip - sj);
                } else {
                    item.sj = Math.max(0, titip - sr);
                }
                sr = Math.max(0, parseInt(item.sr) || 0);
                sj = Math.max(0, parseInt(item.sj) || 0);
            }
            
            // Calculate Laku as Result
            item.laku = titip - sr - sj;
            
            item.bayar = item.laku * (parseFloat(item.harga_beli) || 0);
            item.rowOmset = item.laku * (parseFloat(item.harga_jual) || 0);
            if (shouldSave) this.autoSaveRow(idx);
            
            // Re-sort if in HOT mode
            if (this.sortMode === 'hot') {
                this.$nextTick(() => { this.applySort(); });
            }
        },

        setSortMode(mode) {
            if (this.sortMode === mode) {
                this.sortDesc = !this.sortDesc;
            } else {
                this.sortMode = mode;
                this.sortDesc = false; // Default to natural (ABC, PROD: Asc | HOT, NEW: depends)
                if (mode === 'hot' || mode === 'new') this.sortDesc = true; 
            }
        },

        filteredItems() {
            let list = [...this.items];
            if (this.hideEmpty) {
                list = list.filter(i => (i.titip || 0) > 0 || (i.sr || 0) > 0 || (i.sj || 0) > 0);
            }

            const multi = this.sortDesc ? -1 : 1;

            if (this.sortMode === 'abc') {
                list.sort((a,b) => multi * (a.nama || '').localeCompare(b.nama || ''));
            } else if (this.sortMode === 'prod') {
                list.sort((a,b) => {
                    const res = (a.produsen_nama || '').localeCompare(b.produsen_nama || '') || (a.nama || '').localeCompare(b.nama || '');
                    return multi * res;
                });
            } else if (this.sortMode === 'hot') {
                list.sort((a,b) => multi * ((b.laku || 0) - (a.laku || 0)));
            } else if (this.sortMode === 'new') {
                list.sort((a,b) => multi * (new Date(a.created_at || 0) - new Date(b.created_at || 0)));
            }
            return list;
        },

        applySort() {
            // Force alpine to re-render or do logic if needed
        },

        totalLaku() { return this.items.reduce((sum, item) => sum + (parseInt(item.laku) || 0), 0); },
        totalTitip() { return this.items.reduce((sum, item) => sum + (parseInt(item.titip) || 0), 0); },
        totalSR() { return this.items.reduce((sum, item) => sum + (parseInt(item.sr) || 0), 0); },
        totalSJ() { return this.items.reduce((sum, item) => sum + (parseInt(item.sj) || 0), 0); },
        totalRawModal() { return this.items.reduce((sum, item) => sum + (parseFloat(item.bayar) || 0), 0); },
        totalBayar() { return this.totalRawModal() + this.calcProUp(); },
        totalOmset() { return this.items.reduce((sum, item) => sum + (parseFloat(item.rowOmset) || 0), 0); },
        calcProUp() {
            if (this.isSpecial) return 0;
            const rawModal = this.totalRawModal();
            const rate = 0.015;
            // Heritage Rule: rounddown(Total_Modal * 0.015, -3)
            return Math.floor((rawModal * rate) / 1000) * 1000;
        },

        calcLaba() { return this.totalOmset() - this.totalBayar(); },
        salesPercent() {
            const laku = this.totalLaku(); const titip = this.totalTitip();
            return titip === 0 ? 0 : Math.round((laku / titip) * 100);
        },

        async saveDraft() {
            if (this.saving || !this.isOnline) return; this.saving = true;
            try {
                const response = await fetch("{{ route('admin.penjualan.save-draft') }}", {
                    method: "POST", headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                    body: JSON.stringify({ pedagang_id: this.pedagangId, tanggal: this.selectedDate, items: this.items })
                });
                const data = await response.json();
                if (data.success) { this.editMode = false; if (window.MoonShine && MoonShine.ui) MoonShine.ui.toast(data.message, 'success'); }
            } catch (error) { console.error(error); } finally { this.saving = false; }
        },

        autoSaveRow(idx) {
            const item = this.items[idx]; item._saving = true; item._dirty = true;
            if (this.saveTimeout) clearTimeout(this.saveTimeout);
            this.saveTimeout = setTimeout(() => { this.performAutoSave(); }, 400);
        },

        async performAutoSave() {
            if (!this.pedagangId || !this.isOnline) return;
            const dirtyItems = this.items.filter(i => i._dirty);
            if (dirtyItems.length === 0) return;
            try {
                await fetch("{{ route('admin.penjualan.save-draft') }}", {
                    method: "POST", headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                    body: JSON.stringify({ pedagang_id: this.pedagangId, tanggal: this.selectedDate, items: dirtyItems.map(i => ({ produk_id: i.produk_id, titip: i.titip, laku: i.laku, sj: i.sj })) })
                });
                dirtyItems.forEach(i => { i._dirty = false; i._saving = false; i._saved = true; setTimeout(() => { i._saved = false; }, 2000); });
            } catch (e) { dirtyItems.forEach(i => { i._saving = false; }); }
        },

        async saveSortOrder() {
            const order = this.items.map(i => i.produk_id);
            try {
                await fetch("{{ route('admin.penjualan.save-sort') }}", {
                    method: "POST", headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                    body: JSON.stringify({ pedagang_id: this.pedagangId, sort_order: order })
                });
            } catch (e) {}
        }
    };
}
</script>