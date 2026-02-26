<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table) {
            $table->timestamp('payout_method_verified_at')->nullable()->after('payout_details');
            $table->foreignId('payout_method_verified_by')->nullable()->after('payout_method_verified_at')
                ->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('available_balance')->default(0)->after('payout_method_verified_by')
                ->comment('Solde disponible en XOF (alimenté par les escrows libérés)');
        });
    }

    public function down(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table) {
            $table->dropForeign(['payout_method_verified_by']);
            $table->dropColumn(['payout_method_verified_at', 'payout_method_verified_by', 'available_balance']);
        });
    }
};
