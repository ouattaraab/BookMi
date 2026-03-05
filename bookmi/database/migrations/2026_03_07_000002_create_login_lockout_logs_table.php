<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('login_lockout_logs', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // 'web' = browser via WebLoginController
            // 'mobile' = Flutter app via API (Dart UA detected)
            // 'api' = API call, UA non-identifiable (Postman, scripts, etc.)
            $table->enum('client_type', ['web', 'mobile', 'api'])->default('api');

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedTinyInteger('attempts_count');

            $table->timestamp('locked_at');
            $table->timestamp('locked_until');

            // Null = expired naturally or still active; filled = manually unlocked by admin
            $table->timestamp('unlocked_at')->nullable();
            $table->foreignId('unlocked_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('email');
            $table->index(['locked_until', 'unlocked_at']); // active lockout queries
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_lockout_logs');
    }
};
