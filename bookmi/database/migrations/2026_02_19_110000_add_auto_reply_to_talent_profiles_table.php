<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table) {
            $table->text('auto_reply_message')->nullable()->after('payout_details');
            $table->boolean('auto_reply_is_active')->default(false)->after('auto_reply_message');
        });
    }

    public function down(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table) {
            $table->dropColumn(['auto_reply_message', 'auto_reply_is_active']);
        });
    }
};
