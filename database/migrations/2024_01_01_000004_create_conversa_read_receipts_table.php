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
        Schema::create('conversa_read_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('conversa_messages')->onDelete('cascade');
            $table->foreignId('user_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['message_id', 'user_id']);
            $table->index(['user_id', 'read_at']);
            
            // Unique constraint to prevent duplicate read receipts
            $table->unique(['message_id', 'user_id']);
        });

        // Add last_read_at column to conversa_threads table
        Schema::table('conversa_threads', function (Blueprint $table) {
            $table->timestamp('last_read_at')->nullable()->after('settings');
        });

        // Add read_count and unread_count columns to conversa_messages table
        Schema::table('conversa_messages', function (Blueprint $table) {
            $table->unsignedInteger('read_count')->default(0)->after('reply_count');
            $table->unsignedInteger('unread_count')->default(0)->after('read_count');
            $table->index(['workspace_id', 'unread_count']);
        });

        // Add last_read_message_id to conversa_thread_settings table
        Schema::table('conversa_thread_settings', function (Blueprint $table) {
            $table->foreignId('last_read_message_id')
                ->nullable()
                ->after('notifications_enabled')
                ->constrained('conversa_messages')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove last_read_message_id from conversa_thread_settings table
        Schema::table('conversa_thread_settings', function (Blueprint $table) {
            $table->dropForeign(['last_read_message_id']);
            $table->dropColumn('last_read_message_id');
        });

        // Remove read_count and unread_count from conversa_messages table
        Schema::table('conversa_messages', function (Blueprint $table) {
            $table->dropIndex(['workspace_id', 'unread_count']);
            $table->dropColumn(['read_count', 'unread_count']);
        });

        // Remove last_read_at from conversa_threads table
        Schema::table('conversa_threads', function (Blueprint $table) {
            $table->dropColumn('last_read_at');
        });

        // Drop the main table
        Schema::dropIfExists('conversa_read_receipts');
    }
};
