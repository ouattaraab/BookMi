<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('sent_by_manager_id')
                ->nullable()
                ->after('content')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\User::class, 'sent_by_manager_id');
            $table->dropColumn('sent_by_manager_id');
        });
    }
};
