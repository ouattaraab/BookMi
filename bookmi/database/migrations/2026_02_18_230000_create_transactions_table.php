<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_request_id')->constrained()->cascadeOnDelete();
            $table->string('payment_method');          // PaymentMethod enum
            $table->unsignedBigInteger('amount');      // In XOF (cents equivalent)
            $table->string('currency', 3)->default('XOF');
            $table->string('gateway')->default('paystack');
            $table->string('gateway_reference')->nullable()->unique(); // Paystack ref
            $table->json('gateway_response')->nullable();              // Raw response
            $table->string('status');                  // TransactionStatus enum
            $table->string('idempotency_key')->unique(); // Webhook dedup key
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('booking_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
