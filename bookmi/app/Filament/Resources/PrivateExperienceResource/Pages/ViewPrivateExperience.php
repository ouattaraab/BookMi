<?php

namespace App\Filament\Resources\PrivateExperienceResource\Pages;

use App\Filament\Resources\PrivateExperienceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPrivateExperience extends ViewRecord
{
    protected static string $resource = PrivateExperienceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
