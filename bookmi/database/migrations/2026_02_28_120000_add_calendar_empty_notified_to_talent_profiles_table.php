<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table): void {
            $table->timestamp('calendar_empty_notified_at')->nullable()->after('overload_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table): void {
            $table->dropColumn('calendar_empty_notified_at');
        });
    }
};
