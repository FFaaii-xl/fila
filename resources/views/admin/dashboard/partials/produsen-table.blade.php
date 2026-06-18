<!-- PRODUSEN TABLE -->
<x-moonshine::layout.box class="matrix-container shadow-2xl overflow-hidden rounded-lg mt-0">
    <div
        class="py-5 px-6 border-b dark:border-white/5 border-slate-100 dark:bg-black/40 bg-slate-50 flex items-center justify-between sticky top-0 z-30 backdrop-blur-md">
        <span
            class="text-[12px] font-black opacity-40 tracking-[0.3em] uppercase leading-relaxed pt-0.5">Penjualan
            Produsen</span>
        <span class="text-[11px] font-mono text-blue-500/50 uppercase tracking-widest"
            x-text="`${tables.produsen.length} Produsen Aktif`"></span>
    </div>
    <div class="scroll-port max-h-[60dvh] overflow-y-auto custom-scrollbar relative">
        <table class="pos-table-manifest whitespace-nowrap">
            <thead class="sticky top-0 z-20">
                <tr>
                    <th class="sticky-col-no" style="width: 48px;">No</th>
                    <th class="text-left sticky-col-name shadow-sep" style="width: 150px;">Produsen</th>
                    <th class="text-left" style="width: 120px; max-width: 120px;">Produk</th>
                    <th class="text-right">Bruto</th>
                    <th class="text-right">Kas</th>
                    <th class="text-right">Tab</th>
                    <th
                        class="text-right bg-emerald-50/10 text-emerald-600 dark:bg-emerald-500/5 dark:text-emerald-400">
                        Net Pay</th>
                    <th class="text-center">Status</th>
                    <th class="text-center w-10">
                        <div class="flex items-center justify-center gap-1">
                             <button x-show="state === 'draft' || state === 'pending'" @click="handleAction('delete_all_produsen')"
                                 title="Hapus Semua Data Produsen"
                                 class="text-rose-500/50 hover:text-rose-500 transition-colors flex items-center justify-center">
                                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                     <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                 </svg>
                             </button>
                        </div>
                     </th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(p, index) in tables.produsen" :key="p.id">
                    <tr class="group transition-colors">
                        <td class="text-center opacity-30 text-[12px] font-mono sticky-col-no"
                            x-text="index + 1"></td>
                        <td class="font-bold uppercase text-[13px] tracking-tight sticky-col-name shadow-sep"
                            x-text="p.nama"></td>
                        <td class="px-3 py-3 border-b border-white/5 whitespace-normal cursor-pointer hover:bg-white/[0.02] transition-colors"
                            style="width: 120px; max-width: 120px; overflow: hidden;"
                            @click="p._expanded = !p._expanded">
                            <div class="flex flex-wrap gap-1 max-w-full overflow-hidden">
                                <!-- Show sliced if not expanded, show all if expanded -->
                                <template
                                    x-for="(name, i) in (p.produk_names || '').split(',').slice(0, p._expanded ? 999 : 2)">
                                    <x-moonshine::badge
                                        color="gray"
                                        class="px-1.5 py-1 rounded text-[7px] font-black uppercase text-gray-400 leading-none shadow-sm max-w-[100px] truncate"
                                        ::title="name.trim()" x-text="name.trim()"
                                    />
                                </template>

                                <!-- The Toggle Badge -->
                                <template x-if="!p._expanded && (p.produk_names || '').split(',').length > 2">
                                    <x-moonshine::badge
                                        color="blue"
                                        class="px-1.5 py-0.5 rounded text-[7px] font-black uppercase leading-none animate-pulse"
                                    >
                                        <span x-text="`+${(p.produk_names || '').split(',').length - 2}`"></span>
                                    </x-moonshine::badge>
                                </template>

                                <template x-if="p._expanded && (p.produk_names || '').split(',').length > 2">
                                    <span
                                        class="px-1.2 py-0.5 text-[6px] font-black text-gray-600 uppercase tracking-tighter">Collapse</span>
                                </template>

                                <template x-if="!p.produk_names">
                                    <span class="text-[9px] opacity-20 italic">No Items</span>
                                </template>
                            </div>
                        </td>
                        <td class="text-right font-mono text-[13px]" x-text="formatCurrency(p.hb_total)"></td>
                        <td class="text-right font-mono text-[12px] text-rose-500/70"
                            x-text="formatCurrency((p.status?.toLowerCase() === 'ok' ? (p.kas || 0) : calculateProducerKas(parseFloat(p.hb_total) || 0)))">
                        </td>
                        <td class="text-right font-mono text-[12px] text-rose-500/70">
                            <template x-if="p.status?.toLowerCase() === 'ok'">
                                <span x-text="formatCurrency(p.tabungan || 0)"></span>
                            </template>
                            <template x-if="p.status?.toLowerCase() !== 'ok'">
                                <span
                                    x-text="formatCurrency(((parseFloat(p.hb_total) - calculateProducerKas(parseFloat(p.hb_total))) - (parseFloat(p.pr_tabungan) || 0) <= 0) ? 0 : (p.pr_tabungan || 0))"></span>
                            </template>
                        </td>
                        <td class="text-right font-bold font-mono text-[12px] text-emerald-600 dark:text-emerald-400 bg-emerald-50/10 dark:bg-emerald-500/5"
                            x-text="formatCurrency(calculateProducerNet(p))"></td>
                        <td class="text-center">
                            <x-moonshine::badge
                                class="status-badge text-[8px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full"
                                x-bind:data-status="p.status?.toLowerCase() || 'draft'"
                                x-text="p.status || 'DRAFT'"
                            />
                        </td>
                        <td class="text-center w-10 flex items-center justify-center gap-1">
                            <!-- RESET BUTTON FOR PRODUSEN -->
                            <button x-show="p.status?.toLowerCase() !== 'ok'" @click="resetProducer(p.id, p.nama)"
                                title="Hapus Data Produsen"
                                class="text-rose-500/30 group-hover:text-rose-500 p-1 transition-colors flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
            <tfoot class="font-bold dark:bg-white/2">
                <tr
                    class="border-t border-slate-200 dark:border-white/10 uppercase tracking-widest text-gray-400">
                    <td colspan="3"
                        class="text-center py-1 sticky left-0 z-30 bg-slate-50 dark:bg-[#1a1c23] border-r border-slate-200 dark:border-white/10"
                        style="font-size: 12px !important; font-weight: bold !important; color: #10b981;">Sistem Total</td>
                    <td class="text-right font-mono"
                        style="font-size: 12px !important; font-weight: bold !important;"
                        x-text="formatK(getProducerTotals().hb_total)"></td>
                    <td class="text-right font-mono text-rose-500/40"
                        style="font-size: 12px !important; font-weight: bold !important;"
                        x-text="formatK(getProducerTotals().kas)"></td>
                    <td class="text-right font-mono text-rose-500/40"
                        style="font-size: 12px !important; font-weight: bold !important;"
                        x-text="formatK(getProducerTotals().tabungan)"></td>
                    <td class="text-right font-mono text-emerald-600 dark:text-emerald-400 bg-emerald-50/5 dark:bg-emerald-500/5 px-2"
                        style="font-size: 12px !important; font-weight: bold !important;"
                        x-text="formatK(getProducerTotals().net)"></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-moonshine::layout.box>
