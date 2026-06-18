@php
    $formatUang = function($nominal) use ($formatK) {
        if ($nominal == 0) return 0;
        if ($formatK == '1') {
            $val = $nominal / 1000;
            return (floor($val) == $val ? number_format($val, 0, '', '') : number_format($val, 1, ',', '')) . 'K';
        }
        return number_format($nominal, 0, '', ''); // Raw number for excel if not K formatted
    };
@endphp
<table>
    <tr>
        <td colspan="{{ 2 + count($columnHeaders) + ($mode === 'per_tanggal' ? 2 : 1) }}">
            <b>LAPORAN TABUNGAN CITROROSO</b>
        </td>
    </tr>
    <tr>
        <td colspan="{{ 2 + count($columnHeaders) + ($mode === 'per_tanggal' ? 2 : 1) }}">
            <b>TABUNGAN PRODUSEN</b>
        </td>
    </tr>
    <tr>
        <th><b>No</b></th>
        <th><b>Nama</b></th>
        @foreach ($columnHeaders as $key => $label)
            <th><b>{{ $label }}</b></th>
        @endforeach
        @if ($mode === 'per_tanggal')
            <th><b>T.Bln Ini</b></th>
        @endif
        <th><b>T.Periode</b></th>
    </tr>
    @php $no = 1; @endphp
    @foreach ($produsenData['owners'] as $id => $name)
        @if (isset($produsenData['ownerTotals'][$id]))
            <tr>
                <td>{{ $no++ }}</td>
                <td>{{ $name }}</td>
                @foreach ($columnHeaders as $key => $label)
                    <td>{{ $formatUang($produsenData['grid'][$id][$key] ?? 0) }}</td>
                @endforeach
                @if ($mode === 'per_tanggal')
                    <td>{{ $formatUang($produsenData['ownerMonthTotals'][$id] ?? 0) }}</td>
                @endif
                <td>{{ $formatUang($produsenData['ownerTotals'][$id] ?? 0) }}</td>
            </tr>
        @endif
    @endforeach
    @if ($produsenData['grandTotal'] > 0)
        <tr>
            <td colspan="2"><b>TOTAL</b></td>
            @foreach ($columnHeaders as $key => $label)
                <td><b>{{ $formatUang($produsenData['colTotals'][$key] ?? 0) }}</b></td>
            @endforeach
            @if ($mode === 'per_tanggal')
                <td><b>{{ $formatUang($produsenData['grandMonthTotal'] ?? 0) }}</b></td>
            @endif
            <td><b>{{ $formatUang($produsenData['grandTotal'] ?? 0) }}</b></td>
        </tr>
    @endif
    <tr>
        <td colspan="{{ 2 + count($columnHeaders) + ($mode === 'per_tanggal' ? 2 : 1) }}">
            <b>TABUNGAN PEDAGANG</b>
        </td>
    </tr>
    <tr>
        <th><b>No</b></th>
        <th><b>Nama</b></th>
        @foreach ($columnHeaders as $key => $label)
            <th><b>{{ $label }}</b></th>
        @endforeach
        @if ($mode === 'per_tanggal')
            <th><b>T.Bln Ini</b></th>
        @endif
        <th><b>T.Periode</b></th>
    </tr>
    @php $no = 1; @endphp
    @foreach ($pedagangData['owners'] as $id => $name)
        @if (isset($pedagangData['ownerTotals'][$id]))
            <tr>
                <td>{{ $no++ }}</td>
                <td>{{ $name }}</td>
                @foreach ($columnHeaders as $key => $label)
                    <td>{{ $formatUang($pedagangData['grid'][$id][$key] ?? 0) }}</td>
                @endforeach
                @if ($mode === 'per_tanggal')
                    <td>{{ $formatUang($pedagangData['ownerMonthTotals'][$id] ?? 0) }}</td>
                @endif
                <td>{{ $formatUang($pedagangData['ownerTotals'][$id] ?? 0) }}</td>
            </tr>
        @endif
    @endforeach
    @if ($pedagangData['grandTotal'] > 0)
        <tr>
            <td colspan="2"><b>TOTAL</b></td>
            @foreach ($columnHeaders as $key => $label)
                <td><b>{{ $formatUang($pedagangData['colTotals'][$key] ?? 0) }}</b></td>
            @endforeach
            @if ($mode === 'per_tanggal')
                <td><b>{{ $formatUang($pedagangData['grandMonthTotal'] ?? 0) }}</b></td>
            @endif
            <td><b>{{ $formatUang($pedagangData['grandTotal'] ?? 0) }}</b></td>
        </tr>
    @endif
</table>
