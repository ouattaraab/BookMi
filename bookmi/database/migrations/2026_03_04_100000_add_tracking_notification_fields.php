<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('tracking_events', function (Blueprint $table) {
            $table->timestamp('client_notified_at')->nullable()->after('occurred_at');
        });

        Schema::table('booking_requests', function (Blueprint $table) {
            $table->timestamp('client_confirmed_arrival_at')->nullable()->after('mediation_notes');
        });
    }

    public function down(): void
    {
        Schema::table('tracking_events', function (Blueprint $table) {
            $table->dropColumn('client_notified_at');
        });

        Schema::table('booking_requests', function (Blueprint $table) {
            $table->dropColumn('client_confirmed_arrival_at');
        });
    }
};
