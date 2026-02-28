<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table): void {
            $table->float('visibility_score', 5, 2)->default(0)->after('total_bookings');
            $table->index('visibility_score');
        });
    }

    public function down(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table): void {
            $table->dropIndex(['visibility_score']);
            $table->dropColumn('visibility_score');
        });
    }
};
