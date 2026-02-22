<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Replace per-artist unique with per-booking unique
            $table->dropUnique(['client_id', 'talent_profile_id']);
            $table->unique('booking_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique(['booking_request_id']);
            $table->unique(['client_id', 'talent_profile_id']);
        });
    }
};
