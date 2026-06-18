<div class="fi-page">
    <div class="fi-header">
        <h1 class="fi-title">Mutasi Harian</h1>
        <p class="fi-subtitle">Tanggal: {{ $selectedDate }}</p>
    </div>

    <div class="fi-filters mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
            <input type="date" wire:model.live="selectedDate" class="fi-input rounded-md border-gray-300 shadow-sm">
        </div>
    </div>

    <table class="fi-table w-full">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-2 text-left">Waktu</th>
                <th class="px-4 py-2 text-left">Pemilik</th>
                <th class="px-4 py-2 text-left">Akun</th>
                <th class="px-4 py-2 text-left">Keterangan</th>
                <th class="px-4 py-2 text-right">Debit</th>
                <th class="px-4 py-2 text-right">Kredit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaksiList as $trx)
            <tr class="border-b">
                <td class="px-4 py-2">{{ $trx['tanggal'] }}</td>
                <td class="px-4 py-2">{{ $trx['owner_type'] }}</td>
                <td class="px-4 py-2">{{ $trx['account'] ?? '-' }}</td>
                <td class="px-4 py-2">{{ $trx['keterangan'] }}</td>
                <td class="px-4 py-2 text-right">{{ $trx['debit'] > 0 ? alignUang($trx['debit']) : '-' }}</td>
                <td class="px-4 py-2 text-right">{{ $trx['kredit'] > 0 ? alignUang($trx['kredit']) : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-gray-200 font-bold">
            <tr>
                <td colspan="4" class="px-4 py-2 text-right">TOTAL:</td>
                <td class="px-4 py-2 text-right">{{ alignUang($totalDebit) }}</td>
                <td class="px-4 py-2 text-right">{{ alignUang($totalKredit) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
