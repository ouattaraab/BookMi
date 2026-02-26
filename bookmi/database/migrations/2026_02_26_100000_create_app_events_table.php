<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('app_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('event_type', ['page_view', 'button_tap']);
            $table->string('event_name', 100);
            $table->string('platform', 20);
            $table->string('app_version', 20);
            $table->char('session_id', 36);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event_type', 'event_name', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_events');
    }
};
