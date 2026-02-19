<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_request_id')->constrained('booking_requests')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewee_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 30); // ReviewType enum: client_to_talent | talent_to_client
            $table->unsignedTinyInteger('rating'); // 1–5
            $table->text('comment')->nullable();
            $table->timestamps();

            // One review per type per booking
            $table->unique(['booking_request_id', 'type']);
            $table->index(['reviewee_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
