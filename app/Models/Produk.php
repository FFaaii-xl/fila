<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produk extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'produk';

    protected $fillable = [
        'nama',
        'harga_beli',
        'harga_jual',
        'stok',
        'produsen_id',
    ];

    public $casts = [
        'harga_beli' => 'float',
        'harga_jual' => 'float',
        'stok' => 'integer',
    ];

    public function produsen(): BelongsTo
    {
        return $this->belongsTo(Produsen::class, 'produsen_id', 'id');
    }

    public function penjualans(): HasMany
    {
        return $this->hasMany(Penjualan::class, 'produk_id', 'id');
    }
}
