<?php

namespace App\Filament\Resources\Accounts\Pages;

use App\Filament\Resources\Accounts\AccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['virtual_role'])) {
            [$type, $id] = explode(':', $data['virtual_role']);
            $data['owner_type'] = $type;
            $data['owner_id'] = (int) $id;
            unset($data['virtual_role']);
        }
        return $data;
    }
}
