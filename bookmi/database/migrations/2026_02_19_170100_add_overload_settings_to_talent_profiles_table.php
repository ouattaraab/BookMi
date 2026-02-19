<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table) {
            $table->unsignedTinyInteger('overload_threshold')->default(10)->after('auto_reply_is_active');
            $table->timestamp('overload_notified_at')->nullable()->after('overload_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table) {
            $table->dropColumn(['overload_threshold', 'overload_notified_at']);
        });
    }
};
