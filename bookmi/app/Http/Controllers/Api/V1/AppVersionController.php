<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;

class AppVersionController extends Controller
{
    public function index(): JsonResponse
    {
        $featuresRaw = PlatformSetting::get('app_update_features', '[]');
        /** @var list<string> $features */
        $features = json_decode((string) $featuresRaw, true) ?? [];

        return response()->json([
            'maintenance'          => PlatformSetting::bool('maintenance_enabled'),
            'maintenance_message'  => PlatformSetting::get('maintenance_message'),
            'maintenance_end_at'   => PlatformSetting::get('maintenance_end_at'),
            'version_required'     => PlatformSetting::get('app_version_required', '1.0.0'),
            'update_type'          => PlatformSetting::get('app_update_type', 'none'),
            'update_message'       => PlatformSetting::get('app_update_message'),
            'features'             => $features,
            'store_urls'           => [
                'android' => PlatformSetting::get('play_store_url'),
                'ios'     => PlatformSetting::get('app_store_url'),
            ],
        ]);
    }
}
