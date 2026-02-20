<?php
namespace App\Http\Controllers\Web\Talent;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $profile = auth()->user()->talentProfile;
        $user    = auth()->user();
        return view('talent.profile.edit', compact('profile', 'user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'stage_name'    => 'required|string|max:100',
            'bio'           => 'nullable|string|max:2000',
            'city'          => 'nullable|string|max:100',
            'cachet_amount' => 'nullable|integer|min:0',
            'social_links'  => 'nullable|array',
        ]);

        $profile = auth()->user()->talentProfile;

        if ($profile) {
            $profile->update($data);
        } else {
            auth()->user()->talentProfile()->create(array_merge($data, ['user_id' => auth()->id()]));
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

        if (! $profile) {
            return back()->withErrors(['photo' => 'Profil introuvable.']);
        }

        // Delete old photo if exists
        if ($profile->profile_photo) {
            Storage::disk('public')->delete($profile->profile_photo);
        }

        $path = $request->file('photo')->store('profiles', 'public');
        $profile->update(['profile_photo' => $path]);

        return back()->with('success', 'Photo de profil mise à jour.');
    }
}
