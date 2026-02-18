<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escrow_holds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('cachet_amount');     // Talent's share (XOF)
            $table->unsignedBigInteger('commission_amount'); // BookMi's share (XOF)
            $table->unsignedBigInteger('total_amount');      // Sum (XOF)
            $table->string('status');                        // EscrowStatus enum
            $table->timestamp('held_at')->nullable();
            $table->timestamp('release_scheduled_at')->nullable(); // Auto-confirm deadline
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('booking_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrow_holds');
    }
};
