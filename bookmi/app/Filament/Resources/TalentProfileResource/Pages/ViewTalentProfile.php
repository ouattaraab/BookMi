<?php

namespace App\Filament\Resources\TalentProfileResource\Pages;

use App\Filament\Resources\TalentProfileResource;
use Filament\Resources\Pages\ViewRecord;

class ViewTalentProfile extends ViewRecord
{
    protected static string $resource = TalentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
