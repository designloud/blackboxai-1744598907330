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
        Schema::create('conversa_spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained(config('conversa.workspace.model'))->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type')->default('public'); // public or private
            $table->string('icon_url')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for better performance
            $table->index(['workspace_id', 'type']);
            $table->index('created_by');
            $table->index('deleted_at');
        });

        Schema::create('conversa_space_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('conversa_spaces')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('member'); // admin or member
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint to prevent duplicate memberships
            $table->unique(['space_id', 'user_id']);

            // Indexes
            $table->index(['space_id', 'role']);
            $table->index('user_id');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversa_space_members');
        Schema::dropIfExists('conversa_spaces');
    }
};
