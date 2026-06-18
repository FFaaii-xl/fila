<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Penjualan extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'penjualan';

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
        'status',
    ];

    public $casts = [
        'tanggal' => 'date',
        'titip' => 'integer',
        'laku' => 'integer',
        'sisa_jual' => 'integer',
        'retur' => 'integer',
        'modal' => 'float',
        'jual' => 'float',
    ];

    public function pedagang(): BelongsTo
    {
        return $this->belongsTo(Pedagang::class, 'pedagang_id', 'id');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id', 'id');
    }
}
