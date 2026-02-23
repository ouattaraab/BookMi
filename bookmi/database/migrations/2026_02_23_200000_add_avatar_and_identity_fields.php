<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('phone');
        });

        Schema::table('identity_verifications', function (Blueprint $table) {
            $table->string('document_number', 50)->nullable()->after('document_type');
            $table->string('selfie_path', 255)->nullable()->after('stored_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar');
        });

        Schema::table('identity_verifications', function (Blueprint $table) {
            $table->dropColumn(['document_number', 'selfie_path']);
        });
    }
};
