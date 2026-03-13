<?php

namespace App\Filament\Resources\PrivateExperienceResource\Pages;

use App\Filament\Resources\PrivateExperienceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPrivateExperiences extends ListRecords
{
    protected static string $resource = PrivateExperienceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
