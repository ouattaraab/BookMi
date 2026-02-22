<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->text('accept_comment')->nullable()->after('reject_reason');
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->dropColumn('accept_comment');
        });
    }
};
