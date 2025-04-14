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
        Schema::create('conversa_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained(config('conversa.workspace.model'))->cascadeOnDelete();
            $table->foreignId('space_id')->nullable()->constrained('conversa_spaces')->cascadeOnDelete();
            $table->foreignId('thread_id')->nullable()->constrained('conversa_threads')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('conversa_messages')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('content');
            $table->string('type')->default('text'); // text, system, file
            $table->json('metadata')->nullable(); // For additional message data like mentions, links, etc.
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for better performance
            $table->index(['workspace_id', 'space_id']);
            $table->index(['workspace_id', 'thread_id']);
            $table->index('parent_id');
            $table->index('user_id');
            $table->index('type');
            $table->index('deleted_at');
        });

        Schema::create('conversa_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('conversa_messages')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->integer('file_size');
            $table->string('mime_type');
            $table->json('metadata')->nullable(); // For additional file data like dimensions, duration, etc.
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('message_id');
            $table->index('file_type');
            $table->index('deleted_at');
        });

        Schema::create('conversa_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('conversa_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reaction');
            $table->timestamps();

            // Unique constraint to prevent duplicate reactions
            $table->unique(['message_id', 'user_id', 'reaction']);

            // Indexes
            $table->index(['message_id', 'reaction']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversa_reactions');
        Schema::dropIfExists('conversa_attachments');
        Schema::dropIfExists('conversa_messages');
    }
};
