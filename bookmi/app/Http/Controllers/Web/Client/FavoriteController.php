<?php

namespace App\Http\Controllers\Web\Client;

use App\Http\Controllers\Controller;
use App\Models\TalentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(): View
    {
        $favorites = TalentProfile::whereHas('favoritedBy', fn ($q) => $q->where('users.id', auth()->id()))
            ->with(['user', 'category'])
            ->get();
        return view('client.favorites.index', compact('favorites'));
    }

    public function store(int $talentProfileId): RedirectResponse
    {
        TalentProfile::findOrFail($talentProfileId);
        DB::table('user_favorites')->insertOrIgnore([
            'user_id'           => auth()->id(),
            'talent_profile_id' => $talentProfileId,
        ]);
        return back()->with('success', 'Talent ajouté aux favoris.');
    }

    public function destroy(int $id): RedirectResponse
    {
        DB::table('user_favorites')->where([
            'user_id'           => auth()->id(),
            'talent_profile_id' => $id,
        ])->delete();
        return back()->with('success', 'Talent retiré des favoris.');
    }
}
