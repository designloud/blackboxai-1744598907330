<?php

namespace SwellSystems\Conversa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ConversaReminder extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'message_id',
        'user_id',
        'workspace_id',
        'remind_at',
        'reminded_at',
        'note',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'remind_at' => 'datetime',
        'reminded_at' => 'datetime',
    ];

    /**
     * Get the message associated with the reminder.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(ConversaMessage::class);
    }

    /**
     * Get the user who set the reminder.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('conversa.user_model', 'App\\Models\\User'));
    }

    /**
     * Mark the reminder as sent.
     */
    public function markAsReminded(): void
    {
        $this->update([
            'reminded_at' => now(),
            'status' => 'completed',
        ]);
    }

    /**
     * Snooze the reminder.
     */
    public function snooze(string $duration): void
    {
        $this->update([
            'remind_at' => match ($duration) {
                '1h' => now()->addHour(),
                '3h' => now()->addHours(3),
                'tomorrow' => now()->addDay()->setHour(9)->setMinute(0),
                'nextweek' => now()->addWeek()->startOfWeek(),
                default => Carbon::parse($duration),
            },
            'status' => 'pending',
        ]);
    }

    /**
     * Cancel the reminder.
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }

    /**
     * Scope a query to only include pending reminders.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                    ->whereNull('reminded_at')
                    ->where('remind_at', '<=', now());
    }

    /**
     * Scope a query to only include reminders for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get upcoming reminders for a user.
     */
    public static function getUpcomingForUser(int $userId, ?int $workspaceId = null): array
    {
        $query = static::forUser($userId)
            ->where('status', 'pending')
            ->whereNull('reminded_at')
            ->where('remind_at', '>', now())
            ->with(['message']);

        if ($workspaceId) {
            $query->where('workspace_id', $workspaceId);
        }

        return $query->get()->toArray();
    }

    /**
     * Create a reminder with natural language processing.
     */
    public static function createFromText(string $text, int $messageId, int $userId, int $workspaceId): self
    {
        $remindAt = static::parseReminderTime($text);

        return static::create([
            'message_id' => $messageId,
            'user_id' => $userId,
            'workspace_id' => $workspaceId,
            'remind_at' => $remindAt,
            'note' => $text,
            'status' => 'pending',
        ]);
    }

    /**
     * Parse reminder time from natural language.
     */
    protected static function parseReminderTime(string $text): Carbon
    {
        return match (true) {
            str_contains($text, 'tomorrow') => now()->addDay()->setHour(9)->setMinute(0),
            str_contains($text, 'next week') => now()->addWeek()->startOfWeek(),
            str_contains($text, 'in 1 hour') => now()->addHour(),
            str_contains($text, 'in 3 hours') => now()->addHours(3),
            str_contains($text, 'tonight') => now()->setHour(18)->setMinute(0),
            str_contains($text, 'this evening') => now()->setHour(18)->setMinute(0),
            default => Carbon::parse($text),
        };
    }
}
