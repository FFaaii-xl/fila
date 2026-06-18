<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

class Saldo extends Model
{
    protected $table = 'saldo';

    protected $fillable = [
        'owner_type',
        'owner_id',
        'jumlah',
    ];

    public $timestamps = false;

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function increase(int $amount, array $keterangan = []): void
    {
        $log = [
            'saldo_id' => $this->id,
            'jumlah' => $amount,
            'saldo_awal' => $this->jumlah,
            'saldo_akhir' => $this->jumlah + $amount,
            'tanggal' => now(),
            'keterangan' => $keterangan['keterangan'] ?? null,
        ];

        DB::table('log_saldo')->insert($log);

        $this->jumlah += $amount;
        $this->save();
    }

    public function decrease(int $amount, array $keterangan = []): void
    {
        $log = [
            'saldo_id' => $this->id,
            'jumlah' => -$amount,
            'saldo_awal' => $this->jumlah,
            'saldo_akhir' => $this->jumlah - $amount,
            'tanggal' => now(),
            'keterangan' => $keterangan['keterangan'] ?? null,
        ];

        DB::table('log_saldo')->insert($log);

        $this->jumlah -= $amount;
        $this->save();
    }
}
