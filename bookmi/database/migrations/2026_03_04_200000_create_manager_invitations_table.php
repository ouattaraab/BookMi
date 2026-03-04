<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('manager_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_profile_id')->constrained()->cascadeOnDelete();
            $table->string('manager_email');
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->text('manager_comment')->nullable();
            $table->uuid('token')->unique();
            $table->timestamp('invited_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['talent_profile_id', 'manager_email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_invitations');
    }
};
