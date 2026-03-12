<?php

namespace App\Filament\Resources\HoneypotLogResource\Pages;

use App\Filament\Resources\HoneypotLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHoneypotLogs extends ListRecords
{
    protected static string $resource = HoneypotLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('clear_all')
                ->label('Tout effacer')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Effacer tous les logs honeypot')
                ->modalDescription('Cette action supprimera tous les logs de tentatives de bots. Les IPs bloquées ne seront PAS débloquées.')
                ->action(fn () => \App\Models\HoneypotLog::truncate()),
        ];
    }
}
