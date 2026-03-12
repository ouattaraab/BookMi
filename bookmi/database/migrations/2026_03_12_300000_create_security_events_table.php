<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->enum('type', [
                'login_failed',
                'login_locked',
                'honeypot_hit',
                'rate_limit',
                'blocked_attempt',
                'suspicious_404',
            ])->index();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium')->index();
            $table->string('ip', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->string('method', 10)->nullable();
            $table->string('url', 2000)->nullable();
            $table->string('referer', 2000)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('email', 255)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->boolean('ip_blocked')->default(false)->index();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
