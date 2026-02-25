<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Add 'rejected' as a valid booking status.
     *
     * MySQL ENUM columns cannot be modified with Schema::table + ->change() reliably,
     * so we use a raw ALTER TABLE statement.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE booking_requests MODIFY COLUMN status ENUM(
            'pending', 'accepted', 'paid', 'confirmed', 'completed', 'cancelled', 'rejected', 'disputed'
        ) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Remove rejected — any rows with status='rejected' must be handled first.
        DB::statement("ALTER TABLE booking_requests MODIFY COLUMN status ENUM(
            'pending', 'accepted', 'paid', 'confirmed', 'completed', 'cancelled', 'disputed'
        ) NOT NULL DEFAULT 'pending'");
    }
};
