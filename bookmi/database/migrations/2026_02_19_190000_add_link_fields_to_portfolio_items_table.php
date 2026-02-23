<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('portfolio_items', function (Blueprint $table) {
            $table->string('link_url', 500)->nullable()->after('caption');
            $table->string('link_platform', 30)->nullable()->after('link_url');
            // link_platform: youtube | deezer | apple_music | facebook | tiktok | soundcloud
        });
    }

    public function down(): void
    {
        Schema::table('portfolio_items', function (Blueprint $table) {
            $table->dropColumn(['link_url', 'link_platform']);
        });
    }
};
