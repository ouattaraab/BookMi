<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_profile_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->integer('cachet_amount');
            $table->integer('duration_minutes')->nullable();
            $table->json('inclusions')->nullable();
            $table->string('type');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['talent_profile_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_packages');
    }
};
