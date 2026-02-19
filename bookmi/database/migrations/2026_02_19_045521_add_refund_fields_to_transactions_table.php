<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->unsignedBigInteger('refund_amount')->nullable()->after('amount');
            $table->string('refund_reference')->nullable()->after('gateway_reference');
            $table->string('refund_reason')->nullable()->after('refund_reference');
            $table->timestamp('refunded_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropColumn(['refund_amount', 'refund_reference', 'refund_reason', 'refunded_at']);
        });
    }
};
