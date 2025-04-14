<?php

namespace VendorName\Conversa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConversaReaction extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'conversa_reactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'message_id',
        'user_id',
        'reaction',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the message that owns the reaction.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(ConversaMessage::class, 'message_id');
    }

    /**
     * Get the user who created the reaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('conversa.user.model'), 'user_id');
    }

    /**
     * Scope a query to only include reactions of a specific type.
     */
    public function scopeOfType($query, $reaction)
    {
        return $query->where('reaction', $reaction);
    }

    /**
     * Scope a query to only include reactions by a specific user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if a specific user has already reacted with this reaction.
     */
    public static function hasUserReacted($messageId, $userId, $reaction): bool
    {
        return static::where('message_id', $messageId)
            ->where('user_id', $userId)
            ->where('reaction', $reaction)
            ->exists();
    }

    /**
     * Toggle a reaction for a user.
     */
    public static function toggle($messageId, $userId, $reaction): bool
    {
        $exists = static::where('message_id', $messageId)
            ->where('user_id', $userId)
            ->where('reaction', $reaction)
            ->first();

        if ($exists) {
            $exists->delete();
            return false;
        }

        static::create([
            'message_id' => $messageId,
            'user_id' => $userId,
            'reaction' => $reaction,
        ]);

        return true;
    }

    /**
     * Get reaction counts for a message grouped by reaction.
     */
    public static function getCountsForMessage($messageId): array
    {
        return static::where('message_id', $messageId)
            ->select('reaction', \DB::raw('count(*) as count'))
            ->groupBy('reaction')
            ->pluck('count', 'reaction')
            ->toArray();
    }

    /**
     * Get users who reacted with a specific reaction.
     */
    public static function getUsersForReaction($messageId, $reaction): array
    {
        return static::where('message_id', $messageId)
            ->where('reaction', $reaction)
            ->with('user')
            ->get()
            ->pluck('user.name')
            ->toArray();
    }
}
