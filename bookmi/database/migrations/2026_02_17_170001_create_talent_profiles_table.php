<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('talent_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('stage_name', 100);
            $table->string('slug', 120)->unique();
            $table->text('bio')->nullable();
            $table->string('city', 100);
            $table->bigInteger('cachet_amount');
            $table->json('social_links')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->string('talent_level')->default('nouveau');
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_bookings')->default(0);
            $table->unsignedTinyInteger('profile_completion_percentage')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id');
            $table->index('is_verified');
            $table->index('talent_level');
            $table->index('city');
            $table->index('average_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_profiles');
    }
};
