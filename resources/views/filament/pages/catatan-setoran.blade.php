<div class="fi-page">
    <div class="fi-header">
        <h1 class="fi-title">Catatan Setoran</h1>
        <p class="fi-subtitle">Bulan: {{ $selectedMonth }}</p>
    </div>

    <div class="fi-filters mb-6">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                <input type="month" wire:model.live="selectedMonth" class="fi-input rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pedagang</label>
                <select wire:model.live="selectedPedagang" class="fi-input rounded-md border-gray-300 shadow-sm">
                    <option value="">Semua Pedagang</option>
                    @foreach(\App\Models\Pedagang::all() as $pedagang)
                        <option value="{{ $pedagang->id }}">{{ $pedagang->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <table class="fi-table w-full">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-2 text-left">Tanggal</th>
                <th class="px-4 py-2 text-left">Pedagang</th>
                <th class="px-4 py-2 text-right">Modal</th>
                <th class="px-4 py-2 text-right">KAS</th>
                <th class="px-4 py-2 text-right">Tabungan</th>
                <th class="px-4 py-2 text-right">Setoran</th>
                <th class="px-4 py-2 text-right">Laku</th>
                <th class="px-4 py-2 text-right">Omset</th>
            </tr>
        </thead>
        <tbody>
            @foreach($setoranList as $setoran)
            <tr class="border-b">
                <td class="px-4 py-2">{{ $setoran['tanggal'] }}</td>
                <td class="px-4 py-2">{{ $setoran['pedagang'] }}</td>
                <td class="px-4 py-2 text-right">{{ alignUang($setoran['modal']) }}</td>
                <td class="px-4 py-2 text-right">{{ alignUang($setoran['kas']) }}</td>
                <td class="px-4 py-2 text-right">{{ alignUang($setoran['tabungan']) }}</td>
                <td class="px-4 py-2 text-right font-bold">{{ alignUang($setoran['setoran']) }}</td>
                <td class="px-4 py-2 text-right">{{ $setoran['laku'] }}</td>
                <td class="px-4 py-2 text-right">{{ alignUang($setoran['omset']) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-emerald-100 font-bold">
            <tr>
                <td colspan="2" class="px-4 py-2 text-right">TOTAL:</td>
                <td class="px-4 py-2 text-right">{{ alignUang($totals['modal']) }}</td>
                <td class="px-4 py-2 text-right">{{ alignUang($totals['kas']) }}</td>
                <td class="px-4 py-2 text-right">{{ alignUang($totals['tabungan']) }}</td>
                <td class="px-4 py-2 text-right">{{ alignUang($totals['setoran']) }}</td>
                <td class="px-4 py-2 text-right">-</td>
                <td class="px-4 py-2 text-right">{{ alignUang($totals['omset']) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
