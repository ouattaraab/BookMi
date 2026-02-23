<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            // Optional start time (HH:MM) — enables time-based conflict detection (±1h buffer).
            // Nullable for backwards compatibility with existing date-only bookings.
            $table->time('start_time')->nullable()->after('event_date');
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->dropColumn('start_time');
        });
    }
};
