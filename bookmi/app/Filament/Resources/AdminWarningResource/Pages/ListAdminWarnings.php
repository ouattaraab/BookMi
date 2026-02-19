<?php

namespace App\Filament\Resources\AdminWarningResource\Pages;

use App\Filament\Resources\AdminWarningResource;
use Filament\Resources\Pages\ListRecords;

class ListAdminWarnings extends ListRecords
{
    protected static string $resource = AdminWarningResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
