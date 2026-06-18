<?php

if (!function_exists('alignUang')) {
    /**
     * Format number as Indonesian Rupiah
     */
    function alignUang(?float $value, bool $showPrefix = true): string
    {
        if ($value === null) {
            return '-';
        }

        $formatted = number_format(abs($value), 0, ',', '.');

        if ($showPrefix) {
            return ($value < 0 ? '-' : '') . 'Rp ' . $formatted;
        }

        return ($value < 0 ? '-' : '') . $formatted;
    }
}

use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;

/**
 * Global Helper untuk formatting ribuan ke 'k'
 * Contoh: 1000 -> 1k, 500 -> 500
 */
if (!function_exists('formatK')) {
    function formatK($num)
    {
        // Cast to float to handle string inputs
        $num = (float) $num;

        if ($num === 0.0 || $num === null) {
            return '-';
        }
        if (abs($num) >= 1000) {
            return Number::format(floor($num / 1000), locale: 'id').'K';
        }

        return Number::format($num, locale: 'id');
    }
}

/**
 * Global Helper untuk Toast / Notifikasi
 * Digunakan untuk menyamakan sistem feedback di seluruh Controller custom.
 */
if (!function_exists('citro_toast')) {
    function citro_toast($message, $type = 'success')
    {
        $validTypes = ['success', 'error', 'info', 'warning'];
        $type = in_array($type, $validTypes, true) ? $type : 'success';

        // Trigger Filament v5 Native Notification
        if (class_exists(\Filament\Notifications\Notification::class)) {
            \Filament\Notifications\Notification::make()
                ->title(ucfirst($type))
                ->body($message)
                ->$type()
                ->send();
        }

        // Flash for legacy alerts
        session()->flash($type, $message);

        return redirect();
    }
}

/**
 * Global Helper untuk menyingkat nama produk
 * Jika lebih dari 3 kata, kata ke-4 dst disingkat inisial.
 */
if (!function_exists('abbreviateProductName')) {
    function abbreviateProductName($name)
    {
        if (! $name) {
            return '';
        }

        $words = explode(' ', trim($name));
        $count = count($words);

        if ($count <= 2) {
            return $name;
        }

        // Kata 1 & 2 tetap
        $result = [$words[0], $words[1]];

        // Kata ke-3 hapus AIUEO
        if ($count >= 3) {
            $result[] = str_ireplace(['a', 'i', 'u', 'e', 'o'], '', $words[2]);
        }

        // Kata ke-4 dst Inisial
        if ($count >= 4) {
            for ($i = 3; $i < $count; $i++) {
                $result[] = strtoupper(substr($words[$i], 0, 1));
            }
        }

        return implode(' ', $result);
    }
}
