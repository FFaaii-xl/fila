<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Produsen extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'produsen';

    protected $fillable = [
        'nama',
        'tabungan_rate',
        'tabungan',
        'gender',
        'bundle_ke',
    ];

    public $incrementing = false;

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (int) (DB::table('produsen')->max('id') ?? 0) + 1;
            }
        });
    }

    public function saldo(): HasOne
    {
        return $this->hasOne(Saldo::class, 'owner_id', 'id')
            ->where('owner_type', 'Produsen');
    }

    public function produks(): HasMany
    {
        return $this->hasMany(Produk::class, 'produsen_id', 'id');
    }

    public function pembulatan(): HasOne
    {
        return $this->hasOne(Pembulatan::class, 'produsen_id', 'id');
    }
}
