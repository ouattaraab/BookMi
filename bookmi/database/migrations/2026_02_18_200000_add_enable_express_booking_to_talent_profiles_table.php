<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table) {
            $table->boolean('enable_express_booking')->default(false)->after('total_bookings');
        });
    }

    public function down(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table) {
            $table->dropColumn('enable_express_booking');
        });
    }
};
