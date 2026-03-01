<?php

namespace App\Filament\Resources\FlaggedMessageResource\Pages;

use App\Filament\Resources\FlaggedMessageResource;
use Filament\Resources\Pages\ListRecords;

class ListFlaggedMessages extends ListRecords
{
    protected static string $resource = FlaggedMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
