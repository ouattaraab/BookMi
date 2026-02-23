<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('refund_amount')->nullable()->after('total_amount');
            $table->string('cancellation_policy_applied', 50)->nullable()->after('refund_amount');
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->dropColumn(['refund_amount', 'cancellation_policy_applied']);
        });
    }
};
