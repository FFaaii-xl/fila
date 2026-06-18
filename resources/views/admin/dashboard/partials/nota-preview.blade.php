{{-- Direct Nota Preview via Alpine.js Fetch (No Iframe) - MoonShine v4 --}}
@php
    $produsenId = $produsen->id ?? 0;
    $today = now()->toDateString();
@endphp

<div 
    x-data="{
        notaData: null,
        loading: true,
        error: null,
        selectedDate: '{{ $today }}',
        
        async fetchNota() {
            this.loading = true;
            this.error = null;
            this.notaData = null;
            
            try {
                const response = await fetch(`/api/nota?produsen_id={{ $produsenId }}&date=${this.selectedDate}`, {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });
                if (!response.ok) {
                    const text = await response.text();
                    console.error('API Error:', response.status, text);
                    throw new Error(`Error ${response.status}: ${text}`);
                }
                const data = await response.json();
                console.log('Nota Data:', data);
                this.notaData = data;
            } catch (e) {
                console.error('Fetch Error:', e);
                this.error = e.message || 'Gagal memuat data';
            } finally {
                this.loading = false;
            }
        },
        
        formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num || 0);
        },
        
        prevDay() {
            const d = new Date(this.selectedDate);
            d.setDate(d.getDate() - 1);
            this.selectedDate = d.toISOString().split('T')[0];
            this.fetchNota();
        },
        
        nextDay() {
            const d = new Date(this.selectedDate);
            d.setDate(d.getDate() + 1);
            this.selectedDate = d.toISOString().split('T')[0];
            this.fetchNota();
        },
        
        goToday() {
            this.selectedDate = '{{ $today }}';
            this.fetchNota();
        }
    }"
    x-init="fetchNota()"
    class="space-y-3"
