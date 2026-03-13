<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('private_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_profile_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('event_date');
            $table->string('venue_address')->nullable();
            $table->boolean('venue_revealed')->default(false);
            $table->unsignedInteger('total_price');
            $table->unsignedSmallInteger('max_seats');
            $table->unsignedSmallInteger('booked_seats')->default(0);
            $table->string('status')->default('draft');
            $table->json('premium_options')->nullable();
            $table->string('cover_image')->nullable();
            $table->text('cancelled_reason')->nullable();
            $table->unsignedTinyInteger('commission_rate')->default(15);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'event_date']);
            $table->index('talent_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_experiences');
    }
};
