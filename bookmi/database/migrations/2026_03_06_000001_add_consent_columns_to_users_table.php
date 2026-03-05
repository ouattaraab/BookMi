<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('cgu_version_accepted', 20)->nullable()->after('referred_by_code');
            $table->boolean('marketing_opt_in')->default(false)->after('cgu_version_accepted');
            $table->boolean('image_rights_opt_in')->default(false)->after('marketing_opt_in');
            $table->boolean('survey_opt_in')->default(false)->after('image_rights_opt_in');
            $table->boolean('geolocation_opt_in')->default(false)->after('survey_opt_in');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'cgu_version_accepted',
                'marketing_opt_in',
                'image_rights_opt_in',
                'survey_opt_in',
                'geolocation_opt_in',
            ]);
        });
    }
};
