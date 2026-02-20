<?php

namespace App\Http\Controllers\Web\Talent;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class CertificateController extends Controller
{
    public function download(int $bookingId): RedirectResponse
    {
        return back()->with('info', 'Téléchargement d\'attestation en cours de développement.');
    }
}
