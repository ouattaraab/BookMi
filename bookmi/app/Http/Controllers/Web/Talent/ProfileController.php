<?php
namespace App\Http\Controllers\Web\Talent;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $profile    = auth()->user()->talentProfile;
        $user       = auth()->user();
        $categories = Category::orderBy('name')->get();
        return view('talent.profile.edit', compact('profile', 'user', 'categories'));
    }

    public function update(Request $request): RedirectResponse
    {
        $profile = auth()->user()->talentProfile;

        $data = $request->validate([
            'stage_name'              => 'required|string|max:100',
            'category_id'             => 'required|exists:categories,id',
            'bio'                     => 'nullable|string|max:2000',
            'city'                    => 'nullable|string|max:100',
            'cachet_amount'           => 'nullable|integer|min:0',
            'social_links.instagram'  => 'nullable|url|max:300',
            'social_links.facebook'   => 'nullable|url|max:300',
            'social_links.youtube'    => 'nullable|url|max:300',
            'social_links.tiktok'     => 'nullable|url|max:300',
        ]);

        // Ensure cachet_amount always has a value (NOT NULL in DB)
        $data['cachet_amount'] = $data['cachet_amount'] ?? 0;

        // Build social_links array (only non-empty values)
        $socialLinks = array_filter($request->input('social_links', []), fn ($v) => ! empty($v));
        $data['social_links'] = $socialLinks ?: null;
        unset(
            $data['social_links.instagram'],
            $data['social_links.facebook'],
            $data['social_links.youtube'],
            $data['social_links.tiktok'],
        );

        if ($profile) {
            $profile->update($data);
        } else {
            auth()->user()->talentProfile()->create($data);
        }

        if ($request->filled('first_name') || $request->filled('last_name')) {
            auth()->user()->update($request->only(['first_name', 'last_name']));
        }

        return back()->with('success', 'Profil mis à jour.');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg,png,webp|max:4096',
        ]);

        $profile = auth()->user()->talentProfile;

        // Auto-create a minimal profile if it doesn't exist yet
        if (! $profile) {
            return back()->withErrors(['photo' => 'Veuillez d\'abord enregistrer votre profil (nom de scène et catégorie) avant d\'uploader une photo.']);
        }

        // Delete old photo
        if ($profile->profile_photo) {
            Storage::disk('public')->delete($profile->profile_photo);
        }

        // Store original temporarily to get the path
        $uploaded = $request->file('photo');
        $filename = 'profiles/' . $profile->id . '_' . time() . '.jpg';
        $storagePath = storage_path('app/public/' . $filename);

        // Ensure directory exists
        @mkdir(dirname($storagePath), 0755, true);

        // Optimize with GD (resize to max 800px wide, JPEG quality 85)
        $this->optimizeImage($uploaded->getRealPath(), $storagePath, 800, 85);

        $profile->update(['profile_photo' => $filename]);
        ActivityLogger::log('talent.profile.photo_updated', $profile);

        return back()->with('success', 'Photo de profil mise à jour.');
    }

    /**
     * Resize and re-encode an image using GD.
     * Always outputs JPEG for consistency and compression efficiency.
     */
    private function optimizeImage(string $source, string $dest, int $maxWidth, int $quality): void
    {
        $info = @getimagesize($source);
        if (! $info) {
            // Unreadable image — copy as-is
            copy($source, $dest);
            return;
        }

        [$width, $height, $type] = $info;

        $src = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($source),
            IMAGETYPE_PNG  => imagecreatefrompng($source),
            IMAGETYPE_WEBP => imagecreatefromwebp($source),
            IMAGETYPE_GIF  => imagecreatefromgif($source),
            default        => null,
        };

        if (! $src) {
            copy($source, $dest);
            return;
        }

        // Calculate target dimensions
        if ($width > $maxWidth) {
            $newWidth  = $maxWidth;
            $newHeight = (int) round($height * $maxWidth / $width);
        } else {
            $newWidth  = $width;
            $newHeight = $height;
        }

        $dst = imagecreatetruecolor($newWidth, $newHeight);

        // White background for transparency (PNG/GIF)
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagejpeg($dst, $dest, $quality);

        imagedestroy($src);
        imagedestroy($dst);
    }
}
