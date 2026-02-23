<?php

namespace App\Http\Controllers\Web\Talent;

use App\Http\Controllers\Controller;
use App\Models\ServicePackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function index(): View
    {
        $profile = auth()->user()->talentProfile;
        if (!$profile) {
            return view('talent.coming-soon', ['title' => 'Packages', 'description' => 'Configurez votre profil d\'abord.']);
        }

        $packages = ServicePackage::where('talent_profile_id', $profile->id)
            ->orderBy('sort_order')
            ->get();

        return view('talent.packages.index', compact('packages', 'profile'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'             => 'required|string|max:150',
            'description'      => 'nullable|string|max:1000',
            'cachet_amount'    => 'required|integer|min:0',
            'duration_minutes' => 'nullable|integer|min:0',
            'type'             => 'nullable|string|max:50',
        ]);

        $profile = auth()->user()->talentProfile;
        $data['talent_profile_id'] = $profile->id;
        $data['is_active']         = true;
        $data['sort_order']        = ServicePackage::where('talent_profile_id', $profile->id)->max('sort_order') + 1;

        ServicePackage::create($data);
        return back()->with('success', 'Package créé avec succès.');
    }

    public function update(int $id, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'             => 'required|string|max:150',
            'description'      => 'nullable|string|max:1000',
            'cachet_amount'    => 'required|integer|min:0',
            'duration_minutes' => 'nullable|integer|min:0',
            'is_active'        => 'boolean',
        ]);

        ServicePackage::where('talent_profile_id', auth()->user()->talentProfile?->id)->findOrFail($id)->update($data);
        return back()->with('success', 'Package mis à jour.');
    }

    public function destroy(int $id): RedirectResponse
    {
        ServicePackage::where('talent_profile_id', auth()->user()->talentProfile?->id)->findOrFail($id)->delete();
        return back()->with('success', 'Package supprimé.');
    }
}
