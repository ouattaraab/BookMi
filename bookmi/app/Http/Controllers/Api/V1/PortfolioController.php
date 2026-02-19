<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\CompressPortfolioImage;
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
     * Public endpoint — list portfolio items for a talent.
     */
    public function index(TalentProfile $talentProfile): JsonResponse
    {
        $items = PortfolioItem::where('talent_profile_id', $talentProfile->id)
            ->latest()
            ->get()
            ->map(fn ($item) => $this->format($item));

        return response()->json(['data' => $items]);
    }

    /**
     * POST /talent_profiles/me/portfolio
     *
     * Talent uploads a portfolio item (image or video).
     * Images are queued for compression.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file'               => ['required', 'file', 'max:20480', 'mimes:jpg,jpeg,png,gif,mp4,mov'],
            'caption'            => ['nullable', 'string', 'max:255'],
            'booking_request_id' => ['nullable', 'integer', 'exists:booking_requests,id'],
        ]);

        $user    = $request->user();
        $profile = TalentProfile::where('user_id', $user->id)->firstOrFail();

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
            'booking_request_id' => $validated['booking_request_id'] ?? null,
            'media_type'         => $mediaType,
            'original_path'      => $path,
            'caption'            => $validated['caption'] ?? null,
            'is_compressed'      => false,
        ]);

        if ($mediaType === 'image') {
            CompressPortfolioImage::dispatch($item);
        }

        return response()->json(['data' => $this->format($item)], 201);
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

        Storage::disk('public')->delete($portfolioItem->original_path);
        if ($portfolioItem->compressed_path) {
            Storage::disk('public')->delete($portfolioItem->compressed_path);
        }

        $portfolioItem->delete();

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function format(PortfolioItem $item): array
    {
        return [
            'id'                 => $item->id,
            'talent_profile_id'  => $item->talent_profile_id,
            'booking_request_id' => $item->booking_request_id,
            'media_type'         => $item->media_type,
            'url'                => Storage::disk('public')->url($item->displayPath()),
            'caption'            => $item->caption,
            'is_compressed'      => $item->is_compressed,
            'created_at'         => $item->created_at?->toISOString(),
        ];
    }
}
