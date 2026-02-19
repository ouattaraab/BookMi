<?php

namespace App\Filament\Resources\BookingRequestResource\Pages;

use App\Filament\Resources\BookingRequestResource;
use Filament\Resources\Pages\ViewRecord;

class ViewBookingRequest extends ViewRecord
{
    protected static string $resource = BookingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
