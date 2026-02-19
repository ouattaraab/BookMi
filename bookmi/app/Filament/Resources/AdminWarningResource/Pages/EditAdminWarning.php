<?php

namespace App\Filament\Resources\AdminWarningResource\Pages;

use App\Filament\Resources\AdminWarningResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdminWarning extends EditRecord
{
    protected static string $resource = AdminWarningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
