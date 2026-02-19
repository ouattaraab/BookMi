<?php

namespace App\Filament\Resources\TalentProfileResource\Pages;

use App\Filament\Resources\TalentProfileResource;
use Filament\Resources\Pages\ListRecords;

class ListTalentProfiles extends ListRecords
{
    protected static string $resource = TalentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
