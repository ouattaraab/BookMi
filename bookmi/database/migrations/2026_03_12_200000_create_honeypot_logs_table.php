<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('honeypot_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->index();
            $table->text('user_agent')->nullable();
            $table->string('honeypot_value', 500)->nullable(); // what the bot put in the "website" field
            $table->string('referer', 2000)->nullable();
            $table->string('url', 2000)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->boolean('is_blocked')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('honeypot_logs');
    }
};
