<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type');     // low_rating, suspicious_activity, pending_action
            $table->string('severity'); // info, warning, critical
            $table->nullableMorphs('subject');
            $table->string('title');
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->string('status')->default('open'); // open, resolved, dismissed
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_alerts');
    }
};
