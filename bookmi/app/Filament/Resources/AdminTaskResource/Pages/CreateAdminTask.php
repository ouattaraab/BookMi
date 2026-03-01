<?php

namespace App\Filament\Resources\AdminTaskResource\Pages;

use App\Filament\Resources\AdminTaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdminTask extends CreateRecord
{
    protected static string $resource = AdminTaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['assigned_by'] = auth()->id();
        return $data;
    }
}
