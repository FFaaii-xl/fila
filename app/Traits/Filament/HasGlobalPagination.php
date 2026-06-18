<?php

declare(strict_types=1);

namespace App\Traits\Filament;

use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Dropdown;

trait HasGlobalPagination
{
    /**
     * Override getItemsPerPage() — method yang BENAR dibaca oleh MoonShine v3
     * (bukan itemsPerPage() — itu tidak ada di MoonShine v3)
     */
    protected function getItemsPerPage(): int
    {
        if (request()->has('global_per_page')) {
            $val = request()->get('global_per_page');
            $perPage = ($val === 'all') ? 9999 : (int) $val;
            session(['moonshine_per_page' => $perPage]);
        }

        return (int) session('moonshine_per_page', 40);
    }

    /**
     * Tambahkan tombol pilihan jumlah data ke area toolbar.
     */
    protected function injectPaginationButtons(ListOf $buttons): ListOf
    {
        $current = (int) session('moonshine_per_page', 20);
        $label = (string) (($current === 9999) ? 'Semua' : $current);

        $options = [
            20 => '20',
            50 => '50',
            100 => '100',
            9999 => 'Semua',
        ];

        $items = [];
        foreach ($options as $value => $l) {
            $isActive = ($current === $value);
            $items[] = ActionButton::make(
                $l,
                fn () => request()->fullUrlWithQuery(['global_per_page' => $value === 9999 ? 'all' : $value])
            )
                ->when($isActive, fn ($btn) => $btn->primary())
                ->showInLine();
        }

        $dropdown = Dropdown::make(
            toggler: fn () => ActionButton::make($label, '#')
                ->secondary()
                ->icon('chevron-down')
                ->customAttributes([
                    'style' => 'min-width:4rem; padding: 0 12px; height: 22px !important; min-height: 22px !important;',
                    'class' => 'hhr-pagination-toggler',
                ]),
            items: $items
        );

        // ActionButton::make is used here as a wrapper to satisfy the ListOf<ActionButtonContract> type constraint.
        // rawMode() ensures it doesn't apply its own button classes, letting the internal Dropdown handle the UI.
        $buttons->add(
            ActionButton::make((string) $dropdown, '#')
                ->rawMode()
        );

        return $buttons;
    }
}

