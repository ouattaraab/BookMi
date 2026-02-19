<?php

namespace App\Filament\Pages;

use App\Models\BookingRequest;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class OperationsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Opérations Jour J';
    protected static ?string $title = 'Suivi Opérations — Jour J';
    protected static ?int $navigationSort = 11;

    protected static string $view = 'filament.pages.operations-page';

    public Collection $todayBookings;
    public int $checkedIn = 0;
    public int $pendingCheckin = 0;

    public function mount(): void
    {
        $this->todayBookings = BookingRequest::with(['talentProfile.user', 'client'])
            ->whereDate('event_date', today())
            ->where('status', 'confirmed')
            ->get();

        $this->checkedIn = $this->todayBookings->filter(function ($booking) {
            return $booking->trackingEvents()->where('type', 'checkin')->exists();
        })->count();

        $this->pendingCheckin = $this->todayBookings->count() - $this->checkedIn;
    }
}
