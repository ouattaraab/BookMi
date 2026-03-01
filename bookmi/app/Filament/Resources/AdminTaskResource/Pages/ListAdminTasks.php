<?php

namespace App\Filament\Resources\AdminTaskResource\Pages;

use App\Filament\Resources\AdminTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdminTasks extends ListRecords
{
    protected static string $resource = AdminTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nouvelle tâche'),
        ];
    }
}
