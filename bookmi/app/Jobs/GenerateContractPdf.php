<?php

namespace App\Jobs;

use App\Models\BookingRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateContractPdf implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly BookingRequest $booking,
    ) {
    }

    public function handle(): void
    {
        $booking = $this->booking->load([
            'client:id,name,email,phone',
            'talentProfile:id,stage_name',
            'servicePackage:id,name,type,description,inclusions,duration_minutes',
        ]);

        $pdf = Pdf::loadView('pdf.booking-contract', ['booking' => $booking])
            ->setPaper('a4', 'portrait');

        $path = "contracts/booking-{$booking->id}.pdf";

        Storage::disk('local')->put($path, $pdf->output());

        $booking->update(['contract_path' => $path]);
    }
}
