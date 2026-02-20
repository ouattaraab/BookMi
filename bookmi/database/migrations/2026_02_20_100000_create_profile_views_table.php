<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('viewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('viewer_ip', 45)->nullable();
            $table->timestamp('viewed_at');

            $table->index(['talent_profile_id', 'viewed_at']);
            $table->index('viewer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_views');
    }
};
