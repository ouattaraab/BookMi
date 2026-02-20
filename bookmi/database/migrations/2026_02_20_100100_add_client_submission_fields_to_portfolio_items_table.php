<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolio_items', function (Blueprint $table) {
            $table->boolean('submitted_by_client')->default(false)->after('is_compressed');
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete()->after('submitted_by_client');
            $table->boolean('is_approved')->nullable()->after('submitted_by_user_id');
            // null = pending review, true = approved, false = rejected
        });
    }

    public function down(): void
    {
        Schema::table('portfolio_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('submitted_by_user_id');
            $table->dropColumn(['submitted_by_client', 'is_approved']);
        });
    }
};
