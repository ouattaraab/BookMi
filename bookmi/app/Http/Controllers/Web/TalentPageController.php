<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\TalentProfileService;
use Illuminate\View\View;

class TalentPageController extends Controller
{
    public function __construct(
        private readonly TalentProfileService $talentProfileService,
    ) {
    }

    public function show(string $slug): View
    {
        $result = $this->talentProfileService->getPublicProfile($slug);

        if ($result === null) {
            abort(404);
        }

        return view('web.talent.show', [
            'profile' => $result['profile'],
            'similarTalents' => $result['similar_talents'],
        ]);
    }
}
