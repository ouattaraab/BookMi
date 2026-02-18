<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table): void {
            $table->string('payout_method')->nullable()->after('enable_express_booking');
            $table->json('payout_details')->nullable()->after('payout_method');
        });
    }

    public function down(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table): void {
            $table->dropColumn(['payout_method', 'payout_details']);
        });
    }
};
