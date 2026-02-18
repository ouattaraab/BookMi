<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 100)->after('id');
            $table->string('last_name', 100)->after('first_name');
            $table->string('phone', 20)->unique()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('remember_token');
        });

        // Migrate existing 'name' data to first_name before dropping
        \Illuminate\Support\Facades\DB::statement(
            "UPDATE users SET first_name = COALESCE(name, ''), last_name = '' WHERE first_name IS NULL OR first_name = ''"
        );

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
        });

        \Illuminate\Support\Facades\DB::statement(
            "UPDATE users SET name = first_name || ' ' || last_name"
        );

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'phone', 'phone_verified_at', 'is_active']);
        });
    }
};
