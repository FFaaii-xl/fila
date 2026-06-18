<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'users2';

    protected $fillable = [
        'name',
        'username',
        'password',
        'owner_type',
        'owner_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function owner()
    {
        return $this->morphTo();
    }

    public function home(): string
    {
        return match ($this->owner_type) {
            'Admin', 'Pengurus' => '/admin',
            'Pedagang' => '/pedagang',
            'Produsen' => '/produsen',
            default => '/',
        };
    }
}
