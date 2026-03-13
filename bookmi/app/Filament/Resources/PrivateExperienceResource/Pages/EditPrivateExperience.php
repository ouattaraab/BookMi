<?php

namespace App\Filament\Resources\PrivateExperienceResource\Pages;

use App\Filament\Resources\PrivateExperienceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPrivateExperience extends EditRecord
{
    protected static string $resource = PrivateExperienceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
