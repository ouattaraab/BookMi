<?php

namespace App\Filament\Resources\AdminAlertResource\Pages;

use App\Filament\Resources\AdminAlertResource;
use Filament\Resources\Pages\ListRecords;

class ListAdminAlerts extends ListRecords
{
    protected static string $resource = AdminAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
