<?php

namespace SwellSystems\Conversa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversaMention extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'message_id',
        'mentionable_id',
        'mentionable_type',
        'workspace_id',
        'read_at',
        'notified_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'read_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    /**
     * Get the message that contains the mention.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(ConversaMessage::class, 'message_id');
    }

    /**
     * Get the mentionable model (user or channel).
     */
    public function mentionable()
    {
        return $this->morphTo();
    }

    /**
     * Mark the mention as read.
     */
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Mark the mention as notified.
     */
    public function markAsNotified(): void
    {
        if (!$this->notified_at) {
            $this->update(['notified_at' => now()]);
        }
    }

    /**
     * Scope a query to only include unread mentions.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope a query to only include mentions for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('mentionable_type', config('conversa.user_model', 'App\\Models\\User'))
                    ->where('mentionable_id', $userId);
    }

    /**
     * Scope a query to only include mentions in a specific workspace.
     */
    public function scopeInWorkspace($query, int $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Get unread mentions count for a user.
     */
    public static function getUnreadCountForUser(int $userId, ?int $workspaceId = null): int
    {
        $query = static::unread()->forUser($userId);

        if ($workspaceId) {
            $query->inWorkspace($workspaceId);
        }

        return $query->count();
    }

    /**
     * Get recent mentions for a user.
     */
    public static function getRecentForUser(int $userId, ?int $workspaceId = null, int $limit = 10): array
    {
        $query = static::forUser($userId)
            ->with(['message' => function ($query) {
                $query->with(['user', 'space', 'thread']);
            }])
            ->latest();

        if ($workspaceId) {
            $query->inWorkspace($workspaceId);
        }

        return $query->take($limit)->get()->toArray();
    }

    /**
     * Mark all mentions as read for a user.
     */
    public static function markAllAsRead(int $userId, ?int $workspaceId = null): void
    {
        $query = static::unread()->forUser($userId);

        if ($workspaceId) {
            $query->inWorkspace($workspaceId);
        }

        $query->update(['read_at' => now()]);
    }
}
