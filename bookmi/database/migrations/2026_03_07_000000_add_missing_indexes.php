<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        $this->safeAddIndex('booking_requests', 'status', 'booking_requests_status_index');
        $this->safeAddIndex('push_notifications', 'user_id', 'push_notifications_user_id_index');
        $this->safeAddIndex('push_notifications', 'read_at', 'push_notifications_read_at_index');
        $this->safeAddIndex('messages', 'sender_id', 'messages_sender_id_index');
        $this->safeAddIndex('user_consents', 'user_id', 'user_consents_user_id_index');
    }

    public function down(): void
    {
        $this->safeDropIndex('booking_requests', 'booking_requests_status_index');
        $this->safeDropIndex('push_notifications', 'push_notifications_user_id_index');
        $this->safeDropIndex('push_notifications', 'push_notifications_read_at_index');
        $this->safeDropIndex('messages', 'messages_sender_id_index');
        $this->safeDropIndex('user_consents', 'user_consents_user_id_index');
    }

    private function safeAddIndex(string $table, string $column, string $name): void
    {
        try {
            Schema::table($table, fn (Blueprint $t) => $t->index($column, $name));
        } catch (\Throwable) {
            // Index already exists — skip
        }
    }

    private function safeDropIndex(string $table, string $name): void
    {
        try {
            Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
        } catch (\Throwable) {
            // Index does not exist — skip
        }
    }
};
