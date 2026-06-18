<!-- OPERATIONAL ADJUSTMENT HUB (INLINE) -->
<x-moonshine::layout.box id="adjustment-hub" x-show="showLainHub" x-cloak
    x-init="$watch('showLainHub', value => value && $nextTick(() => $refs.searchInput.focus()))"
    @keydown.escape.window="showLainHub = false" x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 -translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
    class="hub-block bg-[#0b1121] border border-white/10 rounded-[1.5rem] shadow-inner mb-6 relative"
    style="overflow: visible !important;"
    ::style="openSearch ? 'z-index: 1000 !important;' : 'z-index: 40 !important;'">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6 px-4 pt-2">
        <div class="flex items-center gap-4">
            <div
                class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary border border-primary/20">
                <x-moonshine::icon icon="presentation-chart-line" size="6" />
            </div>
            <div>
                <h3 class="text-[12px] font-black uppercase tracking-[0.2em] outfit-font text-white">Nota Tambahan
                </h3>
                <p class="text-[8px] text-white/30 font-mono tracking-[0.2em] uppercase">Penyesuaian Nota Manual
                    </p>
            </div>
        </div>
        <div class="px-4 py-2 bg-emerald-500/5 border border-emerald-500/10 rounded-xl flex items-center gap-3">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="text-[9px] font-mono text-emerald-500/50 uppercase tracking-widest leading-none">Input Langsung</span>
        </div>
        <div class="flex items-center gap-2">
            <!-- BULK MODAL TRIGGER -->
            <x-moonshine::modal
                name="bulk-lain-modal"
                title="IMPORT BULK NOTA TAMBAHAN"
            >
                <div class="p-4 bg-black/20 border border-white/5 rounded-2xl">
                    <label class="block text-[8px] font-black uppercase tracking-[0.3em] mb-3 opacity-30 px-1 italic">Format: Nama Produsen [TAB/\|/;/] Keterangan [TAB/\|/;/] Nominal</label>
                    <textarea x-model="bulkData" rows="8" 
                        placeholder="Contoh:&#10;Sukamto | Bonus | 50000&#10;Budi | Potongan | -10000"
                        class="w-full bg-black/40 border border-white/10 rounded-xl p-4 text-xs font-mono text-emerald-400 outline-none focus:border-primary/40 transition-all custom-scrollbar mb-4"
                        style="resize: none;"></textarea>
                    
                    <div class="flex items-center justify-between">
                        <div class="text-[9px] text-white/20 font-medium flex items-center gap-1">
                            <x-moonshine::icon icon="information-circle" size="4" />
                            Pemisah otomatis: Tab, Pipa (|), atau Titik Koma (;)
                        </div>
                        <button @click="submitBulkLainLain()" :disabled="loadingLain || !bulkData.trim()"
                            class="btn btn-primary px-8">
                            <svg x-show="loadingLain" width="14" height="14" class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="loadingLain ? 'PROSES...' : 'IMPORT SEKARANG'"></span>
                        </button>
                    </div>
                </div>

                <x-slot:outerHtml>
                    <button type="button" @click.prevent="toggleModal" x-show="state !== 'ok'" class="px-4 py-2 rounded-xl border bg-white/5 border-white/10 text-white/40 hover:text-white transition-all flex items-center gap-2 group">
                        <x-moonshine::icon icon="square-3-stack-3d" size="5" />
                        <span class="text-[10px] font-black uppercase tracking-widest">Mode Bulk</span>
                    </button>
                </x-slot:outerHtml>
            </x-moonshine::modal>

            <!-- DELETE ALL LAIN-LAIN -->
            <button x-show="state !== 'ok' && tables.lain_lain && tables.lain_lain.length > 0"
                @click="handleAction('delete_all_lain')"
                class="px-4 py-2 rounded-xl border bg-rose-500/10 border-rose-500/20 text-rose-500/50 hover:text-rose-500 transition-all flex items-center gap-2 group">
                <x-moonshine::icon icon="trash" size="5" />
                <span class="text-[10px] font-black uppercase tracking-widest">Hapus Semua</span>
            </button>
        </div>
    </div>

    <!-- Mode Single -->
    <div x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95">
    <form @submit.prevent="submitLainLain()"
        class="flex flex-wrap items-end gap-4 px-4 pb-4 border-b border-white/10" @keydown.escape.window="openSearch = false">

        <!-- 1. PRODUSEN SEARCH -->
        <div class="flex-grow min-w-[200px] max-w-[450px] relative" @click.away="openSearch = false">
            <label class="block text-[8px] font-black uppercase tracking-[0.3em] mb-2 opacity-30 px-1">Produsen /
                Produk</label>
            <div class="relative group">
                <x-moonshine::icon icon="magnifying-glass" size="5" class="absolute left-4 top-3 text-white/20" />
                <input x-ref="searchInput" type="text" x-model="searchQuery" 
                    @focus="openSearch = true"
                    @input="openSearch = true"
                    @click="openSearch = true" @keydown.tab="if(filtered.length === 1 || selectedIdx >= 0) { 
                            $event.preventDefault();
                            const p = selectedIdx >= 0 ? filtered[selectedIdx] : filtered[0];
                            lainForm.owner = 'Produsen:' + p.id; 
                            searchQuery = p.nama; 
                            openSearch = false;
                            setTimeout(() => $refs.keteranganInput.focus(), 50);
                        }" @keydown.down.prevent="selectedIdx = Math.min(selectedIdx + 1, filtered.length - 1)"
                    @keydown.up.prevent="selectedIdx = Math.max(selectedIdx - 1, 0)" @keydown.enter.prevent="if(selectedIdx >= 0) { 
                           const p = filtered[selectedIdx];
                           lainForm.owner = 'Produsen:' + p.id; 
                           searchQuery = p.nama; 
                           openSearch = false;
                           setTimeout(() => $refs.keteranganInput.focus(), 50);
                       } else if(filtered.length > 0) {
                           const p = filtered[0];
                           lainForm.owner = 'Produsen:' + p.id; 
                           searchQuery = p.nama; 
                           openSearch = false;
                           setTimeout(() => $refs.keteranganInput.focus(), 50);
                       }" placeholder="Cari pedagang..."
                    class="w-full text-xs font-bold border border-white/10 rounded-xl pl-10 pr-4 py-2.5 outline-none focus:border-primary/60 transition-all shadow-inner bg-black/40 text-white"
                    style="background-color: rgba(0,0,0,0.4) !important;">
            </div>

            <!-- Search Results Overlay (Kinetic Style) -->
            <div x-show="openSearch && filtered.length > 0"
                class="kinetic-search-results custom-scrollbar">
                <template x-for="(p, index) in filtered" :key="p.id">
                    <div @click="lainForm.owner = 'Produsen:' + p.id; searchQuery = p.nama; openSearch = false; setTimeout(() => $refs.keteranganInput.focus(), 50)"
                        @mouseenter="selectedIdx = index"
                        class="transition-all flex items-center justify-between group"
                        :class="selectedIdx === index ? 'active-result' : 'result-item'">
                        <div class="flex flex-col">
                            <span class="text-[11px] font-black uppercase tracking-widest"
                                x-text="p.nama"></span>
                            <span class="text-[8px] font-mono opacity-50"
                                x-text="p.produk_names || 'General Adjustment'"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- 2. KETERANGAN -->
        <div class="w-[180px]">
            <label
                class="block text-[8px] font-black uppercase tracking-[0.3em] mb-2 opacity-30 px-1">Keterangan</label>
            <input x-ref="keteranganInput" type="text" x-model="lainForm.keterangan"
                @keydown.enter.prevent="$refs.amountInput.focus()" placeholder="Contoh: Bonus Hari Raya"
                class="w-full text-xs font-bold border border-white/10 rounded-xl px-4 py-2.5 outline-none focus:border-primary/60 transition-all shadow-inner bg-black/40 text-white"
                style="background-color: rgba(0,0,0,0.4) !important;">
        </div>

        <!-- 3. NOMINAL -->
        <div class="w-[140px]">
            <label class="block text-[8px] font-black uppercase tracking-[0.3em] mb-2 opacity-30 px-1">Nominal
                (Rp)</label>
            <input x-ref="amountInput" type="number" x-model="lainForm.jumlah" :disabled="state === 'ok'"
                :class="{ 'animate-success-flash': flashSuccess, 'opacity-50 cursor-not-allowed': state === 'ok' }" @keydown.enter.prevent="submitLainLain()"
                placeholder="0"
                class="w-full text-xs font-black border border-white/10 rounded-xl px-4 py-2.5 outline-none focus:border-primary/60 transition-all shadow-inner bg-black/40 text-white mono-font"
                style="background-color: rgba(0,0,0,0.4) !important;">
        </div>

        <!-- 4. SUBMIT -->
        <button x-show="state !== 'ok'" type="submit" :disabled="loadingLain || !lainForm.owner"
            class="h-[41px] px-6 bg-primary text-on-primary rounded-xl font-black text-[10px] uppercase tracking-widest flex items-center gap-2 transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-20 shadow-lg shadow-primary/20">
            <svg x-show="loadingLain" width="14" height="14" class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg"
                fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            <span x-text="loadingLain ? 'LOGGING...' : '+ ADD'"></span>
        </button>
    </form>
    </div>



    <!-- Log List (High Density Manifest) -->
    <div class="px-4 py-4" x-show="tables.lain_lain && tables.lain_lain.length > 0">
        <div class="overflow-x-auto rounded-xl border border-white/10 bg-black/20 shadow-sm">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white/5 border-b border-white/10">
                        <th class="px-5 py-3 text-[8px] font-black uppercase tracking-[0.2em] text-white/40">
                            Waktu</th>
                        <th class="px-5 py-3 text-[8px] font-black uppercase tracking-[0.2em] text-white/40">
                            Produsen</th>
                        <th class="px-5 py-3 text-[8px] font-black uppercase tracking-[0.2em] text-white/40">Keterangan
                            </th>
                        <th
                            class="px-5 py-3 text-right text-[8px] font-black uppercase tracking-[0.2em] text-white/40">
                            Jumlah</th>
                        <th class="px-3 py-3 w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <template x-for="log in tables.lain_lain" :key="log.id">
                        <tr class="group hover:bg-white/5 transition-colors">
                            <td class="px-5 py-3 text-[9px] font-mono text-white/30 uppercase" x-text="currentDate">
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-[10px] font-black text-white/80 uppercase tracking-wider"
                                    x-text="log.owner_name"></span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-[10px] font-mono text-white/30 uppercase tracking-tighter"
                                    x-text="log.keterangan || '-'"></span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-[11px] font-bold font-mono text-emerald-500"
                                    x-text="formatCurrency(log.jumlah)"></span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <button x-show="state !== 'ok'" @click.prevent="deleteLainLain(log.id)"
                                    class="p-1.5 rounded-lg text-rose-500/30 hover:text-rose-500 hover:bg-rose-500/10 transition-all flex items-center justify-center mx-auto">
                                    <x-moonshine::icon icon="trash" size="4" />
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</x-moonshine::layout.box>
