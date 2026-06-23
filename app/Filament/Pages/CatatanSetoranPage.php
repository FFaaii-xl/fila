<?php

namespace App\Filament\Pages;

use App\Models\Pedagang;
use App\Services\SetoranService;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class CatatanSetoranPage extends Page
{
    public ?int $selectedYear = null;
    public ?int $selectedMonth = null;
    public ?int $selectedPedagangId = null;
    
    public array $grid = [];
    public array $summary = [];
    public int $daysInMonth = 0;
    public string $monthLabel = '';
    
    public array $monthOptions = [];
    public array $yearOptions = [];
    public array $pedagangOptions = [];

    public function mount(): void
    {
        $user = Auth::user();
        
        // Check access: only Admin and Pengurus
        if (!$user || !in_array($user->owner_type, ['Admin', 'Pengurus'])) {
            return;
        }

        $tz = 'Asia/Jakarta';
        $now = now($tz);
        
        $this->selectedYear = (int) request('year', $now->year);
        $this->selectedMonth = (int) request('month', $now->month);
        $this->selectedPedagangId = request('pedagang_id') ? (int) request('pedagang_id') : null;

        $this->loadData();
    }

    protected function loadData(): void
    {
        $service = app(SetoranService::class);
        
        // Build month options
        $this->monthOptions = [];
        for ($m = 1; $m <= 12; $m++) {
            $this->monthOptions[$m] = Carbon::create(2024, $m, 1)->translatedFormat('F');
        }

        // Year range: current year - 2 to current year
        $now = now('Asia/Jakarta');
        $this->yearOptions = range($now->year - 2, $now->year);

        // Pedagang list for filter
        $this->pedagangOptions = Pedagang::query()
            ->whereNull('deleted_at')
            ->orderBy('nama')
            ->pluck('nama', 'id')
            ->toArray();

        // Get grid data
        $this->grid = $service->getMonthlyGrid(
            $this->selectedYear,
            $this->selectedMonth,
            $this->selectedPedagangId
        );

        // Get summary
        $this->summary = $service->getMonthlySummary(
            $this->selectedYear,
            $this->selectedMonth,
            $this->selectedPedagangId
        );

        // Calculate days in month
        $this->daysInMonth = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->daysInMonth;
        $this->monthLabel = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->translatedFormat('F Y');
    }

    public function getHeading(): string
    {
        return 'Catatan Setoran';
    }

    public function getView(): string
    {
        return 'filament.pages.catatan-setoran';
    }

    public function getHeaderWidgets(): array
    {
        return [];
    }

    public function filters(): array
    {
        return [];
    }

    /**
     * Get status badge color class
     */
    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Ok' => 'bg-emerald-500 text-white',
            'S' => 'bg-amber-500 text-white',
            default => match (true) {
                str_starts_with($status, 'T') => 'bg-red-500 text-white',
                $status === 'BELUM' => 'bg-gray-400 text-white',
                $status === 'X' => 'bg-slate-300 text-slate-600',
                default => 'bg-gray-200 text-gray-700',
            },
        };
    }

    /**
     * Get status label
     */
    public static function getStatusLabel(?string $status): string
    {
        if ($status === null) {
            return 'X';
        }
        return $status;
    }
}
