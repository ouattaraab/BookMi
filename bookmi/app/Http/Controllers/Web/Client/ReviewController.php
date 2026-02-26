<?php

namespace App\Http\Controllers\Web\Client;

use App\Enums\ReviewType;
use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviewService)
    {
    }

    public function store(int $id, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $booking = BookingRequest::where('client_id', auth()->id())
            ->whereIn('status', ['confirmed', 'completed'])
            ->findOrFail($id);

        try {
            $this->reviewService->submit(
                $booking,
                auth()->user(),
                ReviewType::ClientToTalent,
                (int) $validated['rating'],
                $validated['comment'] ?? null,
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', implode(' ', $e->errors()['booking'] ?? $e->errors()['type'] ?? ['Impossible de soumettre cet avis.']));
        }

        return back()->with('success', 'Votre avis a été publié. Merci !');
    }
}
