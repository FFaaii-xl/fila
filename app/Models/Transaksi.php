<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaksi extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'transaksi';

    protected $fillable = [
        'owner_type',
        'owner_id',
        'tanggal',
        'jumlah',
        'kemarin',
        'pembulatan',
        'kas',
        'keterangan',
    ];

    public $casts = [
        'tanggal' => 'date',
        'jumlah' => 'float',
        'kemarin' => 'float',
        'pembulatan' => 'float',
        'kas' => 'float',
    ];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
