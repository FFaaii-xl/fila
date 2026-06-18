<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class MutasiHarianPage extends Page
{
    public string $selectedDate = '';
    public array $transaksiList = [];
    public array $saldoAwal = [];
    public array $saldoAkhir = [];
    public float $totalDebit = 0;
    public float $totalKredit = 0;

    public function mount(): void
    {
        $this->selectedDate = date('Y-m-d');
        $this->loadMutasi();
    }

    public function getView(): string
    {
        return 'filament.pages.mutasi-harian';
    }

    public function loadMutasi(): void
    {
        $date = $this->selectedDate;
        $yesterday = date('Y-m-d', strtotime($date . ' -1 day'));

        $saldoKemarin = DB::table('transaksi')
            ->whereDate('tanggal', '<=', $yesterday)
            ->selectRaw('owner_type, owner_id, SUM(jumlah) as total')
            ->groupBy('owner_type', 'owner_id')
            ->get();

        $this->saldoAwal = [];
        foreach ($saldoKemarin as $s) {
            $key = $s->owner_type . '_' . $s->owner_id;
            $this->saldoAwal[$key] = $s->total;
        }

        $transaksi = DB::table('transaksi as t')
            ->leftJoin('account as a', 't.kas', '=', 'a.id')
            ->whereDate('t.tanggal', $date)
            ->whereNull('t.deleted_at')
            ->select(['t.*', 'a.nama as account_nama'])
            ->orderBy('t.tanggal')
            ->get();

        $this->transaksiList = [];
        $this->totalDebit = 0;
        $this->totalKredit = 0;

        foreach ($transaksi as $t) {
            $jumlah = (float) $t->jumlah;
            $isDebit = $jumlah > 0;

            $this->transaksiList[] = [
                'tanggal' => $t->tanggal,
                'owner_type' => $t->owner_type,
                'owner_id' => $t->owner_id,
                'account' => $t->account_nama,
                'keterangan' => $t->keterangan,
                'debit' => $isDebit ? $jumlah : 0,
                'kredit' => !$isDebit ? abs($jumlah) : 0,
            ];

            if ($isDebit) {
                $this->totalDebit += $jumlah;
            } else {
                $this->totalKredit += abs($jumlah);
            }
        }

        $this->saldoAkhir = $this->saldoAwal;
        foreach ($transaksi as $t) {
            $key = $t->owner_type . '_' . $t->owner_id;
            if (!isset($this->saldoAkhir[$key])) {
                $this->saldoAkhir[$key] = 0;
            }
            $this->saldoAkhir[$key] += (float) $t->jumlah;
        }
    }

    public function updatedSelectedDate(): void
    {
        $this->loadMutasi();
    }
}
