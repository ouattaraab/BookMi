<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            $table->unsignedSmallInteger('delivery_days')
                ->nullable()
                ->after('sort_order')
                ->comment('For micro-service packages: expected delivery in N days (auto-sets event_date on booking)');
        });
    }

    public function down(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            $table->dropColumn('delivery_days');
        });
    }
};
