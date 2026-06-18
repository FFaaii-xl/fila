<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DraftPenjualan extends Model
{
    use SoftDeletes;

    protected $table = 'draft_penjualan';

    protected $fillable = [
        'pedagang_id',
        'produk_id',
        'tanggal',
        'titip',
        'laku',
        'sisa_jual',
        'retur',
        'modal',
        'jual',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'titip' => 'integer',
        'laku' => 'integer',
        'sisa_jual' => 'integer',
        'retur' => 'integer',
        'modal' => 'float',
        'jual' => 'float',
    ];

    public function pedagang()
    {
        return $this->belongsTo(Pedagang::class);
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }
}
