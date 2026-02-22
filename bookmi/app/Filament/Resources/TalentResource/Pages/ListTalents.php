<?php

namespace App\Filament\Resources\TalentResource\Pages;

use App\Filament\Resources\TalentResource;
use Filament\Resources\Pages\ListRecords;

class ListTalents extends ListRecords
{
    protected static string $resource = TalentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
