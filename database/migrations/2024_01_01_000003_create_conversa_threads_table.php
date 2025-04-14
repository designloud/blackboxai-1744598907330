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
        Schema::create('conversa_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained(config('conversa.workspace.model'))->cascadeOnDelete();
            $table->string('type')->default('direct'); // direct, group
            $table->string('name')->nullable(); // For group threads
            $table->string('icon_url')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['workspace_id', 'type']);
            $table->index('created_by');
            $table->index('deleted_at');
        });

        Schema::create('conversa_thread_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('conversa_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('member'); // admin or member
            $table->timestamp('last_read_at')->nullable();
            $table->boolean('is_muted')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint to prevent duplicate memberships
            $table->unique(['thread_id', 'user_id']);

            // Indexes
            $table->index(['thread_id', 'role']);
            $table->index('user_id');
            $table->index('deleted_at');
        });

        // Create a table for thread settings (like custom notifications, etc.)
        Schema::create('conversa_thread_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('conversa_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('settings')->nullable();
            $table->timestamps();

            // Unique constraint
            $table->unique(['thread_id', 'user_id']);

            // Indexes
            $table->index('thread_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversa_thread_settings');
        Schema::dropIfExists('conversa_thread_members');
        Schema::dropIfExists('conversa_threads');
    }
};
