<?php

namespace App\Traits;

trait UppercaseAttributes
{
    protected static function booted()
    {
        static::saving(function ($model) {
            foreach ($model->getAttributes() as $key => $value) {
                if (is_string($value) && in_array($key, ['nama'])) {
                    $model->setAttribute($key, strtoupper($value));
                }
            }
        });
    }
}
