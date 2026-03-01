<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table) {
            $table->boolean('is_group')->default(false)->after('auto_reply_is_active');
            $table->unsignedTinyInteger('group_size')->nullable()->after('is_group');
            $table->string('collective_name', 100)->nullable()->after('group_size');
        });
    }

    public function down(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table) {
            $table->dropColumn(['is_group', 'group_size', 'collective_name']);
        });
    }
};
