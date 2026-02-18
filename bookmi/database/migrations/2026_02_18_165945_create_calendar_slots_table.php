<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_profile_id')
                ->constrained('talent_profiles')
                ->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['available', 'blocked', 'rest'])->default('blocked');
            $table->timestamps();

            // A talent can only have one slot per day
            $table->unique(['talent_profile_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_slots');
    }
};
