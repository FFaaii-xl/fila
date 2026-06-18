<?php

namespace App\Filament\Pages;

use App\Services\SaldoService;
use App\Traits\Filament\HasRoleAuthorization;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class LogSaldoPage extends Page
{
    use HasRoleAuthorization;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string | \UnitEnum | null $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 11;
    protected static ?string $title = 'Log Saldo Pedagang';

    protected string $view = 'filament.pages.log-saldo-page';

    public static function canAccess(): bool
    {
        return (new static)->isAdminOrPengurus();
    }

    protected function getViewData(): array
    {
        // Find latest transactional date
        $latestDate = DB::table('transaksi')
            ->where('owner_type', '=', 'Pedagang')
            ->where('status', '=', 'Ok')
            ->whereNull('deleted_at')
            ->max('tanggal');

        if ($latestDate) {
            $latestDate = date('Y-m-d', strtotime($latestDate));
        } else {
            $latestDate = DB::table('penjualan')->max('tanggal');
            if ($latestDate) {
                $latestDate = date('Y-m-d', strtotime($latestDate));
            } else {
                $latestDate = now()->toDateString();
            }
        }

        $tanggal = request('tanggal', $latestDate);

        $service = app(SaldoService::class);
        $logs = $service->getMerchantBailoutLogs($tanggal, $tanggal);

        return [
            'logs' => $logs,
            'tanggal' => $tanggal,
        ];
    }
}
