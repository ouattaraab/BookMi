<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_profile_id')->constrained('talent_profiles')->cascadeOnDelete();
            $table->foreignId('booking_request_id')->nullable()->constrained('booking_requests')->nullOnDelete();
            $table->string('media_type', 10); // image | video
            $table->string('original_path', 500);
            $table->string('compressed_path', 500)->nullable();
            $table->string('caption', 255)->nullable();
            $table->boolean('is_compressed')->default(false);
            $table->timestamps();

            $table->index('talent_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_items');
    }
};
