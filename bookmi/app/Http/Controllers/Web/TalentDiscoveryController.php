<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TalentProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TalentDiscoveryController extends Controller
{
    public function index(Request $request): View
    {
        $query = TalentProfile::with(['user', 'category', 'subcategory', 'servicePackages'])
            ->where('is_verified', true)
            ->whereHas('user', fn ($q) => $q->where('is_active', true));

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('stage_name', 'like', "%{$search}%")
                  ->orWhere('bio', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%"));
            });
        }

        if ($category = $request->string('category')->trim()->value()) {
            $query->whereHas('category', fn ($q) => $q->where('name', $category));
        }

        if ($city = $request->string('city')->trim()->value()) {
            $query->where('city', 'like', "%{$city}%");
        }

        $sort = $request->string('sort')->value() ?: 'recent';
        match ($sort) {
            'price_asc'  => $query->orderByRaw('(SELECT MIN(price) FROM service_packages WHERE talent_profile_id = talent_profiles.id)'),
            'price_desc' => $query->orderByRaw('(SELECT MAX(price) FROM service_packages WHERE talent_profile_id = talent_profiles.id) DESC'),
            default      => $query->orderByDesc('created_at'),
        };

        $talents = $query->paginate(12)->withQueryString();

        return view('talents.index', compact('talents'));
    }
}
