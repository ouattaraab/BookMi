<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->decimal('event_latitude', 10, 8)->nullable()->after('event_location');
            $table->decimal('event_longitude', 11, 8)->nullable()->after('event_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->dropColumn(['event_latitude', 'event_longitude']);
        });
    }
};