>
    {{-- Header with Date Navigation --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <x-moonshine::icon icon="document-text" size="4" class="text-purple" />
            <span class="text-[10px] font-black text-purple uppercase tracking-wider">Nota Preview</span>
        </div>
        
        <div class="flex items-center gap-2">
            {{-- Date Navigation --}}
            <div class="flex items-center gap-1 px-2 py-1 rounded border border-secondary/20 bg-secondary/5">
                <button 
                    @click="prevDay()" 
                    class="p-1 rounded hover:bg-secondary/10 transition-colors"
                >
                    <x-moonshine::icon icon="chevron-left" size="3" class="text-secondary" />
                </button>
                
                <input 
                    type="date" 
                    x-model="selectedDate"
                    @change="fetchNota()"
                    class="bg-transparent text-[10px] font-mono text-secondary border-none outline-none w-28 text-center"
                >
                
                <button 
                    @click="nextDay()" 
                    class="p-1 rounded hover:bg-secondary/10 transition-colors"
                >
                    <x-moonshine::icon icon="chevron-right" size="3" class="text-secondary" />
                </button>
                
                <button 
                    @click="goToday()"
                    class="px-2 py-0.5 text-[8px] font-bold rounded border border-primary/30 bg-primary/10 text-primary hover:bg-primary/20 transition-colors"
                >
                    HARI INI
                </button>
            </div>
            
            {{-- Print Button --}}
            <a 
                :href="`/admin/print-nota?date=${selectedDate}&filter_produsen={{ $produsenId }}`"
                target="_blank"
                class="px-2 py-1 text-[9px] font-bold rounded border border-primary/30 bg-primary/10 text-primary hover:bg-primary/20 transition-colors flex items-center gap-1"
            >
                <x-moonshine::icon icon="printer" size="2" class="inline" />
                Cetak
            </a>
        </div>
    </div>
    
    {{-- Loading State --}}
    <template x-if="loading">
        <div class="rounded-lg border border-secondary/10 bg-secondary/5 p-8 text-center">
            <div class="w-8 h-8 border-2 border-secondary/20 border-t-primary rounded-full animate-spin mx-auto mb-3"></div>
            <p class="text-sm text-secondary">Memuat nota...</p>
        </div>
    </template>
    
    {{-- Error State --}}
    <template x-if="error">
        <div class="rounded-lg border border-red/30 bg-red/10 p-4 text-center">
            <x-moonshine::icon icon="exclamation-triangle" size="6" class="mx-auto text-red mb-2" />
            <p class="text-sm text-red" x-text="error"></p>
            <button @click="fetchNota()" class="mt-2 px-3 py-1 text-xs font-bold rounded border border-red/30 bg-red/10 text-red hover:bg-red/20 transition-colors">
                Coba Lagi
            </button>
        </div>
    </template>
    
    {{-- Empty State --}}
    <template x-if="!loading && !error && notaData && !notaData.has_data">
        <div class="rounded-lg border border-secondary/10 bg-secondary/5 p-8 text-center">
            <x-moonshine::icon icon="calendar" size="8" class="mx-auto text-secondary/50 mb-3" />
            <p class="text-sm text-secondary/70">Belum ada penjualan pada tanggal ini</p>
            <p class="text-[10px] text-secondary/50 mt-2" x-text="new Date(selectedDate).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })"></p>
        </div>
    </template>
    
    {{-- Nota Cards --}}
    <template x-if="!loading && !error && notaData && notaData.has_data">
        <div>
            {{-- Produsen Header --}}
            <div class="mb-3 flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary/30 to-secondary/30 flex items-center justify-center">
                    <x-moonshine::icon icon="user" size="4" class="text-white" />
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white" x-text="notaData.produsen.nama"></h3>
                    <p class="text-[9px] text-secondary/60" x-text="`Tanggal: ${new Date(selectedDate).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}`"></p>
                </div>
            </div>
            
            {{-- Nota Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <template x-for="(nota, index) in notaData.notads" :key="index">
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-secondary/20">
                        {{-- Default MoonShine Header --}}
                        <div class="bg-secondary px-2 py-1.5 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded bg-white/20 flex items-center justify-center text-white font-black text-sm" x-text="nota.no_nota"></span>
                                <span class="text-white font-bold text-[10px] uppercase" x-text="nota.produk.nama"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-white/80 text-[9px] font-mono" x-text="new Date(nota.tanggal).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: '2-digit' })"></span>
                                <span class="block text-white font-bold text-[9px]" x-text="(nota.produsen.gender === 'female' ? 'B. ' : 'P. ') + nota.produsen.nama"></span>
                            </div>
                        </div>
                        
                        {{-- Product Table --}}
                        <table class="w-full border-collapse border border-black text-[8px]">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="border border-black px-0.5 text-center w-3">#</th>
                                    <th class="border border-black px-1 text-left">NAMA</th>
                                    <th class="border border-black px-0.5 text-center w-4">Ttp</th>
                                    <th class="border border-black px-0.5 text-center w-4">S.Jl</th>
                                    <th class="border border-black px-0.5 text-center w-4">Rtrn</th>
                                    <th class="border border-black px-0.5 text-center w-4">Lku</th>
                                    <th class="border border-black px-0.5 text-center w-9">BYAR</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, i) in nota.items" :key="i">
                                    <tr :class="item.is_r ? 'opacity-50' : ''">
                                        <td class="border border-black px-0.5 text-center" x-text="i + 1"></td>
                                        <td class="border border-black px-1" :class="item.is_r ? 'italic' : ''" style="max-width: 55px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" x-text="item.p_display_name"></td>
                                        <td class="border border-black px-0.5 text-center font-bold" x-text="item.titip || ''"></td>
                                        <td class="border border-black px-0.5 text-center font-bold" x-text="item.sisa_jual || ''"></td>
                                        <td class="border border-black px-0.5 text-center font-bold" x-text="item.ret || ''"></td>
                                        <td class="border border-black px-0.5 text-center font-bold" x-text="item.laku || ''"></td>
                                        <td class="border border-black px-0.5 text-center font-bold" x-text="item.f_bayar || ''"></td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr class="font-bold bg-gray-50">
                                    <td colspan="2" class="border border-black px-1 uppercase text-[8px]">Laku <span x-text="nota.avgLaku"></span>%</td>
                                    <td class="border border-black px-0.5 text-center" x-text="nota.sumTitip"></td>
                                    <td class="border border-black px-0.5 text-center" x-text="nota.sumSisaJual"></td>
                                    <td class="border border-black px-0.5 text-center" x-text="nota.sumReturn"></td>
                                    <td class="border border-black px-0.5 text-center" x-text="nota.sumLaku"></td>
                                    <td class="border border-black px-0.5 text-center" x-text="nota.sumBayar >= 1000000 ? (nota.sumBayar / 1000).toFixed(0) + 'K' : formatNumber(nota.sumBayar)"></td>
                                </tr>
                            </tfoot>
                        </table>
                        
                        {{-- Footer Note Number Only --}}
                        <div class="text-center text-[7px] text-gray-400 py-1 bg-gray-50 border-t border-gray-200 font-medium" x-text="`No. ${nota.no_nota}`"></div>
                    </div>
                </template>
            </div>
            
            {{-- Summary Footer - Di bawah semua nota --}}
            <template x-if="notaData.notads && notaData.notads.length > 0">
                <div class="mt-4 bg-secondary/10 rounded-xl p-4 border border-secondary/20">
                    <h4 class="text-[10px] font-black text-primary uppercase tracking-wider mb-3 flex items-center gap-2">
                        <x-moonshine::icon icon="calculator" size="4" class="text-primary" />
                        Ringkasan Keuangan
                    </h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-1 text-[8px]">
                        <template x-if="notaData.notads[0]">
                            <div class="bg-secondary/5 rounded p-1.5 border border-secondary/20 text-center">
                                <div class="text-secondary/70 text-[6px] uppercase tracking-wider mb-0.5">Bayar</div>
                                <div class="text-white font-bold" x-text="'Rp ' + formatNumber(notaData.notads[0].totalBayarProdusen)"></div>
                            </div>
                        </template>
                        <template x-if="notaData.notads[0]">
                            <div class="bg-secondary/5 rounded p-1.5 border border-secondary/20 text-center">
                                <div class="text-secondary/70 text-[6px] uppercase tracking-wider mb-0.5">Kas</div>
                                <div class="text-red font-bold" x-text="'- Rp ' + formatNumber(notaData.notads[0].transaksi?.kas || 0)"></div>
                            </div>
                        </template>
                        <template x-if="notaData.notads[0]">
                            <div class="bg-secondary/5 rounded p-1.5 border border-secondary/20 text-center">
                                <div class="text-secondary/70 text-[6px] uppercase tracking-wider mb-0.5">Kemarin</div>
                                <div class="font-bold" :class="(notaData.notads[0].transaksi?.kemarin || 0) >= 0 ? 'text-primary' : 'text-red'" x-text="(notaData.notads[0].transaksi?.kemarin || 0) >= 0 ? '+ Rp ' + formatNumber(notaData.notads[0].transaksi?.kemarin || 0) : '- Rp ' + formatNumber(Math.abs(notaData.notads[0].transaksi?.kemarin || 0))"></div>
                            </div>
                        </template>
                        <template x-if="notaData.notads[0]">
                            <div class="bg-secondary/5 rounded p-1.5 border border-secondary/20 text-center">
                                <div class="text-secondary/70 text-[6px] uppercase tracking-wider mb-0.5">Lain</div>
                                <div class="font-bold" :class="(notaData.notads[0].transaksi?.details?.reduce((a, b) => a + b.jumlah, 0) || 0) >= 0 ? 'text-primary' : 'text-red'" x-text="(notaData.notads[0].transaksi?.details?.reduce((a, b) => a + b.jumlah, 0) || 0) >= 0 ? '+ Rp ' + formatNumber(notaData.notads[0].transaksi?.details?.reduce((a, b) => a + b.jumlah, 0) || 0) : '- Rp ' + formatNumber(Math.abs(notaData.notads[0].transaksi?.details?.reduce((a, b) => a + b.jumlah, 0) || 0))"></div>
                            </div>
                        </template>
                        <template x-if="notaData.notads[0]">
                            <div class="bg-secondary/5 rounded p-1.5 border border-secondary/20 text-center">
                                <div class="text-secondary/70 text-[6px] uppercase tracking-wider mb-0.5">Tabungan</div>
                                <div class="text-red font-bold" x-text="'- Rp ' + formatNumber(notaData.notads[0].transaksi?.tabungan_sum || 0)"></div>
                            </div>
                        </template>
                        <template x-if="notaData.notads[0]">
                            <div class="bg-secondary/5 rounded p-1.5 border border-secondary/20 text-center">
                                <div class="text-secondary/70 text-[6px] uppercase tracking-wider mb-0.5">Bulatan</div>
                                <div class="font-bold" :class="(notaData.notads[0].transaksi?.pembulatan || 0) >= 0 ? 'text-primary' : 'text-red'" x-text="(notaData.notads[0].transaksi?.pembulatan || 0) >= 0 ? '+ Rp ' + formatNumber(notaData.notads[0].transaksi?.pembulatan || 0) : '- Rp ' + formatNumber(Math.abs(notaData.notads[0].transaksi?.pembulatan || 0))"></div>
                            </div>
                        </template>
                    </div>
                    
                    {{-- Payout Total --}}
                    <template x-if="notaData.notads[0]">
                        <div class="mt-3">
                            <div 
                                class="rounded text-center font-black text-white overflow-hidden"
                                :style="`background: linear-gradient(135deg, ${notaData.notads[0].transaksi?.status === 'Ok' ? '#22c55e' : '#f59e0b'}, ${notaData.notads[0].transaksi?.status === 'Ok' ? '#16a34a' : '#d97706'});`"
                            >
                                <div class="px-3 py-2">
                                    <span class="text-[8px] uppercase tracking-wider opacity-90">Uang Hari Ini</span>
                                    <div class="text-sm font-black">Rp. <span x-text="formatNumber(notaData.notads[0].transaksi?.jumlah || 0)"></span></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
            
            {{-- Pedagang Libur --}}
            <template x-if="notaData.libur_pedagangs && notaData.libur_pedagangs.length > 0">
                <div class="mt-3 text-[8px] text-red/60 px-2">
                    <span class="font-bold">Libur / Belum Kirim:</span>
                    <template x-for="ped in notaData.libur_pedagangs" :key="ped.id">
                        <span class="inline-block mr-1 mb-0.5 px-1 py-0.5 rounded bg-red/10" x-text="ped.display_name"></span>
                    </template>
                </div>
            </template>
        </div>
    </template>
</div>
