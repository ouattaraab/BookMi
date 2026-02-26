<?php

namespace App\Http\Controllers\Web\Talent;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RevenueCertificateController extends Controller
{
    public function index(): View
    {
        $user    = auth()->user();
        $profile = $user->talentProfile;

        $availableYears = $profile
            ? BookingRequest::where('talent_profile_id', $profile->id)
                ->where('status', BookingStatus::Completed->value)
                ->selectRaw('YEAR(event_date) as year')
                ->distinct()
                ->orderByDesc('year')
                ->pluck('year')
                ->toArray()
            : [];

        return view('talent.revenue-certificate.index', compact('availableYears'));
    }

    public function download(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:' . now()->year],
        ]);

        $year    = (int) $request->query('year');
        $user    = auth()->user();
        $talent  = $user->talentProfile;

        if (! $talent) {
            return back()->with('error', 'Profil talent introuvable.');
        }

        $yearlyBookings = BookingRequest::where('talent_profile_id', $talent->id)
            ->where('status', BookingStatus::Completed->value)
            ->whereYear('event_date', $year)
            ->get(['event_date', 'cachet_amount', 'commission_amount', 'total_amount']);

        $monthlyBreakdown = $yearlyBookings
            ->groupBy(fn ($b) => (int) \Carbon\Carbon::parse($b->event_date)->format('m'))
            ->map(fn ($group, $month) => [
                'month'            => $month,
                'bookings_count'   => $group->count(),
                'gross_amount_xof' => (int) $group->sum('cachet_amount'),
                'commission_xof'   => (int) $group->sum('commission_amount'),
                'net_amount_xof'   => (int) $group->sum('total_amount'),
            ])
            ->sortKeys()
            ->values()
            ->toArray();

        $totals = (object) [
            'bookings_count' => $yearlyBookings->count(),
            'gross_amount'   => $yearlyBookings->sum('cachet_amount'),
            'commission'     => $yearlyBookings->sum('commission_amount'),
            'net_amount'     => $yearlyBookings->sum('total_amount'),
        ];

        $data = [
            'talent' => [
                'stage_name' => $talent->stage_name,
                'full_name'  => $user->first_name . ' ' . $user->last_name,
                'email'      => $user->email,
                'phone'      => $user->phone,
            ],
            'year'         => $year,
            'generated_at' => now()->format('d/m/Y'),
            'monthly_breakdown' => $monthlyBreakdown,
            'totals' => [
                'bookings_count'   => (int) ($totals->bookings_count ?? 0),
                'gross_amount_xof' => (int) ($totals->gross_amount ?? 0),
                'commission_xof'   => (int) ($totals->commission ?? 0),
                'net_amount_xof'   => (int) ($totals->net_amount ?? 0),
            ],
        ];

        $pdf      = Pdf::loadView('pdf.revenue_certificate', $data)->setPaper('a4', 'portrait');
        $filename = "attestation-revenus-{$year}-{$talent->id}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
