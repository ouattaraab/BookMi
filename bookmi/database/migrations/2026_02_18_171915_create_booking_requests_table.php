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
        Schema::create('booking_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('talent_profile_id')
                ->constrained('talent_profiles')
                ->cascadeOnDelete();

            $table->foreignId('service_package_id')
                ->nullable()
                ->constrained('service_packages')
                ->nullOnDelete();

            $table->date('event_date');
            $table->string('event_location', 255);
            $table->text('message')->nullable();

            $table->enum('status', [
                'pending', 'accepted', 'paid', 'confirmed', 'completed', 'cancelled', 'disputed',
            ])->default('pending');

            // Financial amounts — stored in centimes (int)
            $table->unsignedInteger('cachet_amount');
            $table->unsignedInteger('commission_amount');
            $table->unsignedInteger('total_amount');

            $table->timestamps();

            $table->index(['talent_profile_id', 'status'], 'booking_requests_talent_profile_id_status_index');
            $table->index(['client_id', 'status'], 'booking_requests_client_id_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_requests');
    }
};
