<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('talent_profile_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_profile_id')
                ->constrained('talent_profiles')
                ->cascadeOnDelete();
            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['talent_profile_id', 'category_id']);
        });

        // Populate pivot from existing category_id (backward compat)
        $profiles = DB::table('talent_profiles')
            ->whereNotNull('category_id')
            ->whereNull('deleted_at')
            ->get(['id', 'category_id']);

        foreach ($profiles as $profile) {
            DB::table('talent_profile_categories')->insertOrIgnore([
                'talent_profile_id' => $profile->id,
                'category_id'       => $profile->category_id,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_profile_categories');
    }
};
