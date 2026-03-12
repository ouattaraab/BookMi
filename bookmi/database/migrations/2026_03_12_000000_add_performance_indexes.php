<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Skip on SQLite (CI tests) — indexes are optional for correctness
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        $this->safeAddIndex(
            'talent_profiles',
            'idx_talent_search_filters',
            ['is_verified', 'city', 'category_id', 'average_rating', 'cachet_amount']
        );
        $this->safeAddIndex(
            'talent_profiles',
            'idx_talent_visibility_sort',
            ['visibility_score', 'id']
        );
        $this->safeAddIndex(
            'talent_profiles',
            'idx_talent_coordinates',
            ['latitude', 'longitude']
        );
        $this->safeAddIndex(
            'messages',
            'idx_messages_conv_sender_read',
            ['conversation_id', 'sender_id', 'read_at']
        );
        $this->safeAddIndex(
            'messages',
            'idx_messages_conversation_deleted_created',
            ['conversation_id', 'deleted_at', 'created_at']
        );
        $this->safeAddIndex(
            'booking_requests',
            'idx_booking_requests_talent_status_date',
            ['talent_profile_id', 'status', 'event_date']
        );
        $this->safeAddIndex(
            'booking_requests',
            'idx_booking_requests_created_status',
            ['created_at', 'status']
        );
        $this->safeAddIndex(
            'transactions',
            'idx_transactions_booking_status_created',
            ['booking_request_id', 'status', 'created_at']
        );
        $this->safeAddIndex(
            'payouts',
            'idx_payouts_talent_status_processed',
            ['talent_profile_id', 'status', 'processed_at']
        );
        $this->safeAddIndex(
            'withdrawal_requests',
            'idx_withdrawal_requests_status_created',
            ['status', 'created_at']
        );
        $this->safeAddIndex(
            'profile_views',
            'idx_profile_views_talent_viewed_at',
            ['talent_profile_id', 'viewed_at']
        );
        $this->safeAddIndex(
            'conversations',
            'idx_conversations_last_message_at',
            ['last_message_at']
        );
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        $indexes = [
            ['talent_profiles', 'idx_talent_search_filters'],
            ['talent_profiles', 'idx_talent_visibility_sort'],
            ['talent_profiles', 'idx_talent_coordinates'],
            ['messages', 'idx_messages_conv_sender_read'],
            ['messages', 'idx_messages_conversation_deleted_created'],
            ['booking_requests', 'idx_booking_requests_talent_status_date'],
            ['booking_requests', 'idx_booking_requests_created_status'],
            ['transactions', 'idx_transactions_booking_status_created'],
            ['payouts', 'idx_payouts_talent_status_processed'],
            ['withdrawal_requests', 'idx_withdrawal_requests_status_created'],
            ['profile_views', 'idx_profile_views_talent_viewed_at'],
            ['conversations', 'idx_conversations_last_message_at'],
        ];

        foreach ($indexes as [$table, $index]) {
            try {
                Schema::table($table, fn (Blueprint $t) => $t->dropIndex($index));
            } catch (\Throwable) {
                // Index may not exist
            }
        }
    }

    private function safeAddIndex(string $table, string $indexName, array $columns): void
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
                $t->index($columns, $indexName);
            });
        } catch (\Throwable) {
            // Index already exists or table not available
        }
    }
};
