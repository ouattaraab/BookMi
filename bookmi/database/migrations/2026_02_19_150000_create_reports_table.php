<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_request_id')->constrained('booking_requests')->cascadeOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 60);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('pending'); // pending | resolved
            $table->timestamps();

            $table->index('booking_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
