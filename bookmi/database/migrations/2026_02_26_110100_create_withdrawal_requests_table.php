<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');              // Montant demandé (XOF)
            $table->string('status', 20)->default('pending'); // WithdrawalStatus enum
            $table->string('payout_method', 30);              // PaymentMethod enum value
            $table->json('payout_details');                   // Snapshot du compte au moment de la demande
            $table->text('note')->nullable();                  // Note admin (rejet ou info)
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['talent_profile_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
};
