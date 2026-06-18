    <script>
        // [GLOBAL_HELPER] Expose isNalangi globally to avoid Alpine scope issues in x-for loops
        function isNalangiGlobal(p) {
            const saldo = parseFloat(p.current_saldo) || 0;
            if (saldo <= 0) return false;
            // Use shield from dashboardHub if available, otherwise inline calculation
            const modal = parseFloat(p.setoran_modal) || 0;
            return saldo < modal;
        }
        
        function dashboardHub() {
            return {
                currentDate: new URLSearchParams(window.location.search).get('d') || '{{ $displayDate }}',
                loading: false,
                actionLoading: null,
                loadingLain: false,
                flashSuccess: false,
                showLainHub: false,
                bulkMode: false,
                bulkData: '',
                lainForm: { owner: '', keterangan: '', jumlah: '' },
                searchQuery: '',
                state: 'draft',
                requireLock: true,
                metrics: { saldo: 0, required: 0, diff: 0, reconciliation: { status: 'Balanced', discrepancy: 0 } },
                tables: { pedagang: [], produsen: [], lain_lain: [] },
                belumKirim: [],
                openSearch: false,
                selectedIdx: -1,
                get filtered() {
                    const q = (this.searchQuery || '').toLowerCase();
                    if (!q) return [];
                    return (this.tables.produsen || []).filter(x => 
                        (x.nama || '').toLowerCase().includes(q) || 
                        (x.produk_names && x.produk_names.toLowerCase().includes(q))
                    );
                },

                init() {
                    window.addEventListener('dashboard-date-changed', (e) => {
                        this.currentDate = e.detail.date;
                        this.refresh();
                    });
                    this.refresh();
                },

                toggleLainLainHub() {
                    this.showLainHub = !this.showLainHub;
                    if (this.showLainHub) {
                        setTimeout(() => {
                            const el = document.getElementById('adjustment-hub');
                            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            if (this.$refs.searchInput) this.$refs.searchInput.focus();
                        }, 100);
                    }
                    this.lainForm = { owner: '', keterangan: '', jumlah: '' };
                    this.searchQuery = '';
                },

                formatK(val) {
                    val = parseFloat(val) || 0;
                    if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                    if (val >= 1000) return (val / 1000).toFixed(0) + 'K';
                    return val;
                },

                async refresh() {
                    this.loading = true;
                    try {
                        const baseUrl = window.location.origin;
                        const [metricsRes, tablesRes] = await Promise.all([
                            fetch(`${baseUrl}/admin/dashboard/metrics?date=${this.currentDate}`).then(r => r.json()),
                            fetch(`${baseUrl}/admin/dashboard/tables?date=${this.currentDate}`).then(r => r.json())
                        ]);

                        this.metrics = metricsRes;
                        this.tables = tablesRes;
                        this.tables.produsen = (this.tables.produsen || []).map(p => ({ ...p, _expanded: false }));
                        this.belumKirim = tablesRes.belum_kirim;

                        const statuses = new Set(this.tables.pedagang.map(p => p.status?.toLowerCase() || 'draft'));
                        if (statuses.has('ok')) this.state = 'ok';
                        else if (statuses.has('pending')) this.state = 'pending';
                        else this.state = 'draft';

                    } catch (e) {
                        console.error("Refresh Dashboard Failed", e);
                    } finally {
                        this.loading = false;
                    }
                },

                async handleAction(action) {
                    const confirmMsg = {
                        transact: "TRANSACT: Siapkan transaksi hari ini? Status akan berubah ke PENDING.",
                        pay: "PAY: Proses pembayaran akhir ke produsen? Status transaksi akan berubah ke OK.",
                        rollback: "ROLLBACK: Hati-hati! Membatalkan seluruh pembayaran hari ini?",
                        reset: "RESET DRAF: Kembalikan transaksi ke status Draf tanpa menghapus datanya?",
                        delete_all: "HAPUS TOTAL SEMUA DATA PENJUALAN HARI INI? Tindakan ini tidak bisa dibatalkan.",
                        delete_all_lain: "Hapus semua Nota Tambahan hari ini?",
                        unlock_all: "Buka semua kunci laporan pedagang?",
                        lock_all: "LOCK ALL: Paksa kunci semua laporan pedagang yang masih draf?",
                        toggle_public_access: "Ubah status akses publik untuk nota tanggal ini?"
                    };

                    if (!confirm(confirmMsg[action])) return;

                    this.actionLoading = action;
                    this.loading = true;
                    try {
                        const baseUrl = window.location.origin;
                        
                        // [SECURITY_FIX] Get CSRF token with fallback
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content 
                            || document.querySelector('meta[name="csrf"]')?.content 
                            || '';
                        
                        console.log('[DEBUG] handleAction:', action, 'Date:', this.currentDate, 'RequireLock:', this.requireLock);
                        
                        const res = await fetch(`${baseUrl}/admin/dashboard/action`, {
                            method: 'POST',
                            headers: { 
                                'Content-Type': 'application/json', 
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ action, date: this.currentDate, require_lock: this.requireLock })
                        });

                        // [ERROR_HANDLING_FIX] Check response status before parsing JSON
                        if (!res.ok) {
                            const errorText = await res.text();
                            console.error('[ERROR] Response not OK:', res.status, errorText);
                            throw new Error(`Server error: ${res.status} - ${errorText.substring(0, 200)}`);
                        }
                        
                        const data = await res.json();
                        
                        console.log('[DEBUG] Response:', data);

                        if (data.status === 'success') {
                            this.refresh();
                            this.$dispatch('toast', {type: 'success', text: data.message});
                        } else {
                            // [UX_IMPROVEMENT] Show specific error message from backend
                            this.$dispatch('toast', {type: 'error', text: data.message || "Gagal mengeksekusi aksi"});
                        }
                    } catch (e) {
                        // [DEBUG_IMPROVEMENT] Log error to console for debugging
                        console.error('[ERROR] handleAction failed:', e);
                        
                        // [UX_IMPROVEMENT] Show specific error message instead of generic
                        const errorMessage = e.message || "Gagal terhubung ke server";
                        this.$dispatch('toast', {type: 'error', text: errorMessage});
                    } finally {
                        this.loading = false;
                        this.actionLoading = null;
                    }
                },

                openLainLainModal() {
                    // Deprecated, integrated inline
                    this.openLainLainHub();
                },

                async submitLainLain() {
                    if (!this.lainForm.owner) {
                        if (window.MoonShine && MoonShine.ui) MoonShine.ui.toast("Pilih pedagang/produsen", 'error');
                        return;
                    }
                    const [owner_type, owner_id] = this.lainForm.owner.split(':');

                    this.loadingLain = true;
                    try {
                        const baseUrl = window.location.origin;
                        const res = await fetch(`${baseUrl}/admin/dashboard/lain-lain`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({
                                date: this.currentDate,
                                owner_type,
                                owner_id: parseInt(owner_id),
                                keterangan: this.lainForm.keterangan,
                                jumlah: parseInt(this.lainForm.jumlah)
                            })
                        }).then(r => r.json());

                        if (res.status === 'success') {
                            this.lainForm = { owner: '', keterangan: '', jumlah: '' };
                            this.searchQuery = '';
                            this.flashSuccess = true;
                            setTimeout(() => this.flashSuccess = false, 1000);
                            this.refresh();
                            this.$dispatch('toast', {type: 'success', text: res.message});
                            this.$nextTick(() => {
                                if (this.$refs.searchInput) this.$refs.searchInput.focus();
                            });
                        } else {
                            this.$dispatch('toast', {type: 'error', text: res.message || "Gagal menyimpan."});
                        }
                    } catch (e) {
                        this.$dispatch('toast', {type: 'error', text: "Error koneksi saat menyimpan Lain-lain."});
                    } finally {
                        this.loadingLain = false;
                    }
                },

                async submitBulkLainLain() {
                    if (!this.bulkData.trim()) return;
                    
                    if (!confirm("Proses input bulk sekarang? Pastikan format sudah benar.")) return;

                    this.loadingLain = true;
                    try {
                        const baseUrl = window.location.origin;
                        const res = await fetch(`${baseUrl}/admin/dashboard/bulk-lain-lain`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({
                                date: this.currentDate,
                                data: this.bulkData
                            })
                        }).then(r => r.json());

                        if (res.status === 'success') {
                            this.bulkData = '';
                            this.bulkMode = false;

                            // [MODERNIZATION] Dispatched event to close native MoonShine v4 modal
                            this.$dispatch('modal_toggled:bulk-lain-modal');
                            
                             if (res.details && res.details.failed > 0) {
                                let errMsg = `${res.message}\n\nDAFTAR KEGAGALAN:\n${res.details.errors.slice(0, 15).join('\n')}`;
                                if (res.details.errors.length > 15) errMsg += `\n...dan ${res.details.errors.length - 15} lainnya.`;
                                this.$dispatch('toast', {type: 'warning', text: errMsg});
                                
                                // Popup Khusus untuk Copy Failed Data
                                if (res.details.failed_data && res.details.failed_data.length > 0) {
                                    const failedText = res.details.failed_data.join('\n');
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            title: 'Sebagian Data Gagal',
                                            html: '<p class="text-sm mb-2">Beberapa baris ditolak karena produsen terkait belum memiliki transaksi (Pending/Draft) hari ini.</p><p class="text-sm font-bold text-rose-500 mb-4">Silakan COPY data di bawah ini untuk diinput kembali besok:</p><textarea id="swal-failed-data" class="w-full h-48 p-3 text-sm font-mono bg-gray-900 text-gray-200 border border-gray-700 rounded-lg focus:ring-purple-500 focus:border-purple-500" readonly></textarea>',
                                            icon: 'warning',
                                            confirmButtonColor: '#9333ea',
                                            confirmButtonText: 'Tutup & Paham',
                                            width: '600px',
                                            didOpen: () => {
                                                document.getElementById('swal-failed-data').value = failedText;
                                            }
                                        });
                                    } else {
                                        prompt('Sebagian gagal karena tidak ada transaksi hari ini.\n\nSilakan COPY data ini untuk besok:', failedText);
                                    }
                                }
                            } else {
                                this.$dispatch('toast', {type: 'success', text: res.message});
                            }
                            
                            this.refresh();
                        } else {
                            this.$dispatch('toast', {type: 'error', text: res.message || "Gagal bulk."});
                        }
                    } catch (e) {
                        this.$dispatch('toast', {type: 'error', text: "Error koneksi saat bulk."});
                    } finally {
                        this.loadingLain = false;
                    }
                },


                async deleteLainLain(id) {
                    console.log("[TACTICAL_LOG] deleteLainLain called with ID:", id);

                    // [HARDENING] Removing global this.state check as it erroneously blocks deletions when ANY merchant is OK.
                    // Safety is already handled by backend and the :disabled attribute on the button.

                    this.loadingLain = true;
                    try {
                        const baseUrl = window.location.origin;
                        const res = await fetch(`${baseUrl}/admin/dashboard/lain-lain/${id}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                        }).then(r => r.json());

                        if (res.status === 'success') {
                            this.refresh();
                            this.$dispatch('toast', {type: 'success', text: res.message});
                        } else {
                            this.$dispatch('toast', {type: 'error', text: res.message || "Gagal menghapus."});
                        }
                    } catch (e) {
                        this.$dispatch('toast', {type: 'error', text: "Error jaringan saat menghapus."});
                    } finally {
                        this.loadingLain = false;
                    }
                },

                async resetMerchant(id, nama) {
                    if (!confirm(`Hapus data penjualan (draf/pending) untuk ${nama}?`)) return;
                    try {
                        const baseUrl = window.location.origin;
                        const res = await fetch(`${baseUrl}/admin/dashboard/action`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ action: 'delete_merchant', date: this.currentDate, pedagang_id: id })
                        }).then(r => r.json());
                        if (res.status === 'success') {
                            this.refresh();
                            this.$dispatch('toast', {type: 'success', text: res.message || "Data berhasil dihapus."});
                        }
                    } catch (e) { 
                        this.$dispatch('toast', {type: 'error', text: "Hapus gagal"});
                    }
                },

                async predictTitip(id, nama) {
                    if (window.MoonShine && MoonShine.ui) {
                        MoonShine.ui.toast(`Sedang menganalisis histori penjualan ${nama}...`, 'info');
                    }
                    try {
                        const res = await fetch(`/admin/api/predict-titip/${id}`).then(r => r.json());
                        if (res.prediction) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: `AI Prediksi: ${nama}`,
                                    html: `<div class="text-left text-sm mt-3 leading-relaxed">${res.prediction}</div>`,
                                    icon: 'info',
                                    confirmButtonColor: '#9333ea',
                                    confirmButtonText: 'Tutup'
                                });
                            } else {
                                alert(`AI Prediksi ${nama}:\n${res.prediction.replace(/<[^>]*>?/gm, '')}`);
                            }
                        } else {
                            this.$dispatch('toast', {type: 'error', text: res.error || "Gagal memprediksi."});
                        }
                    } catch (e) {
                        this.$dispatch('toast', {type: 'error', text: "Gagal terhubung ke AI."});
                    }
                },

                async unlockMerchant(id, nama) {
                    if (!confirm(`Buka kunci laporan untuk ${nama}?`)) return;
                    try {
                        const baseUrl = window.location.origin;
                        const res = await fetch(`${baseUrl}/admin/dashboard/action`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ action: 'unlock', date: this.currentDate, pedagang_id: id })
                        }).then(r => r.json());
                        if (res.status === 'success') {
                            this.refresh();
                            this.$dispatch('toast', {type: 'success', text: res.message || "Laporan dibuka."});
                        }
                    } catch (e) { 
                        this.$dispatch('toast', {type: 'error', text: "Unlock gagal"});
                    }
                },

                async lockMerchant(id, nama) {
                    if (!confirm(`Paksa KUNCI laporan untuk ${nama}?\n\nPedagang tidak akan bisa mengedit draf ini lagi.`)) return;
                    try {
                        const baseUrl = window.location.origin;
                        const res = await fetch(`${baseUrl}/admin/dashboard/action`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ action: 'lock', date: this.currentDate, pedagang_id: id })
                        }).then(r => r.json());
                        if (res.status === 'success') {
                            this.refresh();
                            this.$dispatch('toast', {type: 'success', text: res.message || "Laporan dikunci."});
                        }
                    } catch (e) { 
                        this.$dispatch('toast', {type: 'error', text: "Lock gagal"});
                    }
                },

                hasLocked() {
                    return this.tables.pedagang.some(p => p.status?.toLowerCase() === 'locked');
                },

                formatCurrency(val) {
                    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(val);
                },

                getHeatmapColor(pct) {
                    pct = Math.max(0, Math.min(100, pct));
                    const hue = pct * 1.2;
                    return `hsl(${hue}, 70%, 45%)`;
                },

                calculateProducerKas(hbTotal) {
                    if (hbTotal < 50000) return 0;
                    const flatKas = 1500;
                    let receh = 0;
                    if (hbTotal > flatKas) {
                        receh = (hbTotal - flatKas) % 1000;
                    }
                    return flatKas + receh;
                },

                calculateProducerNet(p) {
                    const isOk = p.status?.toLowerCase() === 'ok';
                    if (isOk) return parseFloat(p.bayar_net) || 0;

                    const hbTotal = parseFloat(p.hb_total) || 0;

                    const kas = this.calculateProducerKas(hbTotal);
                    const currentAmount = hbTotal - kas;

                    let tabungan = parseFloat(p.pr_tabungan) || 0;
                    if ((currentAmount - tabungan) <= 0) {
                        tabungan = 0; // Mati jika omset uang akan habis (= 0) atau minus
                    }

                    return Math.max(0, currentAmount - tabungan);
                },

                // [ CITROROSO_FINANCIAL_SHIELD_v4 ]
                // Dynamically injected from SettingsService
                _getShield() {
                    const cfg = @json($settings);
                    return {
                        m: (modal, cp, n) => {
                            if (cp <= (cfg.proup_threshold_count || 30)) return 0;

                            // Dynamic check against Special Merchant List
                            const specials = (cfg.special_merchant_list || []).map(s => s.trim().toLowerCase());
                            const name = n.trim().toLowerCase();

                            if (specials.includes(name)) return 0;

                            const rate = parseFloat(cfg.proup_rate) || 0.015;
                            return Math.floor((modal * rate) / 1000) * 1000;
                        },
                        k: (m) => {
                            if (m <= 0) return 0;

                            // Dynamic range lookup from settings
                            const ranges = [...(cfg.kas_pedagang_ranges || [])].sort((a, b) => b.min - a.min);
                            for (const r of ranges) {
                                if (m >= r.min) return parseInt(r.fee);
                            }

                            return 0;
                        }
                    }
                },

                calculateMerchantKas(modal) {
                    return this._getShield().k(modal);
                },

                calculateMerchantProup(modal, productCount, name) {
                    return this._getShield().m(modal, productCount, name);
                },

                calculateMerchantSetoran(p) {
                    const modal = parseFloat(p.setoran_modal) || 0;
                    const shield = this._getShield();
                    const kas = shield.k(modal);
                    const tabungan = parseFloat(p.pr_tabungan) || 0;
                    const proup = shield.m(modal, p.produk_count, p.nama);

                    return modal + kas + tabungan + proup;
                },

                getPercentColor(pct) {
                    if (pct >= 90) return 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20';
                    if (pct >= 70) return 'text-amber-500 bg-amber-500/10 border-amber-500/20';
                    return 'text-rose-500 bg-rose-500/10 border-rose-500/20';
                },

                isNalangi(p) {
                    const saldo = parseFloat(p.current_saldo) || 0;
                    if (saldo <= 0) return false;
                    const tagihan = this.calculateMerchantSetoran(p);
                    return saldo < tagihan;
                },

                getStatusClasses(status) {
                    status = status?.toLowerCase() || 'draft';
                    if (status === 'ok') return 'bg-emerald-500/10 border-emerald-500/50 text-emerald-600 shadow-[0_0_10px_rgba(16,185,129,0.1)]';
                    if (status === 'pending') return 'bg-amber-500/10 border-amber-500/50 text-amber-600 shadow-[0_0_10px_rgba(245,158,11,0.1)]';
                    if (status === 'locked') return 'bg-amber-500/10 border-amber-500/50 text-amber-600 font-black';
                    return 'bg-slate-100 border-slate-200 opacity-40';
                },

                getMerchantTotals() {
                    let totals = { modal: 0, kas: 0, tabungan: 0, setoran: 0, laku: 0, titip: 0 };
                    const shield = this._getShield();
                    this.tables.pedagang.forEach(p => {
                        const modal = parseFloat(p.setoran_modal) || 0;
                        const proup = shield.m(modal, p.produk_count, p.nama);
                        const kasTiered = shield.k(modal);
                        const setoran = this.calculateMerchantSetoran(p);

                        // Modal total must include adjustment regardless of status to match visual manifest
                        totals.modal += (modal + proup);
                        totals.laku += parseFloat(p.laku) || 0;
                        totals.titip += parseFloat(p.titip) || 0;
                        totals.kas += kasTiered;
                        totals.tabungan += parseFloat(p.pr_tabungan) || 0;
                        totals.setoran += setoran;
                    });
                    return totals;
                },

                getProducerTotals() {
                    let totals = { omset: 0, hb_total: 0, kas: 0, tabungan: 0, net: 0 };
                    this.tables.produsen.forEach(p => {
                        p.hb_total = parseFloat(p.hb_total) || 0;
                        const omset = parseFloat(p.omset) || 0;
                        const hbTotal = p.hb_total;
                        totals.omset += omset;
                        totals.hb_total += hbTotal;

                        const isOk = p.status?.toLowerCase() === 'ok';
                        const kas = isOk ? (parseFloat(p.kas) || 0) : this.calculateProducerKas(hbTotal);

                        let tabungan = parseFloat(p.pr_tabungan) || 0;
                        if (!isOk) {
                            if ((hbTotal - kas - tabungan) <= 0) {
                                tabungan = 0;
                            }
                        } else {
                            tabungan = parseFloat(p.tabungan) || 0;
                            tabungan = parseFloat(p.tabungan) || parseFloat(p.tabungan_fix) || 0;
                        }

                        totals.kas += kas;
                        totals.tabungan += tabungan;
                        totals.net += this.calculateProducerNet(p);
                    });
                    return totals;
                }
            }
        }
    </script>

    <template x-teleport="body">
        <div x-data="aiChatBot()" class="fixed bottom-6 right-6 z-[9999]">
            <!-- Floating Button -->
            <button @click="open = !open" 
                class="bg-purple-600 hover:bg-purple-500 text-white rounded-full w-14 h-14 flex items-center justify-center shadow-[0_0_15px_rgba(147,51,234,0.5)] transition-all transform hover:scale-110">
                <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <svg x-show="open" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Chat Panel -->
            <div x-show="open" 
                 @click.away="open = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                 class="absolute bottom-16 right-0 w-80 sm:w-96 bg-[#1e1e1e] border border-gray-700/50 rounded-xl shadow-2xl overflow-hidden flex flex-col"
                 style="height: 500px; display: none;">
                
                <div class="bg-purple-600/20 border-b border-purple-500/20 p-4">
                    <h3 class="font-bold text-purple-400 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
                        </svg>
                        Tanya AI (Gemini)
                    </h3>
                </div>
                
                <div class="flex-1 p-4 overflow-y-auto space-y-4" id="ai-chat-box">
                    <template x-for="(msg, i) in messages" :key="i">
                        <div :class="msg.role === 'user' ? 'text-right' : 'text-left'">
                            <div class="inline-block p-3 rounded-xl max-w-[85%] text-sm"
                                 :class="msg.role === 'user' ? 'bg-purple-600 text-white rounded-br-none' : 'bg-gray-800 text-gray-200 rounded-bl-none'">
                                <span x-html="msg.text"></span>
                            </div>
                        </div>
                    </template>
                    
                    <div x-show="loading" class="text-left">
                        <div class="inline-block p-3 rounded-xl bg-gray-800 text-gray-400 rounded-bl-none text-sm animate-pulse">
                            <div class="flex gap-1">
                                <span class="w-2 h-2 bg-gray-500 rounded-full"></span>
                                <span class="w-2 h-2 bg-gray-500 rounded-full"></span>
                                <span class="w-2 h-2 bg-gray-500 rounded-full"></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="p-3 border-t border-gray-700/50 bg-[#161616]">
                    <form @submit.prevent="askAi" class="flex gap-2">
                        <input type="text" x-model="question" placeholder="Tanya performa hari ini..." 
                            class="flex-1 bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:ring-1 focus:ring-purple-500 outline-none"
                            :disabled="loading">
                        <button type="submit" :disabled="loading || !question.trim()"
                            class="bg-purple-600 hover:bg-purple-500 disabled:opacity-50 text-white rounded-lg px-3 py-2 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </template>
    <script>
        function aiChatBot() {
            return {
                open: false,
                loading: false,
                question: '',
                messages: [
                    { role: 'ai', text: 'Halo! Saya asisten AI Anda. Ingin menganalisis laporan penjualan pedagang hari ini?' }
                ],
                async askAi() {
                    if (!this.question.trim() || this.loading) return;
                    
                    const q = this.question;
                    this.messages.push({ role: 'user', text: q });
                    this.question = '';
                    this.loading = true;
                    
                    this.$nextTick(() => this.scrollToBottom());

                    try {
                        const params = new URLSearchParams(window.location.search);
                        const date = params.get('d') || params.get('tanggal') || new Date().toISOString().slice(0,10);
                        
                        const token = document.querySelector('meta[name="csrf-token"]');
                        const res = await fetch('/admin/api/ask-ai', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token ? token.content : ''
                            },
                            body: JSON.stringify({ question: q, date: date })
                        }).then(r => r.json());

                        this.messages.push({ role: 'ai', text: res.answer || 'Gagal merespons.' });
                    } catch (e) {
                        this.messages.push({ role: 'ai', text: 'Koneksi ke AI terputus.' });
                    } finally {
                        this.loading = false;
                        this.$nextTick(() => this.scrollToBottom());
                    }
                },
                scrollToBottom() {
                    const box = document.getElementById('ai-chat-box');
                    if (box) box.scrollTop = box.scrollHeight;
                }
            }
        }
    </script>
