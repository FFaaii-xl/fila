<?php

namespace App\Filament\Pages;

use App\Traits\Filament\FinancialPeriodDetection;
use App\Traits\Filament\HasRoleAuthorization;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TabunganAdminPage extends Page
{
    use HasRoleAuthorization;
    use FinancialPeriodDetection;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';
    protected static string | \UnitEnum | null $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 12;
    protected static ?string $title = 'Laporan Tabungan';

    protected string $view = 'filament.pages.tabungan-admin-page';

    public static function canAccess(): bool
    {
        return (new static)->isAdminOrPengurus();
    }

    protected function getViewData(): array
    {
        $user = Auth::user();
        $mode = request('mode', 'per_bulan');
        $ownerType = request('owner_type', 'Pedagang');
        $periodIdx = request('period_idx', null);

        // Security: Lock ownerType and ownerId for Pedagang/Produsen
        if ($user && in_array($user->owner_type, ['Pedagang', 'Produsen'], true)) {
            $ownerType = $user->owner_type;
        }

        // 1. Build Periods & Tentukan Periode Aktif
        $periods = $this->buildFinancialPeriods();
        if ($periodIdx === null) {
            $periodIdx = count($periods) - 1;
        }
        $activePeriod = $periods[$periodIdx] ?? end($periods);

        $start = $activePeriod['start'];
        $end = $activePeriod['end'];

        // 2. Logika Pembuatan Kolom Tabel
        $columnHeaders = [];
        $availableMonths = [];
        $selectedMonth = null;

        if ($mode === 'per_tanggal') {
            $mPeriod = CarbonPeriod::create(Carbon::parse($start)->startOfMonth(), '1 month', Carbon::parse($end)->startOfMonth());
            foreach ($mPeriod as $dt) {
                $availableMonths[$dt->format('Y-m')] = $dt->format('F Y');
            }

            $selectedMonth = request('month', date('Y-m', strtotime($end)));
            if (! array_key_exists($selectedMonth, $availableMonths)) {
                $selectedMonth = array_key_last($availableMonths);
            }

            $cStart = Carbon::parse($selectedMonth)->startOfMonth();
            $cEnd = Carbon::parse($selectedMonth)->endOfMonth();

            if ($cStart->lt(Carbon::parse($start))) {
                $cStart = Carbon::parse($start);
            }
            if ($cEnd->gt(Carbon::parse($end))) {
                $cEnd = Carbon::parse($end);
            }

            if ($cStart->lte($cEnd)) {
                $period = CarbonPeriod::create($cStart, $cEnd);
                foreach ($period as $date) {
                    $columnHeaders[$date->format('Y-m-d')] = $date->format('d');
                }
            }
        } elseif ($mode === 'per_bulan') {
            $period = CarbonPeriod::create(Carbon::parse($start)->startOfMonth(), '1 month', Carbon::parse($end)->startOfMonth());
            foreach ($period as $date) {
                $columnHeaders[$date->format('Y-m')] = $date->format('M Y');
            }
        } else {
            $columnHeaders['Total'] = 'Total '.$activePeriod['label'];
        }

        $formatK = request('format_k', '1');

        // 3. Ambil Data Detail & Master (Single-Pass JOIN)
        $data = $this->getGridData($ownerType, $start, $end, $mode, $columnHeaders, $user);

        $isAdminOrPengurus = clone $this;
        $isAdminOrPengurus = $isAdminOrPengurus->isAdminOrPengurus();

        return array_merge([
            'mode' => $mode,
            'ownerType' => $ownerType,
            'periods' => $periods,
            'periodIdx' => (int) $periodIdx,
            'columnHeaders' => $columnHeaders,
            'availableMonths' => $availableMonths,
            'selectedMonth' => $selectedMonth,
            'isAdminOrPengurus' => $isAdminOrPengurus,
            'formatK' => $formatK,
        ], $data);
    }

    private function getGridData(string $ownerType, string $start, string $end, string $mode, array $columnHeaders, ?object $user)
    {
        $table = ($ownerType === 'Produsen') ? 'produsen' : 'pedagang';

        $query = DB::table('detail_tabungan as d')
            ->join($table.' as m', 'd.owner_id', '=', 'm.id')
            ->where('d.owner_type', $ownerType) // 1st prefix idx_tabungan_super
            ->whereBetween('d.tanggal', [$start.' 00:00:00', $end.' 23:59:59']) // 2nd prefix
            ->whereNull('d.deleted_at') // 3rd prefix
            ->whereNull('m.deleted_at')
            ->where('d.keterangan', 'NOT LIKE', 'finalize%');

        if ($user && in_array($user->owner_type, ['Pedagang', 'Produsen'], true)) {
            $query->where('d.owner_id', $user->owner_id);
        }

        $dateFormat = "DATE_FORMAT(d.tanggal, '%Y-%m-%d')";
        if ($mode === 'per_bulan') {
            $dateFormat = "DATE_FORMAT(d.tanggal, '%Y-%m')";
        } elseif ($mode === 'range') {
            $dateFormat = "'Total'";
        }

        $reportData = $query->select([
            'd.owner_id',
            'm.nama as owner_nama',
            DB::raw('SUM(d.jumlah) as total'),
        ])
            ->selectRaw("{$dateFormat} as k")
            ->groupBy('d.owner_id', 'owner_nama', 'k')
            ->get();

        // 4. Owners list extracted from reportData
        $owners = $reportData->pluck('owner_nama', 'owner_id');

        if ($owners->isEmpty() && ($user && in_array($user->owner_type, ['Admin', 'Pengurus'], true))) {
            $owners = DB::table($table)->whereNull('deleted_at')->orderBy('nama')->pluck('nama', 'id');
        }

        // 5. Mapping ke Grid & Hitung Total
        $grid = [];
        $ownerTotals = [];
        $ownerMonthTotals = [];
        $colTotals = [];
        $grandTotal = 0;
        $grandMonthTotal = 0;

        foreach ($reportData as $row) {
            $grid[$row->owner_id][$row->k] = $row->total;
            $ownerTotals[$row->owner_id] = ($ownerTotals[$row->owner_id] ?? 0) + $row->total;
            $grandTotal += $row->total;

            if (array_key_exists($row->k, $columnHeaders)) {
                $colTotals[$row->k] = ($colTotals[$row->k] ?? 0) + $row->total;
                $ownerMonthTotals[$row->owner_id] = ($ownerMonthTotals[$row->owner_id] ?? 0) + $row->total;
                $grandMonthTotal += $row->total;
            }
        }

        return [
            'grid' => $grid,
            'owners' => $owners,
            'ownerTotals' => $ownerTotals,
            'ownerMonthTotals' => $ownerMonthTotals,
            'colTotals' => $colTotals,
            'grandTotal' => $grandTotal,
            'grandMonthTotal' => $grandMonthTotal,
        ];
    }
}
