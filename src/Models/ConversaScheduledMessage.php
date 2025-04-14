<?php

namespace SwellSystems\Conversa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;
use SwellSystems\Conversa\Events\MessageSent;
use SwellSystems\Conversa\Services\MessageFormatter;
use SwellSystems\Conversa\Services\MessageEncryption;

class ConversaScheduledMessage extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'workspace_id',
        'space_id',
        'thread_id',
        'user_id',
        'content',
        'metadata',
        'scheduled_for',
        'sent_at',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the space associated with the scheduled message.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(ConversaSpace::class);
    }

    /**
     * Get the thread associated with the scheduled message.
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(ConversaThread::class);
    }

    /**
     * Get the user who scheduled the message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('conversa.user_model', 'App\\Models\\User'));
    }

    /**
     * Get the sent message.
     */
    public function sentMessage(): HasOne
    {
        return $this->hasOne(ConversaMessage::class, 'scheduled_message_id');
    }

    /**
     * Send the scheduled message.
     */
    public function send(): ?ConversaMessage
    {
        if ($this->status !== 'pending') {
            return null;
        }

        // Format content
        $formatter = app(MessageFormatter::class);
        $formattedContent = $formatter->format($this->content);

        // Create the message
        $message = ConversaMessage::create([
            'workspace_id' => $this->workspace_id,
            'space_id' => $this->space_id,
            'thread_id' => $this->thread_id,
            'user_id' => $this->user_id,
            'content' => $formattedContent,
            'type' => 'text',
            'metadata' => array_merge($this->metadata ?? [], ['scheduled' => true]),
            'scheduled_message_id' => $this->id,
        ]);

        // Encrypt if needed
        if (config('conversa.encryption.enabled', false)) {
            $message->encryptContent();
        }

        // Update status
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Broadcast the message
        event(new MessageSent($message));

        return $message;
    }

    /**
     * Cancel the scheduled message.
     */
    public function cancel(): void
    {
        if ($this->status === 'pending') {
            $this->update(['status' => 'cancelled']);
        }
    }

    /**
     * Reschedule the message.
     */
    public function reschedule(Carbon|string $newTime): void
    {
        if ($this->status === 'pending') {
            $this->update([
                'scheduled_for' => Carbon::parse($newTime),
            ]);
        }
    }

    /**
     * Scope a query to only include pending messages.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include messages due for sending.
     */
    public function scopeDueForSending($query)
    {
        return $query->where('status', 'pending')
                    ->where('scheduled_for', '<=', now());
    }

    /**
     * Scope a query to only include messages for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get upcoming scheduled messages for a user.
     */
    public static function getUpcomingForUser(int $userId, ?int $workspaceId = null): array
    {
        $query = static::forUser($userId)
            ->pending()
            ->where('scheduled_for', '>', now())
            ->orderBy('scheduled_for');

        if ($workspaceId) {
            $query->where('workspace_id', $workspaceId);
        }

        return $query->get()->toArray();
    }

    /**
     * Create a scheduled message with natural language time parsing.
     */
    public static function scheduleFromText(array $data, string $timeText): self
    {
        $scheduledFor = static::parseScheduleTime($timeText);

        return static::create(array_merge($data, [
            'scheduled_for' => $scheduledFor,
            'status' => 'pending',
        ]));
    }

    /**
     * Parse schedule time from natural language.
     */
    protected static function parseScheduleTime(string $text): Carbon
    {
        return match (true) {
            str_contains($text, 'tomorrow') => now()->addDay()->setHour(9)->setMinute(0),
            str_contains($text, 'next week') => now()->addWeek()->startOfWeek(),
            str_contains($text, 'tonight') => now()->setHour(18)->setMinute(0),
            str_contains($text, 'this evening') => now()->setHour(18)->setMinute(0),
            default => Carbon::parse($text),
        };
    }
}
