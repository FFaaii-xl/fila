<?php

namespace App\Filament\Pages;

use App\Services\SettingsService;
use App\Traits\Filament\HasRoleAuthorization;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class SettingsPage extends Page
{
    use HasRoleAuthorization;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string | \UnitEnum | null $navigationGroup = 'System';
    protected static ?int $navigationSort = 100;
    protected static ?string $title = 'Pengaturan Sistem';

    protected string $view = 'filament.pages.settings-page';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (new static)->isAdminOrPengurus();
    }

    public function mount(): void
    {
        $settingsService = app(SettingsService::class);
        $settings = $settingsService->all();
        
        // Convert the comma-separated string to array for the form, or keep it as string if the form expects string
        if (isset($settings['special_merchant_list']) && is_array($settings['special_merchant_list'])) {
             $settings['special_merchant_list'] = implode(', ', $settings['special_merchant_list']);
        }

        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->tabs([
                        Tabs\Tab::make('Global & Threshold')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('transaction_threshold')
                                            ->label('Transaction Threshold')
                                            ->numeric()
                                            ->helperText('Minimal nominal untuk diproses bayar (di bawah ini masuk pembulatan/kemarin)'),
                                        TextInput::make('kas_produsen_flat')
                                            ->label('Producer Kas Flat')
                                            ->numeric()
                                            ->helperText('Biaya administrasi dasar produsen'),
                                        TextInput::make('kas_threshold')
                                            ->label('Kas Threshold')
                                            ->numeric()
                                            ->helperText('Minimum omset untuk trigger kas calculation'),
                                    ]),
                                Grid::make(1)
                                    ->schema([
                                        TextInput::make('kas_produsen.denomination')
                                            ->label('Kas Denomination (Receh)')
                                            ->numeric()
                                            ->helperText('Denominasi untuk sisa bagi (receh)'),
                                    ]),
                            ]),

                        Tabs\Tab::make('Pedagang (Merchant)')
                            ->schema([
                                TextInput::make('proup_rate')
                                    ->label('Iuran Rate')
                                    ->numeric()
                                    ->step(0.001)
                                    ->helperText('Default 0.015 (1.5%)'),
                                TextInput::make('proup_threshold_count')
                                    ->label('Iuran Threshold (Item Count)')
                                    ->numeric(),
                                Textarea::make('special_merchant_list')
                                    ->label('Special Merchant (No Iuran)')
                                    ->helperText('Pisahkan dengan koma'),
                                Repeater::make('kas_pedagang_ranges')
                                    ->label('Tabel Kas Pedagang (Lookup)')
                                    ->schema([
                                        TextInput::make('min')
                                            ->label('Min Amount')
                                            ->numeric(),
                                        TextInput::make('fee')
                                            ->label('Fee')
                                            ->numeric(),
                                    ])
                                    ->columns(2)
                                    ->addActionLabel('Add Range'),
                            ]),

                        Tabs\Tab::make('Deadline & Kunci')
                            ->schema([
                                Toggle::make('submission_deadline_active')
                                    ->label('Aktifkan Batas Waktu (Deadline) Pengisian')
                                    ->helperText('Jika aktif, pedagang tidak bisa mengisi data setelah jam yang ditentukan (Khusus untuk hari ini)'),
                                TextInput::make('submission_deadline_time')
                                    ->label('Jam Deadline')
                                    ->mask('99:99')
                                    ->helperText('Format 24 jam. Contoh: 14:00'),
                                Section::make('Safety Rules (Hard-Coded)')
                                    ->description('Nota Sudah Dicetak. Laporan Terkunci. Aturan ini otomatis mengunci draf jika Admin sudah mencetak nota/membayar hari ini.')
                                    ->icon('heroicon-o-lock-closed')
                                    ->schema([]),
                            ]),

                        Tabs\Tab::make('Uang Nota')
                            ->schema([
                                Section::make('Aturan Pembulatan Uang Nota')
                                    ->description('Pengaturan ini mengontrol logika pembulatan untuk transaksi produsen. Nilai default sudah di-set sesuai aturan legacy.')
                                    ->icon('heroicon-o-information-circle')
                                    ->schema([
                                        Toggle::make('uang_nota.enabled')
                                            ->label('Aktifkan Pembulatan')
                                            ->helperText('Jika nonaktif, tidak ada pembulatan diterapkan'),
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('uang_nota.threshold_1')
                                                    ->label('Threshold Bawah 1')
                                                    ->numeric()
                                                    ->helperText('Di bawah nilai ini: Keep as is'),
                                                TextInput::make('uang_nota.target_1')
                                                    ->label('Target Bulat 1')
                                                    ->numeric()
                                                    ->helperText('Bulat ke atas ke: 50.000'),
                                                TextInput::make('uang_nota.threshold_2')
                                                    ->label('Threshold Bawah 2')
                                                    ->numeric()
                                                    ->helperText('Di atas threshold ini: Round UP'),
                                                TextInput::make('uang_nota.target_2')
                                                    ->label('Target Bulat 2')
                                                    ->numeric()
                                                    ->helperText('Bulat ke atas ke: 100.000'),
                                            ]),
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('uang_nota.step')
                                                    ->label('Step (>= 100k)')
                                                    ->numeric()
                                                    ->helperText('Step kelipatan untuk >= 100k'),
                                                TextInput::make('uang_nota.remainder_min')
                                                    ->label('Remainder Min')
                                                    ->numeric()
                                                    ->helperText('Remainder <= ini: Bulat DOWN'),
                                                TextInput::make('uang_nota.remainder_threshold')
                                                    ->label('Remainder Threshold')
                                                    ->numeric()
                                                    ->helperText('Remainder >= ini: Bulat UP'),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $settingsService = app(SettingsService::class);
        $data = $this->form->getState();

        if (isset($data['special_merchant_list']) && is_string($data['special_merchant_list'])) {
            $list = array_map('trim', explode(',', $data['special_merchant_list']));
            $data['special_merchant_list'] = array_filter($list);
        }

        $settingsService->setMany($data);

        Notification::make()
            ->title('Pengaturan berhasil disimpan')
            ->success()
            ->send();
    }
}
