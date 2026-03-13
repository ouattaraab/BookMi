<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experience_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('private_experience_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('seats_count')->default(1);
            $table->unsignedInteger('price_per_seat');
            $table->unsignedInteger('total_amount');
            $table->unsignedInteger('commission_amount');
            $table->string('status')->default('pending');
            $table->text('cancelled_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('premium_options_selected')->nullable();
            $table->timestamps();

            $table->unique(['private_experience_id', 'client_id']);
            $table->index(['private_experience_id', 'status']);
            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experience_bookings');
    }
};
