<?php

namespace App\Filament\Pages;

use App\Traits\Filament\HasRoleAuthorization;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class LegacyConverterPage extends Page
{
    use HasRoleAuthorization;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static string | \UnitEnum | null $navigationGroup = 'Tools';
    protected static ?int $navigationSort = 20;
    protected static ?string $title = 'Legacy Intelligence Converter';

    protected string $view = 'filament.pages.legacy-converter-page';

    public static function canAccess(): bool
    {
        return (new static)->isAdminOrPengurus();
    }

    protected function getViewData(): array
    {
        return [
            'history' => $this->getHistory(),
        ];
    }

    private function getHistory(): array
    {
        $path = 'public/converted';
        if (! Storage::exists($path)) {
            return [];
        }

        $directories = Storage::directories($path);
        rsort($directories); // Sort dates newest first

        $history = [];
        foreach ($directories as $dir) {
            $date = basename($dir);
            $files = Storage::files($dir);

            if (empty($files)) {
                continue;
            }

            $fileData = [];
            foreach ($files as $file) {
                $fileData[] = [
                    'name' => basename($file),
                    'url' => Storage::url($file),
                    'size' => number_format(Storage::size($file) / 1024, 1).' KB',
                    'time' => date('H:i', Storage::lastModified($file)),
                ];
            }

            $history[] = [
                'date' => $date,
                'files' => $fileData,
            ];
        }

        return $history;
    }
}
