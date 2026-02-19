<?php

namespace App\Filament\Resources\TalentProfileResource\Pages;

use App\Filament\Resources\TalentProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTalentProfile extends EditRecord
{
    protected static string $resource = TalentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
