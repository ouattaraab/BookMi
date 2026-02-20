<?php
namespace App\Http\Controllers\Web\Talent;

use App\Http\Controllers\Controller;
use App\Models\PortfolioItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function index(): View
    {
        $profile = auth()->user()->talentProfile;
        if (!$profile) return view('talent.coming-soon', ['title' => 'Portfolio', 'description' => 'Configurez votre profil d\'abord.']);

        $items = PortfolioItem::where('talent_profile_id', $profile->id)
            ->orderByDesc('created_at')
            ->get();

        return view('talent.portfolio.index', compact('items', 'profile'));
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'file'    => 'required|file|mimes:jpg,jpeg,png,webp,gif,mp4,mov|max:51200',
            'caption' => 'nullable|string|max:255',
        ]);

        $profile = auth()->user()->talentProfile;
        $file    = $request->file('file');
        $mime    = $file->getMimeType();
        $isImage = str_starts_with($mime, 'image');
        $isVideo = str_starts_with($mime, 'video');

        if ($isImage) {
            // Optimize image with GD before storing
            $filename    = 'portfolio/' . $profile->id . '/' . uniqid() . '.jpg';
            $storagePath = storage_path('app/public/' . $filename);
            @mkdir(dirname($storagePath), 0755, true);
            $this->optimizeImage($file->getRealPath(), $storagePath, 1200, 82);
            $path = $filename;
        } else {
            // Video — store as-is
            $path = $file->store('portfolio/' . $profile->id, 'public');
        }

        PortfolioItem::create([
            'talent_profile_id' => $profile->id,
            'media_type'        => $isVideo ? 'video' : 'image',
            'original_path'     => $path,
            'caption'           => $request->caption,
            'is_approved'       => true,
        ]);

        return back()->with('success', 'Média ajouté au portfolio.');
    }

    public function addLink(Request $request): RedirectResponse
    {
        $request->validate([
            'link_url'      => 'required|url|max:500',
            'link_platform' => 'nullable|string|max:30',
            'caption'       => 'nullable|string|max:255',
        ]);

        // Auto-detect YouTube
        $url      = $request->link_url;
        $platform = $request->link_platform ?? 'other';
        if (preg_match('/youtu(?:be\.com|\.be)/i', $url)) {
            $platform = 'youtube';
        }

        $profile = auth()->user()->talentProfile;
        PortfolioItem::create([
            'talent_profile_id' => $profile->id,
            'media_type'        => 'link',
            'link_url'          => $url,
            'link_platform'     => $platform,
            'caption'           => $request->caption,
            'is_approved'       => true,
        ]);

        return back()->with('success', 'Lien ajouté au portfolio.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $item = PortfolioItem::where('talent_profile_id', auth()->user()->talentProfile?->id)->findOrFail($id);
        if ($item->original_path) Storage::disk('public')->delete($item->original_path);
        $item->delete();
        return back()->with('success', 'Élément supprimé.');
    }

    /**
     * Resize + re-encode image with GD. Always outputs JPEG.
     */
    private function optimizeImage(string $source, string $dest, int $maxWidth, int $quality): void
    {
        $info = @getimagesize($source);
        if (! $info) { copy($source, $dest); return; }

        [$width, $height, $type] = $info;

        $src = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($source),
            IMAGETYPE_PNG  => imagecreatefrompng($source),
            IMAGETYPE_WEBP => imagecreatefromwebp($source),
            IMAGETYPE_GIF  => imagecreatefromgif($source),
            default        => null,
        };

        if (! $src) { copy($source, $dest); return; }

        if ($width > $maxWidth) {
            $newWidth  = $maxWidth;
            $newHeight = (int) round($height * $maxWidth / $width);
        } else {
            $newWidth  = $width;
            $newHeight = $height;
        }

        $dst   = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagejpeg($dst, $dest, $quality);

        imagedestroy($src);
        imagedestroy($dst);
    }
}
