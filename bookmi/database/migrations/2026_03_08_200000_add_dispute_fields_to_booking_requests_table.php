<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->string('dispute_reason')->nullable()->after('mediation_notes');
            $table->text('dispute_comment')->nullable()->after('dispute_reason');
            $table->timestamp('disputed_at')->nullable()->after('dispute_comment');
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->dropColumn(['dispute_reason', 'dispute_comment', 'disputed_at']);
        });
    }
};
