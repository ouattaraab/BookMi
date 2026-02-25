<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Immutable audit log for every booking status transition.
     */
    public function up(): void
    {
        Schema::create('booking_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_request_id')
                ->constrained('booking_requests')
                ->cascadeOnDelete();
            $table->string('from_status', 30)->nullable(); // null = initial creation
            $table->string('to_status', 30);
            $table->foreignId('performed_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['booking_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_status_logs');
    }
};
