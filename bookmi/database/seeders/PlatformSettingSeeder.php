<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'maintenance_enabled',    'value' => 'false',                                    'type' => 'bool'],
            ['key' => 'maintenance_message',     'value' => 'Nous effectuons une maintenance. Merci de votre patience.', 'type' => 'string'],
            ['key' => 'maintenance_end_at',      'value' => null,                                       'type' => 'string'],
            ['key' => 'app_version_required',    'value' => '1.0.0',                                    'type' => 'string'],
            ['key' => 'app_update_type',         'value' => 'none',                                     'type' => 'string'],
            ['key' => 'app_update_message',      'value' => 'Une nouvelle version est disponible.',     'type' => 'string'],
            ['key' => 'app_update_features',     'value' => '[]',                                       'type' => 'json'],
            ['key' => 'play_store_url',          'value' => 'https://play.google.com/store/apps/details?id=com.bookmi.app', 'type' => 'string'],
            ['key' => 'app_store_url',           'value' => 'https://apps.apple.com/app/bookmi/id0000000000', 'type' => 'string'],
        ];

        foreach ($defaults as $row) {
            PlatformSetting::updateOrCreate(['key' => $row['key']], $row);
        }
    }
}
