<div class="fi-page">
    <div class="fi-header">
        <h1 class="fi-title">Laporan Penjualan Produsen</h1>
        <p class="fi-subtitle">Tanggal: {{ $selectedDate }}</p>
    </div>

    <div class="fi-filters mb-6">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                <input type="date" wire:model.live="selectedDate" class="fi-input rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Produsen</label>
                <select wire:model.live="selectedProdusen" class="fi-input rounded-md border-gray-300 shadow-sm">
                    <option value="">Semua Produsen</option>
                    @foreach(\App\Models\Produsen::all() as $produsen)
                        <option value="{{ $produsen->id }}">{{ $produsen->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <table class="fi-table w-full">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-2 text-left">Produsen</th>
                <th class="px-4 py-2 text-left">Produk</th>
                <th class="px-4 py-2 text-left">Pedagang</th>
                <th class="px-4 py-2 text-right">Titip</th>
                <th class="px-4 py-2 text-right">Laku</th>
                <th class="px-4 py-2 text-right">Modal</th>
                <th class="px-4 py-2 text-right">Omset</th>
                <th class="px-4 py-2 text-right">Laba</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData as $row)
            <tr class="border-b">
                <td class="px-4 py-2">{{ $row['produsen'] }}</td>
                <td class="px-4 py-2">{{ $row['produk'] }}</td>
                <td class="px-4 py-2">{{ $row['pedagang'] }}</td>
                <td class="px-4 py-2 text-right">{{ $row['titip'] }}</td>
                <td class="px-4 py-2 text-right">{{ $row['laku'] }}</td>
                <td class="px-4 py-2 text-right">{{ alignUang($row['modal']) }}</td>
                <td class="px-4 py-2 text-right">{{ alignUang($row['omset']) }}</td>
                <td class="px-4 py-2 text-right">{{ alignUang($row['laba']) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-blue-100 font-bold">
            <tr>
                <td colspan="3" class="px-4 py-2 text-right">TOTAL:</td>
                <td class="px-4 py-2 text-right">{{ $totals['titip'] }}</td>
                <td class="px-4 py-2 text-right">{{ $totals['laku'] }}</td>
                <td class="px-4 py-2 text-right">{{ alignUang($totals['modal']) }}</td>
                <td class="px-4 py-2 text-right">{{ alignUang($totals['omset']) }}</td>
                <td class="px-4 py-2 text-right">{{ alignUang($totals['laba']) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
