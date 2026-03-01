<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_id');
            $table->unsignedBigInteger('referred_user_id')->unique();
            $table->string('code_used', 20);
            $table->string('status', 20)->default('pending'); // pending | completed
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->foreign('referrer_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('referred_user_id')->references('id')->on('users')->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
