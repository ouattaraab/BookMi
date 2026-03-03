<?php

namespace App\Http\Controllers\Web;

use App\Models\PlatformSetting;
use Illuminate\View\View;

class MaintenanceController
{
    public function show(): View
    {
        return view('maintenance', [
            'message' => PlatformSetting::get('maintenance_message', 'Maintenance en cours.'),
            'end_at'  => PlatformSetting::get('maintenance_end_at'),
        ]);
    }
}
