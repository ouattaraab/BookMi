<?php

namespace App\Filament\Resources\LoginLockoutLogResource\Pages;

use App\Filament\Resources\LoginLockoutLogResource;
use Filament\Resources\Pages\ListRecords;

class ListLoginLockoutLogs extends ListRecords
{
    protected static string $resource = LoginLockoutLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
