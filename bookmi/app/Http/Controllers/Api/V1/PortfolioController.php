<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\CompressPortfolioImage;
use App\Models\BookingRequest;
use App\Models\PortfolioItem;
use App\Models\TalentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PortfolioController extends Controller
{
    /**
     * GET /talent_profiles/{talentProfile}/portfolio
     *
     * Public endpoint — list approved portfolio items for a talent.
     * Items submitted by the talent are always shown.
     * Items submitted by a client are shown only if approved by the talent.
     */
    public function index(TalentProfile $talentProfile): JsonResponse
    {
        $items = PortfolioItem::where('talent_profile_id', $talentProfile->id)
            ->where(function ($q) {
                $q->where('submitted_by_client', false)
                    ->orWhere(function ($q2) {
                        $q2->where('submitted_by_client', true)
                            ->where('is_approved', true);
                    });
            })
            ->latest()
            ->get()
            ->map(fn ($item) => $this->format($item));

        return response()->json(['data' => $items]);
    }

    /**
     * POST /talent_profiles/me/portfolio
     *
     * Talent uploads a portfolio item (image or video) OR adds an external link.
     * Images are queued for compression.
     *
     * For file uploads: send multipart/form-data with 'file' field.
     * For external links: send JSON with 'link_url' and 'link_platform' fields.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file'               => ['required_without:link_url', 'file', 'max:20480', 'mimes:jpg,jpeg,png,gif,mp4,mov'],
            'link_url'           => ['required_without:file', 'nullable', 'url', 'max:500'],
            'link_platform'      => ['required_with:link_url', 'nullable', 'in:youtube,deezer,apple_music,facebook,tiktok,soundcloud'],
            'caption'            => ['nullable', 'string', 'max:255'],
            'booking_request_id' => ['nullable', 'integer', 'exists:booking_requests,id'],
        ]);

        $user    = $request->user();
        $profile = TalentProfile::where('user_id', $user->id)->firstOrFail();

        // External link (no file upload)
        if ($request->filled('link_url')) {
            $item = PortfolioItem::create([
                'talent_profile_id'  => $profile->id,
                'booking_request_id' => $request->input('booking_request_id'),
                'media_type'         => 'link',
                'original_path'      => $request->input('link_url'),
                'link_url'           => $request->input('link_url'),
                'link_platform'      => $request->input('link_platform'),
                'caption'            => $request->input('caption'),
                'is_compressed'      => false,
                'submitted_by_client' => false,
            ]);

            return response()->json(['data' => $this->format($item)], 201);
        }

        // File upload (image or video)
        /** @var \Illuminate\Http\UploadedFile $file */
        $file      = $request->file('file');
        $mediaType = str_starts_with($file->getMimeType(), 'video') ? 'video' : 'image';
        $path      = $file->store('uploads/portfolio', 'public');

        if ($path === false) {
            throw ValidationException::withMessages([
                'file' => 'Failed to store the uploaded file.',
            ]);
        }

        $item = PortfolioItem::create([
            'talent_profile_id'  => $profile->id,
            'booking_request_id' => $request->input('booking_request_id'),
            'media_type'         => $mediaType,
            'original_path'      => $path,
            'caption'            => $request->input('caption'),
            'is_compressed'      => false,
            'submitted_by_client' => false,
        ]);

        if ($mediaType === 'image') {
            CompressPortfolioImage::dispatch($item);
        }

        return response()->json(['data' => $this->format($item)], 201);
    }

    /**
     * POST /booking_requests/{booking}/client-portfolio
     *
     * Client submits photos/videos after a completed prestation.
     * The talent must approve each item before it appears publicly.
     */
    public function storeClientSubmission(Request $request, BookingRequest $booking): JsonResponse
    {
        $user = $request->user();

        // Ensure the authenticated user is the client of this booking
        if ($booking->client_id !== $user->id) {
            return response()->json([
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Vous n\'êtes pas le client de cette réservation.'],
            ], 403);
        }

        // Only allow submissions for completed bookings
        if ($booking->status->value !== 'completed') {
            return response()->json([
                'error' => ['code' => 'BOOKING_NOT_COMPLETED', 'message' => 'Vous pouvez soumettre des médias uniquement pour des prestations terminées.'],
            ], 422);
        }

        $request->validate([
            'file'    => ['required', 'file', 'max:20480', 'mimes:jpg,jpeg,png,gif,mp4,mov'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file      = $request->file('file');
        $mediaType = str_starts_with($file->getMimeType(), 'video') ? 'video' : 'image';
        $path      = $file->store('uploads/portfolio/client', 'public');

        if ($path === false) {
            throw ValidationException::withMessages([
                'file' => 'Échec du stockage du fichier.',
            ]);
        }

        $item = PortfolioItem::create([
            'talent_profile_id'    => $booking->talent_profile_id,
            'booking_request_id'   => $booking->id,
            'media_type'           => $mediaType,
            'original_path'        => $path,
            'caption'              => $request->input('caption'),
            'is_compressed'        => false,
            'submitted_by_client'  => true,
            'submitted_by_user_id' => $user->id,
            'is_approved'          => null, // pending talent review
        ]);

        if ($mediaType === 'image') {
            CompressPortfolioImage::dispatch($item);
        }

        return response()->json([
            'data'    => $this->format($item),
            'message' => 'Média soumis avec succès. En attente de validation par le talent.',
        ], 201);
    }

    /**
     * GET /talent_profiles/me/portfolio/pending
     *
     * Talent sees client-submitted items awaiting approval.
     */
    public function pendingSubmissions(Request $request): JsonResponse
    {
        $user    = $request->user();
        $profile = TalentProfile::where('user_id', $user->id)->firstOrFail();

        $items = PortfolioItem::where('talent_profile_id', $profile->id)
            ->where('submitted_by_client', true)
            ->whereNull('is_approved')
            ->latest()
            ->get()
            ->map(fn ($item) => $this->format($item));

        return response()->json(['data' => $items]);
    }

    /**
     * POST /talent_profiles/me/portfolio/{portfolioItem}/approve
     *
     * Talent approves a client-submitted item — makes it publicly visible.
     */
    public function approve(Request $request, PortfolioItem $portfolioItem): JsonResponse
    {
        $user    = $request->user();
        $profile = TalentProfile::where('user_id', $user->id)->first();

        if (! $profile || $portfolioItem->talent_profile_id !== $profile->id) {
            return response()->json([
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Ce média n\'appartient pas à votre profil.'],
            ], 403);
        }

        if (! $portfolioItem->submitted_by_client) {
            return response()->json([
                'error' => ['code' => 'NOT_CLIENT_SUBMISSION', 'message' => 'Ce média a été ajouté par le talent lui-même.'],
            ], 422);
        }

        $portfolioItem->update(['is_approved' => true]);

        return response()->json(['data' => $this->format($portfolioItem->fresh())]);
    }

    /**
     * POST /talent_profiles/me/portfolio/{portfolioItem}/reject
     *
     * Talent rejects a client-submitted item — deletes it.
     */
    public function reject(Request $request, PortfolioItem $portfolioItem): JsonResponse
    {
        $user    = $request->user();
        $profile = TalentProfile::where('user_id', $user->id)->first();

        if (! $profile || $portfolioItem->talent_profile_id !== $profile->id) {
            return response()->json([
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Ce média n\'appartient pas à votre profil.'],
            ], 403);
        }

        if (! $portfolioItem->submitted_by_client) {
            return response()->json([
                'error' => ['code' => 'NOT_CLIENT_SUBMISSION', 'message' => 'Ce média a été ajouté par le talent lui-même.'],
            ], 422);
        }

        // Delete file from storage
        if ($portfolioItem->media_type !== 'link') {
            Storage::disk('public')->delete($portfolioItem->original_path);
            if ($portfolioItem->compressed_path) {
                Storage::disk('public')->delete($portfolioItem->compressed_path);
            }
        }

        $portfolioItem->delete();

        return response()->json(null, 204);
    }

    /**
     * DELETE /talent_profiles/me/portfolio/{portfolioItem}
     */
    public function destroy(Request $request, PortfolioItem $portfolioItem): JsonResponse
    {
        $user    = $request->user();
        $profile = TalentProfile::where('user_id', $user->id)->first();

        if (! $profile || $portfolioItem->talent_profile_id !== $profile->id) {
            return response()->json([
                'error' => [
                    'code'    => 'FORBIDDEN',
                    'message' => 'You do not own this portfolio item.',
                ],
            ], 403);
        }

        if ($portfolioItem->media_type !== 'link') {
            Storage::disk('public')->delete($portfolioItem->original_path);
            if ($portfolioItem->compressed_path) {
                Storage::disk('public')->delete($portfolioItem->compressed_path);
            }
        }

        $portfolioItem->delete();

        return response()->json(null, 204);
    }

    /**
     * GET /talent_profiles/me/portfolio
     *
     * Returns ALL portfolio items for the authenticated talent (including unapproved client submissions).
     */
    public function indexOwn(Request $request): JsonResponse
    {
        $user    = $request->user();
        $profile = TalentProfile::where('user_id', $user->id)->firstOrFail();

        $items = PortfolioItem::where('talent_profile_id', $profile->id)
            ->latest()
            ->get()
            ->map(fn ($item) => $this->format($item));

        return response()->json(['data' => $items]);
    }

    /**
     * PATCH /talent_profiles/me/portfolio/{portfolioItem}
     *
     * Updates caption of a portfolio item owned by the authenticated talent.
     */
    public function update(Request $request, PortfolioItem $portfolioItem): JsonResponse
    {
        $user    = $request->user();
        $profile = TalentProfile::where('user_id', $user->id)->first();

        if (! $profile || $portfolioItem->talent_profile_id !== $profile->id) {
            return response()->json([
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Ce média n\'appartient pas à votre profil.'],
            ], 403);
        }

        $request->validate([
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $portfolioItem->update(['caption' => $request->input('caption')]);

        return response()->json(['data' => $this->format($portfolioItem->fresh())]);
    }

    /**
     * @return array<string, mixed>
     */
    private function format(PortfolioItem $item): array
    {
        return [
            'id'                    => $item->id,
            'talent_profile_id'     => $item->talent_profile_id,
            'booking_request_id'    => $item->booking_request_id,
            'media_type'            => $item->media_type,
            'url'                   => $item->media_type === 'link'
                ? $item->link_url
                : Storage::disk('public')->url($item->displayPath()),
            'link_url'              => $item->link_url,
            'link_platform'         => $item->link_platform,
            'caption'               => $item->caption,
            'is_compressed'         => $item->is_compressed,
            'submitted_by_client'   => $item->submitted_by_client ?? false,
            'submitted_by_user_id'  => $item->submitted_by_user_id,
            'is_approved'           => $item->is_approved,
            'created_at'            => $item->created_at?->toISOString(),
        ];
    }
}
