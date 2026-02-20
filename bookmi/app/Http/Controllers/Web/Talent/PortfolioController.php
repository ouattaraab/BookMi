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
            'file'    => 'required|file|mimes:jpg,jpeg,png,gif,mp4,mov|max:51200',
            'caption' => 'nullable|string|max:255',
        ]);

        $profile = auth()->user()->talentProfile;
        $file    = $request->file('file');
        $path    = $file->store('portfolio/' . $profile->id, 'public');
        $mime    = $file->getMimeType();
        $type    = str_starts_with($mime, 'video') ? 'video' : 'image';

        PortfolioItem::create([
            'talent_profile_id' => $profile->id,
            'media_type'        => $type,
            'original_path'     => $path,
            'caption'           => $request->caption,
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

        $profile = auth()->user()->talentProfile;
        PortfolioItem::create([
            'talent_profile_id' => $profile->id,
            'media_type'        => 'link',
            'link_url'          => $request->link_url,
            'link_platform'     => $request->link_platform ?? 'other',
            'caption'           => $request->caption,
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
}
