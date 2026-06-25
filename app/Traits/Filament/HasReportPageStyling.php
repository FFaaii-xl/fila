<?php

namespace App\Traits\Filament;

/**
 * Trait for consistent Citroroso theme styling in report pages
 */
trait HasReportPageStyling
{
    /**
     * Get stat card color scheme
     */
    public function getStatCardColors(): array
    {
        return [
            'emerald' => [
                'bg' => 'bg-emerald-50 dark:bg-emerald-900/20',
                'text' => 'text-emerald-600 dark:text-emerald-400',
                'dark_text' => 'text-emerald-900 dark:text-emerald-300',
            ],
            'blue' => [
                'bg' => 'bg-blue-50 dark:bg-blue-900/20',
                'text' => 'text-blue-600 dark:text-blue-400',
                'dark_text' => 'text-blue-900 dark:text-blue-300',
            ],
            'amber' => [
                'bg' => 'bg-amber-50 dark:bg-amber-900/20',
                'text' => 'text-amber-600 dark:text-amber-400',
                'dark_text' => 'text-amber-900 dark:text-amber-300',
            ],
            'rose' => [
                'bg' => 'bg-rose-50 dark:bg-rose-900/20',
                'text' => 'text-rose-600 dark:text-rose-400',
                'dark_text' => 'text-rose-900 dark:text-rose-300',
            ],
            'purple' => [
                'bg' => 'bg-purple-50 dark:bg-purple-900/20',
                'text' => 'text-purple-600 dark:text-purple-400',
                'dark_text' => 'text-purple-900 dark:text-purple-300',
            ],
            'teal' => [
                'bg' => 'bg-teal-50 dark:bg-teal-900/20',
                'text' => 'text-teal-600 dark:text-teal-400',
                'dark_text' => 'text-teal-900 dark:text-teal-300',
            ],
        ];
    }

    /**
     * Get primary accent color
     */
    public function getPrimaryColor(): string
    {
        return 'emerald';
    }

    /**
     * Format currency value
     */
    public function formatCurrency(float $value): string
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }

    /**
     * Format number with thousand separator
     */
    public function formatNumber(float $value): string
    {
        return number_format($value);
    }

    /**
     * Get percentage with 1 decimal
     */
    public function formatPercent(float $value): string
    {
        return number_format($value, 1) . '%';
    }
}
