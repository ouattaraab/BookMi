<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // The composite unique was also the only index covering client_id (FK).
            // We must add a standalone index on client_id first, or MySQL refuses to drop.
            $table->index('client_id');
            $table->dropUnique(['client_id', 'talent_profile_id']);
            $table->unique('booking_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique(['booking_request_id']);
            $table->dropIndex(['client_id']);
            $table->unique(['client_id', 'talent_profile_id']);
        });
    }
};
