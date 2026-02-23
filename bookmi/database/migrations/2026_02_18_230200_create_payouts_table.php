<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('talent_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('escrow_hold_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount'); // XOF — cachet_amount only
            $table->string('payout_method');      // PaymentMethod enum
            $table->json('payout_details')->nullable(); // { phone, account_number, bank_code }
            $table->string('gateway')->default('paystack');
            $table->string('gateway_reference')->nullable();
            $table->string('status');             // PayoutStatus enum
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('talent_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
