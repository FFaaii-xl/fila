<?php

namespace App\Models;

use App\Traits\MerchantFinancialRules;
use App\Traits\UppercaseAttributes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Pedagang extends Model
{
    use HasFactory;
    use MerchantFinancialRules;
    use SoftDeletes;
    use UppercaseAttributes;

    protected $table = 'pedagang';

    protected $fillable = [
        'nama',
        'tabungan_rate',
        'tabungan',
        'gender',
    ];

    /**
     * CITROROSO LEGACY SHIELD:
     * Tabel pedagang tidak memiliki Auto-Increment,
     * kita tangani secara manual agar tetap patuh pada Zero-Migration Rule.
     */
    public $incrementing = false;

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (int) (DB::table('pedagang')->max('id') ?? 0) + 1;
            }
        });
    }

    public function saldo(): HasOne
    {
        return $this->hasOne(Saldo::class, 'owner_id', 'id')
            ->where('owner_type', 'Pedagang');
    }

    public function penjualans(): HasMany
    {
        return $this->hasMany(Penjualan::class, 'pedagang_id', 'id');
    }

    public function scopeHanyaAktif(Builder $query, int $days = 14): Builder
    {
        $activeIds = self::getActivePedagangIds($days);
        return $query->whereIn('pedagang.id', $activeIds);
    }

    public static function getActivePedagangIds(int $days = 14): array
    {
        $cacheKey = "active_pedagang_ids_{$days}";
        return cache()->remember($cacheKey, 1800, function () use ($days) {
            return DB::table('penjualan')
                ->where('tanggal', '>=', now()->subDays($days)->toDateString())
                ->whereNull('deleted_at')
                ->where('status', 'Ok')
                ->distinct()
                ->pluck('pedagang_id')
                ->toArray();
        });
    }

    public function rutinitas(): MorphMany
    {
        return $this->morphMany(Rutinitas::class, 'sender');
    }

    public function pembulatan(): HasOne
    {
        return $this->hasOne(Pembulatan::class, 'produsen_id')->whereRaw('1 = 0');
    }
}
