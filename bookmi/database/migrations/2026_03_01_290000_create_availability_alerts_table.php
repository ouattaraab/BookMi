<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('availability_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('talent_profile_id')->constrained()->cascadeOnDelete();
            $table->date('event_date');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'talent_profile_id', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_alerts');
    }
};
