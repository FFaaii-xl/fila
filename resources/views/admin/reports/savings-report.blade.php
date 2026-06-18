@include('admin.reports.report-style')

@php
    $formatUang = function($nominal) use ($formatK) {
        if ($nominal == 0) return 0;
        if ($formatK == '1') {
            $val = $nominal / 1000;
            return (floor($val) == $val ? number_format($val, 0, '', '') : number_format($val, 1, ',', '')) . 'K';
        }
        return number_format($nominal, 0, ',', '.');
    };
@endphp

<div class="space-y-6">
    <x-hhr-toolbar>
        <x-slot:filters>
            <div class="hhr-group">
                <span class="hhr-label-ghost">Mode</span>
                <select name="mode" class="form-select" onchange="this.form.submit()" style="width: auto;">
                    <option value="per_bulan" {{ $mode == 'per_bulan' ? 'selected' : '' }}>Per Bulan</option>
                    <option value="per_tanggal" {{ $mode == 'per_tanggal' ? 'selected' : '' }}>Per Tanggal</option>
                    <option value="range" {{ $mode == 'range' ? 'selected' : '' }}>Total Periode</option>
                </select>
            </div>
            
            <div class="hhr-group">
                <span class="hhr-label-ghost">Owner</span>
                <select name="owner_type" class="form-select" onchange="this.form.submit()" style="width: auto;" {{ ($isAdminOrPengurus) ? '' : 'disabled' }}>
                    <option value="Pedagang" {{ $ownerType == 'Pedagang' ? 'selected' : '' }}>Pedagang</option>
                    <option value="Produsen" {{ $ownerType == 'Produsen' ? 'selected' : '' }}>Produsen</option>
                </select>
            </div>

            <div class="hhr-group">
                <span class="hhr-label-ghost">Periode</span>
                <select name="period_idx" class="form-select max-w-[200px]" onchange="this.form.submit()">
                    @foreach($periods as $idx => $p)
                        <option value="{{ $idx }}" {{ $periodIdx === $idx ? 'selected' : '' }}>
                            {{ $p['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            @if($mode === 'per_tanggal')
                <div class="hhr-group">
                    <span class="hhr-label-ghost">Bulan</span>
                    <select name="month" class="form-select" onchange="this.form.submit()" style="width: auto;">
                        @foreach($availableMonths as $val => $label)
                            <option value="{{ $val }}" {{ $selectedMonth == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </x-slot:filters>
        
        <x-slot:search>
            <div class="relative">
                <input type="text" id="searchInput" placeholder="Cari nama owner..." class="form-input w-full pl-8" style="padding-left: 1.8rem !important;">
                <div class="absolute left-2.5 top-1/2 -translate-y-1/2 opacity-30">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                </div>
            </div>
        </x-slot:search>

        <x-slot:actions>
            <a href="{{ request()->fullUrlWithQuery(['format_k' => $formatK == '1' ? '0' : '1']) }}" class="hhr-btn {{ $formatK == '1' ? 'opacity-100' : 'opacity-60' }}" title="Toggle Format K">
                <b>F.K</b>
            </a>

            <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}" class="hhr-btn hhr-btn-excel" title="Export Excel">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3 -3v-1m-4-4-4 4m0 0-4-4m4 4V4"/></svg>
                <span class="hidden md:inline">Excel</span>
            </a>

            @if($isAdminOrPengurus)
            <x-moonshine::modal title="Eksekusi Finalize Tabungan" class="modal-finalize">
                <div x-data="finalizeEngine()" class="finalize-container space-y-4">
                    {{-- Step 1: Confirmation Text --}}
                    <div x-show="step === 1">
                        <div class="flex items-center gap-3 mb-4 p-4 bg-rose-500/10 border border-rose-500/20 rounded-xl">
                            <div class="text-rose-500">
                                <x-moonshine::icon icon="exclamation-triangle" size="6" />
                            </div>
                            <div class="text-xs opacity-70">Aksi ini bersifat permanen. Seluruh tabungan periode terpilih akan dikunci.</div>
                        </div>

                        <label class="block mb-2 text-xs font-black uppercase tracking-widest opacity-40 px-1">
                            Ketik <code class="bg-rose-500 text-white px-1.5 rounded">finalize</code> untuk konfirmasi:
                        </label>
                        <input type="text" x-model="confirmText" placeholder="..."
                            class="w-full form-input font-mono"
                            @input="confirmText = confirmText.toLowerCase()">

                        <div class="flex gap-2 mt-6 justify-end">
                            <button @click.prevent="toggleModal" class="btn btn-secondary">Batal</button>
                            <button @click.prevent="step = 2; loadPreview()" 
                                :disabled="confirmText !== 'finalize'"
                                class="btn btn-primary"
                                :class="confirmText === 'finalize' ? 'bg-rose-600 border-rose-700' : 'opacity-20 cursor-not-allowed'">
                                Lanjutkan →
                            </button>
                        </div>
                    </div>

                    {{-- Step 2: Date Range & Preview --}}
                    <div x-show="step === 2" x-cloak class="p-4 bg-white/5 border border-white/10 rounded-xl space-y-4">
                        <div class="text-xs opacity-70 mb-2">Pilih rentang tanggal untuk dikonversi menjadi saldo awal (Ledger).</div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] uppercase tracking-wider mb-1 opacity-50">Tgl Mulai</label>
                                <input type="date" x-model="startDate" class="form-input w-full text-xs" @change="resetPreview()">
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase tracking-wider mb-1 opacity-50">Tgl Akhir</label>
                                <input type="date" x-model="endDate" class="form-input w-full text-xs" @change="resetPreview()">
                            </div>
                        </div>

                        <button type="button" @click="loadPreview()" class="w-full btn btn-secondary btn-sm mb-4">
                            🔄 Refresh Preview
                        </button>

                        {{-- Preview Table --}}
                        <div class="max-h-[250px] overflow-y-auto border border-white/10 rounded-xl mb-4 bg-black/20">
                            <div x-show="loading" class="p-8 text-center opacity-40 text-xs">Menghitung data...</div>
                            <div x-show="!loading && previewData.length === 0" class="p-8 text-center opacity-40 text-xs italic">Data tidak ditemukan untuk periode ini.</div>
                            <table x-show="!loading && previewData.length > 0" class="w-full text-left text-[11px]">
                                <thead class="bg-white/5 sticky top-0">
                                    <tr>
                                        <th class="px-3 py-2 opacity-40 uppercase">Nama</th>
                                        <th class="px-3 py-2 text-right opacity-40 uppercase">Cairkan</th>
                                        <th class="px-3 py-2 text-right opacity-40 uppercase">Sisa</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    <template x-for="(item, idx) in previewData" :key="idx">
                                        <tr>
                                            <td class="px-3 py-1.5 font-bold" x-text="item.nama"></td>
                                            <td class="px-3 py-1.5 text-right font-mono text-rose-500" x-text="'-' + formatRp(item.sum)"></td>
                                            <td class="px-3 py-1.5 text-right font-mono text-emerald-500" x-text="formatRp(item.akhir)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div x-show="previewCount > 0" class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl mb-4 text-[10px] flex items-center gap-2">
                            <span>⚠️</span>
                            <span class="opacity-80"><strong x-text="previewCount"></strong> owner terdeteksi. Konfirmasi untuk eksekusi ledger.</span>
                        </div>

                        <div class="flex gap-2 justify-end">
                            <button type="button" @click="step = 1" class="btn btn-secondary">← Kembali</button>
                            <form method="POST" action="{{ route('admin.tabungan.finalize') }}">
                                @csrf
                                <input type="hidden" name="startDate" :value="startDate">
                                <input type="hidden" name="endDate" :value="endDate">
                                <button type="submit" :disabled="previewCount === 0"
                                    class="btn btn-primary"
                                    :class="previewCount > 0 ? 'bg-rose-600 border-rose-700' : 'opacity-20 cursor-not-allowed'">
                                    EKSEKUSI FINALIZE
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <x-slot:outerHtml>
                    <button type="button" @click.prevent="toggleModal" class="hhr-btn" style="background: #dc2626; color: #fff; font-weight: 700; border: 2px solid #991b1b;">
                        <x-moonshine::icon icon="exclamation-circle" size="4" />
                        <span class="hidden md:inline">FINALIZE</span>
                    </button>
                </x-slot:outerHtml>
            </x-moonshine::modal>
            @endif
        </x-slot:actions>
    </x-hhr-toolbar>

    <div class="box">
        <div class="table-responsive">
            <table class="table table-list" id="reportTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama</th>
                        @foreach($columnHeaders as $key => $label)
                            <th class="text-right">{{ $label }}</th>
                        @endforeach
                        @if($mode === 'per_tanggal')
                            <th class="text-right">Total<br><span style="font-size:9px; opacity:0.6;">Bulan Ini</span></th>
                        @endif
                        <th class="text-right">Total<br><span style="font-size:9px; opacity:0.6;">Periode</span></th>
                    </tr>
                </thead>
                <tbody>
                    @php $no = 1; @endphp
                    @forelse($owners as $id => $name)
                        @if(isset($ownerTotals[$id]))
                            <tr class="owner-row">
                                <td style="opacity:0.5;">{{ $no++ }}</td>
                                <td class="font-bold owner-name">
                                    <span class="clickable-name" data-type="{{ strtolower($ownerType) }}" data-id="{{ $id }}">{{ $name }}</span>
                                </td>
                                @foreach($columnHeaders as $key => $label)
                                    <td class="text-right font-mono" style="opacity: {{ isset($grid[$id][$key]) ? '1' : '0.2' }}">
                                        {{ isset($grid[$id][$key]) ? $formatUang($grid[$id][$key]) : '0' }}
                                    </td>
                                @endforeach
                                @if($mode === 'per_tanggal')
                                    <td class="text-right font-mono font-bold" style="color: var(--primary);">
                                        {{ $formatUang($ownerMonthTotals[$id] ?? 0) }}
                                    </td>
                                @endif
                                <td class="text-right font-mono font-black" style="color: var(--success);">
                                    {{ $formatUang($ownerTotals[$id] ?? 0) }}
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="100" class="text-center py-8 opacity-50 italic">
                                Tidak ada data tabungan untuk periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($grandTotal > 0)
                <tfoot class="bg-black/20">
                    <tr class="font-black">
                        <td colspan="2" class="text-center uppercase tracking-widest text-xs">GRAND TOTAL</td>
                        @foreach($columnHeaders as $key => $label)
                            <td class="text-right font-mono text-white/90">
                                {{ $formatUang($colTotals[$key] ?? 0) }}
                            </td>
                        @endforeach
                        @if($mode === 'per_tanggal')
                            <td class="text-right font-mono text-white" style="color: var(--primary);">
                                {{ $formatUang($grandMonthTotal) }}
                            </td>
                        @endif
                        <td class="text-right font-mono text-white" style="color: var(--success);">
                            {{ $formatUang($grandTotal) }}
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<script>
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#reportTable tbody tr.owner-row');
        
        rows.forEach(row => {
            let nameCell = row.querySelector('.owner-name');
            if (nameCell) {
                let name = nameCell.textContent || nameCell.innerText;
                row.style.display = name.toLowerCase().indexOf(filter) > -1 ? "" : "none";
            }
        });
    });

    function finalizeEngine() {
        return {
            step: 1,
            confirmText: '',
            startDate: '{{ explode(' ', $periods[$periodIdx]['start'] ?? '')[0] }}',
            endDate: '{{ explode(' ', $periods[$periodIdx]['end'] ?? '')[0] }}',
            previewData: [],
            previewCount: 0,
            loading: false,

            resetPreview() {
                this.previewData = [];
                this.previewCount = 0;
            },

            loadPreview() {
                this.loading = true;
                this.previewData = [];
                
                fetch('{{ route('admin.tabungan.preview-finalize') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        startDate: this.startDate,
                        endDate: this.endDate
                    })
                })
                .then(res => res.json())
                .then(data => {
                    this.previewData = data.preview;
                    this.previewCount = data.count;
                    this.loading = false;
                })
                .catch(err => {
                    alert('Gagal memuat data preview.');
                    this.loading = false;
                });
            },

            formatRp(angka) {
                return new Intl.NumberFormat('id-ID').format(angka);
            }
        }
    }
</script>