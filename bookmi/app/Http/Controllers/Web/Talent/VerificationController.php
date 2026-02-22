<?php
namespace App\Http\Controllers\Web\Talent;

use App\Http\Controllers\Controller;
use App\Models\IdentityVerification;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function index(): View
    {
        $verifications = IdentityVerification::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        return view('talent.verification.index', compact('verifications'));
    }

    public function submit(Request $request): RedirectResponse
    {
        $request->validate([
            'document_type' => 'required|in:id_card,passport,driver_license',
            'document'      => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $path = $request->file('document')->store('verifications/' . auth()->id(), 'public');

        IdentityVerification::create([
            'user_id'             => auth()->id(),
            'document_type'       => $request->document_type,
            'stored_path'         => $path,
            'original_mime'       => $request->file('document')->getMimeType(),
            'verification_status' => 'pending',
        ]);

        ActivityLogger::log('verification.submitted', null, [
            'document_type' => $request->document_type,
            'mime'          => $request->file('document')->getMimeType(),
        ]);

        return back()->with('success', 'Document soumis. Notre équipe va l\'examiner sous 48h.');
    }
}
