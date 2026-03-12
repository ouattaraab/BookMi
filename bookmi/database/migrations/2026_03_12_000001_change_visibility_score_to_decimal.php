<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return; // SQLite doesn't support column type changes cleanly
        }

        Schema::table('talent_profiles', function (Blueprint $table) {
            $table->decimal('visibility_score', 5, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('talent_profiles', function (Blueprint $table) {
            $table->float('visibility_score', 5, 2)->default(0)->change();
        });
    }
};
