<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table) {
            $table->string('payout_method_status')->nullable()->after('payout_method_verified_by');
            $table->text('payout_method_rejection_reason')->nullable()->after('payout_method_status');
        });

        // Backfill existing rows: verified if payout_method_verified_at is set,
        // pending if payout_method is set but not yet verified.
        DB::table('talent_profiles')
            ->whereNotNull('payout_method_verified_at')
            ->update(['payout_method_status' => 'verified']);

        DB::table('talent_profiles')
            ->whereNotNull('payout_method')
            ->whereNull('payout_method_verified_at')
            ->update(['payout_method_status' => 'pending']);
    }

    public function down(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table) {
            $table->dropColumn(['payout_method_status', 'payout_method_rejection_reason']);
        });
    }
};
