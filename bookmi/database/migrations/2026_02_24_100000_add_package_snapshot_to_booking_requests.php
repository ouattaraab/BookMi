<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->json('package_snapshot')->nullable()->after('service_package_id');
        });

        // Index performance pour les vues profil (si pas encore présent)
        Schema::table('profile_views', function (Blueprint $table) {
            // Index composite pour les queries stats (talent_profile_id + viewed_at)
            if (!collect(\Schema::getIndexes('profile_views'))->pluck('name')->contains('profile_views_talent_viewed_idx')) {
                $table->index(['talent_profile_id', 'viewed_at'], 'profile_views_talent_viewed_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->dropColumn('package_snapshot');
        });
        Schema::table('profile_views', function (Blueprint $table) {
            $table->dropIndex('profile_views_talent_viewed_idx');
        });
    }
};
