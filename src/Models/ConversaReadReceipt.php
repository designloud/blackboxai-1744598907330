<?php

namespace SwellSystems\Conversa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SwellSystems\Conversa\Events\MessageRead;

class ConversaReadReceipt extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'message_id',
        'user_id',
        'read_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, string>
     */
    protected $dispatchesEvents = [
        'saved' => MessageRead::class,
    ];

    /**
     * Get the message that owns the read receipt.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(ConversaMessage::class, 'message_id');
    }

    /**
     * Get the user that owns the read receipt.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('conversa.user_model', 'App\\Models\\User'));
    }

    /**
     * Mark as read now.
     */
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
            $this->updateMessageCounts();
        }
    }

    /**
     * Mark as unread.
     */
    public function markAsUnread(): void
    {
        if ($this->read_at) {
            $this->update(['read_at' => null]);
            $this->updateMessageCounts();
        }
    }

    /**
     * Check if the message has been read.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Get the time elapsed since the message was read.
     */
    public function getTimeElapsedAttribute(): ?string
    {
        return $this->read_at ? $this->read_at->diffForHumans() : null;
    }

    /**
     * Update message read/unread counts.
     */
    protected function updateMessageCounts(): void
    {
        $message = $this->message;
        $readCount = $message->readReceipts()->whereNotNull('read_at')->count();
        $unreadCount = $message->readReceipts()->whereNull('read_at')->count();

        $message->update([
            'read_count' => $readCount,
            'unread_count' => $unreadCount,
        ]);

        // Update thread's last_read_at if this is the latest message
        if ($message->thread_id) {
            $thread = $message->thread;
            $latestMessage = $thread->messages()->latest()->first();

            if ($latestMessage && $latestMessage->id === $message->id && $this->isRead()) {
                $thread->update(['last_read_at' => now()]);
            }
        }
    }

    /**
     * Scope a query to only include read receipts.
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope a query to only include unread receipts.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope a query to only include receipts for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include receipts for a specific message.
     */
    public function scopeForMessage($query, int $messageId)
    {
        return $query->where('message_id', $messageId);
    }

    /**
     * Get all unread messages for a user.
     */
    public static function getUnreadMessagesForUser(int $userId, ?int $workspaceId = null): array
    {
        $query = static::unread()->forUser($userId)
            ->with(['message' => function ($query) {
                $query->select('id', 'content', 'workspace_id', 'space_id', 'thread_id', 'created_at');
            }]);

        if ($workspaceId) {
            $query->whereHas('message', function ($query) use ($workspaceId) {
                $query->where('workspace_id', $workspaceId);
            });
        }

        return $query->get()->pluck('message')->toArray();
    }

    /**
     * Get unread count for a user.
     */
    public static function getUnreadCountForUser(int $userId, ?int $workspaceId = null): int
    {
        $query = static::unread()->forUser($userId);

        if ($workspaceId) {
            $query->whereHas('message', function ($query) use ($workspaceId) {
                $query->where('workspace_id', $workspaceId);
            });
        }

        return $query->count();
    }

    /**
     * Mark all messages as read for a user in a space or thread.
     */
    public static function markAllAsRead(int $userId, array $context): void
    {
        $query = ConversaMessage::query();

        if (isset($context['space_id'])) {
            $query->where('space_id', $context['space_id']);
        }

        if (isset($context['thread_id'])) {
            $query->where('thread_id', $context['thread_id']);
        }

        $messages = $query->get();

        foreach ($messages as $message) {
            $receipt = $message->readReceipts()->firstOrCreate(
                ['user_id' => $userId],
                ['read_at' => now()]
            );

            if (!$receipt->wasRecentlyCreated && !$receipt->read_at) {
                $receipt->markAsRead();
            }
        }
    }
}
