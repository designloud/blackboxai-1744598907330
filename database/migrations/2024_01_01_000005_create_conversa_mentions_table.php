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
        Schema::create('conversa_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('conversa_messages')->onDelete('cascade');
            $table->morphs('mentionable');
            $table->foreignId('workspace_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['mentionable_type', 'mentionable_id']);
            $table->index(['workspace_id', 'mentionable_type', 'mentionable_id']);
            $table->index(['read_at']);
            
            // Unique constraint to prevent duplicate mentions in the same message
            $table->unique(['message_id', 'mentionable_type', 'mentionable_id'], 'unique_mention');
        });

        // Add mentions_count to messages table
        Schema::table('conversa_messages', function (Blueprint $table) {
            $table->unsignedInteger('mentions_count')->default(0)->after('unread_count');
        });

        // Add last_mentioned_at to users table if it doesn't exist
        if (!Schema::hasColumn('users', 'last_mentioned_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('last_mentioned_at')->nullable();
            });
        }

        // Add notification preferences to thread settings
        Schema::table('conversa_thread_settings', function (Blueprint $table) {
            $table->boolean('mention_notifications_enabled')->default(true)->after('notifications_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove notification preferences from thread settings
        Schema::table('conversa_thread_settings', function (Blueprint $table) {
            $table->dropColumn('mention_notifications_enabled');
        });

        // Remove last_mentioned_at from users table if we added it
        if (Schema::hasColumn('users', 'last_mentioned_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('last_mentioned_at');
            });
        }

        // Remove mentions_count from messages table
        Schema::table('conversa_messages', function (Blueprint $table) {
            $table->dropColumn('mentions_count');
        });

        // Drop the main table
        Schema::dropIfExists('conversa_mentions');
    }
};
