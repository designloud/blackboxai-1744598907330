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
        Schema::create('conversa_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('conversa_messages')->onDelete('cascade');
            $table->foreignId('user_id');
            $table->foreignId('workspace_id');
            $table->timestamp('remind_at');
            $table->timestamp('reminded_at')->nullable();
            $table->string('note')->nullable();
            $table->string('status')->default('pending'); // pending, completed, cancelled
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status', 'remind_at']);
            $table->index(['workspace_id', 'status', 'remind_at']);
        });

        Schema::create('conversa_scheduled_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id');
            $table->foreignId('space_id')->nullable();
            $table->foreignId('thread_id')->nullable();
            $table->foreignId('user_id');
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->timestamp('scheduled_for');
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('pending'); // pending, sent, cancelled
            $table->timestamps();

            // Indexes
            $table->index(['status', 'scheduled_for']);
            $table->index(['workspace_id', 'status', 'scheduled_for']);
        });

        // Add unread flag to read receipts
        Schema::table('conversa_read_receipts', function (Blueprint $table) {
            $table->boolean('is_unread')->default(false)->after('read_at');
            $table->index(['user_id', 'is_unread']);
        });

        // Add scheduled_message_id to messages
        Schema::table('conversa_messages', function (Blueprint $table) {
            $table->foreignId('scheduled_message_id')
                  ->nullable()
                  ->after('parent_id')
                  ->constrained('conversa_scheduled_messages')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversa_messages', function (Blueprint $table) {
            $table->dropForeign(['scheduled_message_id']);
            $table->dropColumn('scheduled_message_id');
        });

        Schema::table('conversa_read_receipts', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_unread']);
            $table->dropColumn('is_unread');
        });

        Schema::dropIfExists('conversa_scheduled_messages');
        Schema::dropIfExists('conversa_reminders');
    }
};
