<?php

namespace SwellSystems\Conversa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use SwellSystems\Conversa\Services\MessageEncryption;
use SwellSystems\Conversa\Services\MessageFormatter;
use Illuminate\Support\HtmlString;

class ConversaMessage extends Model
{
    use SoftDeletes, Searchable;

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
        'parent_id',
        'content',
        'type',
        'metadata',
        'is_encrypted',
        'read_count',
        'unread_count',
        'mentions_count',
        'reply_count',
        'last_reply_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'is_encrypted' => 'boolean',
        'last_reply_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array<string>
     */
    protected $with = ['user'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<string>
     */
    protected $appends = ['formatted_content'];

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        $array = $this->toArray();

        // If the content is encrypted, decrypt it for indexing
        if ($this->is_encrypted && isset($array['content'])) {
            $encryption = app(MessageEncryption::class);
            $array['content'] = $encryption->decrypt(['content' => $array['content']])['content'];
        }

        // Remove sensitive data
        unset($array['metadata']);
        unset($array['deleted_at']);

        return $array;
    }

    /**
     * Get the formatted content attribute.
     */
    public function getFormattedContentAttribute(): HtmlString
    {
        $content = $this->content;

        // Decrypt content if needed
        if ($this->is_encrypted) {
            $encryption = app(MessageEncryption::class);
            $content = $encryption->decrypt(['content' => $content])['content'];
        }

        // Format content with Markdown, mentions, and emoji
        $formatter = app(MessageFormatter::class);
        return $formatter->format($content);
    }

    /**
     * Get the user that owns the message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('conversa.user_model', 'App\\Models\\User'));
    }

    /**
     * Get the space that owns the message.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(ConversaSpace::class, 'space_id');
    }

    /**
     * Get the thread that owns the message.
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(ConversaThread::class, 'thread_id');
    }

    /**
     * Get the parent message.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ConversaMessage::class, 'parent_id');
    }

    /**
     * Get the replies to this message.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ConversaMessage::class, 'parent_id');
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ConversaAttachment::class, 'message_id');
    }

    /**
     * Get the reactions for the message.
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(ConversaReaction::class, 'message_id');
    }

    /**
     * Get the read receipts for the message.
     */
    public function readReceipts(): HasMany
    {
        return $this->hasMany(ConversaReadReceipt::class, 'message_id');
    }

    /**
     * Get all mentions in the message.
     */
    public function mentions(): HasMany
    {
        return $this->hasMany(ConversaMention::class, 'message_id');
    }

    /**
     * Get all of the models mentioned in the message.
     */
    public function mentionables(): MorphMany
    {
        return $this->morphMany(ConversaMention::class, 'mentionable');
    }

    /**
     * Process mentions in the message content.
     */
    public function processMentions(): void
    {
        $formatter = app(MessageFormatter::class);
        $mentions = $formatter->extractMentions($this->content);

        // Create mentions for users
        foreach ($mentions['users'] as $user) {
            $mention = $this->mentions()->create([
                'mentionable_id' => $user->id,
                'mentionable_type' => get_class($user),
                'workspace_id' => $this->workspace_id,
            ]);

            // Dispatch UserMentioned event
            event(new \SwellSystems\Conversa\Events\UserMentioned($mention));
        }

        // Create mentions for channels
        foreach ($mentions['channels'] as $channel) {
            $this->mentions()->create([
                'mentionable_id' => $channel->id,
                'mentionable_type' => get_class($channel),
                'workspace_id' => $this->workspace_id,
            ]);
        }

        // Update mentions count
        $this->update([
            'mentions_count' => $this->mentions()->count(),
        ]);
    }

    /**
     * Add a reaction to the message.
     */
    public function addReaction(int $userId, string $reaction): ConversaReaction
    {
        return $this->reactions()->updateOrCreate(
            ['user_id' => $userId],
            ['reaction' => $reaction]
        );
    }

    /**
     * Remove a reaction from the message.
     */
    public function removeReaction(int $userId): bool
    {
        return $this->reactions()->where('user_id', $userId)->delete() > 0;
    }

    /**
     * Get reaction counts grouped by type.
     */
    public function getReactionCounts(): array
    {
        return $this->reactions()
            ->select('reaction', \DB::raw('count(*) as count'))
            ->groupBy('reaction')
            ->pluck('count', 'reaction')
            ->toArray();
    }

    /**
     * Encrypt the message content.
     */
    public function encryptContent(): void
    {
        if (!$this->is_encrypted && config('conversa.encryption.enabled', false)) {
            $encryption = app(MessageEncryption::class);
            $encrypted = $encryption->encrypt([
                'content' => $this->content,
                'metadata' => $this->metadata,
            ]);

            $this->content = $encrypted['content'];
            $this->metadata = $encrypted['metadata'] ?? null;
            $this->is_encrypted = true;
            $this->save();
        }
    }

    /**
     * Decrypt the message content.
     */
    public function decryptContent(): void
    {
        if ($this->is_encrypted) {
            $encryption = app(MessageEncryption::class);
            $decrypted = $encryption->decrypt([
                'content' => $this->content,
                'metadata' => $this->metadata,
            ]);

            $this->content = $decrypted['content'];
            $this->metadata = $decrypted['metadata'] ?? null;
        }
    }

    /**
     * Mark the message as read by a user.
     */
    public function markAsRead(int $userId): void
    {
        $this->readReceipts()->updateOrCreate(
            ['user_id' => $userId],
            ['read_at' => now()]
        );
    }

    /**
     * Check if the message has been read by a user.
     */
    public function hasBeenReadBy(int $userId): bool
    {
        return $this->readReceipts()
            ->where('user_id', $userId)
            ->whereNotNull('read_at')
            ->exists();
    }

    /**
     * Get the message's read status for a user.
     */
    public function getReadStatusFor(int $userId): ?string
    {
        $receipt = $this->readReceipts()
            ->where('user_id', $userId)
            ->first();

        return $receipt ? $receipt->read_at->toISOString() : null;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Process mentions after creating a message
        static::created(function ($message) {
            $message->processMentions();
        });

        // Process mentions after updating a message
        static::updated(function ($message) {
            if ($message->isDirty('content')) {
                $message->mentions()->delete();
                $message->processMentions();
            }
        });
    }
}
