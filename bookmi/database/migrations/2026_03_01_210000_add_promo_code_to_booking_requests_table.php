<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('promo_code_id')->nullable()->after('express_fee');
            $table->unsignedInteger('discount_amount')->default(0)->after('promo_code_id');

            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->foreign('promo_code_id')
                    ->references('id')
                    ->on('promo_codes')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign(['promo_code_id']);
            }
            $table->dropColumn(['promo_code_id', 'discount_amount']);
        });
    }
};
