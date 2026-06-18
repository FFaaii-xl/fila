<!-- PEDAGANG TABLE -->
<x-moonshine::layout.box class="matrix-container shadow-2xl overflow-hidden rounded-lg">
    <div
        class="py-5 px-6 border-b dark:border-white/5 border-slate-100 dark:bg-black/40 bg-slate-50 flex items-center justify-between sticky top-0 z-30 backdrop-blur-md">
        <span
            class="text-[12px] font-black opacity-40 tracking-[0.3em] uppercase leading-relaxed pt-0.5">Penjualan
            Pedagang Hari ini</span>
        <span class="text-[11px] font-mono text-emerald-500/50 uppercase tracking-widest"
            x-text="`${tables.pedagang.length} Pedagang Aktif`"></span>
    </div>
    <div class="scroll-port max-h-[60dvh] overflow-y-auto custom-scrollbar relative">
        <table class="pos-table-manifest whitespace-nowrap">
            <thead class="sticky top-0 z-20">
                <tr>
                    <th class="sticky-col-no" style="width: 48px;">No</th>
                    <th class="text-left sticky-col-name shadow-sep" style="width: 150px;">Pedagang</th>
                    <th class="text-center">LT</th>
                    <th class="text-center" style="width: 65px;">%</th>
                    <th class="text-right">Modal</th>
                    <th class="text-right cursor-help" title="Tiered Lookup: Based on Modal Range">Kas</th>
                    <th class="text-right">Tab</th>
                    <th class="text-right bg-blue-50/10 text-blue-500 dark:bg-blue-500/5">Setoran</th>
                    <th class="text-center">Status</th>
                    <th class="text-center w-10">
                        <div class="flex items-center justify-center gap-1">
                            <!-- LOCK/UNLOCK ALL TOGGLE -->
                            <template x-if="hasLocked()">
                                <button x-show="state === 'draft'" @click="handleAction('unlock_all')"
                                    title="Buka Semua Kunci"
                                    class="text-amber-500 hover:text-amber-600 transition-colors flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                </button>
                            </template>
                            <template x-if="!hasLocked()">
                                <button x-show="state === 'draft'" @click="handleAction('lock_all')"
                                    title="Kunci Semua Laporan"
                                    class="text-blue-500/50 hover:text-blue-500 transition-colors flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                </button>
                            </template>

                            <button x-show="state === 'draft' || state === 'pending'" @click="handleAction('delete_all')"
                                title="Hapus Semua Data"
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
                <template x-for="(p, index) in tables.pedagang" :key="p.id">
                    <tr class="group transition-colors">
                        <td class="text-center opacity-30 text-[12px] font-mono sticky-col-no"
                            x-text="index + 1"></td>
                        <td class="font-bold uppercase text-[13px] tracking-tight sticky-col-name shadow-sep">
                            <div class="flex items-center gap-3">
                                <div class="status-avatar relative" 
                                     :class="isNalangiGlobal(p) ? 'status-avatar-nalangi' : 'status-avatar-ok'"
                                     :title="isNalangiGlobal(p) ? `NALANGI! Saldo: ${formatCurrency(p.current_saldo)}` : `OK. Saldo: ${formatCurrency(p.current_saldo)}`"
                                     x-text="p.nama.charAt(0)">
                                </div>
                                <span x-text="p.nama"></span>
                                <!-- AI Predict Button -->
                                <button @click.stop="predictTitip(p.id, p.nama)" class="text-purple-500 hover:text-purple-400 opacity-50 hover:opacity-100 transition-opacity" title="AI: Prediksi Titip Esok Hari">
                                    ✦
                                </button>
                            </div>
                        </td>
                        <td class="text-center text-[12px] opacity-50 font-mono"
                            x-text="`${p.laku}/${p.titip}`"></td>
                        <td class="px-2">
                            <div class="heatmap-cell mx-auto"
                                :style="`background-color: ${getHeatmapColor((p.laku/p.titip)*100)}`"
                                x-text="p.titip > 0 ? ((p.laku/p.titip)*100).toFixed(0)+'%' : '0%'">
                            </div>
                        </td>

                        <td class="text-right font-mono text-[13px] relative"
                            x-text="formatCurrency(parseFloat(p.setoran_modal) + calculateMerchantProup(p.setoran_modal, p.produk_count, p.nama))">
                        </td>

                        <td class="text-right font-mono text-[12px] opacity-60 cursor-help"
                            title="Tiered Kas Heritage Logic"
                            x-text="formatCurrency(calculateMerchantKas(p.setoran_modal))"></td>

                        <td class="text-right font-mono text-[12px] opacity-60"
                            x-text="formatCurrency(p.pr_tabungan || 0)"></td>

                        <td class="text-right font-bold font-mono text-[14px] text-blue-600 dark:text-blue-400 bg-blue-50/10 dark:bg-blue-500/5"
                            x-text="formatCurrency(calculateMerchantSetoran(p))"></td>

                        <td class="text-center">
                            <x-moonshine::badge
                                class="status-badge text-[8px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full"
                                x-bind:data-status="p.status?.toLowerCase() || 'draft'"
                                x-text="p.status || 'DRAFT'"
                            />
                        </td>
                        <td class="text-center w-10 flex items-center justify-center gap-1">
                            <!-- UNLOCK BUTTON -->
                            <button x-show="p.status?.toLowerCase() === 'locked'" @click="unlockMerchant(p.id, p.nama)"
                                title="Unlock Laporan"
                                class="text-amber-500/30 hover:text-amber-500 p-1 transition-colors flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </button>

                            <!-- LOCK BUTTON (ADMIN) -->
                            <button x-show="!p.status || p.status.toLowerCase() === 'draft'" @click="lockMerchant(p.id, p.nama)"
                                title="Paksa Kunci Laporan"
                                class="text-blue-500/30 hover:text-blue-500 p-1 transition-colors flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </button>

                            <!-- RESET BUTTON -->
                            <button x-show="p.status?.toLowerCase() !== 'ok'" @click="resetMerchant(p.id, p.nama)"
                                title="Hapus Draf"
                                class="text-rose-500/30 group-hover:text-rose-500 p-1 transition-colors flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
            <tfoot class="font-bold dark:bg-white/2 sticky bottom-0 z-20">
                <tr
                    class="border-t border-slate-200 dark:border-white/10 uppercase tracking-widest text-gray-400">
                    <td colspan="2"
                        class="text-center py-1 sticky left-0 z-30 bg-slate-50 dark:bg-[#1a1c23] border-r border-slate-200 dark:border-white/10"
                        style="font-size: 14px !important; font-weight: bold !important; color: #3b82f6;">Sistem Total</td>
                    <td class="text-center font-mono"
                        style="font-size: 14px !important; font-weight: bold !important;"
                        x-text="`${getMerchantTotals().laku}/${getMerchantTotals().titip}`"></td>
                    <td class="text-center">
                        <div class="heatmap-cell mx-auto scale-50"
                            style="background-color: transparent; font-size: 10px !important; font-weight: bold !important;"
                            :style="`background-color: ${getHeatmapColor(getMerchantTotals().titip > 0 ? (getMerchantTotals().laku/getMerchantTotals().titip)*100 : 0)}`"
                            x-text="getMerchantTotals().titip > 0 ? ((getMerchantTotals().laku/getMerchantTotals().titip)*100).toFixed(0)+'%' : '0%'">
                        </div>
                    </td>
                    <td class="text-right font-mono"
                        style="font-size: 14px !important; font-weight: bold !important;"
                        x-text="formatK(getMerchantTotals().modal)"></td>
                    <td class="text-right font-mono"
                        style="font-size: 14px !important; font-weight: bold !important;"
                        x-text="formatK(getMerchantTotals().kas)"></td>
                    <td class="text-right font-mono"
                        style="font-size: 14px !important; font-weight: bold !important;"
                        x-text="formatK(getMerchantTotals().tabungan)"></td>
                    <td class="text-right font-mono text-blue-600 dark:text-blue-400 bg-blue-50/5 dark:bg-blue-500/5 px-2"
                        style="font-size: 14px !important; font-weight: bold !important;"
                        x-text="formatK(getMerchantTotals().setoran)"></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-moonshine::layout.box>
