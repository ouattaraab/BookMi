<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RevenueCertificateController extends BaseController
{
    public function download(Request $request): Response|\Illuminate\Http\JsonResponse
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:' . now()->year],
        ]);

        $year = (int) $request->query('year');

        /** @var \App\Models\User $user */
        $user = $request->user();
        $talent = $user->talentProfile;

        if (! $talent) {
            return $this->errorResponse('TALENT_PROFILE_NOT_FOUND', 'Profil talent introuvable.', 404);
        }

        // Revenue data for the requested year — PHP-level grouping for DB portability
        $yearlyBookings = BookingRequest::where('talent_profile_id', $talent->id)
            ->where('status', BookingStatus::Completed->value)
            ->whereYear('event_date', $year)
            ->get(['event_date', 'cachet_amount', 'commission_amount', 'total_amount']);

        $monthlyBreakdown = $yearlyBookings
            ->groupBy(fn ($b) => (int) \Carbon\Carbon::parse($b->event_date)->format('m'))
            ->map(fn ($group, $month) => [
                'month' => $month,
                'bookings_count' => $group->count(),
                'gross_amount_xof' => (int) $group->sum('cachet_amount'),
                'commission_xof' => (int) $group->sum('commission_amount'),
                'net_amount_xof' => (int) $group->sum('total_amount'),
            ])
            ->sortKeys()
            ->values()
            ->toArray();

        $totals = (object) [
            'bookings_count' => $yearlyBookings->count(),
            'gross_amount' => $yearlyBookings->sum('cachet_amount'),
            'commission' => $yearlyBookings->sum('commission_amount'),
            'net_amount' => $yearlyBookings->sum('total_amount'),
        ];

        $data = [
            'talent' => [
                'stage_name' => $talent->stage_name,
                'full_name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'year' => $year,
            'generated_at' => now()->format('d/m/Y'),
            'monthly_breakdown' => $monthlyBreakdown,
            'totals' => [
                'bookings_count' => (int) ($totals?->bookings_count ?? 0),
                'gross_amount_xof' => (int) ($totals?->gross_amount ?? 0),
                'commission_xof' => (int) ($totals?->commission ?? 0),
                'net_amount_xof' => (int) ($totals?->net_amount ?? 0),
            ],
        ];

        $pdf = Pdf::loadView('pdf.revenue_certificate', $data)
            ->setPaper('a4', 'portrait');

        $filename = "attestation-revenus-{$year}-{$talent->id}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
