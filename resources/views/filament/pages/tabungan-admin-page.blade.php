<x-filament-panels::page>
    <div class="kinetic-wrapper">
        @include('admin.reports.savings-report', [
            'mode' => $mode,
            'ownerType' => $ownerType,
            'periods' => $periods,
            'periodIdx' => $periodIdx,
            'columnHeaders' => $columnHeaders,
            'availableMonths' => $availableMonths,
            'selectedMonth' => $selectedMonth,
            'isAdminOrPengurus' => $isAdminOrPengurus,
            'formatK' => $formatK,
            'grid' => $grid,
            'owners' => $owners,
            'ownerTotals' => $ownerTotals,
            'ownerMonthTotals' => $ownerMonthTotals,
            'colTotals' => $colTotals,
            'grandTotal' => $grandTotal,
            'grandMonthTotal' => $grandMonthTotal,
        ])
    </div>
</x-filament-panels::page>
