<?php

namespace App\Http\Controllers\Web\Talent;

use App\Enums\TalentLevel;
use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user    = auth()->user();
        $profile = $user->talentProfile;

        if (! $profile) {
            return view('talent.dashboard', [
                'bookings'   => collect(),
                'stats'      => ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'completed' => 0, 'revenue' => 0],
                'profile'    => null,
                'levelData'  => null,
            ]);
        }

        $bookings = BookingRequest::where('talent_profile_id', $profile->id)
            ->with('client')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $stats = [
            'total'     => BookingRequest::where('talent_profile_id', $profile->id)->count(),
            'pending'   => BookingRequest::where('talent_profile_id', $profile->id)->where('status', 'pending')->count(),
            'confirmed' => BookingRequest::where('talent_profile_id', $profile->id)->where('status', 'confirmed')->count(),
            'completed' => BookingRequest::where('talent_profile_id', $profile->id)->where('status', 'completed')->count(),
            'revenue'   => BookingRequest::where('talent_profile_id', $profile->id)
                ->where('status', 'completed')
                ->sum('cachet_amount'),
        ];

        $talentLevel = $profile->talent_level instanceof TalentLevel
            ? $profile->talent_level
            : TalentLevel::from((string) $profile->talent_level);

        $completedCount = BookingRequest::where('talent_profile_id', $profile->id)
            ->where('status', 'completed')
            ->count();

        $levelData = $this->buildLevelData($talentLevel, $completedCount);

        return view('talent.dashboard', compact('bookings', 'stats', 'profile', 'levelData'));
    }

    /**
     * @return array{level: TalentLevel, totalBookings: int, nextLevel: TalentLevel|null, nextMin: int|null, progress: int}
     */
    private function buildLevelData(TalentLevel $level, int $totalBookings): array
    {
        /** @var array<string, int> $levelMins */
        $levelMins = [
            'nouveau'   => (int) config('bookmi.talent.levels.nouveau.min_bookings', 0),
            'confirme'  => (int) config('bookmi.talent.levels.confirme.min_bookings', 6),
            'populaire' => (int) config('bookmi.talent.levels.populaire.min_bookings', 21),
            'elite'     => (int) config('bookmi.talent.levels.elite.min_bookings', 51),
        ];

        $ordered    = [TalentLevel::NOUVEAU, TalentLevel::CONFIRME, TalentLevel::POPULAIRE, TalentLevel::ELITE];
        $currentIdx = 0;
        foreach ($ordered as $idx => $lvl) {
            if ($lvl === $level) {
                $currentIdx = $idx;
                break;
            }
        }

        $nextLevel  = $ordered[$currentIdx + 1] ?? null;
        $currentMin = $levelMins[$level->value] ?? 0;
        $nextMin    = $nextLevel !== null ? ($levelMins[$nextLevel->value] ?? null) : null;

        $progress = 100;
        if ($nextMin !== null) {
            $range    = $nextMin - $currentMin;
            $progress = $range > 0
                ? (int) min(100, (int) round(($totalBookings - $currentMin) / $range * 100))
                : 100;
        }

        return [
            'level'         => $level,
            'totalBookings' => $totalBookings,
            'nextLevel'     => $nextLevel,
            'nextMin'       => $nextMin,
            'progress'      => max(0, $progress),
        ];
    }
}
