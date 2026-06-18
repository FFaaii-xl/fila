<x-filament-panels::page>
    <x-filament-panels::form wire:submit="submit">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="[
                \Filament\Actions\Action::make('save')
                    ->label('Simpan Perubahan')
                    ->submit('submit')
                    ->keyBindings(['mod+s']),
            ]"
        />
    </x-filament-panels::form>
</x-filament-panels::page>
