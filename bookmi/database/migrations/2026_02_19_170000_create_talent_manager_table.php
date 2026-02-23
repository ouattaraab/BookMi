<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('talent_manager', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_profile_id')
                ->constrained('talent_profiles')
                ->cascadeOnDelete();
            $table->foreignId('manager_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            $table->unique(['talent_profile_id', 'manager_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_manager');
    }
};
