<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DetailTabungan;
use App\Models\Pedagang;
use App\Models\Produsen;
use App\Traits\FinancialPeriodDetection;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

final class TabunganController extends Controller
{
    use FinancialPeriodDetection;

    /**
     * Finalize tabungan untuk periode tertentu.
     * Mengikuti logika legacy: TabunganController@finalize
     *
     * Untuk setiap pedagang/produsen yang memiliki tabungan di periode tsb:
     * - Insert detail_tabungan: jumlah = -sum, awal = owner->tabungan, akhir = owner->tabungan - sum
     * - Update owner->tabungan -= sum
     * - Keterangan = "finalize Tabungan Periode DD MMM YYYY - DD MMM YYYY"
     */
    public function finalize(Request $request)
    {
        $validated = $request->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ]);

        $startDate = $validated['startDate'];
        $endDate = $validated['endDate'];

        $startStr = date('d M Y', strtotime($startDate));
        $endStr = date('d M Y', strtotime($endDate));
        $description = "finalize Tabungan Periode {$startStr} - {$endStr}";

        DB::transaction(function () use ($startDate, $endDate, $description) {
            // Process Produsen
            $produsens = Produsen::whereNull('deleted_at')->get();
            foreach ($produsens as $produsen) {
                $sum = DetailTabungan::where('owner_type', 'Produsen')
                    ->where('owner_id', $produsen->id)
                    ->whereNull('deleted_at')
                    ->whereBetween('tanggal', ["{$startDate} 00:00:00", "{$endDate} 23:59:59"])
                    ->where('keterangan', 'not like', '%finalize%')
                    ->sum('jumlah');

                if ($sum > 0) {
                    DetailTabungan::create([
                        'owner_type' => 'Produsen',
                        'owner_id' => $produsen->id,
                        'awal' => $produsen->tabungan,
                        'akhir' => $produsen->tabungan - $sum,
                        'jumlah' => -$sum,
                        'keterangan' => $description,
                        'tanggal' => $endDate,
                    ]);
                    $produsen->tabungan -= $sum;
                    $produsen->save();
                }
            }

            // Process Pedagang
            $pedagangs = Pedagang::whereNull('deleted_at')->get();
            foreach ($pedagangs as $pedagang) {
                $sum = DetailTabungan::where('owner_type', 'Pedagang')
                    ->where('owner_id', $pedagang->id)
                    ->whereNull('deleted_at')
                    ->whereBetween('tanggal', ["{$startDate} 00:00:00", "{$endDate} 23:59:59"])
                    ->where('keterangan', 'not like', '%finalize%')
                    ->sum('jumlah');

                if ($sum > 0) {
                    DetailTabungan::create([
                        'owner_type' => 'Pedagang',
                        'owner_id' => $pedagang->id,
                        'awal' => $pedagang->tabungan,
                        'akhir' => $pedagang->tabungan - $sum,
                        'jumlah' => -$sum,
                        'keterangan' => $description,
                        'tanggal' => $endDate,
                    ]);
                    $pedagang->tabungan -= $sum;
                    $pedagang->save();
                }
            }

            // Clear cache agar periode baru terdeteksi
            Cache::forget('tabungan_periods_finalize_global');
            Cache::forget('tabungan_boundaries_global');
        });

        return citro_toast('✅ Finalize berhasil! Periode baru telah dibuat.', 'success')->back();
    }

    /**
     * Get preview data for finalize (AJAX)
     */
    public function previewFinalize(Request $request)
    {
        $validated = $request->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ]);

        $startDate = $validated['startDate'];
        $endDate = $validated['endDate'];

        $preview = [];
        foreach (['Produsen', 'Pedagang'] as $type) {
            $model = $type === 'Produsen' ? Produsen::class : Pedagang::class;
            $owners = $model::whereNull('deleted_at')->get();

            foreach ($owners as $owner) {
                $sum = DetailTabungan::where('owner_type', $type)
                    ->where('owner_id', $owner->id)
                    ->whereNull('deleted_at')
                    ->whereBetween('tanggal', ["{$startDate} 00:00:00", "{$endDate} 23:59:59"])
                    ->where('keterangan', 'not like', '%finalize%')
                    ->sum('jumlah');

                if ($sum > 0) {
                    $preview[] = [
                        'type' => $type,
                        'nama' => $owner->nama,
                        'tabungan' => $owner->tabungan,
                        'sum' => $sum,
                        'akhir' => $owner->tabungan - $sum,
                    ];
                }
            }
        }

        return response()->json([
            'preview' => $preview,
            'count' => count($preview),
        ]);
    }

    /**
     * Export Tabungan Report to Excel
     */
    public function exportExcel(Request $request)
    {
        $mode = $request->get('mode', 'per_bulan');
        $ownerType = $request->get('owner_type', 'Pedagang');
        $periodIdx = (int) $request->get('period_idx', 0);

        // Build periods
        $periods = $this->buildFinancialPeriods();
        $activePeriod = $periods[$periodIdx] ?? end($periods);
        $start = $activePeriod['start'];
        $end = $activePeriod['end'];

        // Build column headers
        $columnHeaders = [];
        if ($mode === 'per_tanggal') {
            $selectedMonth = $request->get('month', date('Y-m', strtotime($end)));
            $cStart = Carbon::parse($selectedMonth)->startOfMonth();
            $cEnd = Carbon::parse($selectedMonth)->endOfMonth();
            if ($cStart->lt(Carbon::parse($start))) $cStart = Carbon::parse($start);
            if ($cEnd->gt(Carbon::parse($end))) $cEnd = Carbon::parse($end);
            if ($cStart->lte($cEnd)) {
                foreach (CarbonPeriod::create($cStart, $cEnd) as $date) {
                    $columnHeaders[$date->format('Y-m-d')] = $date->format('d');
                }
            }
        } elseif ($mode === 'per_bulan') {
            foreach (CarbonPeriod::create(Carbon::parse($start)->startOfMonth(), '1 month', Carbon::parse($end)->startOfMonth()) as $date) {
                $columnHeaders[$date->format('Y-m')] = $date->format('M Y');
            }
        }

        // Get data
        $table = ($ownerType === 'Produsen') ? 'produsen' : 'pedagang';
        $reportData = DB::table('detail_tabungan as d')
            ->join($table.' as m', 'd.owner_id', '=', 'm.id')
            ->where('d.owner_type', $ownerType)
            ->whereBetween('d.tanggal', [$start.' 00:00:00', $end.' 23:59:59'])
            ->whereNull('d.deleted_at')
            ->whereNull('m.deleted_at')
            ->where('d.keterangan', 'NOT LIKE', 'finalize%')
            ->select([
                'd.owner_id',
                'm.nama as owner_nama',
                DB::raw('SUM(d.jumlah) as total'),
            ])
            ->selectRaw($mode === 'per_bulan' 
                ? "DATE_FORMAT(d.tanggal, '%Y-%m') as k" 
                : ($mode === 'per_tanggal' ? "DATE_FORMAT(d.tanggal, '%Y-%m-%d') as k" : "'Total' as k"))
            ->groupBy('d.owner_id', 'owner_nama', 'k')
            ->get();

        // Map to grid
        $grid = [];
        $ownerTotals = [];
        foreach ($reportData as $row) {
            $grid[$row->owner_id][$row->k] = $row->total;
            $ownerTotals[$row->owner_id] = ($ownerTotals[$row->owner_id] ?? 0) + $row->total;
        }

        // Get owners
        $owners = $reportData->pluck('owner_nama', 'owner_id');
        if ($owners->isEmpty()) {
            $owners = DB::table($table)->whereNull('deleted_at')->orderBy('nama')->pluck('nama', 'id');
        }

        // Generate filename
        $filename = "Tabungan_{$ownerType}_{$mode}_{$activePeriod['label']}.xlsx";

        // Export using inline export class
        return Excel::download(new class($columnHeaders, $grid, $owners, $ownerTotals, $ownerType, $mode) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function __construct(
                private array $columnHeaders,
                private array $grid,
                private $owners,
                private array $ownerTotals,
                private string $ownerType,
                private string $mode
            ) {}

            public function collection(): \Illuminate\Support\Collection
            {
                $rows = collect();
                foreach ($this->owners as $id => $name) {
                    if (!isset($this->ownerTotals[$id])) continue;
                    
                    $row = [
                        'No' => $rows->count() + 1,
                        'Nama' => $name,
                    ];
                    
                    foreach ($this->columnHeaders as $key => $label) {
                        $row[$label] = isset($this->grid[$id][$key]) 
                            ? number_format($this->grid[$id][$key], 0, ',', '.') 
                            : '0';
                    }
                    
                    if ($this->mode === 'per_tanggal') {
                        $monthTotal = collect($this->columnHeaders)
                            ->keys()
                            ->sum(fn($k) => $this->grid[$id][$k] ?? 0);
                        $row['Total Bulan'] = number_format($monthTotal, 0, ',', '.');
                    }
                    
                    $row['Total Periode'] = number_format($this->ownerTotals[$id], 0, ',', '.');
                    $rows->push($row);
                }
                return $rows;
            }

            public function headings(): array
            {
                $headings = ['No', 'Nama'];
                foreach ($this->columnHeaders as $label) {
                    $headings[] = $label;
                }
                if ($this->mode === 'per_tanggal') {
                    $headings[] = 'Total Bulan';
                }
                $headings[] = 'Total Periode';
                return $headings;
            }
        }, $filename);
    }
}
