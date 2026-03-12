<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('escrow_holds', function (Blueprint $table) {
            $table->unsignedBigInteger('released_by')->nullable()->after('released_at');
            $table->string('released_by_type', 20)->nullable()->after('released_by'); // client|talent|admin|system
        });
    }

    public function down(): void
    {
        Schema::table('escrow_holds', function (Blueprint $table) {
            $table->dropColumn(['released_by', 'released_by_type']);
        });
    }
};
