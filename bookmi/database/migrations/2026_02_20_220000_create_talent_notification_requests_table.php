<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('talent_notification_requests', function (Blueprint $table) {
            $table->id();
            $table->string('search_query', 200);
            $table->string('email', 255)->nullable();
            $table->string('phone', 20)->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index('search_query');
            $table->index('notified_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_notification_requests');
    }
};
