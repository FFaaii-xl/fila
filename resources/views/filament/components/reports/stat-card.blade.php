{{-- Reusable Stat Card Component for Reports --}}
{{-- Usage: <x-filament.reports.stat-card :label="$label" :value="$value" color="emerald" /> --}}

@props([
    'label' => '',
    'value' => '',
    'color' => 'emerald',
    'icon' => null,
    'formatted' => false,
])

@php
$colors = [
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

$colorScheme = $colors[$color] ?? $colors['emerald'];
@endphp

<div class="{{ $colorScheme['bg'] }} rounded-lg p-3">
    <div class="flex items-center gap-2 mb-1">
        @if($icon)
            <x-icon :name="$icon" class="w-4 h-4 {{ $colorScheme['text'] }}" />
        @endif
        <p class="text-xs {{ $colorScheme['text'] }} font-medium">{{ $label }}</p>
    </div>
    <p class="text-lg font-bold {{ $colorScheme['dark_text'] }}">
        {{ $formatted ? $value : number_format($value, 0, ',', '.') }}
    </p>
</div>
