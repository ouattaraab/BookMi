<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->text('dispute_admin_response')->nullable()->after('disputed_at');
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->dropColumn('dispute_admin_response');
        });
    }
};
