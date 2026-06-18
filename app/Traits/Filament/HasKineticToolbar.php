<?php

declare(strict_types=1);

namespace App\Traits\Filament;

use App\MoonShine\Pages\KineticIndexPage;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Fields\File;

trait HasKineticToolbar
{
    /**
     * Define pages for MoonShine v4, using our custom KineticIndexPage
     * that supports indexButtons().
     */
    public function pages(): array
    {
        return [
            KineticIndexPage::class,
            FormPage::class,
            DetailPage::class,
        ];
    }

    /**
     * Helper to initialize a ListOf ActionButtons.
     */
    protected function indexButtonsList(): ListOf
    {
        return new ListOf(ActionButtonContract::class, []);
    }

    /**
     * Inject Master Data Toolbar Buttons (Edit Mode, Template, Import)
     * Following Clean Code: DRY and Single Responsibility.
     */
    protected function injectKineticButtons(ListOf $buttons, string $resourceType): ListOf
    {
        $user = auth()->user();

        // Only Admin and Pengurus have access to these management tools
        if (! $user || ! in_array($user->owner_type, ['Admin', 'Pengurus'], true)) {
            return $buttons;
        }

        $buttons->add($this->buildEditToggleBtn());
        $buttons->add($this->buildTemplateBtn($resourceType));
        $buttons->add($this->buildExportBtn($resourceType));
        $buttons->add($this->buildImportBtn($resourceType));

        return $buttons;
    }

    private function buildEditToggleBtn(): ActionButton
    {
        return ActionButton::make('OFF', '#')
            ->icon('pencil-square')
            ->secondary()
            ->customAttributes([
                'style' => 'padding: 0 4px !important; height: 22px !important; min-height: 22px !important; min-width: 32px !important;',
                'x-data' => '{ editMode: false }',
                'x-init' => "document.body.classList.add('edit-mode-off')",
                '@click.prevent' => "
                    editMode = !editMode;
                    if(editMode) {
                        \$el.innerText = 'ON';
                        \$el.classList.replace('btn-secondary', 'btn-success');
                        document.body.classList.replace('edit-mode-off', 'edit-mode-on');
                        \$dispatch('edit-mode-toggled', { active: true });
                    } else {
                        \$el.innerText = 'OFF';
                        \$el.classList.replace('btn-success', 'btn-secondary');
                        document.body.classList.replace('edit-mode-on', 'edit-mode-off');
                        \$dispatch('edit-mode-toggled', { active: false });
                    }
                ",
            ]);
    }

    private function buildTemplateBtn(string $resourceType): ActionButton
    {
        return ActionButton::make('', route('admin.import.template', $resourceType))
            ->icon('document-arrow-down')
            ->secondary()
            ->customAttributes([
                'class' => 'hhr-push-right',
                'style' => 'padding: 0 !important; height: 22px !important; min-height: 22px !important; width: 22px !important; min-width: 22px !important; justify-content: center;',
            ]); // Pushes to the right alongside Filters
    }

    private function buildImportBtn(string $resourceType): ActionButton
    {
        return ActionButton::make('', '#')
            ->icon('arrow-up-tray')
            ->success()
            ->customAttributes([
                'style' => 'padding: 0 !important; height: 22px !important; min-height: 22px !important; width: 22px !important; min-width: 22px !important; justify-content: center;',
            ])
            ->inModal(
                title: 'Import Data '.ucfirst($resourceType),
                content: fn () => FormBuilder::make(route('admin.import', $resourceType))
                    ->fields([
                        File::make('File Excel/CSV', 'file')->required(),
                    ])
                    ->submit('Upload & Proses')
            );
    }

    private function buildExportBtn(string $resourceType): ActionButton
    {
        return ActionButton::make('', route('admin.export', $resourceType))
            ->icon('table-cells')
            ->primary()
            ->customAttributes([
                'style' => 'padding: 0 !important; height: 22px !important; min-height: 22px !important; width: 22px !important; min-width: 22px !important; justify-content: center;',
            ]);
    }
}

