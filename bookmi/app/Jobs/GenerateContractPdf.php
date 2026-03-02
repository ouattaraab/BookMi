<?php

namespace App\Jobs;

use App\Models\BookingRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateContractPdf implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var int Retry up to 3 times on transient failures. */
    public int $tries = 3;

    /** @var int Max execution time in seconds (PDF generation can be slow). */
    public int $timeout = 120;

    /** @var int Delay between retries. */
    public int $backoff = 30;

    public function __construct(
        public readonly BookingRequest $booking,
    ) {
        $this->onQueue('media');
    }

    public function handle(): void
    {
        try {
            $booking = $this->booking->load([
                'client:id,first_name,last_name,email,phone',
                'talentProfile:id,stage_name',
                'servicePackage:id,name,type,description,inclusions,duration_minutes',
            ]);

            $pdf = Pdf::loadView('pdf.booking-contract', ['booking' => $booking])
                ->setPaper('a4', 'portrait');

            $path = "contracts/booking-{$booking->id}.pdf";

            Storage::disk('local')->put($path, $pdf->output());

            $booking->update(['contract_path' => $path]);
        } catch (\Throwable $e) {
            Log::error('GenerateContractPdf failed', [
                'booking_id' => $this->booking->id,
                'attempt'    => $this->attempts(),
                'error'      => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
