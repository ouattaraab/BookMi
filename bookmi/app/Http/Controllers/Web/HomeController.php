<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PrivateExperience;
use App\Models\TalentProfile;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $featuredTalents = TalentProfile::with(['user', 'category'])
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->inRandomOrder()
            ->limit(6)
            ->get();

        // Count par catégorie pour les cards de la landing
        $categoryCount = TalentProfile::with('category')
            ->get()
            ->groupBy(fn ($t) => $t->category?->name ?? 'Autre')
            ->map->count()
            ->toArray();

        $upcomingExperiences = PrivateExperience::with(['talentProfile.user', 'talentProfile.category'])
            ->publiclyVisible()
            ->upcoming()
            ->orderBy('event_date')
            ->limit(6)
            ->get();

        return view('home', compact('featuredTalents', 'categoryCount', 'upcomingExperiences'));
    }
}
