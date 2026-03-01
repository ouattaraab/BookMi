<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->unsignedBigInteger('mediator_id')->nullable()->after('reject_reason');
            $table->text('mediation_notes')->nullable()->after('mediator_id');
            $table->foreign('mediator_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->dropForeign(['mediator_id']);
            $table->dropColumn(['mediator_id', 'mediation_notes']);
        });
    }
};
