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

if (!function_exists('formatTanggal')) {
    /**
     * Format date as Indonesian format
     */
    function formatTanggal($date, string $format = 'd/m/Y'): string
    {
        if (!$date) {
            return '-';
        }

        return \Carbon\Carbon::parse($date)->format($format);
    }
}
