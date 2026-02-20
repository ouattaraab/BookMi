<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TalentProfile;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $featuredTalents = TalentProfile::with(['user', 'category'])
            ->where('is_verified', true)
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->inRandomOrder()
            ->limit(6)
            ->get();

        return view('home', compact('featuredTalents'));
    }
}
